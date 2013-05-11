<?php

Import::php("util.Properties");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * 
 * @author Gaël Saunier
 */
class OpenM_IDLoginClientServer {

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
    const RETURN_IS_CONNECTED_PARAMETER = self::IS_CONNECTED_ACTION;

    public function __construct($config_file_path, $embeded = true) {
        $this->config_file_path = $config_file_path;
        $this->embeded = $embeded;
    }

    public function handle() {
        $manager = OpenM_SSOClientPoolSessionManager::fromFile($this->config_file_path);
        $mode = OpenM_SessionController::get(self::SESSION_MODE);
        $api = OpenM_SessionController::get(self::SESSION_API_SELECTED);

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

        $action = null;
        if (isset($_POST[self::ACTION_PARAMETER]) && $_POST[self::ACTION_PARAMETER] == self::IS_CONNECTED_ACTION)
            $action = self::IS_CONNECTED_ACTION;

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

        if ($this->embeded)
            $sso->setEmbeded();

        switch ($action) {
            case self::IS_CONNECTED_ACTION:
                $this->isConnected($sso);
                break;
            default:
                $sso->login(null, true);
                $this->isConnectedDisplay($sso);
                break;
        }
    }

    public function isConnected($sso) {
        die("{\"" . self::RETURN_IS_CONNECTED_PARAMETER . "\":" . ($sso->isConnected() ? 1 : 0) . "}");
    }

    public function isConnectedDisplay($sso) {
        if ($sso->isConnected())
            echo "you're connected";
        else
            echo "you're not connected";
    }

}

?>