<?php

Import::php("util.pkg.OpenM_Dependencies");
$dependencies = new OpenM_Dependencies(dirname(dirname(dirname(__DIR__))) . "/lib");
$dependencies->addInClassPath(OpenM_Dependencies::TEST, true);
Import::addClassPath();
?>