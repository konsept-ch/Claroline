<?php

namespace Claroline\CoreBundle\Library\GeoIp;

use GeoIp2\Database\Reader as MaxMindReader;
use GeoIp2\Exception\AddressNotFoundException;

class MaxMindGeoIpInfoProvider implements GeoIpInfoProviderInterface
{
    private $geoIpDatabase;

    public function __construct(MaxMindReader $geoIpDatabase)
    {
        $this->geoIpDatabase = $geoIpDatabase;
    }

    public function getGeoIpInfo(string $ip): ?GeoIpinfo
    {
        try {
            return GeoIpInfo::fromMaxMind($this->geoIpDatabase->city($ip));
        } catch (AddressNotFoundException $e) {
            return null;
        }
    }
}
