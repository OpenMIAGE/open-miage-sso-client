<?php

require_once 'src.php';
require_once 'lib.php';

Import::php("OpenM-SSO.client.OpenM_SSOClientConnectionManagerServer");
$server = new OpenM_SSOClientConnectionManagerServer("config.properties");
$server->handle();
?>