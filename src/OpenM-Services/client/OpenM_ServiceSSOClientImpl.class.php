<?php

Import::php("OpenM-Services.client.OpenM_ServiceClientImpl");
Import::php("OpenM-SSO.client.OpenM_SSOSession");

/**
 * OpenM_ServiceSSOClientImpl manage api calling protected by OpenM_SSO.
 * @package OpenM 
 * @subpackage OpenM\OpenM-Services\client 
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
class OpenM_ServiceSSOClientImpl extends OpenM_ServiceClientImpl {

    /**
     * wait SSO OpenM_SSOSession object instead of api path to call api
     * @uses OpenM_ServiceClientImpl::__construct
     * @uses OpenM_SSOSession::getAPIpath
     * @param OpenM_SSOSession $sso is used to put the SSID on api calling
     * @param String $api_name is the interface name of the API (ex OpenM_SSO).
     * @param boolean $throwsException is true to manage api exception, else, just
     * return HashtableString that could contain error messages.
     * @throws InvalidArgumentException if a parameter is not valid.
     */
    public function __construct(OpenM_SSOSession $sso, $api_name=null, $throwsException=true) {
        parent::__construct($sso->getAPIpath(),$api_name, $throwsException);
        $this->sso = $sso;
    }
}

?>
