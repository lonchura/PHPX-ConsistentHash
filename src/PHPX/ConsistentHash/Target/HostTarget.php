<?php
/**
 * PHP Extension Library (https://github.com/PsyduckMans/PHPX-ConsistentHash)
 *
 * @link      https://github.com/PsyduckMans/PHPX-ConsistentHash for the canonical source repository
 * @copyright Copyright (c) 2014 PsyduckMans (https://ninth.not-bad.org)
 * @license   https://github.com/PsyduckMans/PHPX-ConsistentHash/blob/master/LICENSE MIT
 * @author    Psyduck.Mans
 */

namespace PHPX\ConsistentHash\Target;

use PHPX\ConsistentHash\RuntimeException;
use PHPX\ConsistentHash\Target;

/**
 * Class HostTarget
 * @package PHPX\ConsistentHash\Target
 */
class HostTarget extends Target {
    /**
     * host
     *
     * @var string
     */
    private $host;

    /**
     * port
     *
     * @var string
     */
    private $port;

    /**
     * __construct
     *
     * @param string $host
     * @param int $port
     * @throws \PHPX\ConsistentHash\RuntimeException
     */
    public function __construct($host, $port) {
        #if DEVELOPMENT
        if(!is_string($host)) {
            throw new RuntimeException('host is not with string');
        }
        if(!gethostbynamel($host)) {
            throw new RuntimeException('host:'.$host.' could not be resolved');
        }
        if(!is_integer($port)) {
            throw new RuntimeException('illegal port:'.$port.'');
        }
        #endif
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * The unique identity of the target
     *
     * @return string
     */
    public function getIdentity()
    {
        return $this->host.':'.$this->port;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }
}