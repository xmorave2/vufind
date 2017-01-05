<?php
/**
 * KohaRESTful ILS Driver
 *
 * PHP version 5
 *
 * Copyright (C) Josef Moravec, 2016.
 * Copyright (C) Jiri Kozlovsky, 2016.
 * Copyright (C) Martin Kravec, 2016.
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
 * @package  ILS_Drivers
 * @author   Josef Moravec <josef.moravec@gmail.com>
 * @author   Jiri Kozlovsky <@>
 * @author   Martin Kravec <kravec.martin@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace VuFind\ILS\Driver;

use VuFind\Exception\ILS as ILSException,
    VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface,
    VuFind\Exception\Date as DateException;

//todo: will extend \VuFind\ILS\Driver\AbstractBase, this is just for testing and developing purposes
class KohaRESTful extends \VuFind\ILS\Driver\KohaILSDI implements
    HttpServiceAwareInterface, LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * REST API base URL
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * REST API user userid/login
     *
     * @var string
     */
    protected $apiUserid;

    /**
     * REST API user password
     *
     * @var string
     */
    protected $apiPassword;

    /**
     * Location codes
     *
     * @var array
     */
    protected $locations;

    /**
     * Codes of locations available for pickup
     *
     * @var array
     */
    protected $pickupEnableBranchcodes;

    /**
     * Codes of locations always should be available
     *   - For example reference material or material
     *     not for loan
     *
     * @var array
     */
    protected $availableLocationsDefault;

    /**
     * Default location code
     *
     * @var string
     */
    protected $defaultLocation;


    //TODO: make date format configurable
    protected $dateFormat = "d. m. Y";

    /**
     * Date converter object
     *
     * @var \VuFind\Date\Converter
     */
    //protected $dateConverter;

    /**
     * Id of CGI session for Koha RESTful API 
     */
    protected $CGISESSID;

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        if (empty($this->config)) {
            throw new ILSException('Configuration needs to be set.');
        }

        // Is debugging enabled?
        // TODO: get rid of this and use standard vufind debugging system
        $this->debug_enabled = isset($this->config['Catalog']['debug'])
            ? $this->config['Catalog']['debug'] : false;

        // Storing the base RESTful API connection information
        $this->apiUrl = isset($this->config['Catalog']['apiurl'])
            ? $this->config['Catalog']['apiurl'] : "";
        $this->apiUserid = isset($this->config['Catalog']['apiuserid'])
            ? $this->config['Catalog']['apiuserid'] : null;
        $this->apiPassword = isset($this->config['Catalog']['apiuserpassword'])
            ? $this->config['Catalog']['apiuserpassword'] : null;
        // Authenticate to RESTful API
        $patron = $this->makeRESTfulRequest('/auth/session', 'POST', ['userid' => $this->apiUserid, 'password' => $this->apiPassword ]);
        if ($patron) {
            $this->CGISESSID = $patron->sessionid;
        } else {
            throw new ILSException('Can not authenticate to Koha through RESTful API');
        }

        // MySQL database host
        $this->host = isset($this->config['Catalog']['host']) ?
            $this->config['Catalog']['host'] : "localhost";

        // Storing the base URL of ILS-DI
        $this->ilsBaseUrl = isset($this->config['Catalog']['url'])
            ? $this->config['Catalog']['url'] : "";

        // Default location defined in 'KohaRESTful.ini'
        $this->defaultLocation
            = isset($this->config['Holds']['defaultPickUpLocation'])
            ? $this->config['Holds']['defaultPickUpLocation'] : null;
        $this->pickupEnableBranchcodes
            = isset($this->config['Holds']['pickupLocations'])
            ? $this->config['Holds']['pickupLocations'] : [];

        // Locations that should default to available, defined in 'KohaRESTful.ini'
        $this->availableLocationsDefault
            = isset($this->config['Other']['availableLocations'])
            ? $this->config['Other']['availableLocations'] : [];

        // Create a dateConverter
        //$this->dateConverter = new \VuFind\Date\Converter;
    }

   /**
     * Make Request
     *
     * Makes a request to the Koha ILSDI API
     *
     * @param string $apiQuery    Query string for request (starts with "/")
     * @param string $httpMethod  HTTP method (default = GET)
     * @param array  $data        Provide needed data in this paramater (default = null)
     *
     * @throws ILSException
     * @return array
     */
    protected function makeRESTfulRequest($apiQuery, $httpMethod = "GET", $data = null)
    {
        // TODO - get rid of this kind of authentication and use just session
        $kohaDate = date("r"); // RFC 1123/2822
        $signature = implode(" ", [(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "HTTPS" : "HTTP",
                                   $this->apiUserid,
                                   $kohaDate
                     ]);

        $hashedSignature = hash_hmac("sha256", $signature, $this->apiPassword);

        $httpHeaders = [
            "Accept" => "application/json",
            "X-Koha-Date" => $kohaDate,
            "Authorization" => "Koha " . $this->apiUserid . ":" . $hashedSignature ,
        ];

        $client = $this->httpService->createClient($this->apiUrl . $apiQuery, $httpMethod);
        $client->setHeaders($httpHeaders);
        if(isset($this->CGISESSID)) {
            $client->addCookie('CGISESSID', $this->CGISESSID);
        }
        if($data !== null) {
            if ($httpMethod == 'GET') {
                $client->setParameterGet($data);
            } else {
                $client->setRawBody(http_build_query($data));
            }
        }
        try {
            $response = $client->send();
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$response->isSuccess()) {
            throw new ILSException("Error in communication with Koha API:" . $response->getBody() . 
                                   " HTTP status code: " . $response->getStatusCode() );
        }

        $result = json_decode($response->getBody());
        if (json_last_error() !== JSON_ERROR_NONE ) {
            throw new ILSException("Error parsing json response of Koha API");
        }
        return $result;
    }

    /**
     * Format dates
     */
    protected function formatDate($date)
    {
        if (!$date) { return NULL; }
        $dateObject = new \DateTime($date);
        return $dateObject->format($this->dateFormat);
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     */
    public function getConfig($function)
    {
        $functionConfig = "";
        if (isset($this->config[$function])) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }

    /**
     * https://vufind.org/wiki/development:plugins:ils_drivers#getpickuplocations
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        // TODO: check if pickupEnableBranchcodes is set (maybe better in init method), if not, use default, if it's not set, get all locations from API
        if ( !isset($this->locations )) {
            $libraries = $this->makeRESTfulRequest("/libraries");
            $locations = [];
            foreach($libraries as $library)
            {
                if (in_array($library->branchcode, $this->pickupEnableBranchcodes)) {
                    $locations[] = [
                        "locationID" => $library->branchcode,
                        "locationDisplay" => $library->branchname,
                    ];
                }
            }
            $this->locations = $locations;
        }
        return $this->locations;
    }

	/**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or throws an exception on failure of support
     * classes
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @throws ILSException
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available)
     */
    public function placeHold($holdDetails)
    {
        $patron          = $holdDetails['patron'];
        $pickup_location = !empty($holdDetails['pickUpLocation'])
            ? $holdDetails['pickUpLocation'] : $this->defaultLocation;
        $level           = isset($holdDetails['level'])
            && !empty($holdDetails['level']) ? $holdDetails['level'] : "item";

        try {
            $dateObject = \DateTime::createFromFormat(
                "m-d-Y", $holdDetails['requiredBy']
            );
            if (is_object($dateObject)) {
                $needed_before_date = $dateObject->format("Y-m-d");
            }
        } catch (\Exception $e) {
            return [
                "success" => false,
                "sysMessage" => "It seems you entered an invalid expiration date."
            ];
        }

        $data = [
            'borrowernumber' => $patron['id'],
            'expirationdate' => $needed_before_date,
            'branchcode'     => $pickup_location,
        ];
        if ($level == 'item') { 
            $data['itemnumber'] = $holdDetails['item_id'];
        } else {
            $data['biblionumber'] = $holdDetails['id'];
        }
        if (isset($holdDetails['comment']) && !empty($holdDetails['comment'])) {
            $data['reservenotes'] = $holdDetails['comment'];
        }
        try {
            $result = $this->makeRESTfulRequest('/holds', 'POST', $data);
        } catch (Exception $e) {
            return [
                "succes" => false,
                "sysMessage" => $e->getMessage(),
            ];
        }

        return ['success' => true];
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location set in KohaILSDI.ini
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.    May be used to limit the pickup options
     * or may be ignored.
     *
     * @return string The default pickup location for the patron.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        return $this->defaultLocation;
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron's password
     *
     * @throws ILSException
     * @return mixed          Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        $patron = $this->makeRESTfulRequest('/auth/session', 'POST', ['userid' => $username, 'password' => $password ]);
        if ($patron) {
            return [
                'id' => $patron->borrowernumber,
                'firstname' => $patron->firstname,
                'lastname' => $patron->surname,
                'cat_username' => $username,
                'cat_password' => $password,
                'email' => $patron->email,
                'major' => null,
                'college' => null,
            ];
        }
        return null;
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @throws ILSException
     * @return array        Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
         $patron = $this->makeRESTfulRequest('/patrons/' . $patron['id']);
         if ($patron) {
             return [
                'firstname' => $patron->firstname,
                'lastname'  => $patron->surname,
                'address1'  => $patron->address . ' ' . $patron->streetnumber,
                'address2'  => $patron->address2,
                'city'      => $patron->city,
                'country'   => $patron->country,
                'zip'       => $patron->zipcode,
                'phone'     => $patron->phone,
                'group'     => $patron->categorycode,
             ];
         }
         return null;
    }

	/**
     * Change Password
     *
     * This method changes patron's password (PIN code)
     *
     * @param array $details An array of patron id and old and new password:
     *
     * patron      The patron array from patronLogin
     * oldPassword Old password
     * newPassword New password
     *
     * @return array An array of data on the request including
     * whether or not it was successful and a system message (if available)
     */
    public function changePassword($details)
    {
        $patron = $details['patron'];
        $data = [
            'current_password' => $details['oldPassword'],
            'new_password' => $details['newPassword'],
        ];
        try {
            $change = $this->makeRESTfulRequest(
                '/patrons/' . $patron['id'] . '/password',
                'PATCH',
                $data
            );
        } catch (Exception $e) {
            return [ 'success' => false, 'status' => $e->getMessage() ];
        }
        return ['success' => true, 'status' => 'change_password_ok'];
    }    

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws \VuFind\Exception\Date
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactions($patron)
    {
        $checkouts = $this->makeRESTfulRequest('/checkouts', 'GET', [ 'borrowernumber' => $patron['id'] ]);
        $checkoutsList = [];
        if($checkouts) {
            foreach ($checkouts as $checkout) {
                $item = $this->makeRESTfulRequest('/items/' . $checkout->itemnumber);
                try {
                     $renewability = $this->makeRESTfulRequest(
                         '/checkouts/' . $checkout->issue_id . '/renewability'
                     ); 
                     $renewable = true;
                 } catch (ILSException $e) {
                     $renewable = false;
                 }
                $checkoutsList[] = [
                    'duedate'           => $this->formatDate($checkout->date_due),
                    'id'                => $item ? $item->biblionumber : 0,
                    'item_id'           => $checkout->itemnumber,
                    'barcode'           => $item ? $item->barcode : 0,
                    'renew'             => $checkout->renewals,
                    'borrowingLocation' => $checkout->branchcode, //TODO: add branch name
           			'renewable'         => $renewable,
//                    'request => , //TODO: is item reserved?
                ];
       
            }
        }
        return $checkoutsList;
    }

	/**
	 * Get Patron Items history
	 *
	 * This is responsible for retrieving all items history (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param array $patron The patron array from patronLogin
	 *
	 * @throws \VuFind\Exception\Date
	 * @throws ILSException
	 * @return array        Array of the patron's items history on success.
	 */
	public function getItemsHistory($patron)
	{
	    $checkouts = $this->makeRESTfulRequest('/checkouts', 'GET', [ 'borrowernumber' => $patron['id'] ]);
	    $checkoutsList = [];
	    if($checkouts) {
	        foreach ($checkouts as $checkout) {
	            $item = $this->makeRESTfulRequest('/items/' . $checkout->itemnumber);

	            $checkoutsList[] = [
	                'duedate'           => $this->formatDate($checkout->date_due),
	                'id'                => $item ? $item->biblionumber : 0,
	                'item_id'           => $checkout->itemnumber,
	                'barcode'           => $item ? $item->barcode : 0,
	                'renew'             => $checkout->renewals,
	                'borrowingLocation' => $checkout->branchcode, //TODO: add branch name
	            ];
	   
	        }
	    }

		$checkoutsList[] = $checkouts;
	    return $checkoutsList;
	}

    /** Get Patron Holds
     *
     */
    public function getMyHolds($patron)
    {
        $holds = $this->makeRESTfulRequest('/holds', 'GET', [ 'borrowernumber' => $patron['id'] ]);
        $holdsList = [];
        if($holds) {
            foreach ($holds as $hold) {
                $holdsList[] = [
                    'id'         => $hold->biblionumber,
                    'location'   => $hold->branchcode, //TODO: add branch name
                    'expire'     => $this->formatDate($hold->expirationdate),
                    'create'     => $this->formatDate($hold->reservedate),
                    'position'   => $hold->priority,
                    'available'  => $hold->found,
                    'item_id'    => $hold->itemnumber,
                    'reserve_id' => $hold->reserve_id,
                ];
            }
        }
        return $holdsList;
    }

    /** Get Patron Fines
     *
     */
//    public function getMyFines($patron)
//    {
//        $fines = $this->makeRESTfulRequest('/');
 
    /**
     * Insert Suggestion
     */

    /** Get Holdings
     *
     */
    public function getHolding($id, array $patron = null)
    {
        $biblio = $this->makeRESTfulRequest('/biblios/' . $id);
        $holdingsList = [];
        if ($biblio) {
			$holds = $this->makeRESTfulRequest('/holds', 'GET', [ 'biblionumber' => $biblio->biblionumber ]);
            foreach ($biblio->items as $i) {
                $item = $this->makeRESTfulRequest('/items/' . $i->itemnumber);
                $holdingsList[] = [
                    'id'              => $id,
                    'availability'    => $item->onloan ? false : true,
                    'status'          => $item->onloan ? 'Checked out' : 'Available', //TODO: more statuses
                    'location'        => $item->holdingbranch,
                    'callnumber'      => $item->callnumber,
                    'number'          => $item->stocknumber,
                    'barcode'         => $item->barcode,
                    'supplements'     => $item->materials,
                    'item_notes'      => $item->itemnotes,
                    'item_id'         => $i->itemnumber,
                    'requests_placed' => count($holds),
                    'duedate'         => null, //TODO
                    'returnDate'      => false, //TODO
                    'reserve'         => (count($holds) > 1) ? 'Y' : 'N',
                ];
            }
        }
        return $holdingsList;
    }

	/**
     * Get Cancel Hold Details
     *
     * Get required data for canceling a hold. This value is used by relayed to the
     * cancelHolds function when the user attempts to cancel a hold.
     *
     * @param array $holdDetails An array of hold data
     *
     * @return string Data for use in a form field
     */
    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['available'] ? '' : $holdDetails['reserve_id'];
    }

    /**
     * Cancel Holds
     *
     * This method cancels a list of holds for a specific patron
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     */
    public function cancelHolds($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $patron = $cancelDetails['patron'];
        $count = 0;
        $response = [];
        foreach ($details as $holdId) {
            try {
                $cancelledHold = $this->makeRESTfulRequest('/holds/' . $holdId, 'DELETE');
                $count++;
                $response[$holdId] = [
                    'success' => true, 
                    'status' => 'hold_cancel_success'
                ];
            } catch (ILSException $e) {
                $response[$holdId] = [
                    'success' => false, 
                    'status' => 'hold_cancel_fail',
                    'sysMessage' => $e->getMessage()
                ];
            }
        }
        return ['count' => $count, 'items' => $response];
    }

    /**
     * Get Renew Details
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     */
    public function getRenewDetails($checkOutDetails)
    {
        return $checkOutDetails['checkout_id'] . '|' . $checkOutDetails['item_id'];
    }

    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     */
    public function renewMyItems($renewDetails)
    {
        $patron = $renewDetails['patron'];
        $results = ['details' => [], 'blocks' => false ];
        foreach ($renewDetails['details'] as $details) {
            list($checkoutId, $itemId) = explode('|', $details);
            try {
                $renew = $this->makeRequest('/checkouts/' . $checkoutId, 'PUT');
                $results['details'][$itemId] = [
                    'success' => true,
                    'new_date' => $this->formatDate($renew->date_due),
                    'item_id' => $itemId,
                ];

            } catch (ILSException $e) {
                $results['details'][$itemId] = [
                    'success' => false,
                    'item_id' => $itemId,
                ];
            }
        }
        return $results;
    }

}
