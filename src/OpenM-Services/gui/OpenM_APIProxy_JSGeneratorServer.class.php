<?php

Import::php("util.OpenM_Log");

if (!Import::php("Smarty"))
    throw new ImportException("Smarty");

/**
 * This server manage Javascrip API client file generation for an api list in parameter
 * @package OpenM\OpenM-Services
 * @subpackage OpenM\OpenM-Services\gui
 * @copyright (c) 2013, www.open-miage.org
 * @license http://www.apache.org/licenses/LICENSE-2.0 Licensed under the Apache 
 * License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * @link http://www.open-miage.org
 * @author Gael Saunier
 */
class OpenM_APIProxy_JSGeneratorServer {

    const FILE_URL_PARAMETER = "api_gen";
    const MIN_MODE_PARAMETER = "min";
    const FILE_URL_SEPARATOR_PARAMETER = ";";

    private $root_path;
    private $smarty;

    private static function min($string) {
        $string = str_replace("= ", "=", $string);
        $string = str_replace(" =", "=", $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = str_replace("  ", " ", $string);
        $string = str_replace("\r\n", "", $string);
        $string = str_replace("\n", "", $string);
        $string = str_replace(' (', "(", $string);
        $string = str_replace('( ', "(", $string);
        $string = str_replace(' )', ")", $string);
        $string = str_replace(') ', ")", $string);
        $string = str_replace(': ', ":", $string);
        $string = str_replace(' :', ":", $string);
        $string = str_replace('{ ', "{", $string);
        $string = str_replace(' {', "{", $string);
        $string = str_replace(' }', "}", $string);
        $string = str_replace('} ', "}", $string);
        $string = str_replace(' ;', ";", $string);
        $string = str_replace('; ', ";", $string);
        $string = str_replace(' +', "+", $string);
        $string = str_replace('+ ', "+", $string);
        $string = str_replace(' -', "-", $string);
        $string = str_replace('- ', "-", $string);
        $string = str_replace(' ,', ",", $string);
        $string = str_replace(', ', ",", $string);
        $string = str_replace(' /', "/", $string);
        $string = str_replace('/ ', "/", $string);
        return $string;
    }

    /**
     * used to display javascript API client content generated from an api list
     * @param String $apis is the list of api need to generate clients
     * @param boolean $min true if minified version required, else false
     * @throws ImportException if api definition file required not found
     */
    public function display($apis, $min = true) {
        $files = explode(self::FILE_URL_SEPARATOR_PARAMETER, $apis);
        OpenM_Log::debug("define header JS", __CLASS__, __METHOD__, __LINE__);
        header('Content-type: text/javascript');
        $this->smarty->assign("min", $min);

        OpenM_Log::debug("recover sso proxy js file path", __CLASS__, __METHOD__, __LINE__);
        $sso_proxy = Import::getAbsolutePath("OpenM-SSO/gui/js/OpenM_SSOConnectionProxy.js");
        OpenM_Log::debug("recover api proxy js file path", __CLASS__, __METHOD__, __LINE__);
        $api_proxy = Import::getAbsolutePath("OpenM-Services/gui/js/OpenM_APIProxy_AJAXController.js");
        OpenM_Log::debug("recover api proxy tpl file path", __CLASS__, __METHOD__, __LINE__);
        $tpl = Import::getAbsolutePath("OpenM-Services/gui/tpl/OpenM_APIProxy_Controller.tpl");
        OpenM_Log::debug("define api proxy js controller cache id", __CLASS__, __METHOD__, __LINE__);
        $id = "OpenM-SSO/gui/OpenM_SSOConnectionProxy.js_OpenM-Services/gui/js/OpenM_APIProxy_AJAXController.js"
                . "_" . filectime($sso_proxy) . "_"
                . filectime($api_proxy) . "_"
                . ($min ? "min" : "");
        OpenM_Log::debug("check if cache already build", __CLASS__, __METHOD__, __LINE__);
        if ($this->smarty->isCached($tpl, $id))
            $this->smarty->display($tpl, $id);
        else {
            OpenM_Log::debug("build cache", __CLASS__, __METHOD__, __LINE__);
            $string = file_get_contents($sso_proxy);
            if ($min)
                $string = self::min($string);
            $this->smarty->assign("OpenM_SSOConnectionProxy", $string);
            $string = file_get_contents($api_proxy);
            if ($min)
                $string = self::min($string);
            $this->smarty->assign("OpenM_APIProxy_AJAXController", $string);
            OpenM_Log::debug("assign id", __CLASS__, __METHOD__, __LINE__);
            $this->smarty->cache_id = $id;
            $this->smarty->display($tpl);
        }

        $display = __DIR__ . "/tpl/OpenM_APIProxy_JSGeneratorServer.tpl";

        foreach ($files as $api) {
            OpenM_Log::debug("check if api required is exist", __CLASS__, __METHOD__, __LINE__);
            if (!is_file("$api.interface.php"))
                die("Forbidden display");

            OpenM_Log::debug("import api", __CLASS__, __METHOD__, __LINE__);
            if (!Import::php("$api"))
                throw new ImportException("$api");

            $reflexion = new ReflectionClass("$api");
            $file = $reflexion->getFileName();

            $id = $file . filectime($file) . "_" . ($min ? "min" : "");
            OpenM_Log::debug("check if cache is exist for this api", __CLASS__, __METHOD__, __LINE__);
            if ($this->smarty->isCached($display, $id))
                $this->smarty->display($display, $id);
            else {
                OpenM_Log::debug("build cache", __CLASS__, __METHOD__, __LINE__);
                $this->smarty->cache_id = $id;
                $constants = $reflexion->getConstants();
                $arrayConstants = array();
                foreach ($constants as $name => $value) {
                    $arrayConstant = array();
                    $arrayConstant["name"] = $name;
                    $arrayConstant["value"] = $value;
                    $arrayConstants[] = $arrayConstant;
                }

                $this->smarty->assign("constants", $arrayConstants);
                $methods = get_class_methods("$api");
                $arrayMethods = array();

                foreach ($methods as $method) {

                    $arrayMethod = array();
                    $arrayMethod["name"] = $method;

                    $r = new ReflectionMethod($api, $method);
                    $r->getParameters();
                    $i = 1;
                    $args = $r->getParameters();

                    $arrayParameters = array();

                    foreach ($args as $param) {
                        $arrayParameter = array();
                        $arrayParameter["name"] = $param->getName();
                        $arrayParameter["isOptional"] = $param->isOptional();
                        if ($param->isOptional())
                            $arrayParameter["defaultValue"] = $param->getDefaultValue();
                        $arrayParameter["parameterName"] = "arg$i";
                        $arrayParameters["arg$i"] = $arrayParameter;
                        $i++;
                    }

                    $arrayMethod["args"] = $arrayParameters;
                    $arrayMethods[] = $arrayMethod;
                }

                $this->smarty->assign("methods", $arrayMethods);
                $this->smarty->assign("api", "$api");
                OpenM_Log::debug("display api", __CLASS__, __METHOD__, __LINE__);
                $this->smarty->display($display);
            }
        }
    }

    /**
     * used to instanciate server from the path from host to server directory,
     * absolute/relative dir path of smarty compilation dir and absolute/relative dir path of
     * smarty cache dir
     * @param String $root_path is relative path from host to server directory in URL
     * @param String $compile_dir is absolute/relative directory path of smarty compile dir
     * @param String $cache_dir is absolute/relative directory path of smarty cache dir
     */
    public function __construct($root_path = null, $compile_dir = null, $cache_dir = NULL) {
        $this->root_path = $root_path;
        $this->smarty = new Smarty();
        if ($cache_dir !== null)
            $this->smarty->setCacheDir($cache_dir);
        if ($compile_dir !== null)
            $this->smarty->setCompileDir($compile_dir);
        $this->smarty->assign("api_url", $this->root_path);
        $this->smarty->caching = true;
        $this->smarty->compile_check = false;
    }

    /**
     * server handler URL controller
     * this method is required to catch URL
     */
    public function handle() {
        if (isset($_GET[self::FILE_URL_PARAMETER])) {
            try {
                $this->display($_GET[self::FILE_URL_PARAMETER], isset($_GET[self::MIN_MODE_PARAMETER]));
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
        else
            die("api not found");
    }

}

?>