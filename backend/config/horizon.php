<?php

use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path'   => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080,
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job'  => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 256,

    'defaults' => [
        'supervisor-1' => [
            'connection'    => 'redis',
            'queue'         => ['default'],
            'balance'       => 'auto',
            'autoScaleDownDelaySeconds' => 300,
            'maxProcesses'  => 3,
            'maxTime'       => 0,
            'maxJobs'       => 0,
            'memory'        => 256,
            'tries'         => 1,
            'timeout'       => 600,
            'nice'          => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'connection'   => 'redis',
                'queue'        => ['default'],
                'balance'      => 'auto',
                'maxProcesses' => 5,
                'tries'        => 1,
            ],
            'supervisor-ai' => [
                'connection'   => 'redis',
                'queue'        => ['ai-analysis'],
                'balance'      => 'simple',
                'maxProcesses' => 3,
                'tries'        => 1,
                'timeout'      => 600,
            ],
            'supervisor-repositories' => [
                'connection'   => 'redis',
                'queue'        => ['repository-processing', 'code-parsing'],
                'balance'      => 'auto',
                'maxProcesses' => 4,
                'tries'        => 2,
            ],
            'supervisor-reports' => [
                'connection'   => 'redis',
                'queue'        => ['report-generation', 'notifications'],
                'balance'      => 'auto',
                'maxProcesses' => 3,
                'tries'        => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection'   => 'redis',
                'queue'        => ['default', 'ai-analysis', 'repository-processing', 'code-parsing', 'report-generation', 'notifications'],
                'balance'      => 'simple',
                'maxProcesses' => 2,
                'tries'        => 1,
            ],
        ],
    ],
];
