<?php

namespace Atwinta\Bridges;

use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

abstract class CacheableBridge extends Bridge
{
    /**
     * @throws \RuntimeException
     */
    protected function resetRequest(): void
    {
        parent::resetRequest();

        $this->request->withMiddleware(
            new CacheMiddleware(
                $this->getCacheStrategy(new LaravelCacheStorage(Cache::store()))
            ),
        );
    }

    protected function getCacheStrategy(CacheStorageInterface $storage): CacheStrategyInterface
    {
        return new PrivateCacheStrategy($storage);
    }
}
