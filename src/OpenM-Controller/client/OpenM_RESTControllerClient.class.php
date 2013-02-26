<?php

Import::php("OpenM-Controller.client.OpenM_RESTControllerClientException");
Import::php("util.HashtableString");
Import::php("util.JSON.OpenM_MapConvertor");
Import::php("OpenM-SSO.client.OpenM_SSOSession");
Import::php("OpenM-SSO.api.OpenM_SSO");
Import::php("OpenM-Services.client.OpenM_ServiceClientImpl");
Import::php("util.http.OpenM_URL");
Import::php("util.OpenM_Log");
Import::php("util.wrapper.Float");

/**
 * OpenM_RESTControllerClient::call is a calling interceptor that
 * call the api by the good way (local/remote).
 * @package OpenM 
 * @subpackage OpenM\OpenM-Controller\client 
 * @copyright (c) 2013, www.open-miage.org
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
 * @author Gael SAUNIER
 */
class OpenM_RESTControllerClient {

    /**
     * OpenM_RESTControllerClient::call is a method calling interceptor that
     * call the api by the good way (local/remote).
     * @param String $url is URL of remote/local API. It could be a local path.
     * @param String $api is the interface name of API (ex.: 'OpenM_SSO')
     * @param String $method is the method name of method called
     * @param array $args is an array that contains all arguments of called method
     * @param OpenM_SSOSession $sso permit to manage an api protected by 
     * an OpenM_SSO provider
     * @param boolean $resultInJSON if true, return of 'call' will be a String,
     * else return the HashtableString returned by the api
     * @return String|HashtableString return of api called or JSON transformation
     * @throws InvalidArgumentException
     * @throws OpenM_RESTControllerClientException if api calling throws an error
     * @uses OpenM_SSOSessionLocalManager in case of local api calling
     */
    public static function call($url, $api, $method, $args = array(), OpenM_SSOSession $sso = null, $resultInJSON = false) {
        if (!String::isString($api))
            throw new InvalidArgumentException("'api' must be a string");
        if (!String::isString($method))
            throw new InvalidArgumentException("'method' must be a string");
        if (!ArrayList::isArray($args))
            throw new InvalidArgumentException("'args' must be an array");
        if ($args instanceof ArrayList)
            $args = $args->toArray();

        OpenM_Log::debug("call $url => $api.$method(" . implode(", ", $args) . ")", __CLASS__, __METHOD__, __LINE__);

        if (is_dir($url)) {
            $url = realpath($url);
            Import::addClassPath($url);
            $cwd = getcwd();
            chdir($url);
            $api = $api . "Impl";
            if (!Import::php($api))
                throw new InvalidArgumentException("API not found");
            $a = new $api();
            if ($sso != null) {
                if (Import::php("OpenM-SSO.client.OpenM_SSOSessionLocalManager"))
                    OpenM_SSOSessionLocalManager::init($sso->getID(), $sso->getSSID());
            }
            $return = call_user_func_array(array($a, $method), $args);
            chdir($cwd);
            if ($return === false)
                throw new InvalidArgumentException("Method bad called");
            if (!$resultInJSON)
                return $return;
            else
                return OpenM_MapConvertor::mapToJSON($return);
        }

        $file = $url . "?api=" . $api . "&method=" . $method;

        $arrayArgs = array();
        $i = 1;
        foreach ($args as $value) {
            if (!String::isStringOrNull($value) && !Float::isNumber($value))
                throw new InvalidArgumentException("args must be an array of string or a number");
            if ($value !== null) {
                $arrayArgs["arg$i"] = (String::isString($value)) ? OpenM_URL::encode($value) : String::cast($value);
                OpenM_Log::debug("arg$i=$value", __CLASS__, __METHOD__, __LINE__);
            }
            $i++;
        }

        if ($sso != null && $sso->isSSOapiConnectionOK())
            $file .= "&" . OpenM_SSO::SSID_PARAMETER . "=" . $sso->getSSID();

        OpenM_Log::debug("api=$file", __CLASS__, __METHOD__, __LINE__);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayArgs);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $string = substr($response, $header_size);
        OpenM_Log::debug("return=$string", __CLASS__, __METHOD__, __LINE__);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        OpenM_ServiceClientImpl::treatHttpResponse($code, $header);
        curl_close($ch);

        if ($string) {
            if ($resultInJSON)
                return $string;
            else
                $return = OpenM_MapConvertor::JSONToMap($string);
        }
        else
            throw new InvalidArgumentException("argument 'url' must be a valid api url");

        if ($return != null && !($return->containsKey(OpenM_Service::RETURN_VOID_PARAMETER) && $return->size() == 1))
            return $return;
        else if ($return != null)
            return null;
        else
            throw new OpenM_RESTControllerClientException("return of API " . $api . " on Method " . $method . " is bad implemented.");
    }

}

?>