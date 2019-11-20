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
 * DynamoDB based session handler. Based on Redis and Database backends
 * This file should be placed in moodles /lib/classes/session/ folder
 *
 * @package    core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace core\session;

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\DynamoDb\DynamoDbClient;

defined('MOODLE_INTERNAL') || die();

/**
 * DynamoDB based session handler.
 */
class local_session_dynamodb_handler extends \core\session\handler {

    /**
     * Possible config items, from https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/dynamodb-session-handler.html
     * table_name
     * hash_key
     * session_lifetime
     * consistent_read
     * locking
     * batch_config
     * max_lock_wait_time
     * min_lock_retry_microtime
     * max_lock_retry_microtime
     */

    /**
     * @var $client The AWS client instance.
     */
    protected $client = null;

    /**
     * @var \Aws\DynamoDb\Session\SessionHandler $handler The AWS dynamodb session handler
     */
    protected $handler = null;

    /**
     * @var \Aws\DynamoDb\DynamoDbClient $endpoint The handler endpoint for testing this locally
     */
    protected $endpoint = null;

    protected $aws_key = null;
    protected $aws_secret = null;

    /**
     * Create new instance of handler.
     */
    public function __construct() {
        global $CFG;

        if (!isset($CFG->session_dynamodb_region)) {
            throw new core\session\exception('sessionhandlerproblem', 'error', '', null,
                    '$CFG->session_dynamodb_region must be specified in config.php');
        }

        if (!isset($CFG->session_dynamodb_table)) {
            throw new core\session\exception('sessionhandlerproblem', 'error', '', null,
                    '$CFG->session_dynamodb_table must be specified in config.php');
        }

        if (isset($CFG->session_dynamodb_endpoint)) {
            $this->endpoint = $CFG->session_dynamodb_endpoint;
        }

        $credentials = [];

        if (!empty($CFG->session_dynamodb_aws_key)) {
            $this->aws_key = $CFG->session_dynamodb_aws_key;
            $credentials['key'] = $this->aws_key;
        }

        if (!empty($CFG->session_dynamodb_aws_secret)) {
            $this->aws_secret = $CFG->session_dynamodb_aws_secret;
            $credentials['secret'] = $this->aws_secret;
        }

        $params = [
            'version' => 'latest',
            'region' => $CFG->session_dynamodb_region,
            'endpoint' => $this->endpoint];
        if ($credentials) {
            $params['credentials'] = $credentials;
        }
        $this->client = DynamoDbClient::factory($params);
        
        $this->handler = $this->client->registerSessionHandler([
            'table_name' => $CFG->session_dynamodb_table,
            'session_lifetime' => $CFG->sessiontimeout,
            'automatic_gc' => 0,
        ]);
    }

    /**
     * Start the session.
     *
     * @return bool success
     */
    public function start() {
        $result = parent::start();

        return $result;
    }

    /**
     * Init session handler.
     */
    public function init() {
        return true;
    }

    /**
     * Check the backend contains data for this session id.
     *
     * Note: this is intended to be called from manager::session_exists() only.
     *
     * @param string $sid
     * @return bool true if session found.
     */
    public function session_exists($sid) {
        return $this->handler->isSessionOpen();
    }

    /**
     * Kill all active sessions, the core sessions table is purged afterwards.
     */
    public function kill_all_sessions() {
        return;
    }

    /**
     * Kill one session, the session record is removed afterwards.
     *
     * @param string $sid
     */
    public function kill_session($sid) {
        // Entries in DynamoDB are not deleted until garbageCollect() runs,
        // which is done as part of a Scheduled Task in local/session_dynamodb
        $this->handler->destroy($sid);
    }
}
