<?php

/**
 * VuFind Search Service factory.
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2019.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Service
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace VuFind\Service;

class Redis
{
    /**
     * Redis connection
     *
     * @var \Credis_Client
     */
    protected $connection = false;

    /**
     * Redis version
     *
     * @var int
     */
    protected $version = 3;

    /**
     *  Hostname
     *
     * @var string
     */
    protected $host;

    /**
     * Port
     *
     * @var int
     */
    protected $port;

    /**
     * Timeout
     *
     * @var float
     */
    protected $timeout;

    /**
     * Authentication string/password
     *
     * @var bool
     */
    protected $auth;

    /**
     * Database identifier
     *
     * @var int
     */
    protected $db;

    /**
     * Use standalone mode if true, if false use predis php extension
     *
     * @var bool
     */
    protected $standalone;

    /**
     * Redis constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        // Set defaults if nothing set in config file.
        $this->host = $config->redis_host ?? 'localhost';
        $this->port = $config->redis_port ?? 6379;
        $this->timeout = $config->redis_connection_timeout ?? 0.5;
        $this->auth = $config->redis_auth ?? false;
        $this->db = $config->redis_db ?? 0;
        $this->version = (int)($config->redis_version ?? 3);
        $this->standalone = (bool)($config->redis_standalone ?? true);
    }

    /**
     * Get connection to Redis
     *
     * @throws \Exception
     * @return \Credis_Client
     */
    public function getConnection()
    {
        if (!$this->connection) {
            // Create Credis client, the connection is established lazily
            $this->connection = new \Credis_Client(
                $this->host, $this->port, $this->timeout, '', $this->db, $this->auth
            );
            if ($this->standalone) {
                $this->connection->forceStandalone();
            }
        }
        return $this->connection;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
