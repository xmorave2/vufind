<?php 
/**
 * Trait for building MARC records drivers, adds ability to lend ebooks from eReading.cz
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Josef Moravec <josef.moravec@knihovna-uo.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wikia
 */

namespace Ereading\RecordDriver;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
trait EreadingTrait {

    public function getEreadingId()
    {
        return isset($this->fields['ereading_id']) ? $this->fields['ereading_id'] : array();
    }

    public function isEreadingBook()
    {
        return (boolean) $this->getEreadingId();
    }

    public function ereadingEnabled()
    {
        return $this->ereadingConfig["enable_ereading"];
    }

    public function getEreadingUrl()
    {
        return isset($this->fields['ereading_url']) ? $this->fields['ereading_url'] : array();
    }

    public function getEreadingPreviewPdf()
    {
        return isset($this->fields['ereading_preview_pdf']) ? $this->fields['ereading_preview_pdf'] : array();
    }

    public function getEreadingPreviewEpub()
    {
        return isset($this->fields['ereading_preview_epub']) ? $this->fields['ereading_preview_epub'] : array();
    }

    public function getEreadingPreviewMobi()
    {
        return isset($this->fields['ereading_preview_mobi']) ? $this->fields['ereading_preview_mobi'] : array();
    }

    public function getEreadingPreviews()
    {
        $previews = array();
        if(isset($this->fields['ereading_preview_pdf'])) {
                $previews["pdf"]  = $this->fields['ereading_preview_pdf'];
        }
        if(isset($this->fields['ereading_preview_epub'])) {
                $previews["epub"]  = $this->fields['ereading_preview_epub'];
        }
        if(isset($this->fields['ereading_preview_mobi'])) {
                $previews["mobi"]  = $this->fields['ereading_preview_mobi'];
        }
        return $previews;
    }

    public function getEreadingLendingUrl()
    {
        $url = "";
        $id = $this->getEreadingId();
        $user = $this->authManager->isLoggedIn();
        if($id && $user)
        {
                $url_params = "?knihovna=" . $this->ereadingConfig["library_id"]
                . "&user=" . urlencode(trim($user->email))
                . "&count=1"
                . "&ebook=" . $id
                . "&time=" . time();
                $hash = MD5($url_params . $this->ereadingConfig["password"]);
                $url = $this->ereadingConfig["api_url"] . $url_params . "&hash=" . $hash;
        }
        return $url;
    }

    public function getMaxEreadingIssues()
    {
        return $this->ereadingConfig["max_issues_in_time"] ? $this->ereadingConfig["max_issues_in_time"] : 3;
    }

    public function getEreadingLendingInterval()
    {
        return $this->ereadingConfig["lending_interval"] ? $this->ereadingConfig["lending_interval"] : 21;
    }

}

