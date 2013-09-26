<?php

Import::php("OpenM-SSO.client.OpenM_SSOClientSession");
Import::php("OpenM-Services.client.OpenM_ServiceClientImpl");
Import::php("OpenM-SSO.client.OpenM_SSO_OpenIDClient");
Import::php("OpenM-SSO.client.OpenM_SSOSession");
Import::php("OpenM-SSO.api.OpenM_SSO");
Import::php("util.http.OpenM_URL");
Import::php("util.http.OpenM_Header");
Import::php("util.time.Date");

/**
 * Manage a SSO (Single Sign On) session without create manualy 
 * the connection with OpenM_ID and open session with an OpenM_SSO provider.
 * @package OpenM
 * @subpackage OpenM\OpenM-SSO\client
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
class OpenM_SSOClientSessionImpl implements OpenM_SSOClientSession, OpenM_SSOSession {

    private $version;
    private $OpenIdConnectionStatus;
    private $SSOApiConnectionStatus;
    private $properties = null;
    private $SSID = null;
    private $token = null;
    private $SSID_session_start = null;
    private $SSID_session_end = null;
    private $isSSIDrequested = false;
    private $SSOClient = null;
    private $OpenIDClient = null;
    private $isLoginInProcess = false;
    private $OID;
    private $realm;
    private $uri;
    private $openM_ID_api_path;
    private $store_path;
    private $sso_api_path;
    private $isLogoutInProgress;
    private $connectedAtLeastOneTimeBefore = false;
    private $embeded = false;

    /**
     * Initialize session with api (no connection, only initializing local properties).
     * @param String $OpenM_ID_api_path is URL OpenM_ID api (ex.: http://auth.open-miage.fr)
     * @param String $store_path is a directory required to manage OpenID
     * sesssion by JanRain Lib.
     * RQ: it's extremely recommended to put a directory not accessible from http request
     * @param String $realm domain / sub domain / root directory, that you ask a OpenID right
     * @param String $sso_api_path is api path that protected by an OpenM_SSO provider
     */
    public function __construct($OpenM_ID_api_path, $store_path, $realm = null, $sso_api_path = null) {
        $this->openM_ID_api_path = $OpenM_ID_api_path;
        $this->store_path = realpath($store_path);
        $this->sso_api_path = $sso_api_path;
        if ($realm != null)
            $this->realm = $realm;
        else
            $this->realm = OpenM_URL::getURLWithoutHttpAndWww(OpenM_URL::getHost());
        if (strpos($this->realm, OpenM_URL::getURLWithoutHttpAndWww(OpenM_URL::getHost())) === false)
            throw new InvalidArgumentException("realm (" . $this->realm . ") must be on the same domain or on a sub domain of " . OpenM_URL::getURLWithoutHttpAndWww(OpenM_URL::getHost()));
        $this->init();
    }

    /**
     * Savoir si la session avec l'api est initialisé (parametre chargé)
     * @return boolean
     */
    private function isInitialized() {
        return $this->uri != null;
    }

    /**
     * initialise une session (charge les parametres)
     * @throws InvalidArgumentException
     */
    public function init() {
        OpenM_Log::debug("init begin", __CLASS__, __METHOD__, __LINE__);
        $this->reset();

        $this->version = self::VERSION;

        if ($this->sso_api_path != null) {
            OpenM_Log::debug("API used (" . $this->sso_api_path . "), sso activated", __CLASS__, __METHOD__, __LINE__);
            $this->SSOClient = new OpenM_ServiceClientImpl($this->sso_api_path, "OpenM_SSO", false);
            $this->SSOApiConnectionStatus = self::STATUS_SSO_API_START;
        } else {
            $this->SSOApiConnectionStatus = self::STATUS_SSO_API_NOT_ACTIVATED;
            OpenM_Log::debug("No API used, sso not activated", __CLASS__, __METHOD__, __LINE__);
        }

        $this->uri = OpenM_URL::getURL();
        OpenM_Log::debug("uri=" . $this->uri, __CLASS__, __METHOD__, __LINE__);
        $this->isSSIDrequested = false;
        $this->OpenIdConnectionStatus = self::STATUS_OpenID_START;
        $this->isLoginInProcess = false;
        //on crée le client OpenID
        $this->OpenIDClient = new OpenM_SSO_OpenIDClient($this->openM_ID_api_path, $this->store_path, $this->uri);

        if (!String::isStringOrNull($this->realm))
            throw new InvalidArgumentException("realm must be a string");

        if ($this->realm == null)
            $this->realm = OpenM_URL::getHost();
        OpenM_Log::debug("realm=" . $this->realm, __CLASS__, __METHOD__, __LINE__);
    }

    /**
     * used to know if sso connection is OK and permit api calling
     * @return boolean true if SSO api is correctly connected
     */
    public function isSSOapiActivated() {
        return $this->SSOClient != null;
    }

    /**
     * use to ckeck the OpenID connection status
     * @return int is one of a interface const value
     * @see OpenM_SSOClientSession constants
     */
    public function getStatusOpenID() {
        return $this->OpenIdConnectionStatus;
    }

    /**
     * use to ckeck the OpenM_SSO client connection status
     * @return int is one of a interface const value
     * @see OpenM_SSOClientSession constants
     */
    public function getStatusSSO() {
        return $this->SSOApiConnectionStatus;
    }

    public function isSSOapiConnectionOK($optimisticMode = true) {
        if ($optimisticMode) {
            OpenM_Log::debug("optimistic mode ON", __CLASS__, __METHOD__, __LINE__);
            $return = $this->OpenIdConnectionStatus == self::STATUS_OpenID_CONNECTED && !($this->isSSIDrequested && $this->SSID == null);
            if (!$return) {
                OpenM_Log::debug("Global status not OK", __CLASS__, __METHOD__, __LINE__);
                return $return;
            }
            OpenM_Log::debug("Global status OK", __CLASS__, __METHOD__, __LINE__);
            if ($this->SSID_session_end != null && ($this->SSID_session_end instanceof Date)) {
                OpenM_Log::debug("SSO API session initializated", __CLASS__, __METHOD__, __LINE__);
                $now = new Date();
                $return = $this->SSID_session_end->compareTo($now) > 0;
                if (!$return) {
                    OpenM_Log::debug("SSO API connection Expired (" . $this->SSID_session_end->toString() . ")", __CLASS__, __METHOD__, __LINE__);
                    $this->SSOApiConnectionStatus = self::STATUS_SSO_API_EXPIRED;
                }
                else
                    OpenM_Log::debug("SSO API connection OK", __CLASS__, __METHOD__, __LINE__);
                return $return;
            }
        }

        if ($this->SSID == null)
            return false;

        $return = $this->SSOClient->isSessionOK($this->SSID);
        $isOk = $return->get(OpenM_SSO::RETURN_STATUS_PARAMETER) == OpenM_SSO::RETURN_STATUS_OK_VALUE;
        if (!$isOk) {
            $this->SSID = null;
            $this->isSSIDrequested = false;
            $this->SSOApiConnectionStatus = self::STATUS_SSO_API_ERROR;
        }
        return $isOk;
    }

    public function login($properties = null, $force = false) {
        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_CONNECTED && $this->isLoginInProcess) {
            $this->isLoginInProcess = false;
            OpenM_Log::debug("end of login" . ($force ? " (forced)" : ""), __CLASS__, __METHOD__, __LINE__);
            return $this->getProperties();
        }
        OpenM_Log::debug("login" . ($force ? " (forced)" : ""), __CLASS__, __METHOD__, __LINE__);
        if (($force || $this->OpenIdConnectionStatus == self::STATUS_OpenID_NOT_CONNECTED) && !$this->isLoginInProcess) {
            $this->init();
            $this->isLoginInProcess = true;
        }
        return $this->checkAuth($properties, true);
    }

    public function checkAuth($properties = null, $redirectToLoginIfNotConnected = false, $optimisticMode = true, $isSSOapiActivated = true) {
        OpenM_Log::debug("checkAuth begin", __CLASS__, __METHOD__, __LINE__);
        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR && $this->connectedAtLeastOneTimeBefore)
            $this->init();
        else if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR ||
                $this->OpenIdConnectionStatus == self::STATUS_OpenID_NOT_CONNECTED)
            return null;

        if (!$this->isInitialized())
            $this->init();

        if ($this->isConnected($optimisticMode))
            return $this->getProperties();
        else {
            OpenM_Log::debug("Not Connected", __CLASS__, __METHOD__, __LINE__);
            if ($this->SSOApiConnectionStatus == self::STATUS_SSO_API_EXPIRED) {
                OpenM_Log::debug("SSO session EXPIRED", __CLASS__, __METHOD__, __LINE__);
                $OpenIdClient = $this->OpenIDClient;
                $this->init();
                $this->OpenIDClient = $OpenIdClient;
            }
        }

        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_START) {
            OpenM_Log::debug("load OID", __CLASS__, __METHOD__, __LINE__);
            $this->OID = $this->OpenIDClient->get($redirectToLoginIfNotConnected, $this->embeded);
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_GET;
        }

        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_GET && $this->OID == OpenM_SSO::RETURN_ERROR_MESSAGE_NOT_CONNECTED_VALUE) {
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_NOT_CONNECTED;
            OpenM_Log::debug("Not connected", __CLASS__, __METHOD__, __LINE__);
            OpenM_Header::redirect($this->uri);
        } else if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_GET && !$this->OpenIDClient->isValid($this->OID)) {
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_ERROR;
            OpenM_Log::warning("OID not valid (" . $this->OID . ")", __CLASS__, __METHOD__, __LINE__);
            OpenM_Header::redirect($this->uri);
        } else if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_GET && $this->OpenIDClient->isValid($this->OID))
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_CHECK_AUTH_BEGIN;

        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_CHECK_AUTH_BEGIN) {
            $this->begin($properties, $redirectToLoginIfNotConnected);
            return null;
        } else if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_CHECK_AUTH_COMPLETE) {
            $this->complete($isSSOapiActivated);
            if ($this->isConnected($optimisticMode)) {
                $this->OpenIdConnectionStatus = self::STATUS_OpenID_CONNECTED;
                return $this->getProperties();
            } else {
                $this->OpenIdConnectionStatus = self::STATUS_OpenID_ERROR;
                OpenM_Log::error("Not connected after complete OpenM-ID connection process", __CLASS__, __METHOD__, __LINE__);
                return null;
            }
        } else {
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_ERROR;
            OpenM_Log::error("No standard calling", __CLASS__, __METHOD__, __LINE__);
            if (OpenM_URL::getURL() != $this->uri)
                OpenM_Header::redirect($this->uri);
            else
                return null;
        }
    }

    /**
     * entame une connexion
     * @param ArrayList $properties
     * @param boolean $redirectToLoginIfNotConnected
     * @return HashtableString
     * @throws InvalidArgumentException
     */
    private function begin($properties = null, $redirectToLoginIfNotConnected = true) {
        OpenM_Log::debug("begin transaction with OpenID provider", __CLASS__, __METHOD__, __LINE__);
        if (!$this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR || $this->isConnected())
            return;

        if (!ArrayList::isArrayOrNull($properties))
            throw new InvalidArgumentException("properties must be an array");
        if (!is_bool($redirectToLoginIfNotConnected))
            throw new InvalidArgumentException("redirectToLoginIfNotConnected must be a boolean");

        if ($this->isSSOapiActivated()) {
            if ($properties == null)
                $properties = new ArrayList();
            if (!($properties instanceof ArrayList))
                $properties = ArrayList::from($properties);
            if (!$properties->contains(OpenM_ID::TOKEN_PARAMETER))
                $properties->add(OpenM_ID::TOKEN_PARAMETER);
        }

        $this->properties = null;
        //On commence la connexion
        OpenM_Log::debug("use OpenIDClient " . $this->uri . " on " . $this->realm, __CLASS__, __METHOD__, __LINE__);
        $redirectURL = $this->OpenIDClient->begin($this->uri, $this->realm, $properties);

        if ($redirectURL != null) {
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_CHECK_AUTH_COMPLETE;
            OpenM_Log::debug("Transaction with OpenID provider initialisation succeed", __CLASS__, __METHOD__, __LINE__);
            OpenM_Header::redirect($redirectURL);
        } else {
            OpenM_Log::debug("Transaction with OpenID provider initialisation failed", __CLASS__, __METHOD__, __LINE__);
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_ERROR;
        }
    }

    /**
     * Finit une connexion
     * @return void
     */
    private function complete($isSSOapiActivated = true) {
        OpenM_Log::debug("complete transaction with OpenID provider", __CLASS__, __METHOD__, __LINE__);
        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR || $this->isConnected())
            return;

        if (!$this->OpenIDClient->complete()) {
            OpenM_Log::debug("Transaction with OpenID provider completion failed", __CLASS__, __METHOD__, __LINE__);
            $this->OpenIdConnectionStatus = self::STATUS_OpenID_ERROR;
            return;
        }

        if ($isSSOapiActivated && $this->isSSOapiActivated()) {
            OpenM_Log::debug("SSO activated => openSession with SSO provider", __CLASS__, __METHOD__, __LINE__);

            /**
             * to extract token (see getProperties())
             */
            $this->getProperties();

            $this->SSID_session_start = new Date();
            $session = $this->SSOClient->openSession($this->OID, $this->token);

            if ($session->containskey(OpenM_Service::RETURN_ERROR_PARAMETER)) {
                OpenM_Log::debug("SSO session initialisation failed", __CLASS__, __METHOD__, __LINE__);
                $this->SSOApiConnectionStatus = self::STATUS_SSO_API_ERROR;
            } else {
                OpenM_Log::debug("SSO session initialisation succeed", __CLASS__, __METHOD__, __LINE__);
                $this->SSID = $session->get(OpenM_SSO::RETURN_SSID_PARAMETER);
                $this->SSID_session_end = $this->SSID_session_start->plus(new Delay($session->get(OpenM_SSO::RETURN_SSID_TIMER_PARAMETER)));
                OpenM_Log::debug("SSO session end : " . $this->SSID_session_end->toString(), __CLASS__, __METHOD__, __LINE__);
                $this->isSSIDrequested = true;
                $this->SSOApiConnectionStatus = self::STATUS_SSO_API_CONNECTED;
            }
        }

        OpenM_Log::debug("OpenM-ID successfuly connected", __CLASS__, __METHOD__, __LINE__);
        $this->OpenIdConnectionStatus = self::STATUS_OpenID_CONNECTED;

        if ($this->uri != null && $this->uri != "") {
            OpenM_Header::redirect($this->uri);
        }
    }

    public function logout($redirectToLogin = false) {
        OpenM_Log::debug("SSO close session (logout)", __CLASS__, __METHOD__, __LINE__);
        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR)
            return;
        if (!$this->isLogoutInProgress) {
            OpenM_Log::debug("SSO logout start", __CLASS__, __METHOD__, __LINE__);
            $this->isLogoutInProgress = true;
        } else {
            OpenM_Log::debug("SSO logout stop", __CLASS__, __METHOD__, __LINE__);
            $this->isLogoutInProgress = false;
            return;
        }
        if ($this->isSSOapiConnectionOK() && $this->SSOClient != null)
            $this->SSOClient->closeSession($this->SSID);

        $OpenIDClient = $this->OpenIDClient;
        $this->reset();
        $this->isLogoutInProgress = true;
        OpenM_Log::debug("OpenM_ID logout", __CLASS__, __METHOD__, __LINE__);
        if ($OpenIDClient != null)
            $OpenIDClient->logout($redirectToLogin);
    }

    private function reset() {
        OpenM_Log::debug("reset", __CLASS__, __METHOD__, __LINE__);
        $openID_api_path = $this->openM_ID_api_path;
        $store_path = $this->store_path;
        $sso_api_path = $this->sso_api_path;
        $realm = $this->realm;
        $version = $this->version;
        $connectedAtLeastOneTimeBefore = $this->connectedAtLeastOneTimeBefore;
        $embeded = $this->embeded;
        $array = get_object_vars($this);
        foreach ($array as $attrName => $value)
            $this->$attrName = null;
        $this->openM_ID_api_path = $openID_api_path;
        $this->store_path = $store_path;
        $this->sso_api_path = $sso_api_path;
        $this->realm = $realm;
        $this->version = $version;
        $this->connectedAtLeastOneTimeBefore = $connectedAtLeastOneTimeBefore;
        $this->embeded = $embeded;
    }

    public function getProperties() {
        if ($this->properties == null) {
            $return = $this->OpenIDClient->getPropeties();
            $this->token = $return->get(OpenM_ID::TOKEN_PARAMETER);
            $this->properties = $return->remove(OpenM_ID::TOKEN_PARAMETER);
        }
        return $this->properties;
    }

    public function getID() {
        return $this->OpenIDClient->getID() . "";
    }

    public function getOID() {
        return $this->OID . "";
    }

    public function getToken() {
        return $this->token . "";
    }

    public function isConnected($optimisticMode = true) {
        OpenM_Log::debug("SSO session connected ?", __CLASS__, __METHOD__, __LINE__);
        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_ERROR ||
                $this->OpenIdConnectionStatus == self::STATUS_OpenID_NOT_CONNECTED)
            return false;

        if (!$this->OpenIdConnectionStatus == self::STATUS_OpenID_CONNECTED)
            return false;

        if ($this->isSSOapiActivated())
            return $this->isSSOapiConnectionOK($optimisticMode);

        if ($this->OpenIdConnectionStatus == self::STATUS_OpenID_CONNECTED && !$this->isLoginInProcess) {
            OpenM_Log::debug("response => yes", __CLASS__, __METHOD__, __LINE__);
            return true;
        }
        else
            return false;
    }

    public function getSSID() {
        return $this->SSID . "";
    }

    /**
     * use to check if version of object is same as declare in const
     * @return String contains version of object at initialisation
     * @see OpenM_SSOClientSession constants
     */
    public function getVersion() {
        return $this->version . "";
    }

    public function getAPIpath() {
        return $this->sso_api_path . "";
    }

    public function setEmbeded() {
        $this->embeded = true;
    }

}

?>