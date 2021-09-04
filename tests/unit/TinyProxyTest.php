<?php

use Noem\TinyProxy\TinyProxy;
use Noem\TinyProxy\Tests\MyProxyClass;
use PHPUnit\Framework\TestCase;

class TinyProxyTest extends TestCase
{
    public function testValidInstance()
    {
        $proxy = $this->createProxy('foo', 41);
        $this->assertInstanceOf(MyProxyClass::class, $proxy);

    }

    public function testGetProperty()
    {
        $proxy = $this->createProxy('foo', 41);
        $this->assertSame('foo', $proxy->foo);

    }

    public function testSetProperty()
    {
        $proxy = $this->createProxy('foo', 41);
        $proxy->bar = 99;
        $this->assertSame(99, $proxy->bar);

    }

    public function testMethod()
    {
        $proxy = $this->createProxy('foo', 41);
        $shouldBe42 = $proxy->getBarPlusOne();
        $this->assertSame(42, $shouldBe42);
    }

    private function createProxy(string $foo, int $bar): MyProxyClass
    {
        $proxyClassName = TinyProxy::proxyClassName(MyProxyClass::class);
        $factory = fn() => new MyProxyClass($foo, $bar);
        if (!class_exists($proxyClassName)) {

            $phpCode = TinyProxy::generateCode(
                MyProxyClass::class,
            );
            echo $phpCode;
            eval($phpCode);
        }

        return new $proxyClassName($factory);
    }
}
