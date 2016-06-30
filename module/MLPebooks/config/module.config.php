<?php
namespace VuFindLocalTemplate\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'solrmarc' => 'MLPebooks\RecordDriver\Factory::getSolrMarc'
                ],
            ]
        ]
    ]
];

return $config;
