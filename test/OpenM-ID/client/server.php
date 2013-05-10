<?php

require_once 'src.php';
require_once 'lib.php';

Import::php("OpenM-ID.client.OpenM_IDLoginClientServer");
$server = new OpenM_IDLoginClientServer("config.properties");
$server->handle();
?>