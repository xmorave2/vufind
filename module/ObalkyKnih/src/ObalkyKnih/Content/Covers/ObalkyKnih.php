<?php
/**
 * ObalkyKnih cover loader.
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
 * @category VuFind
 * @package  Content
 * @author   Josef Moravec <josef.moravec@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace ObalkyKnih\Content\Covers;
/**
 * OpenLibrary cover content loader.
 *
 * @category VuFind
 * @package  Content
 * @author   Josef Moravec <josef.moravec@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ObalkyKnih extends \VuFind\Content\AbstractCover
    implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        /* First implementation supports ISBN and ISSN, but obalkyknih.cz supports
           also EAN and OCLC, could be implemented in the future */
        $this->supportsIsbn = $this->supportsIssn = $this->cacheAllowed = true;
    }

    /**
     * Get an HTTP client
     *
     * @param string $url URL for client to use
     *
     * @return \Zend\Http\Client
     */
    protected function getHttpClient($url)
    {
        if (null === $this->httpService) {
            throw new \Exception('HTTP service missing.');
        }
        return $this->httpService->createClient($url);
    }

    /**
     * Get image URL for a particular API key and set of IDs (or false if invalid).
     *
     * @param string $key  API key
     * @param string $size Size of image to load (small/medium/large)
     * @param array  $ids  Associative array of identifiers (keys may include 'isbn'
     * pointing to an ISBN object and 'issn' pointing to a string)
     *
     * @return string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($key, $size, $ids)
    {
        // Don't bother trying if we can't read JSON or ISBN or ISSN is missing:
        if (!is_callable('json_decode') || (!isset($ids['isbn']) && !isset($ids['issn']))) {
            return false;
        }
        $biblioinfo = [];
        if (isset($ids['isbn'])) {
            $biblioinfo['isbn'] = $ids['isbn']->get13();
        } elseif (isset($ids['issn'])) {
            // yes, the parametr for ISSN in obalkyknih API is still "isbn"
            $biblioinfo['isbn'] = $ids['issn'];
        }
        $url = "http://cache.obalkyknih.cz/api/books?multi=";
        $url .= json_encode([$biblioinfo]);

        $result = $this->getHttpClient($url)->send();
        if ($result->isSuccess()) {
            $data = json_decode($result->getBody(), true);
        }

        if (!isset($data[0]) || $data[0]["flag_bare_record"] == 1) {
            return false;
        }

        $coverUrl = false;
        switch ($size) {
            case "small":
                $coverUrl = $data[0]["cover_thumbnail_url"];
                break;
            case "medium":
                $coverUrl = $data[0]["cover_icon_url"];
                break;
            case "large":
                $coverUrl = $data[0]["cover_medium_url"];
                break;
        }

        return $coverUrl;
    }
}
