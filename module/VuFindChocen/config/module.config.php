<?php
namespace VuFindChocen\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'solrmarc' => 'VuFindChocen\RecordDriver\Factory::getSolrMarc',
                ],
            ],
        ],
    ],
];

return $config;
