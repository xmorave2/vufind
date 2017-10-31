<?php
namespace ObalkyKnih\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'content_covers' => [
                'factories' => [
                    'obalkyknih' => 'ObalkyKnih\Factory::getObalkyKnih'
                ]
            ]
        ]
    ]
];

return $config;
