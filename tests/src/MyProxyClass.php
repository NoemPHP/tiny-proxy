<?php

declare(strict_types=1);

namespace Noem\TinyProxy\Tests;

class MyProxyClass
{
    public function __construct(public string $foo, public int $bar)
    {
    }

    public function attach(MyOtherClass $otherClass)
    {

    }

    public function getBarPlusOne(): int
    {
        return $this->bar + 1;
    }
}
