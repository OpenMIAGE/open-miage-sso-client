<?php

Import::php("util.OpenM_Log");

if (!Import::php("Smarty"))
    throw new ImportException("Smarty");

/**
 * 
 * @package OpenM-Services
 * @subpackage gui
 * @license http://www.apache.org/licenses/LICENSE-2.0 Licensed under the Apache License, Version 2.0 (the "License");
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

    public static function display($apis, $min = true, $root_path = null) {
        $files = explode(self::FILE_URL_SEPARATOR_PARAMETER, $apis);
        header('Content-type: text/javascript');
        $smarty = new Smarty();
        $smarty->assign("min", $min);
        $smarty->display(__DIR__ . "/tpl/OpenM_APIProxy_AJAXController.tpl");
        foreach ($files as $api) {
            if (!is_file("$api.interface.php"))
                die("Forbidden display");

            if (!Import::php("$api"))
                throw new ImportException("$api");

            $smarty = new Smarty();

            $reflexion = new ReflectionClass("$api");

            $constants = $reflexion->getConstants();
            $arrayConstants = array();
            foreach ($constants as $name => $value) {
                $arrayConstant = array();
                $arrayConstant["name"] = $name;
                $arrayConstant["value"] = $value;
                $arrayConstants[] = $arrayConstant;
            }

            $smarty->assign("constants", $arrayConstants);

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

            $smarty->assign("methods", $arrayMethods);
            $smarty->assign("api", "$api");
            $smarty->assign("api_url", $root_path);
            $smarty->assign("min", $min);
            $smarty->display(__DIR__ . "/tpl/OpenM_APIProxy_JSGeneratorServer.tpl");
        }
    }

    public function __construct($root_path = null) {
        $this->root_path = $root_path;
    }

    public function handle() {
        if (isset($_GET[self::FILE_URL_PARAMETER])) {
            try {
                self::display($_GET[self::FILE_URL_PARAMETER], isset($_GET[self::MIN_MODE_PARAMETER]), $this->root_path);
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
        else
            die("api not found");
    }

}

?>