<?php

return [
    'field_types' => [
        'string' => [
            'db_type' => 'string',
            'dto_type' => 'string',
            'default_rules' => ['string'],
            'default_length' => 255,
        ],
        'text' => [
            'db_type' => 'text',
            'dto_type' => 'string',
            'default_rules' => ['string'],
        ],
        'integer' => [
            'db_type' => 'integer',
            'dto_type' => 'int',
            'default_rules' => ['integer'],
        ],
        'decimal' => [
            'db_type' => 'decimal',
            'dto_type' => 'float',
            'default_rules' => ['numeric'],
            'default_precision' => 10,
            'default_scale' => 2,
        ],
        'boolean' => [
            'db_type' => 'boolean',
            'dto_type' => 'bool',
            'default_rules' => ['boolean'],
            'default' => false,
        ],
        'datetime' => [
            'db_type' => 'datetime',
            'dto_type' => 'string',
            'default_rules' => ['date'],
        ],
        'json' => [
            'db_type' => 'json',
            'dto_type' => 'array',
            'default_rules' => ['array'],
        ],
    ],
];
