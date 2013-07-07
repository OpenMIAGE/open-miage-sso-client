<?php

require_once 'src.php';
require_once 'lib.php';

Import::php("OpenM-Services.gui.OpenM_APIProxy_JSGeneratorServer");
$server = new OpenM_APIProxy_JSGeneratorServer("gui/");
$server->handle();
?>