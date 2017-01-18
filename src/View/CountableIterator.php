<?php

namespace SilverStripe\View;

use IteratorAggregate;

interface CountableIterator extends IteratorAggregate
{
    /**
     * @return int
     */
    public function getIteratorCount();
}
