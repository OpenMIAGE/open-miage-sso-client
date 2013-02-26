<?php

Import::php("OpenM-SSO.api.OpenM_SSO");
Import::php("OpenM-SSO.client.OpenM_SSOClientPoolSessionManager");
Import::php("OpenM-Services.client.OpenM_ServiceSSOClientImpl");
Import::php("OpenM-SSO.api.OpenM_SSOAdmin");
Import::php("util.Properties");
Import::php("util.file.OpenM_Dir");
Import::php("util.OpenM_Log");

/**
 * OpenM_SSOClientInstaller is an OpenM_SSO client installer.
 * An OpenM_SSO client has to be registered and validated on OpenM_SSO provider,
 * before open session on OpenM_SSO provider.
 * This installer do the operation of registering and (in case of user logged is
 * an administrator) validate the registration.
 * @package OpenM 
 * @subpackage OpenM\OpenM-SSO\client 
 * @author Gaël Saunier
 */
class OpenM_SSOClientInstaller {

    const OpenM_SSO_CLIENT_ID = "OpenM_SSO.client.id";

    /**
     * Step1 register the OpenM_SSO client on an OpenM_SSO provider.
     * @param boolean $log activate or not log generation (activated if true,
     * else not activated)
     * @throws Exception
     * @throws OpenM_RestControllerException
     */
    public static function step1($log = true) {
        if ($log)
            OpenM_Log::init(".", OpenM_Log::DEBUG, "install.log");
        OpenM_Log::info("load manager");
        $manager = OpenM_SSOClientPoolSessionManager::fromFile(OpenM_SSOClientInstaller_CONFIG);
        try {
            OpenM_Log::info("generate sso instance");
            $sso = $manager->get();
            OpenM_Log::info("login (force)");
            $sso->login(array(OpenM_ID::TOKEN_PARAMETER), true);
            OpenM_Log::info("check if connected");
            if (!$sso->isConnected()) {
                throw new Exception("Installer bad parameterized... connection not possible");
            }
            OpenM_Log::info("load SSO client");
            $p = Properties::fromFile(OpenM_SSOClientInstaller_CONFIG);
            $ssoAdminClient = new OpenM_ServiceClientImpl($p->get("OpenM_SSO.api.path"), "OpenM_SSO", true);
            OpenM_Log::info("add API Client");
            $return = $ssoAdminClient->addClient($sso->getOID(), $sso->getToken());
            $p->set(self::OpenM_SSO_CLIENT_ID, $return->get(OpenM_SSO::RETURN_CLIENT_ID_PARAMETER));
            $p->save();
            OpenM_Log::info("installation successfully finished");
            echo "<h1>Client successfully installed !</h1>";
            OpenM_Log::info("remove store path");
            OpenM_Dir::rm($p->get("OpenM_ID.store.path"));
            OpenM_Log::info("close pool session manager");
            $manager->close(OpenM_SSOClientInstaller_CONFIG);
        } catch (Exception $e) {
            $manager->close(OpenM_SSOClientInstaller_CONFIG);
            die($e->getMessage());
        }
    }

    /**
     * Step1 validate the OpenM_SSO client on an OpenM_SSO provider.
     * This step could succed only if user connected is an administrator.
     * @param boolean $log
     * @throws Exception
     */
    public static function step2($log = true) {
        if ($log)
            OpenM_Log::init(".", OpenM_Log::DEBUG, "install.log");

        OpenM_Log::info("load manager");
        $manager = OpenM_SSOClientPoolSessionManager::fromFile(OpenM_SSOClientInstaller_CONFIG);
        try {
            $p = Properties::fromFile(OpenM_SSOClientInstaller_CONFIG);
            if ($p->get("OpenM_SSO.api.name") == null)
                throw new Exception("OpenM_SSO.api.name not found in " . OpenM_SSOClientInstaller_CONFIG);
            OpenM_Log::info("generate sso instance");
            $sso = $manager->get($p->get("OpenM_SSO.api.name"));
            if (!$sso->isConnected())
                throw new Exception("Installer bad parameterized... connection not possible");
            $ssoAdminClient = new OpenM_ServiceSSOClientImpl($sso, "OpenM_SSOAdmin");
            if ($p->get(OpenM_SSOClientInstaller::OpenM_SSO_CLIENT_ID) == null)
                throw new Exception("OpenM_SSO.client.id not found in " . OpenM_SSOClientInstaller_CONFIG);
            $ssoAdminClient->addClientRights($p->get(OpenM_SSOClientInstaller::OpenM_SSO_CLIENT_ID), OpenM_SSOAdmin::DEFAULT_CLIENT_RIGHTS);
            $p->remove(OpenM_SSOClientInstaller::OpenM_SSO_CLIENT_ID);
            $p->save();
            echo "<h1>Installation successfuly finished !!</h1>";
            OpenM_Log::info("remove store path");
            OpenM_Dir::rm($p->get("OpenM_ID.store.path"));
            OpenM_Log::info("close pool session manager");
            $manager->close(OpenM_SSOClientInstaller_CONFIG);
        } catch (Exception $e) {
            $manager->close(OpenM_SSOClientInstaller_CONFIG);
            die($e->getMessage());
        }
    }

}

/**
 * By default, wait installer.properties file to
 * parameterized the sso installation 
 */
if (!defined('OpenM_SSOClientInstaller_CONFIG'))
    define('OpenM_SSOClientInstaller_CONFIG', "installer.properties");
?>