<?php

if (is_dir(dirname(__DIR__) . "/src"))
    Import::addClassPath(dirname(__DIR__) . "/src");
else
    Import::addLibPath("OpenM-SSO/client/1.0.3");
Import::addLibPath("openid/2.0.2x");
Import::addLibPath("OpenM-Commons/api/1.0.0");
Import::addLibPath("OpenM-SSO/api/1.0.0");
Import::addLibPath("OpenM-ID/api/1.0.0");
?>