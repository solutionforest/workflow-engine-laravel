<?php

// config for Solutionforest/LaravelWorkflowEngine
return [
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how workflow instances are stored. Available drivers:
    | - database: Store in database using DatabaseStorage
    | - file: Store in filesystem using FileStorage (development only)
    |
    */
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),

        'database' => [
            'connection' => env('WORKFLOW_DB_CONNECTION', config('database.default')),
            'table' => env('WORKFLOW_DB_TABLE', 'workflow_instances'),
        ],

        'file' => [
            'path' => env('WORKFLOW_FILE_PATH', storage_path('app/workflows')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configure event dispatching for workflow lifecycle events
    |
    */
    'events' => [
        'enabled' => env('WORKFLOW_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default action settings
    |
    */
    'actions' => [
        'timeout' => env('WORKFLOW_ACTION_TIMEOUT', '5m'),
        'retry_attempts' => env('WORKFLOW_ACTION_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WORKFLOW_ACTION_RETRY_DELAY', '30s'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for asynchronous workflow execution
    |
    */
    'queue' => [
        'enabled' => env('WORKFLOW_QUEUE_ENABLED', false),
        'connection' => env('WORKFLOW_QUEUE_CONNECTION', config('queue.default')),
        'queue_name' => env('WORKFLOW_QUEUE_NAME', 'workflows'),
    ],
];
