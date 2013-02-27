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
 * By default, $sso ready to be used by SSOClient
 */
$manager = OpenM_SSOClientPoolSessionManager::fromFile("example.config.properties");
$sso = $manager->get("sso");
?>
<h1>
    Access to an API, with/without SSO, demonstration:
</h1>
Rq: (thanks to read the <a href="../README" target="_blank">README</a> before)
<h2>
    Access without SSO (OpenM_Example):
</h2>
<?php
Import::php("OpenM-Services.client.OpenM_ServiceSSOClientImpl");

/**
 * instanciate an object ServiceClientImpl to access to OpenM_Example
 */
$p = Properties::fromFile("example.config.properties");
$example = new OpenM_ServiceClientImpl($p->get("OpenM_SSO.api.path"), "OpenM_Example");

/**
 * call a method and display result
 * (result are allways in HashtableString type (except void)
 * Rq: by default, the OpenM_ServiceClientImpl manages Exceptions
 */
try {
    $result = $example->method1("argument one", "argument two");
    ?>
    return of <a href="<?= $sso->getAPIpath() ?>"><?= $sso->getAPIpath() ?></a> => OpenM_Example.method1("argument one", "argument two") is a map(key/value) :<br>
    <?php
    $e = $result->keys();
    while ($e->hasNext()) {
        $key = $e->next();
        echo " - key='$key' => value='" . $result->get($key) . "'<br>";
    }
} catch (Exception $e) {
    echo "Un error Occurs: " . $e->getMessage() . "<br>";
}
?>
<h2>
    Access with SSO (OpenM_ExampleSSO):
</h2>
<?php
if (!$sso->isConnected() || !$sso->isSSOapiConnectionOK())
    die("SSO not connected (check your SSO Configuration in example.config.properties)");

/**
 * instanciate a new ServiceClientImpl to use OpenM_ExampleSSO
 */
$exampleSSO = new OpenM_ServiceSSOClientImpl($sso, "OpenM_ExampleSSO");

/**
 * call a method and display result
 */
try {
    $result = $exampleSSO->method1("argument one", "argument two");
    ?>
    return of <a href="<?= $sso->getAPIpath() ?>"><?= $sso->getAPIpath() ?></a> => OpenM_ExampleSSO.method1("argument one", "argument two") is a map(key/value) :<br>
    <?php
    $e = $result->keys();
    while ($e->hasNext()) {
        $key = $e->next();
        echo " - key='$key' => value='" . $result->get($key) . "'<br>";
    }
} catch (Exception $e) {
    echo "Un error Occurs: " . $e->getMessage() . "<br>";
}
?>
<br>
<h3>
    See source code of this file (<?= __FILE__ ?>) to understand API calling with/without SSO.
</h3>
<div style="margin: 10px; padding: 10px; background-color: grey">
    <?= str_replace("\r", "<br>", str_replace("<", htmlentities("<"), file_get_contents(__FILE__))) ?>
</div>