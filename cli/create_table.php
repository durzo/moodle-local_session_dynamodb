<?php
define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(__DIR__))) . '/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\SessionHandler;

// Define the input options.
$longparams = array(
        'help' => false,
        'readCapacityUnits' => '',
        'writeCapacityUnits' => '',
);

$shortparams = array(
        'h' => 'help',
        'r' => 'readCapacityUnits',
        'w' => 'writeCapacityUnits',
);

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Creates a sessions table for the dynamodb session handler.

Options:
-h, --help                    Print out this help
-r, --readCapacityUnits=#     Specify the number of read capacity units
-w, --writeCapacityUnits=#    Specify the number of write capacity units

Example:
\$sudo -u www-data /usr/bin/php local/session_dynamodb/cli/create_table.php -r=5 -w=5

Important:
!! Make sure you uncomment \$CFG->session_handler_class in config.php before calling this script otherwise it will fail. !!
";

    echo $help;
    die;
}
if ($options['readCapacityUnits'] == '' ) {
    cli_heading('DynamoDB Session Table Creation');
    $prompt = "Enter the amount of read capacity units";
    $readCapacityUnits = cli_input($prompt);
} else {
    $readCapacityUnits = $options['readCapacityUnits'];
}

if ($options['writeCapacityUnits'] == '' ) {
    cli_heading('DynamoDB Session Table Creation');
    $prompt = "Enter the amount of write capacity units";
    $writeCapacityUnits = cli_input($prompt);
} else {
    $writeCapacityUnits = $options['writeCapacityUnits'];
}

if (!isset($CFG->session_dynamodb_region)) {
    throw new core\session\exception('sessionhandlerproblem', 'error', '', null,
            '$CFG->session_dynamodb_region must be specified in config.php');
}
if (!isset($CFG->session_dynamodb_table)) {
    throw new core\session\exception('sessionhandlerproblem', 'error', '', null,
            '$CFG->session_dynamodb_table must be specified in config.php');
}
if (isset($CFG->session_dynamodb_endpoint)) {
    $endpoint = $CFG->session_dynamodb_endpoint;
}

$credentials = [];

if (isset($CFG->session_dynamodb_aws_key)) {
    $credentials['key'] = $CFG->session_dynamodb_aws_key;
}
if (isset($CFG->session_dynamodb_aws_secret)) {
    $credentials['secret'] = $aws_secret = $CFG->session_dynamodb_aws_secret;
}

$params = [
    'version' => 'latest',
    'region' => $CFG->session_dynamodb_region,
    'endpoint' => isset($endpoint) ? $endpoint : null];
if ($credentials) {
    $params['credentials'] = $credentials;
}

echo "Creating sessions table...\n";

$client = DynamoDbClient::factory($params);

$result = $client->createTable([
    'AttributeDefinitions' => [
        [
            'AttributeName' => 'id',
            'AttributeType' => 'S',
        ],
    ],
    'KeySchema' => [
        [
            'AttributeName' => 'id',
            'KeyType' => 'HASH',
        ],
    ],
    'ProvisionedThroughput' => [
        'ReadCapacityUnits' => (int)$readCapacityUnits,
        'WriteCapacityUnits' => (int)$writeCapacityUnits,
    ],
    'TableName' => $CFG->session_dynamodb_table,
]);