# RedisBundle ![project status](https://img.shields.io/maintenance/yes/2016.svg?maxAge=2592000) [![build status](https://secure.travis-ci.org/webonaute/PhpRedisBundle.png?branch=master)](https://secure.travis-ci.org/webonaute/PhpRedisBundle) #

## About ##

This bundle integrates [phpredis](https://github.com/phpredis/phpredis) into your Symfony 3.0+ application.

## Prerequisite ##

Session handler requires `Redis >= 2.6.12` for LUA scripts and SET with options.

## Installation ##

Add the `webonaute/phpredis-bundle` package to your `require` section in the `composer.json` file.

``` bash
$ composer require webonaute/phpredis-bundle 1.x-dev
```

Add the PhpRedisBundle to your application's kernel:

``` php
<?php
public function registerBundles()
{
    $bundles = array(
        // ...
        new WebonautePhpredisBundle\PhpRedisBundle(),
        // ...
    );
    ...
}
```

## Usage ##

Configure the `redis` client(s) in your `config.yml`:

_Please note that passwords with special characters in the DSN string such as `@ % : +` must be urlencoded._

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn: redis://localhost
```

You have to configure at least one client. In the above example your service
container will contain the service `webonaute_phpredis.default` which will return a
`phpredis` client.

Available types are `single` and `cluster`.

A more complex setup which contains a clustered client could look like this:

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn: redis://localhost
            logging: %kernel.debug%
        cache:
            type: single
            alias: cache
            dsn: redis://secret@localhost/1
            options:
                profile: 2.2
                connection_timeout: 10
                read_write_timeout: 30
        session:
            type: single
            alias: session
            dsn: redis://localhost/2
        mycluster:
            type: cluster
            alias: mycluster
            dsn:
                - redis://localhost/3?weight=10
                - redis://localhost/4?weight=5
                - redis://localhost/5?weight=1
```

In your controllers you can now access all your configured clients:

``` php
<?php
$redis = $this->container->get('webonaute_phpredis.default');
$val = $redis->incr('foo:bar');
$redis_cluster = $this->container->get('webonaute_phpredis.cluster');
$val = $redis_cluster->get('ab:cd');
$val = $redis_cluster->get('ef:gh');
$val = $redis_cluster->get('ij:kl');
```

A setup using `phpredis` master-slave replication could look like this:

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn:
                - redis://master-host?alias=master
                - redis://slave-host1
                - redis://slave-host2
            options:
                replication: true
```

A setup using `phpredis` sentinel replication could look like this:

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn:
                - redis://localhost
                - redis://otherhost
            options:
                replication: sentinel
                service: mymaster
                parameters:
                    database: 1
                    password: pass
```

The `service` is the name of the set of Redis instances.
The optional parameters option can be used to set parameters like the 
database number and password for the master/slave connections, 
they don't apply for the connection to sentinal.
You can find more information about this on [Configuring Sentinel](https://redis.io/topics/sentinel#configuring-sentinel).

### Sessions ###

Use Redis sessions by adding the following to your config:

``` yaml
webonaute_phpredis:
    ...
    session:
        client: session
```

This bundle then provides the `WebonautePhpredisBundle\Session\Storage\Handler\RedisSessionHandler` service which
you have to activate at `framework.session.handler_id`:

``` yaml
framework:
    ...
    session:
        handler_id: WebonautePhpredisBundle\Session\Storage\Handler\RedisSessionHandler
```

This will use the default prefix `session`.

You may specify another `prefix`:

``` yaml
webonaute_phpredis:
    ...
    session:
        client: session
        prefix: foo
```

By default, a TTL is set using the `framework.session.cookie_lifetime` parameter. But
you can override it using the `ttl` option:

``` yaml
webonaute_phpredis:
    ...
    session:
        client: session
        ttl: 1200
```

This will make session data expire after 20 minutes, on the **server side**.
This is highly recommended if you don't set an expiration date to the session
cookie. Note that using Redis for storing sessions is a good solution to avoid
garbage collection of sessions by PHP.

### Doctrine caching ###

Use Redis caching for Doctrine by adding this to your config:

``` yaml
webonaute_phpredis:
    ...
    doctrine:
        metadata_cache:
            client: cache
            entity_manager: default          # the name of your entity_manager connection
            document_manager: default        # the name of your document_manager connection
        result_cache:
            client: cache
            entity_manager: [default, read]  # you may specify multiple entity_managers
        query_cache:
            client: cache
            entity_manager: default
        second_level_cache:
            client: cache
            entity_manager: default
```

### Monolog logging ###

You can store your logs in a redis `LIST` by adding this to your config:

``` yaml
webonaute_phpredis:
    clients:
        monolog:
            type: single
            alias: monolog
            dsn: redis://localhost/1
            logging: false
            options:
                connection_persistent: true
    monolog:
        client: monolog
        key: monolog

monolog:
    handlers:
        main:
            type: service
            id: webonaute_phpredis.monolog.handler
            level: debug
```

You can also add a custom formatter to the monolog handler

``` yaml
webonaute_phpredis:
    clients:
        monolog:
            type: single
            alias: monolog
            dsn: redis://localhost/1
            logging: false
            options:
                connection_persistent: true
    monolog:
        client: monolog
        key: monolog
        formatter: my_custom_formatter
```

### SwiftMailer spooling ###

You can spool your mails in a redis `LIST` by adding this to your config:

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn: redis://localhost
            logging: false
    swiftmailer:
        client: default
        key: swiftmailer
```

Additionally you have to configure the swiftmailer spool:

Since version 2.2.6 and 2.3.4 of the SwiftmailerBundle you can configure
custom spool implementations using the `service` type:

``` yaml
swiftmailer:
    ...
    spool:
        type: service
        id: webonaute_phpredis.swiftmailer.spool
```

If you are using an older version of the SwiftmailerBundle the following configuration
should work, but this was kind of a hack:

``` yaml
swiftmailer:
    ...
    spool:
        type: redis
```

### Profiler storage ###

To store your profiler data in Redis for Symfony 3 add following to your config:

``` yaml
webonaute_phpredis:
    ...
    profiler_storage:
        client: profiler_storage
        ttl: 3600
```

This will overwrite the `profiler.storage` service.
Prior to [Symfony 4.0 support for Redis was built-in](http://symfony.com/doc/current/profiler/storage.html).

### Complete configuration example ###

``` yaml
webonaute_phpredis:
    clients:
        default:
            type: single
            alias: default
            dsn: redis://localhost
            logging: %kernel.debug%
        cache:
            type: single
            alias: cache
            dsn: redis://localhost/1
            logging: true
        profiler_storage:
            type: single
            alias: profiler_storage
            dsn: redis://localhost/2
            logging: false
        cluster:
            type: single
            alias: cluster
            dsn:
                - redis://127.0.0.1/1
                - redis://127.0.0.2/2
                - redis://pw@/var/run/redis/redis-1.sock/10
                - redis://pw@127.0.0.1:63790/10
            options:
                prefix: foo
                profile: 2.4
                connection_timeout: 10
                connection_persistent: true
                read_write_timeout: 30
                iterable_multibulk: false
                throw_errors: true
                replication: false
    session:
        client: default
        prefix: foo
    doctrine:
        metadata_cache:
            client: cache
            entity_manager: default
            document_manager: default
        result_cache:
            client: cache
            entity_manager: [default, read]
            document_manager: [default, slave1, slave2]
            namespace: "dcrc:"
        query_cache:
            client: cache
            entity_manager: default
        second_level_cache:
            client: cache
            entity_manager: default
    monolog:
        client: cache
        key: monolog
    swiftmailer:
        client: default
        key: swiftmailer
    profiler_storage:
        client: profiler_storage
        ttl: 3600
```
