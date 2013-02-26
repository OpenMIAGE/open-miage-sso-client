<?php

Import::php("OpenM-ID.api.OpenM_ID_Tool");

/**
 * Used by OpenM_RESTController to initialize local calling
 * In this case, OpenM_SSO provider is a local provider, but need to work same.
 * @package OpenM 
 * @subpackage OpenM\OpenM-SSO\client 
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
class OpenM_SSOSessionLocalManager {

    private static $localID=null;
    private static $localSSID=null;
    
    /**
     * used to initialize local api calling by giving user ID and opened session
     * SSID
     * @param String $ID is ID of connected user
     * @param String $SSID is SSID of localy opened session
     * @throws InvalidArgumentException
     */
    public static function init($ID, $SSID) {
        if (!OpenM_ID_Tool::isTokenValid($ID))
            throw new InvalidArgumentException("ID must be in a valid format");
        if (!OpenM_ID_Tool::isTokenValid($SSID))
            throw new InvalidArgumentException("SSID must be in a valid format");
        self::$localID = "$ID";
        self::$localSSID = "$SSID";
    }

    /**
     * used to know if api caller is local caller
     * @return boolean is true if api caller is local caller
     */
    public static function isAPILocal(){
        return self::$localID!==null;
    }
    
    /**
     * used by api to know the user ID
     * @return String ID of user
     */
    public static function getID(){
        return self::$localID;
    }

    /**
     * used by api to recover all opened session with other API and their
     * OpenM_SSO providers
     * @return String SSID of opened session
     */
    public static function getSSID(){
        return self::$localSSID;
    }
}

?>