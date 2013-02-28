<?php

Import::php("OpenM-Services.api.OpenM_Service");
Import::php("util.HashtableString");
Import::php("OpenM-Services.client.OpenM_HttpError_BadRequest");
Import::php("OpenM-Services.client.OpenM_HttpError_Forbidden");
Import::php("OpenM-Services.client.OpenM_HttpError_InternalServerError");
Import::php("OpenM-Services.client.OpenM_HttpError_NotFound");
Import::php("OpenM-Services.client.OpenM_HttpError_NotImplemented");

/**
 * OpenM_ServiceClientImpl is a generic client to access to an OpenM api
 * without SSO session (public api, like OpenM_SSO).
 * This client manage api calling local/remote, api exception or api error messages
 * @package OpenM 
 * @subpackage OpenM\OpenM-Services\client 
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
 * @author Gaël Saunier
 */
class OpenM_ServiceClientImpl implements OpenM_Service {

    protected $api_path;
    private $api_name;
    protected $sso;

    /**
     * create a client for a specified API from it's name.
     * @param String $api_path is the path/url to the api
     * @param String $api_name is the interface name of the API (ex OpenM_SSO).
     * @throws InvalidArgumentException if a parameter is not valid.
     */
    public function __construct($api_path, $api_name) {
        if (!String::isString($api_path))
            throw new InvalidArgumentException("api_path must be a string");
        if (!String::isString($api_name))
            throw new InvalidArgumentException("api_name must be a string");
        $this->api_path = $api_path . ((RegExp::ereg("/$", $api_path)) ? "" : "/");
        $this->api_name = $api_name;
    }

    /**
     * 
     * @param int $code
     * @param String $message
     * @throws OpenM_HttpError_BadRequest
     * @throws OpenM_HttpError_Forbidden
     * @throws OpenM_HttpError_NotFound
     * @throws OpenM_HttpError_InternalServerError
     * @throws OpenM_HttpError_NotImplemented
     * @throws OpenM_HttpClientError
     * @throws OpenM_HttpServerError
     */
    public static function treatHttpResponse($code, $header) {
        if ($code == 200)
            return;
        if (strlen($header) > 0)
            $array = explode("\r\n", $header);
        $message = "";
        foreach ($array as $value) {
            if (strpos($value, "$code")) {
                $message = $value;
                break;
            }
        }
        OpenM_Log::debug("$code [$message]", __CLASS__, __METHOD__, __LINE__);
        switch ($code) {
            case 400:
                throw new OpenM_HttpError_BadRequest($message);
                break;
            case 403:
                throw new OpenM_HttpError_Forbidden($message);
                break;
            case 404:
                throw new OpenM_HttpError_NotFound($message);
                break;
            case 500:
                throw new OpenM_HttpError_InternalServerError($message);
                break;
            case 501:
                throw new OpenM_HttpError_NotImplemented($message);
                break;
            default:
                if ($code > 400 && $code < 500)
                    throw new OpenM_HttpClientError($message);
                else if ($code > 500 && $code < 600)
                    throw new OpenM_HttpServerError($message);
                break;
        }
    }

    /**
     * magic method to call genericly the called method on remote or local api.
     * @param String $name is the method name
     * @param array $arguments contains the arguments given to the called method 
     * @return null|HashtableString return of the called api
     * @throws OpenM_ServiceClientException if api calling throws an exception
     */
    public function __call($name, $arguments) {
        $return = OpenM_RESTControllerClient::call($this->api_path, $this->api_name, $name, $arguments, $this->sso);
        if ($return == null)
            return null;
        if (!($return instanceof HashtableString))
            throw new OpenM_ServiceClientException("Not correct call return");
        if ($return->containsKey(self::RETURN_ERROR_PARAMETER))
            throw new OpenM_ServiceClientException($return->get(self::RETURN_ERROR_MESSAGE_PARAMETER));
        return $return;
    }

}

Import::php("OpenM-Controller.client.OpenM_RESTControllerClient");
?>