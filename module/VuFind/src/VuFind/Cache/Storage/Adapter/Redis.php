<?php

/**
 * VuFind Redis cache adapter.
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
 * @package  Cache
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace VuFind\Cache\Storage\Adapter;

use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\Adapter\AdapterOptions;

/**
 * VuFind Redis cache adapter.
 *
 * @category VuFind
 * @package  Cache
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Redis extends AbstractAdapter
{
    /**
     * Redis connection
     *
     * @var \VuFind\Service\Redis
     */
    protected $service = null;

    /**
     * Redis constructor.
     *
     * @param \VuFind\Service\Redis $redisService
     * @param null|array|\Traversable|AdapterOptions $options
     */
    public function __construct(\VuFind\Service\Redis $redisService, $options = null)
    {
        parent::__construct($options);
        $this->service = $redisService;
    }

    /**
     * Get connection to Redis
     *
     * @throws \CredisException
     * @return \Credis_Client
     */
    protected function getConnection()
    {
        return $this->service->getConnection();
    }

    /**
     * Internal method to get an item.
     *
     * @param string $normalizedKey
     * @param bool   $success
     * @param mixed  $casToken
     *
     * @return mixed Data on success, null on failure
     * @throws \CredisException
     * @throws \Exception
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $value = $this->getConnection()->get($normalizedKey);

        if ($value === false) {
            $success = false;
            return null;
        }
        $success = true;
        $casToken = $value;
        return $value;
    }

    /**
     * Internal method to store an item.
     *
     * @param string $normalizedKey
     * @param mixed $value
     * @return bool
     * @throws \CredisException
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $options = $this->getOptions();
        $ttl     = $options->getTtl();
        if ($ttl) {
            $return = $this->getConnection()->setex($normalizedKey, $ttl, $value);
        } else {
            $return = $this->getConnection()->set($normalizedKey, $value);
        }
        return $return;
    }

    /**
     * Internal method to remove an item.
     *
     * @param string $normalizedKey
     * @return bool
     * @throws \CredisException
     */
    protected function internalRemoveItem(& $normalizedKey)
    {
        if ($this->service->getVersion() >= 4) {
            return (bool)$this->getConnection()->unlink($normalizedKey);
        } else {
            return (bool)$this->getConnection()->del($normalizedKey);
        }
    }
}
