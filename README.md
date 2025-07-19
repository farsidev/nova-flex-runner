# Nova Flex Runner

A powerful, customizable command runner and log viewer for Laravel Nova 4, developed by Farsi Studio.

## Features

### 🎯 Core Command Runner Features
- **Command Profiles**: Define commands via config file with flexible categorization
- **Multiple Command Types**: Support for `artisan`, `job`, `service`, `shell`, and `http` commands
- **Rich Input Types**: text, textarea, select, multiselect, checkbox, boolean, datepicker, tags, file upload, resource-select
- **Queue Integration**: Commands executed via Laravel queue jobs
- **Real-time Feedback**: Live output display with confirmation modals
- **Security First**: Built-in command validation and security controls

### 📊 Advanced Log Viewer
- **Comprehensive Logging**: Every execution stored with detailed metadata
- **Advanced Filtering**: Search by user, command, category, date, status
- **Export Capabilities**: Download logs as `.log` files
- **Performance Analytics**: Execution statistics and performance metrics
- **Code Highlighting**: Syntax-highlighted output display

### 🛡️ Security & Access Control
- **Policy-based Authorization**: Granular permission controls
- **Command Validation**: Built-in security checks for shell commands
- **Audit Trail**: Complete execution history with user attribution
- **Confirmation Modals**: Required confirmations for destructive operations

## Installation

### 1. Install via Composer

```bash
composer require farsi/nova-flex-runner
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=nova-flex-runner-config
```

### 3. Publish and Run Migrations

```bash
php artisan vendor:publish --tag=nova-flex-runner-migrations
php artisan migrate
```

### 4. Publish Assets (Optional)

```bash
php artisan vendor:publish --tag=nova-flex-runner-assets
```

### 5. Register Tools in NovaServiceProvider

The tools are automatically registered via the service provider. No manual registration required.

## Configuration

### Basic Setup

The package uses the `config/nova-flex-runner.php` configuration file:

```php
<?php

return [
    // Security settings
    'security' => [
        'require_confirmation' => true,
        'log_all_executions' => true,
        'max_execution_time' => 300,
    ],

    // Shell command settings
    'shell' => [
        'enabled' => false, // Enable with caution
        'timeout' => 300,
        'allowed_commands' => [
            '/^git /',
            '/^composer /',
        ],
    ],

    // Command definitions
    'commands' => [
        // Your command categories here
    ],
];
```

### Defining Command Profiles

Commands are organized into categories. Here's an example configuration:

```php
'commands' => [
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
                ],
            ],
        ],
    ],
],
```

## Command Types

### Artisan Commands

```php
[
    'name' => 'Run Migrations',
    'slug' => 'migrate',
    'type' => 'artisan',
    'command' => 'migrate',
    'inputs' => [
        [
            'name' => 'force',
            'label' => 'Force run in production',
            'type' => 'checkbox',
            'is_option' => true,
        ],
    ],
]
```

### Laravel Jobs

```php
[
    'name' => 'Process Data',
    'slug' => 'process-data',
    'type' => 'job',
    'job_class' => 'App\\Jobs\\ProcessDataJob',
    'queue' => 'default',
    'inputs' => [
        [
            'name' => 'batch_size',
            'label' => 'Batch Size',
            'type' => 'number',
            'min' => 1,
            'max' => 1000,
            'required' => true,
        ],
    ],
]
```

### Custom Services

```php
[
    'name' => 'Generate Report',
    'slug' => 'generate-report',
    'type' => 'service',
    'service_class' => 'App\\Services\\ReportService',
    'method' => 'generateReport',
    'inputs' => [
        [
            'name' => 'report_type',
            'label' => 'Report Type',
            'type' => 'select',
            'options' => [
                ['value' => 'daily', 'label' => 'Daily Report'],
                ['value' => 'weekly', 'label' => 'Weekly Report'],
            ],
            'required' => true,
        ],
    ],
]
```

### Shell Commands (Use with Extreme Caution)

```php
[
    'name' => 'Git Status',
    'slug' => 'git-status',
    'type' => 'shell',
    'command' => 'git status',
    'timeout' => 30,
    'inputs' => [],
]
```

## Available Input Types

### Text Input
```php
[
    'name' => 'username',
    'label' => 'Username',
    'type' => 'text',
    'placeholder' => 'Enter username',
    'maxlength' => 50,
    'required' => true,
]
```

### Select Input
```php
[
    'name' => 'environment',
    'label' => 'Environment',
    'type' => 'select',
    'options' => [
        ['value' => 'dev', 'label' => 'Development'],
        ['value' => 'staging', 'label' => 'Staging'],
        ['value' => 'prod', 'label' => 'Production'],
    ],
    'required' => true,
]
```

### Number Input
```php
[
    'name' => 'timeout',
    'label' => 'Timeout (seconds)',
    'type' => 'number',
    'min' => 1,
    'max' => 600,
    'step' => 1,
    'required' => true,
]
```

### Multiselect Input
```php
[
    'name' => 'features',
    'label' => 'Features to Enable',
    'type' => 'multiselect',
    'options' => [
        ['value' => 'feature_a', 'label' => 'Feature A'],
        ['value' => 'feature_b', 'label' => 'Feature B'],
    ],
]
```

### Checkbox Input
```php
[
    'name' => 'force',
    'label' => 'Force execution',
    'type' => 'checkbox',
]
```

### Textarea Input
```php
[
    'name' => 'message',
    'label' => 'Message',
    'type' => 'textarea',
    'rows' => 4,
    'placeholder' => 'Enter your message...',
]
```

## Custom Input Components

You can easily add your own custom input components:

1. Create a Vue component in `resources/js/components/inputs/`
2. Register it in the config file:

```php
'input_types' => [
    'custom-input' => [
        'component' => 'CustomInput',
        'props' => ['custom_prop'],
    ],
],
```

## Log Viewer Module

The Log Viewer provides comprehensive tracking of all command executions:

### Features
- **Execution History**: Complete log of all command runs
- **Advanced Filtering**: Filter by user, command, date, status
- **Performance Metrics**: Duration tracking and statistics
- **Export Functionality**: Download logs as `.log` files
- **Real-time Updates**: Live status updates for running commands

### Usage
Access the Log Viewer through the Nova sidebar menu "Command Logs".

## Security Considerations

⚠️ **Important Security Warnings:**

1. **Shell Commands**: Disabled by default. Enable only with extreme caution and proper command whitelisting.
2. **Production Safety**: Never run destructive commands like `db:wipe` in production without proper safeguards.
3. **Access Control**: Always implement proper authorization policies.
4. **Input Validation**: Validate all user inputs before execution.
5. **Audit Trail**: All executions are logged for security auditing.

### Authorization

Implement custom authorization by defining Gate policies:

```php
// In your AuthServiceProvider
Gate::define('viewNovaFlexRunner', function ($user) {
    return $user->hasRole('admin');
});

Gate::define('executeNovaFlexRunner', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('developer');
});

Gate::define('viewNovaFlexRunnerLogs', function ($user) {
    return $user->hasRole('admin');
});
```

## API Endpoints

The package exposes several API endpoints for integration:

- `GET /nova-vendor/nova-flex-runner/api/commands` - List all commands
- `POST /nova-vendor/nova-flex-runner/api/execute` - Execute a command
- `GET /nova-vendor/nova-flex-runner/api/status/{log}` - Get execution status
- `GET /nova-vendor/nova-flex-runner/api/logs` - List command logs
- `GET /nova-vendor/nova-flex-runner/api/logs/{log}/download` - Download log file

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Laravel Nova 4.0+

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please see our contributing guidelines for details.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- **Developer**: Ali Sameni
- **Company**: Farsi Studio

### Inspired By
- [stepanenko3/nova-command-runner](https://github.com/stepanenko3/nova-command-runner)
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)
- [opcodesio/log-viewer](https://github.com/opcodesio/log-viewer)
- [filamentphp](https://filamentphp.com/)
- [nova-tabs](https://github.com/eminiarts/nova-tabs)
- [optimistdigital/nova-settings](https://github.com/optimistdigital/nova-settings)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

**Developed by Ali Sameni**  
**Maintained by Farsi Studio**

For support and updates, visit: [https://github.com/farsi/nova-flex-runner](https://github.com/farsi/nova-flex-runner)
