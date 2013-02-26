<?php

/**
 * Used by OpenM_RestControllerClient to check if OpenM_SSO client is ready.
 * If ready, use SSID associated.
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
interface OpenM_SSOSession {

    /**
     * used to recover SSID of opened session with an OpenM_SSO provider
     * @return string SSID of SSO session if exist, else emtpy 
     */
    public function getSSID();
        
    /**
     * used to know if connection with OpenM_SSO provider is OK and permit to 
     * call api protected by it.
     * @param boolean $optimisticMode true if trust impl object, false if you
     * whant to ask API for real session status.
     * @return boolean true if sso api connection OK, else false.
     */
    public function isSSOapiConnectionOK($optimisticMode = true);
        
    /**
     * used by OpenM_ServiceSSOClientImpl to call api from it's URL/path.
     * @return String api path
     */
    public function getAPIpath();
        
    /**
     * Provide a unique user id if localy needed
     * @return String unique key that identify user
     */
    public function getID();

}
?>