<?php

namespace DarthSoup\Tests\Cart;

use DarthSoup\Cart\Hasher\Md5;
use DarthSoup\Cart\Hasher\RandomString;
use DarthSoup\Cart\Hasher\Uuid;
use DarthSoup\Cart\HashFactory;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class HashFactoryTest extends TestCase
{
    public function testMakeMd5Hash()
    {
        $factory = $this->getFactory();

        $this->assertInstanceOf(
            Md5::class,
            $factory->make('md5')
        );
    }

    public function testMakeUuidHash()
    {
        $factory = $this->getFactory();

        $this->assertInstanceOf(
            Uuid::class,
            $factory->make('uuid')
        );
    }

    public function testMakeRandomstringHash()
    {
        $factory = $this->getFactory();

        $this->assertInstanceOf(
            RandomString::class,
            $factory->make('randomstring')
        );
    }

    public function testMakeInvalidHasher()
    {
        $factory = $this->getFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported hasher method [foo]');

        $factory->make('foo');
    }

    protected function getFactory()
    {
        return new HashFactory();
    }
}
