<?php

namespace WebonautePhpredisBundle\Tests\Client\Phpredis;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use WebonautePhpredisBundle\Client\RedisLoggedClient;

/**
 * ClientTest
 */
class ClientTest extends WebTestCase
{
    /**
     * @covers \WebonautePhpredisBundle\Client\RedisLoggedClient::getCommandString
     * @throws \ReflectionException
     */
    public function testGetCommandString(): void
    {
        if (!\extension_loaded('redis')) {
            $this->markTestSkipped('This test needs the PHP Redis extension to work');
        }

        $method = new \ReflectionMethod(
            RedisLoggedClient::class, 'getCommandString'
        );

        $method->setAccessible(true);

        $name = 'foo';
        $arguments = [['chuck', 'norris']];

        $this->assertEquals(
            'FOO chuck norris',
            $method->invoke(new RedisLoggedClient(['alias' => 'bar']), $name, $arguments)
        );

        $arguments = ['chuck:norris'];

        $this->assertEquals(
            'FOO chuck:norris',
            $method->invoke(new RedisLoggedClient(['alias' => 'bar']), $name, $arguments)
        );

        $arguments = ['chuck:norris fab:pot'];

        $this->assertEquals(
            'FOO chuck:norris fab:pot',
            $method->invoke(new RedisLoggedClient(['alias' => 'bar']), $name, $arguments)
        );

        $arguments = ['foo' => 'bar', 'baz' => null];

        $this->assertEquals(
            'FOO foo bar baz <null>',
            $method->invoke(new RedisLoggedClient(['alias' => 'bar']), $name, $arguments)
        );
    }
}
