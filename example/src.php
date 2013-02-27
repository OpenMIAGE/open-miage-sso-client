<?php
if(!defined("OpenM_Import")){
    if(is_file(dirname(dirname(dirname(dirname(__FILE__)))). '/src/Import.class.php'))
        require_once dirname(dirname(dirname(dirname(__FILE__)))). '/src/Import.class.php';
    else if(is_file("../OpenM-SSO.path"))
        require_once dirname(dirname(((__FILE__)))). '/lib/'.  file_get_contents("../OpenM-SSO.path").'/Import.class.php';
    else if(is_file("../../OpenM-SSO.path"))
        require_once dirname(dirname(((__FILE__)))). '/lib/'.  file_get_contents("../../OpenM-SSO.path").'/Import.class.php';
    else
        throw new Exception("OpenM-SSO.client not found");
    Import::addClassPath(dirname(__FILE__));
    define("OpenM_Import", true);
}

require_once 'lib.php';
?>