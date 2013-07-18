<?php

require_once dirname(__DIR__) . '/gui/src.php';
require_once dirname(__DIR__) . '/gui/lib.php';
require_once dirname(__DIR__) . '/config.php';

Import::php("util.Properties");
Import::php("OpenM-Services.gui.OpenM_ServiceView");
$property = Properties::fromFile(OpenM_SERVICE_CONFIG_FILE_NAME);
OpenM_Log::init($property->get(OpenM_ServiceView::LOG_PATH_PROPERTY), $property->get(OpenM_ServiceView::LOG_LEVEL_PROPERTY), $property->get(OpenM_ServiceView::LOG_FILE_NAME), $property->get(OpenM_ServiceView::LOG_LINE_MAX_SIZE));

Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");
$ssoManager = OpenM_SSOClientPoolSessionManager::fromFile(OpenM_SERVICE_CONFIG_DIRECTORY . "/OpenM_SSO.config.properties");
$sso = $ssoManager->get("OpenM_Book", false);

Import::php("OpenM-Controller.client.OpenM_REST_APIProxy");
$server = new OpenM_REST_APIProxy($sso);
$server->handle();
?>