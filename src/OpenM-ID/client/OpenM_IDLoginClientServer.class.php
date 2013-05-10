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

    const IS_CONNECTED_PARAMETER = "isConnected";

    public function __construct($config_file_path, $embede = true) {
        $this->config_file_path = $config_file_path;
        $this->embeded = $embede;
    }

    public function handle() {
        $manager = OpenM_SSOClientPoolSessionManager::fromFile($this->config_file_path);
        $sso = $manager->get();
        if (isset($_GET[self::IS_CONNECTED_PARAMETER]))
            die("{\"" . self::IS_CONNECTED_PARAMETER . "\":" . ($sso->isConnected() ? 1 : 0) . "}");

        if ($this->embeded)
            $sso->setEmbeded();
        $sso->login();
        if ($sso->isConnected())
            echo "you're connected";
        else
            echo "you're not connected";
    }

}

?>