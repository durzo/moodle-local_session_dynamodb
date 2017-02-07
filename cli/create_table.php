<?php
define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(__DIR__))) . '/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once(dirname(__DIR__) . '/vendor/autoload.php');

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Session\SessionHandler;

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
    throw new exception('sessionhandlerproblem', 'error', '', null,
            '$CFG->session_dynamodb_region must be specified in config.php');
}
if (!isset($CFG->session_dynamodb_table)) {
    throw new exception('sessionhandlerproblem', 'error', '', null,
            '$CFG->session_dynamodb_table must be specified in config.php');
}
if (isset($CFG->session_dynamodb_endpoint)) {
    $endpoint = $CFG->session_dynamodb_endpoint;
}
if (isset($CFG->session_dynamodb_aws_key)) {
    $aws_key = $CFG->session_dynamodb_aws_key;
}
if (isset($CFG->session_dynamodb_aws_secret)) {
    $aws_secret = $CFG->session_dynamodb_aws_secret;
}

$client = DynamoDbClient::factory([
            'version' => 'latest',
            'region' => $CFG->session_dynamodb_region,
            'endpoint' => isset($endpoint) ? $endpoint : null,
            'credentials' => array(
                'key'    => isset($aws_key) ? $aws_key : null,
                'secret' => isset($aws_secret) ? $aws_secret : null,
            ),
        ]);
$handler = SessionHandler::factory([
            'dynamodb_client' => $client,
            'table_name' => $CFG->session_dynamodb_table,
            'session_lifetime' => $CFG->sessiontimeout,
            'automatic_gc' => 0,
        ]);

echo "Creating sessions table...\n";
$handler->createSessionsTable($readCapacityUnits, $writeCapacityUnits);
