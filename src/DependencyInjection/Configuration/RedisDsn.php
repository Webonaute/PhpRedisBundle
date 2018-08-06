<?php

namespace WebonautePhpredisBundle\DependencyInjection\Configuration;

/**
 * RedisDsn
 */
class RedisDsn implements RedisDsnInterface
{
    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $socket;

    /**
     * @var int
     */
    protected $database;

    /**
     * @var int
     */
    protected $weight;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Constructor
     *
     * @param string $dsn
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
        $this->parseDsn($dsn);
    }

    /**
     * @param string $dsn
     */
    protected function parseDsn($dsn): void
    {
        $dsn = str_replace('redis://', '', $dsn); // remove "redis://"
        if (false !== $pos = strrpos($dsn, '@')) {
            // parse password
            $password = substr($dsn, 0, $pos);

            if (false !== strpos($password, ':')) {
                [, $password] = explode(':', $password, 2);
            }

            $this->password = urldecode($password);

            $dsn = substr($dsn, $pos + 1);
        }
        $dsn = preg_replace_callback('/\?(.*)$/', [$this, 'parseParameters'], $dsn); // parse parameters
        if (preg_match('#^(.*)/(\d+|%[^%]+%|env_\w+_[[:xdigit:]]{32,})$#', $dsn, $matches)) {
            // parse database
            $this->database = is_numeric($matches[2]) ? (int)$matches[2] : null;
            $dsn = $matches[1];
        }
        if (preg_match('#^([^:]+)(:(\d+|%[^%]+%|env_\w+_[[:xdigit:]]{32,}))?$#', $dsn, $matches)) {
            if (!empty($matches[1])) {
                // parse host/ip or socket
                if ('/' === $matches[1]{0}) {
                    $this->socket = $matches[1];
                } else {
                    $this->host = $matches[1];
                }
            }
            if (null === $this->socket && !empty($matches[3])) {
                // parse port
                $this->port = is_numeric($matches[3] ) ? (int)$matches[3] : null;
            }
        }
    }

    /**
     * @return int|null
     */
    public function getDatabase(): ?int
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getWeight(): ?int
    {
        return $this->weight;
    }

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getPersistentId(): ?string
    {
        return md5($this->dsn);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (0 !== strpos($this->dsn, 'redis://')) {
            return false;
        }

        if ((null !== $this->getHost() && null !== $this->getPort())
            || null !== $this->getSocket()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): ?int
    {
        if (null !== $this->socket) {
            return null;
        }

        return $this->port ?: 6379;
    }

    /**
     * @return string
     */
    public function getSocket(): ?string
    {
        return $this->socket;
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    protected function parseParameters($matches): string
    {
        parse_str($matches[1], $params);

        foreach ($params as $key => $val) {
            if (!$val) {
                continue;
            }
            if ($key === 'weight') {
                $this->weight = (int)$val;
            }
            if ($key === 'alias') {
                $this->alias = $val;
            }
        }

        return '';
    }
}
