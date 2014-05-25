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

use PHPX\ConsistentHash\Target;
use PHPX\ConsistentHash\RuntimeException;

class StringTarget extends Target {

    /**
     * @var string
     */
    private $string;

    /**
     * @param $string
     * @throws RuntimeException
     */
    public function __construct($string) {
        #if DEVELOPMENT
        if(!is_string($string)) {
            throw new RuntimeException('host is not with string');
        }
        #endif
        $this->string = $string;
    }

    /**
     * The unique identity of target
     *
     * @return string
     */
    public function getIdentity()
    {
        return $this->string;
    }
}