<?php

require_once 'src.php';
require_once 'lib.php';

Import::php("OpenM-Services.gui.OpenM_ServiceClientJSGeneratorServer");
OpenM_ServiceClientJSGeneratorServer::handle();
?>