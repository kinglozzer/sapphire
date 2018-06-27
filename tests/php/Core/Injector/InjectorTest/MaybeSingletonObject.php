<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\Injector\Injector;

class MaybeSingletonObject
{
    public function __construct()
    {
        if (Injector::inst()->isConstructingSingleton()) {
            throw new \Exception('Is singleton');
        } else {
            throw new \Exception('Is not singleton');
        }
    }
}
