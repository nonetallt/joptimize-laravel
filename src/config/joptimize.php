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
                    'type'   => 'enum',
                    'name'   => 'SLEEP',
                    'values' => [1]
                ]
            ]
        ]
    ]
];
