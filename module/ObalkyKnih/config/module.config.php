<?php
namespace ObalkyKnih\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'content_covers' => [
                'invokables' => [
                    'obalkyknih' => 'ObalkyKnih\Content\Covers\ObalkyKnih',
                ],
            ],
        ]
    ]
];

return $config;
