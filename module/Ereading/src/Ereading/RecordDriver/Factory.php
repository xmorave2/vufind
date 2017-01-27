<?php
/**
* Record Driver Factory Class
*
* PHP version 5
*
* Copyright (C) Josef Moravec 2015-2016
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2,
* as published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*
* @category VuFind2
* @package RecordDrivers
* @author Josef Moravec <josef.moravec@gmail.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
* @link http://vufind.org/wiki/vufind2:hierarchy_components Wiki
*/
namespace Ereading\RecordDriver;
use Zend\ServiceManager\ServiceManager;

/**
* Record Driver Factory Class
*
* @category VuFind2
* @package RecordDrivers
* @author Josef Moravec <josef.moravec@gmail.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
* @link http://vufind.org/wiki/vufind2:hierarchy_components Wiki
*/
class Factory
{
    /**
     * Factory for SolrMarc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public function getSolrMarc(ServiceManager $sm)
    {
        $driver = new \Ereading\RecordDriver\SolrMarc(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('ereading'),
            $sm->getServiceLocator()->get('VuFind\AuthManager')
        );
        $driver->attachILS(
            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
        );
        return $driver;
    }

}

