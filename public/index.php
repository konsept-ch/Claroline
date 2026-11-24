<?php

use Claroline\CoreBundle\Library\Maintenance\MaintenanceHandler;
use Claroline\KernelBundle\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

// Cette valeur l'emporte sur le memory_limit de php.ini pour toute requête web, en local
// comme en production. CEP : 1G depuis le 24.11.2026. À 512M, l'inscription publique
// échouait en production par dépassement mémoire (UserVoter::checkCreate). Ne réduire
// qu'après avoir mesuré la consommation réelle en production.
if ((int) ini_get('memory_limit') > 0) {
    ini_set('memory_limit', '1G');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

if (!MaintenanceHandler::isMaintenanceEnabled()) {
    $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} else {
    $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'/../maintenance.php';
    header("Location: {$url}");
}
