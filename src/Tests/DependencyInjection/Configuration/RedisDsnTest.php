<?php

namespace WebonautePhpredisBundle\Tests\DependencyInjection\Configuration;

use PHPUnit\Framework\TestCase;
use WebonautePhpredisBundle\DependencyInjection\Configuration\RedisDsn;

/**
 * RedisDsnTest
 */
class RedisDsnTest extends TestCase
{
    /**
     * @static
     *
     * @return array
     */
    public static function hostValues()
    {
        return array(
            array('redis://localhost', 'localhost'),
            array('redis://localhost/1', 'localhost'),
            array('redis://localhost:63790', 'localhost'),
            array('redis://localhost:63790/10', 'localhost'),
            array('redis://pw@localhost:63790/10', 'localhost'),
            array('redis://127.0.0.1', '127.0.0.1'),
            array('redis://127.0.0.1/1', '127.0.0.1'),
            array('redis://127.0.0.1:63790', '127.0.0.1'),
            array('redis://127.0.0.1:63790/10', '127.0.0.1'),
            array('redis://pw@127.0.0.1:63790/10', '127.0.0.1'),
            array('redis://%redis_host%', '%redis_host%'),
            array('redis://%redis_host%/%redis_db%', '%redis_host%'),
            array('redis://%redis_host%:%redis_port%', '%redis_host%'),
            array('redis://%redis_host%:%redis_port%/%redis_db%', '%redis_host%'),
            array('redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', '%redis_host%'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed', 'env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21', 'env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed'),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed'),
        );
    }

    /**
     * @param string $dsn  DSN
     * @param string $host Host
     *
     * @dataProvider hostValues
     */
    public function testHost($dsn, $host)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($host, $dsn->getHost());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function socketValues()
    {
        return array(
            array('redis:///redis.sock', '/redis.sock'),
            array('redis:///redis.sock/1', '/redis.sock'),
            array('redis:///redis.sock:63790', '/redis.sock'),
            array('redis:///redis.sock:63790/10', '/redis.sock'),
            array('redis://pw@/redis.sock:63790/10', '/redis.sock'),
            array('redis:///var/run/redis/redis-1.sock', '/var/run/redis/redis-1.sock'),
            array('redis:///var/run/redis/redis-1.sock/1', '/var/run/redis/redis-1.sock'),
            array('redis:///var/run/redis/redis-1.sock:63790', '/var/run/redis/redis-1.sock'),
            array('redis:///var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'),
            array('redis://pw@/var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'),
        );
    }

    /**
     * @param string $dsn    DSN
     * @param string $socket Socket
     *
     * @dataProvider socketValues
     */
    public function testSocket($dsn, $socket)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($socket, $dsn->getSocket());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function portValues()
    {
        return array(
            array('redis://localhost', 6379),
            array('redis://localhost/1', 6379),
            array('redis://localhost:63790', 63790),
            array('redis://localhost:63790/10', 63790),
            array('redis://pw@localhost:63790/10', 63790),
            array('redis://127.0.0.1', 6379),
            array('redis://127.0.0.1/1', 6379),
            array('redis://127.0.0.1:63790', 63790),
            array('redis://127.0.0.1:63790/10', 63790),
            array('redis://pw@127.0.0.1:63790/10', 63790),
            array('redis://%redis_host%:%redis_port%', '%redis_port%'),
            array('redis://%redis_host%:%redis_port%/%redis_db%', '%redis_port%'),
            array('redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', '%redis_port%'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21', 'env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21'),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21'),
            array('redis:///redis.sock', null),
            array('redis:///redis.sock/1', null),
            array('redis:///redis.sock:63790', null),
            array('redis:///redis.sock:63790/10', null),
            array('redis://pw@/redis.sock:63790/10', null),
        );
    }

    /**
     * @param string $dsn  DSN
     * @param int    $port Port
     *
     * @dataProvider portValues
     */
    public function testPort($dsn, $port)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($port, $dsn->getPort());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function databaseValues()
    {
        return array(
            array('redis://localhost', null),
            array('redis://localhost/0', 0),
            array('redis://localhost/1', 1),
            array('redis://localhost:63790', null),
            array('redis://localhost:63790/10', 10),
            array('redis://pw@localhost:63790/10', 10),
            array('redis://127.0.0.1', null),
            array('redis://127.0.0.1/0', 0),
            array('redis://127.0.0.1/1', 1),
            array('redis://127.0.0.1:63790', null),
            array('redis://127.0.0.1:63790/10', 10),
            array('redis://pw@127.0.0.1:63790/10', 10),
            array('redis://%redis_host%', null),
            array('redis://%redis_host%/%redis_db%', '%redis_db%'),
            array('redis://%redis_host%:%redis_port%', null),
            array('redis://%redis_host%:%redis_port%/%redis_db%', '%redis_db%'),
            array('redis://pw@%redis_host%:%redis_port%/%redis_db%', '%redis_db%'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed', null),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21', null),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a'),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a'),
            array('redis:///redis.sock', null),
            array('redis:///redis.sock/0', 0),
            array('redis:///redis.sock/1', 1),
            array('redis:///redis.sock:63790', null),
            array('redis:///redis.sock:63790/10', 10),
            array('redis://pw@/redis.sock:63790/10', 10),
        );
    }

    /**
     * @param string $dsn      DSN
     * @param int    $database Database
     *
     * @dataProvider databaseValues
     */
    public function testDatabase($dsn, $database)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($database, $dsn->getDatabase());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function passwordValues()
    {
        return array(
            array('redis://localhost', null),
            array('redis://localhost/1', null),
            array('redis://pw@localhost:63790/10', 'pw'),
            array('redis://user:pw@localhost:63790/10', 'pw'),
            array('redis://user:pw:withcolon@localhost:63790/10', 'pw:withcolon'),
            array('redis://Pw%3AColon%25@localhost:63790/10', 'Pw:Colon%'),
            array('redis://p%40w@localhost:63790/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@localhost:63790/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
            array('redis://127.0.0.1', null),
            array('redis://127.0.0.1/1', null),
            array('redis://pw@127.0.0.1:63790/10', 'pw'),
            array('redis://p%40w@127.0.0.1:63790/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@127.0.0.1:63790/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
            array('redis://%redis_host%', null),
            array('redis://%redis_host%/%redis_db%', null),
            array('redis://%redis_pass%@%redis_host%:%redis_port%', '%redis_pass%'),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed', null),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', null),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', 'env_REDIS_PW_e7406513a853fd4692343d101baecb7c'),
            array('redis:///redis.sock', null),
            array('redis:///redis.sock/1', null),
            array('redis://pw@/redis.sock/10', 'pw'),
            array('redis://p%40w@/redis.sock/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@/redis.sock/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
        );
    }

    /**
     * @param string $dsn      DSN
     * @param string $password Password
     *
     * @dataProvider passwordValues
     */
    public function testPassword($dsn, $password)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($password, $dsn->getPassword());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function isValidValues()
    {
        return array(
            array('redis://localhost', true),
            array('redis://localhost/1', true),
            array('redis://pw@localhost:63790/10', true),
            array('redis://127.0.0.1', true),
            array('redis://127.0.0.1/1', true),
            array('redis://pw@127.0.0.1:63790/10', true),
            array('redis:///redis.sock', true),
            array('redis:///redis.sock/1', true),
            array('redis://pw@/redis.sock/10', true),
            array('redis://pw@/redis.sock/10', true),
            array('redis://%redis_host%', true),
            array('redis://%redis_host%/%redis_db%', true),
            array('redis://%redis_host%:%redis_port%', true),
            array('redis://%redis_host%:%redis_port%/%redis_db%', true),
            array('redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', true),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed', true),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', true),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21', true),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', true),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', true),
            array('localhost', false),
            array('localhost/1', false),
            array('pw@localhost:63790/10', false),
            array('env_REDIS_URL_e07910a06a086c83ba41827aa00b26ed', false),
        );
    }

    /**
     * @param string $dsn   DSN
     * @param bool   $valid Valid
     *
     * @dataProvider isValidValues
     */
    public function testIsValid($dsn, $valid)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($valid, $dsn->isValid());
    }

    /**
     * @static
     *
     * @return array
     */
    public static function parameterValues()
    {
        return array(
            array('redis://localhost', null, null),
            array('redis://localhost/1?weight=1&alias=master', 1, 'master'),
            array('redis://pw@localhost:63790/10?alias=master&weight=2', 2, 'master'),
            array('redis://127.0.0.1?weight=3', 3, null),
            array('redis://127.0.0.1/1?alias=master&weight=4', 4, 'master'),
            array('redis://pw@127.0.0.1:63790/10?weight=5&alias=master', 5, 'master'),
            array('redis:///redis.sock?weight=6&alias=master', 6, 'master'),
            array('redis:///redis.sock/1?weight=7', 7, null),
            array('redis://pw@/redis.sock/10?weight=8&alias=master', 8, 'master'),
            array('redis://pw@/redis.sock/10?alias=master&weight=9', 9, 'master'),
            array('redis://localhost?alias=master', null, 'master'),
        );
    }

    /**
     * @param string $dsn    DSN
     * @param int    $weight Weight
     * @param string $alias  Alias
     *
     * @dataProvider parameterValues
     */
    public function testParameterValues($dsn, $weight, $alias)
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($weight, $dsn->getWeight());
        $this->assertSame($alias, $dsn->getAlias());
    }
}