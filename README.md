# joptimize-laravel
[Joptimize](https://github.com/nonetallt/joptimize) wrapper for Laravel framework

# Installation
```
composer require nonetallt/joptimize-laravel --dev
```

# Basic usage:

Running the command:
```
php artisan joptimize
```

# Configuration

Publishing the configuration:
```
php artisan vendor:publish --provider="Nonetallt\Jsroute\Laravel\JoptimizeServiceProvider"
```

Check the [main package](https://github.com/nonetallt/joptimize) for supported parameter types and other details
```php
<?php

return [

    /* Path to the dotenv file you want to save and read the variables from */
    'env_path' => base_path('.env'),

    /* Create optimization values in .env if the setting is missing */
    'create_missing_variables' => true,

    /* Declare methods to optimize */
    'optimize' => [
        [
            'method' => function($params) {
                sleep($params->SLEEP);
            },
            'parameters' => [
                [
                    'type' => 'enum',
                    'name' => 'SLEEP',
                    'args' => [1]
                ]
            ]
        ]
    ]
];
```
