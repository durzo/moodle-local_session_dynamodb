<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for performing garbage collection for the AWS DynamoDB session handler.
 *
 * @package   local_session_dynamodb
 * @copyright 2017 Jordan Tomkinson <jordan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_session_dynamodb\task;
require_once(dirname(dirname(__DIR__)) . '/vendor/autoload.php');
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Session\SessionHandler;

class session_gc extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('session_gc', 'local_session_dynamodb');
    }

    /**
     * Execute task.
     */
    public function execute() {
        global $CFG;
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
        $handler->garbageCollect();
    }
}
