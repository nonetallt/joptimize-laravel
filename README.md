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
php artisan vendor:publish --provider="Nonetallt\Joptimize\Laravel\JoptimizeServiceProvider"
```

Check the [main package](https://github.com/nonetallt/joptimize) for supported parameter types and other details
```php
<?php

return [

    /* Path to the dotenv file you want to save and read the variables from */
    'env_path' => base_path('.env'),

    /* Create optimization values in .env if the setting is missing */
    'create_missing_variables' => false,

    /* Declare methods to optimize */
    'optimize' => [
        [
            /* Values you need to calculate only once but are useful for
             * multiple iterations, for example: starting time.
             */
            'init' => ['example' => true],

            /* The method to optimize */
            'method' => function($params, $iteration, $initializationValues) {

                $sleepTime = $params->SLEEP;

                /* Example usage */ 
                if($initializationValues->example) $sleepTime++;

                sleep($sleepTime);
            },

            /* Supported types enum, linear, range 
             *
             * --args for types--
             *
             * enum   : as many args as you want to test as parameter value
             * linear : 1. start, 2. end, 3. stepSize (optional, 1 by default)
             * range  : 1. min  , 2. max, 3. maxIterations
             */
            'parameters' => [
                [
                    'type'   => 'enum',
                    'name'   => 'SLEEP',
                    'args'   => [1],
                    /* You can use the mutate callback to modify the value
                     * that will be saved in .env, this can be useful for example
                     * if you need the value to be an integer instead of float
                     * when using a range parameter.
                     */
                    'mutate' => function($value) {
                        return floor($value);
                    }
                ]
            ]
        ]
    ]
];
```
