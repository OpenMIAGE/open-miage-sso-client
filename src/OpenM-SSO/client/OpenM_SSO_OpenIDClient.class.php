<?php

Import::php("OpenM-SSO.api.OpenM_SSO");
Import::php("OpenM-ID.api.OpenM_ID");
Import::php("OpenM-ID.api.OpenM_ID_Tool");
Import::php("util.ArrayList");
Import::php("util.http.OpenM_Header");
Import::php("util.http.OpenM_URL");

/**
 * @copyright (c) 2013, www.open-miage.org
 * @license http://www.apache.org/licenses/LICENSE-2.0 Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * @link http://www.open-miage.org
 * @author Gaël Saunier
 */

/**
 * in case of no existing /dev/urandom,
 * setup Auth_OpenID_RAND_SOURCE with null.
 * this configuration pertmit to use this class
 * with minimum settings. 
 */
if (!is_file('/dev/urandom') || !defined('Auth_OpenID_RAND_SOURCE'))
    define("Auth_OpenID_RAND_SOURCE", null);

$abs = Import::getAbsolutePath("Auth/OpenID/Consumer.php");
if ($abs != null)
    Import::addInPhpClassPath(dirname(dirname(dirname($abs))));
else
    throw new ImportException("Auth/OpenID/Consumer");

Import::php("Auth/OpenID/FileStore.php");
Import::php("Auth/OpenID/Consumer.php");
Import::php("Auth/OpenID/Server.php");
Import::php("Auth/OpenID/SReg.php");

/**
 * OpenID client that drive OpenID JanRain library
 * @package OpenM 
 * @subpackage OpenM\OpenM-SSO\client 
 * @author Gaël Saunier
 */
class OpenM_SSO_OpenIDClient {

    private $store;
    private $openM_ID_api_path;
    private $properties;
    private $initialURL;
    private $OID;

    /**
     * @param String $openM_ID_api_path is OpenM_ID url (ex.: http://auth.open-miage.fr)
     * @param String $store_path is an existing direcoty required to store OpenID
     * transactions 
     * @param String $currentURL is current URL in browser
     * @throws InvalidArgumentException
     */
    public function __construct($openM_ID_api_path, $store_path, $currentURL) {
        if (!String::isString($openM_ID_api_path))
            throw new InvalidArgumentException("api_path must be a string");
        if (!String::isString($currentURL))
            throw new InvalidArgumentException("uri must be a string");
        if (!String::isString($store_path))
            throw new InvalidArgumentException("store_path must be a string");

        $this->openM_ID_api_path = $openM_ID_api_path;
        $this->initialURL = $currentURL;
        //on crée le répértoire pour stocquer les fichiers 
        $this->store = new Auth_OpenID_FileStore($store_path);
        $this->properties = new HashtableString();
        OpenM_Log::debug("$openM_ID_api_path OpenIDclient stored in $store_path", __CLASS__, __METHOD__, __LINE__);
    }

    /**
     * used to complete an OpenID authentification transaction
     * on OpenM_ID
     */
    public function complete() {
        OpenM_Log::debug("complete begin", __CLASS__, __METHOD__, __LINE__);
        $consumer = new Auth_OpenID_Consumer($this->store);

        OpenM_Log::debug("complete openId", __CLASS__, __METHOD__, __LINE__);
        $result = $consumer->complete($this->initialURL);
        OpenM_Log::debug("complete status: '" . $result->status . "'", __CLASS__, __METHOD__, __LINE__);
        if ($result->status == Auth_OpenID_SUCCESS) {
            OpenM_Log::debug("complete success", __CLASS__, __METHOD__, __LINE__);
            $sreg = Auth_OpenID_SRegResponse::fromSuccessResponse($result)->contents();
            $this->properties = HashtableString::from($sreg);
            return true;
        } else {
            OpenM_Log::debug("complete failure", __CLASS__, __METHOD__, __LINE__);
            return false;
        }
    }

    /**
     * used to begin an OpenID authentification transaction
     * on OpenM_ID
     * @param String $return_to is initial URL given to OpenM_ID to anwser
     * @param String $realm is realm under that asked authorization
     * @param array|ArrayList $properties contains list of properties asked
     * to OpenM_ID (use OpenID protocol)
     * @return String URL of OpenM_ID with parameters of required
     * OpenID authentification
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    public function begin($return_to, $realm, $properties = null) {

        if (!String::isString($return_to))
            throw new InvalidArgumentException("return_to must be a string");
        if ($realm != null && !String::isString($realm))
            throw new InvalidArgumentException("realm must be a string");
        if (!ArrayList::isArrayOrNull($properties))
            throw new InvalidArgumentException("properties must be an array or an ArrayList");

        if ($this->OID == null)
            throw new ErrorException("OID must be initialized before starting OpenID authentification.");

        $consumer = new Auth_OpenID_Consumer($this->store);
        OpenM_Log::debug("Begin request build with OID=" . $this->OID, __CLASS__, __METHOD__, __LINE__);
        $authRequest = $consumer->begin($this->OID);

        if ($properties != null) {
            if ($properties instanceof ArrayList)
                $properties = $properties->toArray();

            $sreg_request = Auth_OpenID_SRegRequest::build($properties);

            if ($sreg_request) {
                OpenM_Log::debug("add properties (" . implode(", ", $properties) . ")", __CLASS__, __METHOD__, __LINE__);
                $authRequest->addExtension($sreg_request);
            }
        }
        OpenM_Log::debug("return request with realm=" . $realm . " and uri=" . $this->initialURL, __CLASS__, __METHOD__, __LINE__);
        return $authRequest->redirectURL($realm, $this->initialURL);
    }

    /**
     * used to logout from OpenM_ID
     * @param boolean $redirectToLogin
     */
    public function logout($redirectToLogin = true) {
        OpenM_Log::debug("logout redirection", __CLASS__, __METHOD__, __LINE__);
        OpenM_Header::redirect($this->openM_ID_api_path . "?" . OpenM_ID::LOGOUT_API . "&return_to=" . OpenM_URL::encode() . ((!$redirectToLogin) ? ("&" . OpenM_ID::NO_REDIRECT_TO_LOGIN_PARAMETER) : ""));
    }

    /**
     * used to recover asked property values
     * @return HashtableString contains the list of asked properties with associated values
     */
    public function getPropeties() {
        return $this->properties->copy();
    }

    /**
     * used to recover the OID of connected user by using OpenM_ID / OpenM-ID_GET API
     * @param boolean $redirectToLoginIfNotConnected is true if you whant to
     * redirect to OpenM_ID login form in case of not connected, else just return
     * @return String OID of user
     */
    public function get($redirectToLoginIfNotConnected = true) {
        if (isset($_GET[OpenM_ID::OID_PARAMETER])) {
            OpenM_Log::debug(OpenM_ID::OID_PARAMETER . " present in url (" . $_GET[OpenM_ID::OID_PARAMETER] . ")", __CLASS__, __METHOD__, __LINE__);
            $this->OID = OpenM_URL::decode($_GET[OpenM_ID::OID_PARAMETER]);
        }

        if ($this->OID != null)
            return $this->OID;

        if ($this->OID == null && !isset($_GET[OpenM_ID::OID_PARAMETER])) {
            OpenM_Log::debug(OpenM_ID::OID_PARAMETER . " not present in url and not previewsly loaded", __CLASS__, __METHOD__, __LINE__);
            OpenM_Header::redirect($this->openM_ID_api_path . "?" . OpenM_ID::GetOpenID_API . "&return_to=" . OpenM_URL::encode($this->initialURL) . ((!$redirectToLoginIfNotConnected) ? ("&" . OpenM_ID::NO_REDIRECT_TO_LOGIN_PARAMETER) : ""));
        }
    }

    /**
     * used to check if an OID is valid
     * @see self::getOID
     * @param String $OID is an OID generaly provided by get method
     * @return boolean equals to true if OID is valid, else false
     * @throws InvalidArgumentException
     */
    public function isValid($OID = null) {
        if (!String::isStringOrNull($OID))
            throw new InvalidArgumentException("OID must be a string");

        if ($OID == OpenM_SSO::RETURN_ERROR_MESSAGE_NOT_CONNECTED_VALUE)
            return false;

        if (!strpos(OpenM_URL::getURLwithoutParameters($this->openM_ID_api_path) . "?" . OpenM_ID::URI_API . "=", $OID))
            return true;

        return false;
    }

    /**
     * used to recover the user ID.
     * this ID could be calculate from the user OID.
     * @see self::getOID
     * @param String $OID of user. generaly provided by get method
     * @return null|String is user ID if exist, else null
     */
    public function getID($OID = null) {
        if ($OID == null)
            $OID = $this->OID;
        if (!$this->isValid($OID))
            return null;
        else
            return OpenM_ID_Tool::getId($OID);
    }

    /**
     * used to recover the OID of user
     * @return String is OID of user
     */
    public function getOID() {
        return $this->OID . "";
    }

}

?>