<?php

namespace Claroline\HomeBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Small helper to cache the serialized payload returned by /home.
 * The cache key is built at controller level (per role/profile).
 */
class HomeCache
{
    private const CACHE_PREFIX = 'claroline_home_';

    /** @var CacheItemPoolInterface */
    private $cachePool;

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function get(string $profileKey): ?array
    {
        $item = $this->cachePool->getItem($this->formatKey($profileKey));

        if ($item->isHit()) {
            $value = $item->get();

            return is_array($value) ? $value : null;
        }

        return null;
    }

    public function save(string $profileKey, array $payload): void
    {
        $item = $this->cachePool->getItem($this->formatKey($profileKey));
        $item->set($payload);

        $this->cachePool->save($item);
    }

    public function invalidateProfile(string $profileKey): void
    {
        $this->cachePool->deleteItem($this->formatKey($profileKey));
    }

    public function invalidateAll(): void
    {
        $this->cachePool->clear();
    }

    private function formatKey(string $profileKey): string
    {
        return self::CACHE_PREFIX.sha1($profileKey);
    }
}
