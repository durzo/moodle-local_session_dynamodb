# Moodle DynamoDB Session Handler
This plugin contains:

1: The AWS PHP SDK

2: A Scheduled Task to perform DynamoDB Garbage Collection on expired keys.

2: The DynamoDB Session Handler.

3: A CLI script for creating the DynamoDB sessions table.

## Install Instructions

To install it using git, type this command in the root of your Moodle install:
```
git clone https://github.com/durzo/moodle-local_session_dynamodb.git local/session_dynamodb
```

## Configuration

The following should be added to moodles config.php AFTER plugin installation:
```
$CFG->session_handler_class = 'local_session_dynamodb_handler'; // use this plugin as the session handler
$CFG->session_dynamodb_region = 'us-east-1'; // The AWS region of your DynamoDB table
// $CFG->session_dynamodb_endpoint = 'http://127.0.0.1:8000'; // The endpoint url if using a locally hosted DynamoDB server.
$CFG->session_dynamodb_table = 'sessions'; // The DynamoDB table name
$CFG->session_dynamodb_aws_key = 'XYZ'; // If needed, your AWS Access Key. comment out for IAM Instance Roles
$CFG->session_dynamodb_aws_secret = 'XYZ'; // If needed, your AWS Secret Key. comment out for IAM Instance Roles
$CFG->sessiontimeout = '3600'; // How long in seconds before a session times out.
```

## Setting up DynamoDB

Create a sessions table through the AWS Console or use the CLI tool cli/create_table.php:  
Edit config.php and comment out $CFG->session_handler_class before running the CLI tool.  
You must configure the $CFG->session_dynamodb\_ variables before running the CLI tool.  
```
sudo -u www-data php local/session_dynamodb/cli/create_table.php -h
```

## Scheduled Task

When keys expire or get deleted in DynamoDB they do not get removed until garbage collection is called.  
This is a compute expensive operation, so we do not want to do this on every cron run.  
This plugin creates a Scheduled Task that runs at 02:00 once a day to perform DynamoDB garbage collection  
The schedule can be modified through: Site Administration / Server / Scheduled Tasks  
It is recommended to run this task only when your Moodle site is idle, or during low activity.

Alternatively, modify your DynamoDB table and enable TTL on the 'expires' attribute for automatic garbage collection at the cost of table performance

## AWS IAM Policy

To use the plugin your Role or User will need the following IAM policy, replacing "tablename" with the name of your DynamoDB table.
Note: This policy does not allow for table creation, it is recommended to use API keys for the table creation and then switch to using a Role with this policy.

```
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": [
                "dynamodb:BatchGetItem",
                "dynamodb:BatchWriteItem",
                "dynamodb:PutItem",
                "dynamodb:DescribeTable",
                "dynamodb:DeleteItem",
                "dynamodb:GetItem",
                "dynamodb:Scan",
                "dynamodb:Query",
                "dynamodb:UpdateItem",
                "dynamodb:GetRecords"
            ],
            "Resource": [
                "arn:aws:dynamodb:us-east-2:*:table/tablename/stream/*",
                "arn:aws:dynamodb:us-east-2:*:table/tablename",
                "arn:aws:dynamodb:us-east-2:*:table/tablename/index/*"
            ]
        },
        {
            "Sid": "VisualEditor1",
            "Effect": "Allow",
            "Action": [
                "dynamodb:ListTables",
                "dynamodb:DescribeLimits"
            ],
            "Resource": "*"
        }
    ]
}
```

## LICENSE

This plugin is licensed under GNU GPL v3

This plugin uses 3rd party libraries which have the following licenses:

* AWS PHP SDK: Apache 2.0
* Composer: MIT
* Guzzle: MIT
* Symfony: MIT
