<?php

Import::php("util.OpenM_Log");

/**
 * OpenM_RESTControllerClientException is throws by an OpenM_RESTControllerClient
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
class OpenM_RESTControllerClientException extends Exception {

    public function __construct($message=null) {
        OpenM_Log::error("$message", __CLASS__, __METHOD__, __LINE__);
        parent::__construct($message);
    }
}
?>