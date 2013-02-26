<?php

/**
 * OpenM_SSOClientSession is an OpenM_SSO client that manage session with OpenM_ID
 * and an OpenM_SSO provider. That required to correctly manage this object in session.
 * @see OpenM_SSOClientSessionManager for session management
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
interface OpenM_SSOClientSession {

    const STATUS_OpenID_ERROR = -1;
    const STATUS_OpenID_START = 0;
    const STATUS_OpenID_GET = 1;
    const STATUS_OpenID_CHECK_AUTH_BEGIN = 2;
    const STATUS_OpenID_CHECK_AUTH_COMPLETE = 3;
    const STATUS_OpenID_CONNECTED = 4;
    const STATUS_OpenID_NOT_CONNECTED = 5;
    const STATUS_SSO_API_ERROR = -1;
    const STATUS_SSO_API_NOT_ACTIVATED = 0;
    const STATUS_SSO_API_START = 1;
    const STATUS_SSO_API_CONNECTED = 2;
    const STATUS_SSO_API_EXPIRED = 3;
    const VERSION = "1.0.1";

    /**
     * Login by using OpenM_ID
     * RQ: that use checkAuth with same properties and 
     * redirectToLoginIfNotConnected with true value.
     * RQ2: in case of preview ERROR Status ending,
     * that reinitialize the connection.
     * @see self::checkAuth
     * @param ArrayList $properties contains all properties ask to OpenM_ID by
     * using OpenID protocol
     * @param boolean $force is forcing reconnection in case of true, else connect
     * only if conection is not OK
     * @return null|HashtableString contains properties asked
     * and values assciated
     */
    public function login($properties = null, $force = false);

    /**
     * CheckAuth by using OpenM_ID. it's possible to just check
     * your connection status on OpenM_ID
     * @param ArrayList $properties list the personnal parameters you want 
     * to keep from OpenM_ID (Only 'mail' for now),
     * @param boolean $redirectToLoginIfNotConnected if true, redirect user to login page.
     * @param boolean $optimisticMode see isConnected
     * @param boolean $isSSOapiActivated true by default,
     * else not initialize SSO api connection
     * @return HashtableString properties asked with their values 
     * (if OpenM_ID push them, depend on User access Right)
     */
    public function checkAuth($properties = null, $redirectToLoginIfNotConnected = false, $optimisticMode = true, $isSSOapiActivated = true);

    /**
     * Close opened OpenM_ID session connection and API SSO session.
     * @param boolean $redirectToLogin if true, redirect user to login page after logout.
     */
    public function logout($redirectToLogin = true);

    /**
     * Permit to keep the user personal OpenM_ID properties ask at the opening
     * of the OpenM_ID session.
     * @return HashtableString properties asked with their values
     * (if OpenM_ID push them, depend on User access Right)
     */
    public function getProperties();

    /**
     * Return the connection status connnected or not.
     * @param boolean $optimisticMode if true, don't beleive SSO connection status
     * localy stored and ask connection status to SSO provider, else return true
     * if SSO connection status is localy stored as OK.
     * @return boolean return true if connected else false
     */
    public function isConnected($optimisticMode = true);

    /**
     * used to keep OID of user because it's automaticly recovered by:
     * OpenM_ID / OpenM-ID_GET API before initializing OpenM_ID connection
     * @return String OID of current session if connected else empty
     */
    public function getOID();

    /**
     * used to keep the token recovered by the OpenID protocol
     * @return String token of current initialized session if OpenM_ID
     * connection OK else empty
     */
    public function getToken();
}

?>