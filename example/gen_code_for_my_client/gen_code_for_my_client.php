<?php

/**
 * Load Import Class
 */
require_once 'src.php';

/**
 * using Import class to load the SSO manager : OpenM_SSOClientPoolSessionManager
 * this class is in {OpenM library directory}/OpenM-SSO/client/
 */
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * By default, $sso ready to be used by SSOClient
 */
$manager = OpenM_SSOClientPoolSessionManager::fromFile("myConfig.properties");
/**
 * $ssoName must be the value of a OpenM_SSO.api.name or OpenM_SSO.api.X.name
 * in myConfig.properties
 */
$ssoName = "the book api";
$sso = $manager->get($ssoName, false);
$sso->login(array("email"));

/**
 * instanciate a new ServiceSSOClientImpl to use your the requested API
 * For example: 'OpenM_Book'
 */
$openM_BookClient = new OpenM_ServiceSSOClientImpl($sso, "OpenM_Book");

/**
 * You can now call the api by calling a method directly on client
 * for example: 'registerMe()'
 */
$openM_BookClient->registerMe();

/**
 * or you can instanciate a local JSON server for dynamic application using AJAX
 */
Import::php("OpenM-Controller.client.OpenM_RESTControllerClient_JSONLocalServer");
$server = new OpenM_RESTControllerClient_JSONLocalServer($sso);
$server->handle();
?>
