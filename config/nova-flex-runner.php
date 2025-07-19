<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security settings for the Nova Flex Runner tool.
    |
    */
    'security' => [
        'require_confirmation' => true,
        'log_all_executions' => true,
        'max_execution_time' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Shell Command Settings
    |--------------------------------------------------------------------------
    |
    | Configure shell command execution settings.
    |
    */
    'shell' => [
        'enabled' => false,
        'timeout' => 300,
        'allowed_commands' => [
            // Add regex patterns for allowed commands
            // '/^git /',
            // '/^composer /',
            // '/^npm /',
            // '/^yarn /',
        ],
        'blocked_commands' => [
            // Additional blocked commands beyond the default security list
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for command execution.
    |
    */
    'queue' => [
        'connection' => env('NOVA_FLEX_RUNNER_QUEUE_CONNECTION'),
        'queue' => env('NOVA_FLEX_RUNNER_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Profiles
    |--------------------------------------------------------------------------
    |
    | Define your command profiles here. Each profile represents a command
    | that can be executed through the Nova Flex Runner interface.
    |
    */
    'commands' => [
        /*
        |--------------------------------------------------------------------------
        | Artisan Commands
        |--------------------------------------------------------------------------
        */
        'maintenance' => [
            'name' => 'Maintenance',
            'description' => 'Application maintenance commands',
            'icon' => 'wrench-screwdriver',
            'commands' => [
                [
                    'name' => 'Clear Application Cache',
                    'slug' => 'cache-clear',
                    'description' => 'Clear all application caches',
                    'type' => 'artisan',
                    'command' => 'cache:clear',
                    'confirmation_required' => false,
                    'inputs' => [],
                ],
                [
                    'name' => 'Clear Configuration Cache',
                    'slug' => 'config-clear',
                    'description' => 'Clear configuration cache',
                    'type' => 'artisan',
                    'command' => 'config:clear',
                    'confirmation_required' => false,
                    'inputs' => [],
                ],
                [
                    'name' => 'Clear Route Cache',
                    'slug' => 'route-clear',
                    'description' => 'Clear route cache',
                    'type' => 'artisan',
                    'command' => 'route:clear',
                    'confirmation_required' => false,
                    'inputs' => [],
                ],
                [
                    'name' => 'Clear View Cache',
                    'slug' => 'view-clear',
                    'description' => 'Clear compiled view files',
                    'type' => 'artisan',
                    'command' => 'view:clear',
                    'confirmation_required' => false,
                    'inputs' => [],
                ],
                [
                    'name' => 'Put Application in Maintenance Mode',
                    'slug' => 'maintenance-down',
                    'description' => 'Put the application into maintenance mode',
                    'type' => 'artisan',
                    'command' => 'down',
                    'confirmation_required' => true,
                    'inputs' => [
                        [
                            'name' => 'message',
                            'label' => 'Maintenance Message',
                            'type' => 'textarea',
                            'placeholder' => 'We are currently performing maintenance...',
                            'required' => false,
                            'is_option' => true,
                        ],
                        [
                            'name' => 'retry',
                            'label' => 'Retry After (seconds)',
                            'type' => 'number',
                            'placeholder' => '60',
                            'min' => 1,
                            'required' => false,
                            'is_option' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Bring Application Up',
                    'slug' => 'maintenance-up',
                    'description' => 'Bring the application out of maintenance mode',
                    'type' => 'artisan',
                    'command' => 'up',
                    'confirmation_required' => true,
                    'inputs' => [],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Database Commands
        |--------------------------------------------------------------------------
        */
        'database' => [
            'name' => 'Database',
            'description' => 'Database management commands',
            'icon' => 'circle-stack',
            'commands' => [
                [
                    'name' => 'Run Migrations',
                    'slug' => 'migrate',
                    'description' => 'Run database migrations',
                    'type' => 'artisan',
                    'command' => 'migrate',
                    'confirmation_required' => true,
                    'inputs' => [
                        [
                            'name' => 'force',
                            'label' => 'Force run in production',
                            'type' => 'checkbox',
                            'required' => false,
                            'is_option' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Rollback Migrations',
                    'slug' => 'migrate-rollback',
                    'description' => 'Rollback database migrations',
                    'type' => 'artisan',
                    'command' => 'migrate:rollback',
                    'confirmation_required' => true,
                    'inputs' => [
                        [
                            'name' => 'step',
                            'label' => 'Steps to rollback',
                            'type' => 'number',
                            'placeholder' => '1',
                            'min' => 1,
                            'required' => false,
                            'is_option' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Seed Database',
                    'slug' => 'db-seed',
                    'description' => 'Seed the database with records',
                    'type' => 'artisan',
                    'command' => 'db:seed',
                    'confirmation_required' => true,
                    'inputs' => [
                        [
                            'name' => 'class',
                            'label' => 'Seeder Class',
                            'type' => 'text',
                            'placeholder' => 'DatabaseSeeder',
                            'required' => false,
                            'is_option' => true,
                        ],
                    ],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Queue Commands
        |--------------------------------------------------------------------------
        */
        'queue' => [
            'name' => 'Queue',
            'description' => 'Queue management commands',
            'icon' => 'queue-list',
            'commands' => [
                [
                    'name' => 'Start Queue Worker',
                    'slug' => 'queue-work',
                    'description' => 'Start processing queue jobs',
                    'type' => 'artisan',
                    'command' => 'queue:work',
                    'confirmation_required' => false,
                    'inputs' => [
                        [
                            'name' => 'queue',
                            'label' => 'Queue Name',
                            'type' => 'text',
                            'placeholder' => 'default',
                            'required' => false,
                            'is_option' => true,
                        ],
                        [
                            'name' => 'timeout',
                            'label' => 'Timeout (seconds)',
                            'type' => 'number',
                            'placeholder' => '60',
                            'min' => 1,
                            'required' => false,
                            'is_option' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Clear Failed Jobs',
                    'slug' => 'queue-flush',
                    'description' => 'Delete all failed queue jobs',
                    'type' => 'artisan',
                    'command' => 'queue:flush',
                    'confirmation_required' => true,
                    'inputs' => [],
                ],
                [
                    'name' => 'Retry Failed Jobs',
                    'slug' => 'queue-retry',
                    'description' => 'Retry failed queue jobs',
                    'type' => 'artisan',
                    'command' => 'queue:retry',
                    'confirmation_required' => false,
                    'inputs' => [
                        [
                            'name' => 'id',
                            'label' => 'Job ID (leave empty for all)',
                            'type' => 'text',
                            'placeholder' => 'all',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Field Types
    |--------------------------------------------------------------------------
    |
    | Define the available input field types and their configurations.
    |
    */
    'input_types' => [
        'text' => [
            'component' => 'TextInput',
            'props' => ['placeholder', 'maxlength', 'pattern'],
        ],
        'textarea' => [
            'component' => 'TextareaInput',
            'props' => ['placeholder', 'rows', 'maxlength'],
        ],
        'number' => [
            'component' => 'NumberInput',
            'props' => ['min', 'max', 'step', 'placeholder'],
        ],
        'select' => [
            'component' => 'SelectInput',
            'props' => ['options', 'placeholder'],
        ],
        'multiselect' => [
            'component' => 'MultiselectInput',
            'props' => ['options', 'placeholder'],
        ],
        'checkbox' => [
            'component' => 'CheckboxInput',
            'props' => ['label'],
        ],
        'boolean' => [
            'component' => 'BooleanInput',
            'props' => ['true_label', 'false_label'],
        ],
        'datepicker' => [
            'component' => 'DatepickerInput',
            'props' => ['format', 'min_date', 'max_date'],
        ],
        'tags' => [
            'component' => 'TagsInput',
            'props' => ['placeholder', 'suggestions'],
        ],
        'file' => [
            'component' => 'FileInput',
            'props' => ['accept', 'multiple', 'max_size'],
        ],
        'resource-select' => [
            'component' => 'ResourceSelectInput',
            'props' => ['resource', 'display_field', 'value_field'],
        ],
    ],
];