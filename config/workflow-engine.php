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
    |
    */
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),

        'database' => [
            'connection' => env('WORKFLOW_DB_CONNECTION', config('database.default')),
            'table' => env('WORKFLOW_DB_TABLE', 'workflow_instances'),
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
        // Enforce step timeouts with SIGALRM. Only safe in a CLI worker running one workflow
        // at a time; leave off in web requests and Octane / FrankenPHP workers and rely on
        // the queue job timeout (queue.timeout) instead.
        'signal_timeouts' => (bool) env('WORKFLOW_SIGNAL_TIMEOUTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | workflow:resume-due resumes suspended workflows whose wait time has passed
    | (delays, deadlines). It is scheduled every minute unless switched off here.
    |
    */
    'schedule' => [
        'resume_due' => (bool) env('WORKFLOW_SCHEDULE_RESUME_DUE', true),
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
        // Seconds a StartWorkflow / SignalWorkflow job may run.
        'timeout' => (int) env('WORKFLOW_QUEUE_TIMEOUT', 300),
    ],
];
