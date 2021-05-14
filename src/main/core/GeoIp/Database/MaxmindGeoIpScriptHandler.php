<?php

namespace Claroline\CoreBundle\GeoIp\Database;

use Claroline\BundleRecorder\Logger\ConsoleIoLogger;
use Composer\Script\Event;

class MaxmindGeoIpScriptHandler
{
    public static function refreshDatabase(Event $event): void
    {
        $logger = new ConsoleIoLogger($event->getIo());
        $geoIpLicenseKey = getenv('MAXMIND_GEOIP_LICENSE_KEY');

        if (!$geoIpLicenseKey) {
            $logger->notice('Cannot download the GeoIp database: the "MAXMIND_GEOIP_LICENSE_KEY" env var is missing.');

            return;
        }

        $destination = realpath($event->getComposer()->getConfig()->get('vendor-dir').'/..').'/config/geoip';

        $geoIpDownloader = new MaxMindGeoIpDatabaseDownloader($geoIpLicenseKey, $logger);
        $geoIpDownloader->downloadDatabase($destination);
    }
}
