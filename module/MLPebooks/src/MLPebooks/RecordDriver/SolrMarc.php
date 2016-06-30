<?php
/**
 * Model for MARC records in Solr. Extended for work with ebooks from Municipal Library Prague
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015.
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
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <josef.moravec@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace MLPebooks\RecordDriver;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Josef Moravec <josef.moravec@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    /**
     * Get links for ebooks from Municipal Library Prague
     *
     * @return array
     */
    public function getMlpEbooksLinks()
    {
        $result = [];
        if(isset($this->fields['eFormats'])) {
            $formats = explode(',', $this->fields['eFormats']);
            $filename = $this->fields['mlpFilename'];
            $idPath = ltrim($this->fields['id'], "mlp.");
            $idPath = str_pad($idPath, 10, "0", STR_PAD_LEFT);
            $idPath = chunk_split($idPath, 2, "/");
            $baseUrl = "https://web2.mlp.cz/koweb/";
            foreach($formats as $format) {
                $result[$format] = $baseUrl . $idPath . $filename . "." . $format;
           }
        }
        return $result;
    }
}


