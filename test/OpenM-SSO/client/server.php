<?php

require_once 'src.php';
require_once 'lib.php';

OpenM_Log::init(".", OpenM_Log::DEBUG, "log", 2000);

Import::php("OpenM-SSO.client.OpenM_SSOClientConnectionManagerServer");
$server = new OpenM_SSOClientConnectionManagerServer("config.properties");
$server->handle();
?>