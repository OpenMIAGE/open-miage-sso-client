<?php

/**
 * Load Import Class
 */
require_once 'src.php';

/**
 * if you whant to follow the treatment, you can initiate a log a show it after execution.
 */
Import::php("util.OpenM_Log");
OpenM_Log::init(".", OpenM_Log::DEBUG, "log");

/**
 * using Import class to load the SSO manager : OpenM_SSOClientPoolSessionManager
 * this class is in {OpenM library directory}/OpenM-SSO/client/
 */
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");

/**
 * Check local connection status (your connection status mechanism)
 */
Import::php("util.session.OpenM_SessionController");
if (OpenM_SessionController::get("connected") == "OK")
    OpenM_Header::redirect("./");

/**
 * use sso object to login
 * RQ: more option could be used (see OpenM_SSOClientSession interface for doc)
 */
$manager = OpenM_SSOClientPoolSessionManager::fromFile("example.config.properties");
$sso = $manager->get();
$sso->login();

/**
 * check if sso is connected and fil you own local connection mechanism
 * Connection depend on sso configuration, OpenM_ID server running..
 * in case of connection error, check you sso configuration
 */
if ($sso->isConnected()) {
    OpenM_SessionController::set("connected", "OK");
    OpenM_SessionController::set("id", $sso->getID());
    OpenM_Header::redirect("./");
}
else
    die("Connection Error...");
?>