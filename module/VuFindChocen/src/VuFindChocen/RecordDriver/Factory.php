<?php

namespace VuFindChocen\RecordDriver;

use Zend\ServiceManager\ServiceManager;

class Factory
{
    public static function getSolrMarc(ServiceManager $sm)
    {
        $driver = new \VuFindChocen\RecordDriver\SolrMarc(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
        $driver->attachILS(
            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
        );
        $driver->attachSearchService($sm->getServiceLocator()->get('VuFind\Search'));        
        return $driver;
    }
}

