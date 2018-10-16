<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Simple\ArrayCache;

class ArrayCacheFactory implements CacheFactory
{
    public function create($service, array $params = array())
    {
        return Injector::inst()->create(ArrayCache::class, false, [
            (isset($params['defaultLifetime'])) ? $params['defaultLifetime'] : 0
        ]);
    }
}
