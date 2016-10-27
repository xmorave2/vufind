<?php
/**
 * Model for MARC records in Solr, customized for Municipal Library �st� nad Orlic�
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
 * @package  RecordDrivers
 * @author   Josef Moravec <josef.moravec@knihovna-uo.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
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

class SolrMarc extends \VuFind\RecordDriver\SolrMarc {
    use EreadingTrait;

    protected $ereadingConfig;
    protected $authManager;

    public function __construct($mainConfig = null, $recordConfig = null,
        $searchSettings = null, $ereadingConfig = null, $authManager = null
    ) {
        $this->ereadingConfig = $ereadingConfig["Ereading"];
        $this->authManager = $authManager;
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
    }

}
