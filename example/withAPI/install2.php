<?php

require_once 'src.php';

define('OpenM_SSOClientInstaller_CONFIG', "example.config.properties");
Import::php("OpenM-SSO.client.OpenM_SSOClientInstaller");
OpenM_SSOClientInstaller::step2();
?>