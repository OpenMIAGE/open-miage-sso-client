<?php

Import::php("OpenM-SSO.client.OpenM_SSOClientSessionImpl");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * OpenM_SSOClientPoolSessionImpl is an OpenM_SSOClientSession interceptor.
 * It's used to catch logout call on one of sso connection, that launch automaticly
 * logout action on each sso connection.
 * This class is used by OpenM_SSOClientPoolSessionManager
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
class OpenM_SSOClientPoolSessionImpl implements OpenM_SSOClientSession, OpenM_SSOSession {
    
    private $sso;
    private $manager;
    
    /**
     * The constructor is only used by OpenM_SSOClientPoolSessionManager
     * to intercept logout calling on OpenM_SSOClientSession objects.
     * @param OpenM_SSOClientPoolSessionManager $manager is OpenM_SSOClientPoolSessionManager
     * that instanciate this object
     * @param OpenM_SSOClientSessionImpl $sso is the OpenM_SSOClientSession
     * that need to be intercept
     * @see OpenM_SSOClientPoolSessionManager is instanciate this class
     */
    public function __construct(OpenM_SSOClientPoolSessionManager $manager, OpenM_SSOClientSessionImpl $sso) {
        $this->sso = $sso;
        $this->manager = $manager;
    }
    
    public function checkAuth($properties = null, $redirectToLoginIfNotConnected = false, $optimisticMode = true, $isSSOapiActivated = true) {
        return $this->sso->checkAuth($properties, $redirectToLoginIfNotConnected, $optimisticMode, $isSSOapiActivated);
    }

    public function getID() {
        return $this->sso->getID();
    }

    public function getProperties() {
        return $this->sso->getProperties();
    }

    public function isConnected($optimisticMode = true) {
        return $this->sso->isConnected($optimisticMode);
    }

    public function login($properties = null, $force = false) {
        return $this->sso->login($properties, $force);
    }

    public function logout($redirectToLogin = true) {
        return $this->manager->logout($redirectToLogin);
    }

    public function getSSID() {
        return $this->sso->getSSID();
    }

    public function isSSOapiConnectionOK($optimisticMode = true) {
        return $this->sso->isSSOapiConnectionOK($optimisticMode);
    }

    public function getAPIpath() {
        return $this->sso->getAPIpath();
    }

    public function getOID() {
        return $this->sso->getOID();
    }

    public function getToken() {
        return $this->sso->getToken();
    }

    public function setEmbeded() {
        return $this->sso->setEmbeded();
    }

}

?>