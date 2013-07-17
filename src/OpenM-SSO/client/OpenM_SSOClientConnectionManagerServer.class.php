<?php

Import::php("util.Properties");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * 
 * @author Gaël Saunier
 */
class OpenM_SSOClientConnectionManagerServer {

    private $config_file_path;
    private $embeded;

    const SESSION_MODE = "OpenM_IDLoginClientServer_session_mode";
    const MODE_PARAMETER = "API_SESSION_MODE";
    const API_SELECTION_PARAMETER = "API";
    const MODE_API_SELECTION = "API_SELECTION";
    const MODE_ALL_API = "ALL_API";
    const MODE_WITHOUT_API = "WITHOUT_API";
    const SESSION_API_SELECTED = "OpenM_IDLoginClientServer_api_selected";
    const ACTION_PARAMETER = "ACTION";
    const IS_CONNECTED_ACTION = "isConnected";
    const WAITING_CONNECTION = "waitingConnection";
    const RETURN_IS_CONNECTED_PARAMETER = self::IS_CONNECTED_ACTION;

    public function __construct($config_file_path, $embeded = true) {
        OpenM_Log::debug("server initialisation", __CLASS__, __METHOD__, __LINE__);
        $this->config_file_path = $config_file_path;
        $this->embeded = $embeded;
    }

    public function handle() {
        if (isset($_GET[self::WAITING_CONNECTION]))
            return $this->waitingConnection();

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
            if ($mode !== null)
                OpenM_SessionController::set(self::SESSION_MODE, $mode);
        }
        OpenM_Log::debug("api:$api, mode:$mode", __CLASS__, __METHOD__, __LINE__);

        $action = null;
        if (isset($_POST[self::ACTION_PARAMETER]) && $_POST[self::ACTION_PARAMETER] == self::IS_CONNECTED_ACTION)
            $action = self::IS_CONNECTED_ACTION;
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
            default:
                OpenM_Log::debug("action:login(null, true)", __CLASS__, __METHOD__, __LINE__);
                $sso->login(null, true);
                $this->isConnectedDisplay($sso);
                break;
        }
    }

    public function waitingConnection() {
        if (!isset($_GET[self::API_SELECTION_PARAMETER]))
            OpenM_Header::error(400, self::API_SELECTION_PARAMETER . " parameter missing");
//        for ($i = 0; $i < 5; $i++) {
            session_start();
//            $manager = OpenM_SSOClientPoolSessionManager::fromFile($this->config_file_path);
//            $sso = $manager->get($_GET[self::API_SELECTION_PARAMETER], false);
//            if ($sso->isConnected()) {
//                OpenM_Header::ok();
//                exit();
//            }
            session_write_close();
            sleep(10);
//            session_write_close }
        OpenM_Header::error(408, "timeOut");
    }

    public function isConnected($sso) {
        die("{\"" . self::RETURN_IS_CONNECTED_PARAMETER . "\":" . ($sso->isConnected() ? 1 : 0) . "}");
    }

    public function isConnectedDisplay($sso) {
        OpenM_Log::debug("check if connected", __CLASS__, __METHOD__, __LINE__);
        if ($sso->isConnected())
            echo "you're connected";
        else
            echo "you're not connected";
    }

}

?>