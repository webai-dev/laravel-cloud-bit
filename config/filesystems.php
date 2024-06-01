<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Max upload file size
    |--------------------------------------------------------------------------
    |
    | Determines the maximum file size in kilobytes that is allowed to be uploaded
    */

    'max_upload_size' => 2 * 1024 * 1024, // 2GB

    /*
    |--------------------------------------------------------------------------
    | Default team storage limit
    |--------------------------------------------------------------------------
    |
    | Determines the default storage limit for teams in bytes
    */

    'default_storage_limit' => 16 * (1024 ** 3), // 16GB

    /*
    |--------------------------------------------------------------------------
    | Download link duration
    |--------------------------------------------------------------------------
    |
    | The duration of the download link for files
    */

    'download_link_duration' => '+1 minute',

    /*
    |--------------------------------------------------------------------------
    | CDN URL
    |--------------------------------------------------------------------------
    |
    | The URL for the production CDN
    */

    'cdn_url' => 'cdn.ybit.io',

    /*
    |--------------------------------------------------------------------------
    | File Versions TTL
    |--------------------------------------------------------------------------
    |
    | The default duration to keep file versions
    */

    'file_versions_ttl' => '1 month',

];
