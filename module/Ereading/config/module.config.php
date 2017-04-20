<?php
namespace Ereading\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'solrmarc' => 'Ereading\RecordDriver\Factory::getSolrMarc',
                ],
            ],
            'db_table' => [
                'invokables' => [
                    'EbookIssues' => 'Ereading\Db\Table\EbookIssues',
                ]
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'record' => 'Ereading\Controller\Factory::getRecordController',
        ],
        'invokables' => [
            'ajax' => 'Ereading\Controller\AjaxController',
        ],
    ],
];

$nonTabRecordActions = array('LendEbook');

foreach ($nonTabRecordActions as $action) {
    $config['router']['routes']['record' . '-' . strtolower($action)] = [
        'type'    => 'Zend\Mvc\Router\Http\Segment',
        'options' => [
            'route'    => '/' . 'Record' . '/[:id]/' . $action,
            'constraints' => [
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            ],
            'defaults' => [
                'controller' => 'Record',
                'action'     => $action,
            ]
        ]
    ];
}

return $config;

