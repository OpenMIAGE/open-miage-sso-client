<?php

Import::php("OpenM-SSO.client.OpenM_SSOClientSessionImpl");
Import::php("util.session.OpenM_SessionController");
Import::php("util.Properties");

/**
 * Create a OpenM_SSOClientSession if no instance exist an manage it in php session.
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
class OpenM_SSOClientSessionManager {

    const DEFAULT_FROM_PROPERTY_FILE_PATH = "config.properties";
    const OpenM_ID_API_PATH = "OpenM_ID.api.path";
    const OpenM_ID_STORE_PATH = "OpenM_ID.store.path";
    const OpenM_ID_REALM = "OpenM_ID.realm";
    const OpenM_SSO_API_PREFIX = "OpenM_SSO.api";
    const OpenM_SSO_API_PATH_SUFFIX = ".path";

    private $varSessionName;

    /**
     * @param String $varSessionName is the name of OpenM_SSOClientSession
     * in session 
     */
    public function __construct($varSessionName = "OpenM_SSOClientSessionManager") {
        $this->varSessionName = $varSessionName;
    }

    /**
     * use to recover an OpenM_SSOClientSession implementation from php session
     * or from new instance
     * @see OpenM_SSOClientSessionImpl::__construct
     * @return OpenM_SSOClientSession
     */
    public function get($openM_ID_api_path, $store_path, $realm = null, $sso_api_path = null) {
        $return = $this->getFromSession();
        if ($return != null)
            return $return;

        $return = new OpenM_SSOClientSessionImpl($openM_ID_api_path, $store_path, $realm, $sso_api_path);
        OpenM_SessionController::set($this->varSessionName, $return);
        return $return;
    }

    /**
     * use to recover an OpenM_SSOClientSession implementation from php session
     * or from new instance
     * @see OpenM_SSOClientSessionImpl::__construct
     * @param String $propertyFilePath is path of existing property file used to
     * load required parameter of OpenM_SSOClientSessionImpl::__construct
     * @return OpenM_SSOClientSession
     */
    public function getFromFile($propertyFilePath = null) {
        $return = $this->getFromSession();
        if ($return != null)
            return $return;

        if ($propertyFilePath === null)
            $propertyFilePath = self::DEFAULT_FROM_PROPERTY_FILE_PATH;
        if (!String::isString($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a string");
        if (!is_file($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a file");

        $p = Properties::fromFile($propertyFilePath);
        if ($p->get(self::OpenM_ID_API_PATH) == null)
            throw new Exception(self::OpenM_ID_API_PATH . " not defined in $propertyFilePath");
        if ($p->get(self::OpenM_ID_STORE_PATH) == null)
            throw new Exception(self::OpenM_ID_STORE_PATH . " not defined in $propertyFilePath");

        $return = new OpenM_SSOClientSessionImpl(
                $p->get(self::OpenM_ID_API_PATH), dirname(realpath($propertyFilePath)) . "/" . $p->get(self::OpenM_ID_STORE_PATH), $p->get(self::OpenM_ID_REALM), $p->get(self::OpenM_SSO_API_PREFIX . self::OpenM_SSO_API_PATH_SUFFIX)
        );

        OpenM_SessionController::set($this->varSessionName, $return);
        return $return;
    }

    private function getFromSession() {
        if (OpenM_SessionController::get($this->varSessionName) != null) {
            $return = OpenM_SessionController::get($this->varSessionName);
            if ($return instanceof OpenM_SSOClientSessionImpl && $return->getVersion() == OpenM_SSOClientSessionImpl::VERSION)
                return $return;
        }
        else
            return null;
    }

    /**
     * remove the associated OpenM_SSOClientSession from session
     */
    public function close() {
        OpenM_Log::debug("remove session manager from session (" . $this->varSessionName . ")", __CLASS__, __METHOD__, __LINE__);
        OpenM_SessionController::remove($this->varSessionName);
    }

}

?>