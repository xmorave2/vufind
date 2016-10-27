<?php
/**
 * Record Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Controller
 * @author   Josef Moravec <josef.moravec@knihovna-uo.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Ereading\Controller;
use VuFind\Controller\RecordController as RecordControllerBase;
/**
 * Redirects the user to the appropriate default VuFind action.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Josef Moravec <josef.moravec@knihovna-uo.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class RecordController extends RecordControllerBase
{
    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct(\Zend\Config\Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($config);
    }

    public function lendEbookAction()
    {
        // Force login:
        if (!($user = $this->getUser())) {
            return $this->forceLogin(null);
        }

        // Retrieve the record driver:
        $driver = $this->loadRecord();
        $ebookLendTable = $this->getTable("EbookIssues");

        $maxIssues = $driver->getMaxEreadingIssues();
        $lendingInterval = $driver->getEreadingLendingInterval();

        $select = $ebookLendTable->getSql()->select();
        $select->where(array('user_id' => $user->id));
        $where = $select->where;
        $where->expression('timestamp >= NOW() - INTERVAL ? DAY', $lendingInterval);
        $where->expression('status = ?', 1);

        $activeIssues = $ebookLendTable->selectWith($select);
        $activeIssuesCount = $activeIssues->count();

        $eLendReason = "";

        if($activeIssuesCount < $maxIssues) {
                $eReadingResponse = file_get_contents($driver->getEreadingLendingUrl());
                $eLendOK = (strpos($eReadingResponse, "Loan assigned") !== false) && (strpos($eReadingResponse, "OK")  !== false);
                $eLendEmail = "";
                $rows = explode("\n",$eReadingResponse);
                if($eLendOK) {
                        foreach ($rows as $row) {
                                if(strpos($row,"'user'")) {
                                        $parts = explode("'",$row);
                                        $eLendEmail = $parts[3];
                                }
                        }
                } else {
                        $eLendReason = (strpos($eReadingResponse, "Bad library ID") !== false) ? "Configuration error - wrong library id" : "";
                        $eLendReason = (strpos($eReadingResponse, "User email is missing") !== false) ? "User email missing" : "";
                        $eLendReason = (strpos($eReadingResponse, "Count parameter is missing") !== false) ? "API Call error" : "";
                        $eLendReason = (strpos($eReadingResponse, "Count is not valid number") !== false) ? "API Call error" : "";
                        $eLendReason = (strpos($eReadingResponse, "Ebook parameter is missing") !== false) ? "E-book not found" : "";
                        $eLendReason = (strpos($eReadingResponse, "Ebook id is not valid") !== false) ? "E-book not found" : "";
                        $eLendReason = (strpos($eReadingResponse, "Validity of the link expired") !== false) ? "Validity of the link expired" : "";
                        $eLendReason = (strpos($eReadingResponse, "Bad hash/password") !== false) ? "Configuration error - wrong library password" : "";
                }
        } else {
                $eLendOK = false;
                $eLendReason = "Too many issues";

        }

        /* Save info about the e-book lend action
        *
        * user_id - id from user table
        * cardnumber
                * username - user firstname and surname  
                * ereading_id - id of e-reading book     
                * record_id - record id from solr        
                * title - title of e-book - for fallback
                * author - author of e-book - for fallback       
                * year - publication year - for fallback         
                * timestamp - date and time when ebook lend action was committed         
                * status - status 0 - error, 1 - ok      
                * status_string - message returned in eReading API call           
                */


        $insertValues = array(
                'user_id' => $user->id,
                'cardnumber' => $user->username,
                'username' => $user->firstname . " " . $user->lastname,
                'email' => trim($user->email),
                'ereading_id' => $driver->getEreadingId(),
                'record_id' => $driver->getUniqueID(),
                'title' => $driver->getFullTitle(),
                'author' => $driver->getPrimaryAuthor(),
                'year' => $driver->getPublicationDates()[0],
                'status' => $eLendOK,
                'status_string' => $eLendReason,
        );
        $ebookLendTable->insert($insertValues);


        $view = $this->createViewModel(
            array(
                'driver' => $driver,
                'eLendResponse' => $eLendOK,
                'eLendEmail' => trim($user->email),
                'eLendReason' => $eLendReason,
                'maxIssues' => $maxIssues,
                'lendingInterval' => $lendingInterval,
            )
        );
        $view->setTemplate('record/ebook-lend');
        return $view;
    }
}
