<?php

Import::php("util.Properties");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * Server to manage OpenM-SSO connections from Javascript HMI
 * it's drive from OpenM_SSOConnectionProxy.js
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
class OpenM_SSOClientConnectionManagerServer {

    private $config_file_path;
    private $embeded;

    const LOGIN_IN_PROGRESS = "OpenM_IDLoginClientServer_login_in_progress";
    const SESSION_MODE = "OpenM_IDLoginClientServer_session_mode";
    const MODE_PARAMETER = "API_SESSION_MODE";
    const API_SELECTION_PARAMETER = "API";
    const MODE_API_SELECTION = "API_SELECTION";
    const MODE_ALL_API = "ALL_API";
    const MODE_WITHOUT_API = "WITHOUT_API";
    const SESSION_API_SELECTED = "OpenM_IDLoginClientServer_api_selected";
    const ACTION_PARAMETER = "ACTION";
    const IS_CONNECTED_ACTION = "isConnected";
    const RETURN_IS_CONNECTED_PARAMETER = self::IS_CONNECTED_ACTION;
    const LOGIN_ACTION = "login";
    const RETURN_TO_PARAMETER = "proxy_return_to";
    const DASH = "! !";

    /**
     * config file path is required to instanciate connection server
     * @param String $config_file_path if file path to property file that
     * parameterized the SSO connection
     * @param boolean $embeded true by default to manage embeded display for iframe
     */
    public function __construct($config_file_path, $embeded = true) {
        OpenM_Log::debug("server initialisation", __CLASS__, __METHOD__, __LINE__);
        $this->config_file_path = $config_file_path;
        $this->embeded = $embeded;
    }

    /**
     * OpenM-ID / SSO server proxy to manage SSO session from Javascript HMI
     */
    public function handle() {

        if (OpenM_SessionController::get(self::LOGIN_IN_PROGRESS) === true)
            return $this->loginInProgress();

        $manager = OpenM_SSOClientPoolSessionManager::fromFile($this->config_file_path);
        $mode = OpenM_SessionController::get(self::SESSION_MODE);
        OpenM_Log::debug("mode[session]: $mode", __CLASS__, __METHOD__, __LINE__);
        $api = OpenM_SessionController::get(self::SESSION_API_SELECTED);
        OpenM_Log::debug("api[session]: $api", __CLASS__, __METHOD__, __LINE__);

        if ($mode === null) {
            if (isset($_GET[self::MODE_PARAMETER])) {
                switch ($_GET[self::MODE_PARAMETER]) {
                    case self::MODE_API_SELECTION:
                        $mode = self::MODE_API_SELECTION;
                        if (isset($_GET[self::API_SELECTION_PARAMETER])) {
                            $api = $_GET[self::API_SELECTION_PARAMETER];
                            OpenM_SessionController::set(self::SESSION_API_SELECTED, $api);
                        } else {
                            die("no api selected");
                        }
                        break;
                    case self::MODE_ALL_API:
                        $mode = self::MODE_ALL_API;
                        break;
                    case self::MODE_WITHOUT_API:
                        $mode = self::MODE_WITHOUT_API;
                        break;
                    default:
                        break;
                }
            }
        }

        if ($mode !== null)
            OpenM_SessionController::set(self::SESSION_MODE, $mode);

        OpenM_Log::debug("api:$api, mode:$mode", __CLASS__, __METHOD__, __LINE__);

        $action = null;
        if (isset($_POST[self::ACTION_PARAMETER]))
            $action = $_POST[self::ACTION_PARAMETER];
        else if (isset($_GET[self::ACTION_PARAMETER]))
            $action = $_GET[self::ACTION_PARAMETER];
        OpenM_Log::debug("action:$action", __CLASS__, __METHOD__, __LINE__);

        switch ($mode) {
            case self::MODE_API_SELECTION:
                $sso = $manager->get(OpenM_SessionController::get(self::SESSION_API_SELECTED), false);
                break;
            case self::MODE_ALL_API:
                die("not implemented for now");
                break;
            case self::MODE_WITHOUT_API:
                $sso = $manager->get();
                break;
            default:
                die("no mode selected");
                break;
        }

        if ($this->embeded) {
            $sso->setEmbeded();
            OpenM_Log::debug("embeded case", __CLASS__, __METHOD__, __LINE__);
        }

        switch ($action) {
            case self::IS_CONNECTED_ACTION:
                OpenM_Log::debug("isConnected", __CLASS__, __METHOD__, __LINE__);
                $this->isConnected($sso);
                break;
            case self::LOGIN_ACTION:
                OpenM_Log::debug("login", __CLASS__, __METHOD__, __LINE__);
                if ($sso->isConnected())
                    $this->return_to();
                else {
                    OpenM_SessionController::set(self::LOGIN_IN_PROGRESS, true);
                    return $this->loginInProgress();
                }
                break;
            default:
                OpenM_Log::debug("action:login(null, true)", __CLASS__, __METHOD__, __LINE__);
                $sso->login(null, true);
                $this->isConnectedDisplay($sso);
                break;
        }
    }

    private function loginInProgress() {
        if (OpenM_SessionController::get(self::LOGIN_IN_PROGRESS) !== true)
            die("LOGIN not in progress");
        $manager = OpenM_SSOClientPoolSessionManager::fromFile($this->config_file_path);
        $sso = $manager->get(OpenM_SessionController::get(self::SESSION_API_SELECTED), false);
        $sso->login(null, true);
        OpenM_SessionController::set(self::LOGIN_IN_PROGRESS, false);
        $this->return_to();
    }

    private function return_to() {
        if (!isset($_GET[self::RETURN_TO_PARAMETER]))
            die("return_to not found");
        $return_to = $_GET[self::RETURN_TO_PARAMETER];
        OpenM_Header::redirect(str_replace(self::DASH, "#", OpenM_URL::decode($return_to)));
    }

    private function isConnected($sso) {
        die("{\"" . self::RETURN_IS_CONNECTED_PARAMETER . "\":" . ($sso->isConnected() ? 1 : 0) . "}");
    }

    private function isConnectedDisplay($sso) {
        OpenM_Log::debug("check if connected", __CLASS__, __METHOD__, __LINE__);

        echo "<html><body>";
        if ($sso->isConnected())
            echo "you're connected";
        else
            echo "you're not connected";
        echo '<script type="text/javascript">window.close();</script>';
        echo "</body></html>";
    }

}
?>