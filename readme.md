## yBit API

The API for the yBit application.

## Requirements
- GD PHP extension (for resizing images)
- An elasticsearch node instance

## Setup
- Configure your database and AWS credentials
- Run `composer install && php artisan migrate && php artisan jwt:generate` to bootstrap the project
- Run `php artisan db:seed` to insert the pin types in the database
- Configure permissions in `app/storage` to allow writing logs and cache
- Make sure any bit types have their attributes properly set in the database 
- Configure the `ES_HOST`,`ES_PORT` variables for elasticsearch.
  Optionally, change the `ES_INDEX` and `ES_MAPPING_TYPE` variables.
  *Note:* When using the AWS elasticsearch service, you must also specify the `ES_REGION` variable
- Run `php artisan index:create` to generate the elasticsearch index.
- Set Twilio credentials (those provided in the example .env are testing credentials)

## Integrating with stripe
- In the dashboard in `/acount/webhooks` register an endpoint with the following settings. Under `URL to be called` put `<YOUR_API_URL>/integrations`, keep `Webhook version` set to `default` and `filter event option` set to `Send all event types`.
- In the `/subscriptions/products` create products following the guidelines :
- If you want to create a storage plan add `type : main` and `storage : <PRODUCTS STORAGE LIMIT>` fields as metadata 
- To create a custom plan available only to a specific team also add the `custom : true` and `team_id : <TEAMS ID>` fields to metadata
- In the .env file configure `STRIPE_API_KEY` and `STRIPE_WEBHOOK_SECRET` variables.

# Testing
- Make sure the database **ybit_api_test** exists and has the latest migrations
- Run `vendor/bin/phpunit` to run the entire suite
- Run `vendor/bin/phpunit --filter {ExampleTest}` to only run a specific test

## Staging/Production
- Ensure a Redis instance is running
- Configure the `REDIS_HOST`,`REDIS_PORT`,`REDIS_PASSWORD` environment variables
- Configure the `REDIS_QUEUE` environment variable to specify a queue name, `ybit` by default
- Run `php artisan queue:work --tries=2` to start the queue worker

## S3 migration
In order to migrate a 
team's files manually to a different S3:
- Follow [this](https://monospace.ybit.io/bit/869) guide to sync the files
- Add the team's credentials in the database
- Run `php artisan init:s3_secrets {team}` where `team` is the team's subdomain to encrypt the team's secrets
