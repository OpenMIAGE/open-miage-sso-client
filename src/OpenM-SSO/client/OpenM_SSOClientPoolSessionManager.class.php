<?php

Import::php("OpenM-SSO.client.OpenM_SSOClientSessionManager");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionImpl");
Import::php("util.http.OpenM_URL");
Import::php("util.OpenM_Log");

/**
 * OpenM_SSOClientPoolSessionManager manage all api connection (remote and/or
 * local), that are protected by OpenM_SSO provider.
 * It use OpenM_SSOClientSessionManager for each api connection with SSO.
 * @see OpenM_SSOClientSessionManager
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
class OpenM_SSOClientPoolSessionManager {

    const OpenM_SSO_POOL_SESSION_NAME = "OpenM_SSO.pool.session.name";
    const OpenM_SSO_API_NUMBER_PATTERN = "/^\.[1-9][0-9]*$/";
    const OpenM_SSO_API_NAME_SUFFIX = ".name";

    private $OpenM_ID_URL;
    private $store_path;
    private $realm;
    private $standard_sso;
    private $name_to_path;
    private $path_to_ssoManager;
    private $session_identifier;
    private $configFileChangeTime;

    /**
     * Instanciate a manager from OpenM_ID provider, a dir path to store OpenID
     * transactions and the realm of authorization required.
     * @param String $OpenM_ID_Provider_URL is URL of OpenM_ID api 
     * (ex.: http://auth.open-miage.fr)
     * @param String $store_path is path of a directory need for OpenID JanRain 
     * library to store openId transactions.
     * @param String $realm is the URL space that you ask for authorization.
     * @see OpenM_SSO_OpenIDClient::__construct
     * @throws InvalidArgumentException
     */
    public function __construct($OpenM_ID_Provider_URL, $store_path, $realm = null) {
        if (!OpenM_URL::isValid($OpenM_ID_Provider_URL))
            throw new InvalidArgumentException("OpenM_ID_Provider_URL must be a valid URL");
        $this->OpenM_ID_URL = $OpenM_ID_Provider_URL;
        if (!String::isString($store_path))
            throw new InvalidArgumentException("store_path must be a string");
        if (!is_dir($store_path)) {
            if (!mkdir($store_path, 0777, true))
                throw new InvalidArgumentException("store_path doesn't exist and couldn't be created");
        }
        if (!is_writable($store_path))
            throw new InvalidArgumentException("store_path isn't writable");
        $this->store_path = realpath($store_path);

        $this->name_to_path = new HashtableString();
        $this->path_to_ssoManager = new HashtableString();

        $this->session_identifier = "OpenM_SSOClientPoolSessionManager_" . rand(0, 10000000000000000);

        if (!String::isStringOrNull($realm))
            throw new InvalidArgumentException("realm must be a string");
        if ($realm != null && !OpenM_URL::isValid($realm))
            throw new InvalidArgumentException("realm must be an URL");

        if ($realm == null)
            $realm = OpenM_URL::getHost();

        $this->realm = $realm;
    }

    /**
     * add new api URL with associated name to call it after by it's name.
     * @param String $api_name is a chosen name to named your api and call itself
     * when you need a SSO object to call this api.
     * @param String $api_path is the URL of API you need to call
     * @throws InvalidArgumentException
     */
    public function put($api_name, $api_path) {
        if (!String::isString($api_path))
            throw new InvalidArgumentException("api_url must be a string");
        if (!String::isStringOrNull($api_name))
            throw new InvalidArgumentException("api_name must be a string");
        if ($api_name == null)
            $api_name = $api_path;

        OpenM_Log::debug("$api_name => $api_path", __CLASS__, __METHOD__, __LINE__);
        $this->name_to_path->put($api_name, $api_path);
    }

    /**
     * used to keep an OpenM_SSO client object, generaly used to instanciate
     * a OpenM_ServiceSSOClientImpl and call an api that protected by a SSO.
     * @see OpenM_ServiceSSOClientImpl, OpenM_SSOClientSessionImpl::login
     * @param String $api_name is api name set on put($api_name, $api_path)
     * @param boolean $connected if true, login is called on OpenM_SSOClientSession
     * object before returned it.
     * @return OpenM_SSOClientSession is an OpenM_SSOClientSession implementation
     * associated to the api
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function get($api_name = null, $connected = true) {
        if ($api_name == null)
            return $this->getStandardSSO();

        OpenM_Log::debug("get('$api_name')", __CLASS__, __METHOD__, __LINE__);

        if (!String::isString($api_name))
            throw new InvalidArgumentException("api_name must be a string");
        if (!is_bool($connected))
            throw new InvalidArgumentException("connected must be a boolean");
        if (!$this->name_to_path->containsKey($api_name))
            return null;

        $path = $this->name_to_path->get($api_name);
        if ($path == null)
            throw new Exception("$api_name must be put with a path before call on");
        $manager = $this->path_to_ssoManager->get($path);
        if ($manager == null) {
            $manager = new OpenM_SSOClientSessionManager($this->session_identifier . $path);
            $this->path_to_ssoManager->put($path, $manager);
        }

        $sso = $manager->get($this->OpenM_ID_URL, $this->store_path, $this->realm, $path);
        OpenM_Log::debug("sso found", __CLASS__, __METHOD__, __LINE__);

        if ($connected)
            $sso->login();
        OpenM_Log::debug("sso status: " . $sso->getStatusOpenID() . " " . $sso->getStatusSSO(), __CLASS__, __METHOD__, __LINE__);

        OpenM_Log::debug("return poolSSO", __CLASS__, __METHOD__, __LINE__);
        return $this->getPoolSSO($sso);
    }

    private function getStandardSSO() {
        if ($this->standard_sso == null)
            $this->standard_sso = new OpenM_SSOClientSessionManager($this->session_identifier);

        OpenM_Log::debug("return poolSSO", __CLASS__, __METHOD__, __LINE__);
        return $this->getPoolSSO($this->standard_sso->get($this->OpenM_ID_URL, $this->store_path, $this->realm));
    }

    /**
     * used to call login on each sso on each api put in this pool manager
     */
    public function initAllApi() {
        $e = $this->path_to_ssoManager->keys();
        while ($e->hasNext()) {
            $key = $e->next();
            $manager = $this->path_to_ssoManager->get($key);
            if ($manager != null) {
                $sso = $manager->get($this->OpenM_ID_URL, $this->store_path, $this->realm, $key);
                if (!$sso->isConnected()) {
                    OpenM_Log::debug("init $key", __CLASS__, __METHOD__, __LINE__);
                    $sso->login(null, true);
                }
            }
        }
    }

    /**
     * used to call logout on each sso on each api put in this pool manager
     * @param String $redirectToLogin if true, redirect to login form after all
     * logout.
     */
    public function logout($redirectToLogin = false) {
        OpenM_Log::debug("pool logout start", __CLASS__, __METHOD__, __LINE__);
        $e = $this->path_to_ssoManager->keys();
        $sso = null;
        while ($e->hasNext()) {
            $key = $e->next();
            $manager = $this->path_to_ssoManager->get($key);
            if ($manager != null) {
                $sso = $manager->get($this->OpenM_ID_URL, $this->store_path, $this->realm, $key);
                if ($sso->isConnected()) {
                    OpenM_Log::debug("close session $key", __CLASS__, __METHOD__, __LINE__);
                    $sso->logout(false);
                }
            }
        }

        if ($this->standard_sso != null) {
            $sso2 = $this->standard_sso->get($this->OpenM_ID_URL, $this->store_path, $this->realm);
            if ($sso2->isConnected()) {
                OpenM_Log::debug("logout standard", __CLASS__, __METHOD__, __LINE__);
                $sso2->logout($redirectToLogin);
            }
        } else if ($sso !== null && $redirectToLogin) {
            OpenM_Log::debug("logout and redirect to login if necessary", __CLASS__, __METHOD__, __LINE__);
            $sso->logout($redirectToLogin);
        }
    }

    private function getPoolSSO(OpenM_SSOClientSession $sso) {
        return new OpenM_SSOClientPoolSessionImpl($this, $sso);
    }

    /**
     * used to save pool manager in php session with chosen name.
     * This name is used to save it in session. You can call it by using: 
     * $_SESSION['$name'] to keep the pool manager from session after start it.
     * @param String $name is the name of the manager var in session
     * @throws InvalidArgumentException
     */
    public function save($name) {
        if (!String::isString($name))
            throw new InvalidArgumentException("name must be a string");
        if ($name instanceof String)
            $name = "$name";
        OpenM_SessionController::set($name, $this);
    }

    /**
     * used to load pool manager from session
     * @see self::save
     * @param String $name is the name filled in save method
     * @return OpenM_SSOClientPoolSessionManager instance from session
     * @throws InvalidArgumentException
     */
    public static function load($name) {
        if (!String::isString($name))
            throw new InvalidArgumentException("name must be a string");
        if ($name instanceof String)
            $name = "$name";
        return OpenM_SessionController::get($name);
    }

    /**
     * used to load a pool manager from a configuration property file.
     * that load all properties required to instanciate the manager and check
     * if exist in session. If not, instanciate a new pool manager with loaded
     * properties
     * @see Properties::fromFile
     * @param String $propertyFilePath is the path of property file
     * @return OpenM_SSOClientPoolSessionManager is loaded manager from session
     * or new instance
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function fromFile($propertyFilePath = null) {
        if ($propertyFilePath === null)
            $propertyFilePath = OpenM_SSOClientSessionManager::DEFAULT_FROM_PROPERTY_FILE_PATH;
        if (!String::isString($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a string");
        if (!is_file($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a file");

        $p = Properties::fromFile($propertyFilePath);
        if ($p->get(OpenM_SSOClientSessionManager::OpenM_ID_API_PATH) == null)
            throw new Exception(OpenM_SSOClientSessionManager::OpenM_ID_API_PATH . " not defined in $propertyFilePath");
        if ($p->get(OpenM_SSOClientSessionManager::OpenM_ID_STORE_PATH) == null)
            throw new Exception(OpenM_SSOClientSessionManager::OpenM_ID_STORE_PATH . " not defined in $propertyFilePath");
        if ($p->get(self::OpenM_SSO_POOL_SESSION_NAME) == null)
            throw new Exception(self::OpenM_SSO_POOL_SESSION_NAME . " not defined in $propertyFilePath");

        OpenM_Log::debug("load manager from session", __CLASS__, __METHOD__, __LINE__);
        $manager = self::load($p->get(self::OpenM_SSO_POOL_SESSION_NAME));
        if ($manager instanceof OpenM_SSOClientPoolSessionManager) {
            if ($manager->configFileChangeTime == filemtime($propertyFilePath))
                return $manager;
        }

        OpenM_Log::debug("create new manager instance", __CLASS__, __METHOD__, __LINE__);
        $manager = new OpenM_SSOClientPoolSessionManager(
                $p->get(OpenM_SSOClientSessionManager::OpenM_ID_API_PATH), dirname($propertyFilePath)
                . "/" . $p->get(OpenM_SSOClientSessionManager::OpenM_ID_STORE_PATH), $p->get(OpenM_SSOClientSessionManager::OpenM_ID_REALM)
        );
        $manager->configFileChangeTime = filemtime($propertyFilePath);
        $size = $p->getAll()->size();
        for ($i = 0; $i < $size; $i++) {
            $num = ($i === 0) ? "" : ".$i";
            OpenM_Log::debug("load " . OpenM_SSOClientSessionManager::OpenM_SSO_API_PREFIX
                    . $num . self::OpenM_SSO_API_NAME_SUFFIX, __CLASS__, __METHOD__, __LINE__
            );
            $name = $p->get(OpenM_SSOClientSessionManager::OpenM_SSO_API_PREFIX
                    . $num . self::OpenM_SSO_API_NAME_SUFFIX
            );
            if ($name != null) {
                $path = $p->get(OpenM_SSOClientSessionManager::OpenM_SSO_API_PREFIX
                        . $num . OpenM_SSOClientSessionManager::OpenM_SSO_API_PATH_SUFFIX
                );
                OpenM_Log::debug("api found: $path", __CLASS__, __METHOD__, __LINE__);
                if ($path == null)
                    throw new Exception(OpenM_SSOClientSessionManager::OpenM_SSO_API_PREFIX
                    . $num . OpenM_SSOClientSessionManager::OpenM_SSO_API_PATH_SUFFIX
                    . " not defined in $propertyFilePath"
                    );
                $manager->put($name, $path);
            }
            else if ($i !== 0)
                break;
        }

        $manager->save($p->get(self::OpenM_SSO_POOL_SESSION_NAME));
        return $manager;
    }

    /**
     * used to close pool manager. That remove it from session and close each
     * OpenM_SSOClientSessionManager included.
     * @uses OpenM_SSOClientSessionManager::close
     * @param String $propertyFilePath is property file used to load pool
     * session name
     * @throws InvalidArgumentException
     */
    public function close($propertyFilePath = null) {
        if ($propertyFilePath === null)
            $propertyFilePath = OpenM_SSOClientSessionManager::DEFAULT_FROM_PROPERTY_FILE_PATH;
        if (!String::isString($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a string");
        if (!is_file($propertyFilePath))
            throw new InvalidArgumentException("propertyFilePath must be a file");

        $p = Properties::fromFile($propertyFilePath);
        OpenM_Log::debug("remove pool session manager from session (" . $p->get(self::OpenM_SSO_POOL_SESSION_NAME) . ")", __CLASS__, __METHOD__, __LINE__);
        OpenM_SessionController::remove($p->get(self::OpenM_SSO_POOL_SESSION_NAME));
        $e = $this->path_to_ssoManager->enum();
        while ($e->hasNext()) {
            $manager = $e->next();
            if ($manager instanceof OpenM_SSOClientSessionManager) {
                OpenM_Log::debug("close an sso manager", __CLASS__, __METHOD__, __LINE__);
                $manager->close();
            }
        }
        if ($this->standard_sso instanceof OpenM_SSOClientSessionManager) {
            OpenM_Log::debug("close standard manager", __CLASS__, __METHOD__, __LINE__);
            $this->standard_sso->close();
        }
    }

}

?>