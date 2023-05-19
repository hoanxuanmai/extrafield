<?php
/**
 * Created by HoanXuanMai
 * @author hoanxuanmai@gmail.com
 */

use HXM\ExtraField\Enums\ExtraFieldTypeEnums;
use HXM\ExtraField\Services\ExtraValueProcessValue;

return [
    'cache' => true,
    'lifetime' => 3600,
    'wrap' => null,
    'privates' => [
    ],
    'private_tables' => [
    ],
    'tables' => [
        'fields' => 'extra_fields',
        'options' => 'extra_field_options',
        'values' => 'extra_field_values',
    ],
    'validations' => [
        'rules' => [
            'FILE' => [
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:10000'
            ]
        ],
        'attributes' => [
            'slug' => "Label",
            'label' => "Label",
            'type' => "Type",
            'options' => "Options",
            'options.*.label' => "Label",
            'fields.*.label' => "Label",
            'fields.*.type' => "Type",
            'fields.*.fields' => "Fields",
            'fields.*.options' => "Options",
        ]
    ],
    'defaults' => [
        'valueProcessionInstance' => new ExtraValueProcessValue(),
        'enumInstance' => new ExtraFieldTypeEnums(),
    ],
    'enums' => [
//        \App\Models\Demo::class => new \App\Enums\DemoExtraFieldTypeEnums(),
    ],
    'valueProcessions' => [
//        \App\Models\Demo::class => new \App\Enums\DemoExtraValueProcessValue(),
    ]
];
