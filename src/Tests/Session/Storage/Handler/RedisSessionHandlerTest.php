<?php

namespace WebonautePhpredisBundle\Tests\Session\Storage\Handler;

use PHPUnit\Framework\TestCase;
use WebonautePhpredisBundle\Client\RedisLoggedClient;
use WebonautePhpredisBundle\Session\Storage\Handler\RedisSessionHandler;

/**
 * RedisSessionHandlerTest
 */
class RedisSessionHandlerTest extends TestCase
{
    private $redis;

    protected function setUp()
    {
        $this->redis = $this->getMockClass(RedisLoggedClient::class, array('get', 'set', 'setex', 'del'));
    }

    protected function tearDown()
    {
        unset($this->redis);
    }

    public function testSessionReading(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_symfony'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), null, false);
        $handler->read('_symfony');
    }

    public function testDeletingSessionData(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('del')
            ->with($this->equalTo('session:_symfony'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), 'session:', false);
        $handler->destroy('_symfony');
    }

    public function testWritingSessionDataWithNoExpiration(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo('session:_symfony'), $this->equalTo('some data'))
        ;

        $handler = new RedisSessionHandler($this->redis, array(), 'session:', false);
        $handler->write('_symfony', 'some data');
    }

    public function testWritingSessionDataWithExpiration(): void
    {
        $this->redis
            ->expects($this->exactly(3))
            ->method('setex')
            ->with($this->equalTo('session:_symfony'), $this->equalTo(10), $this->equalTo('some data'))
        ;

        // Expiration is set by cookie_lifetime option
        $handler = new RedisSessionHandler($this->redis, array('cookie_lifetime' => 10), 'session:', false);
        $handler->write('_symfony', 'some data');

        // Expiration is set with the TTL attribute
        $handler = new RedisSessionHandler($this->redis, array(), 'session:', false);
        $handler->setTtl(10);
        $handler->write('_symfony', 'some data');

        // TTL attribute overrides cookie_lifetime option
        $handler = new RedisSessionHandler($this->redis, array('cookie_lifetime' => 20), 'session:', false);
        $handler->setTtl(10);
        $handler->write('_symfony', 'some data');
    }

    public function testSessionLocking(): void
    {
        $lockMaxWait = 2;
        ini_set('max_execution_time', $lockMaxWait);

        // The first time it will say it's locked, the second time
        $this->redis
            ->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->equalTo('session_symfony_locktest.lock'),
                $this->isType('string'),
                $this->equalTo('PX'),
                $this->equalTo($lockMaxWait * 1000 + 1),
                $this->equalTo('NX')
            )
            ->will($this->onConsecutiveCalls(0,1))
        ;

        // We prepare our handlers
        $handler = new RedisSessionHandler($this->redis, array(), 'session', true, 1000000);

        // The first will set the lock and the second will loop until it's free
        $handler->read('_symfony_locktest');
    }
}
