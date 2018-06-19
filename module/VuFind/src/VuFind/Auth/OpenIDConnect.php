<?php
/**
 * OpenID Connect authentication module.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace VuFind\Auth;
use VuFind\Exception\Auth as AuthException;
use Jumbojett\OpenIDConnectClient;

/**
 * OpenID Connect authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class OpenIDConnect extends AbstractBase implements
    \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Session container
     *
     * @var \Zend\Session\Container
     */
    protected $session;

    /**
     * OpenID Connect provider settings
     *
     * @var array
     */
    protected $provider

    /**
     * Constructor
     *
     * @param \Zend\Session\Container $container Session container for persisting
     * state information.
     */
    public function __construct(\Zend\Session\Container $container)
    {
        $this->session = $container;
    }

    protected getProvider()
    {
        if (!isset($this->provider)) {
            $url = $this->config->OpenIDConnect->url . ".well-known/openid-configuration";
            try {
                $this->provider = json_decode($this->httpService->get($url)->getBody());
            } catch($e) {
                throw new AuthException(
                    "Cannot fetch provider configuration: " . $e->getMessage()
                );
            }
        }
        return $this->provider;
    }

    /**
     * Validate configuration parameters.  This is a support method for getConfig(),
     * so the configuration MUST be accessed using $this->config; do not call
     * $this->getConfig() from within this method!
     *
     * @throws AuthException
     * @return void
     */
    protected function validateConfig()
    {
        $requiredParams = ['url', 'client_id', 'client_secret'];
        foreach ($requiredParams as $param) {
            if (!isset($this->config->OpenIDConnect->$param)
                || empty($this->config->OpenIDConnect->$param)
            ) {
                throw new AuthException(
                    "One or more OpenID Connect parameters are missing. Check your OpenIDConnect.ini!"
                );
            }
        }
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Zend\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        $code = $request->getQuery()->get('code');
        if (empty($code)) {
            throw new AuthException('authentication_error_admin');
        }
        $request_token = $this->getRequestToken($code);
        $state = $request->getQuery()->get('state');

        if ($state != $this->session->state) {
           throw new AuthException('authentication_error_admin');
        }
        unset($this->session->state);

        $claims = $this->decodeJWT($request_token->id_token, 1);
        //TODO Verify token signature

        //TODO Verify JWT claims

        unset($this->session->nonce);

        $id_token = $request_token->id_token;
        $access_token = $request_token->access_token;

        $user_info = $this->getUserInfo($access_token);

        return $user;
    }

    /**
     * Get the URL to establish a session (needed when the internal VuFind login
     * form is inadequate).  Returns false when no session initiator is needed.
     *
     * @param string $target Full URL where external authentication method should
     * send user after login (some drivers may override this).
     *
     * @return bool|string
     */
    public function getSessionInitiator($target)
    {
        $provider = $this->getProvider();
        $endpoint = $provider->authorization_endpoint;

        $nonce = hash( "sha256", random_bytes(32));
        $state = hash( "sha256", random_bytes(32));
        $this->session->openid_connect_nonce = $nonce;
        $this->session->openid_connect_state = $state;
    
        $params = [
            'response_type' => 'code',
            'redirect_uri' => $this->session->lastUri,
            'client_id' => $this->config->OpenIDConnect->client_id,
            'nonce' => $nonce,
            'state' => $state,
            'scope' => 'openid',
        ];
        //TODO  make url using httpClient
        $url = $provide->authorization_endpoint . '?' . http_build_query($params, null, '&');

        return $url;  
    }

    /**
     * Obtain an access token from a code.
     *
     * @param string $code Code to look up.
     *
     * @return string
     */
    protected function getRequestToken($code)
    {
        $url = $this->getProvider()->token_endpoint;
        $params = [
           'grant_type' => 'authorization_code',
           'code' => $code,
           'redirect_uri' => $this->session->lastUri,
           'client_id' => $this->config->OpenIDConnect->client_id,
           'client_secret' => $this->config->OpenIDConnect->client_secret,
        ];
        if (in_array('client_secret_basic', $this->getProvider()->token_endpoint_auth_methods_supported)) {
            $headers = ['Authorization: Basic ' . base64_encode($this->config->OpenIDConnect->client_id . ':' . $this->config->OpenIDConnect->client_secret)]; 
            unset($params['client_secret']);
        }
        $response = $this->httpService->get($url, $params, null, $headers);
        $json = json_decode($response->getBody());
        if ($json->error || !property_exists($json->id_token)) {
            throw new AuthException('authentication_error_admin');
        }
        return $json;
    }

    /**
     * Given an access token, look up user details.
     *
     * @param string $accessToken Access token
     *
     * @return array
     */
    protected function getUserInfo($access_token)
    {
        $url = $this->getProvider()->userinfo_endpoint;
        $params = [
            'schema' => 'openid',
        ]
        $headers = ['Authorization: Bearer ' . $access_token];

        return json_decode($this->httpService->get($url, $params, null, $headers));
    }

    protected function decodeJWT($jwt, $section = 0)
    {
        $parts = explode(".", $jwt);
        return json_decode(base64url_decode($parts[$section]));
    }

}
