<?php
/**
 * PHP Extension Library (https://github.com/PsyduckMans/PHPX-ConsistentHash)
 *
 * @link      https://github.com/PsyduckMans/PHPX-ConsistentHash for the canonical source repository
 * @copyright Copyright (c) 2014 PsyduckMans (https://ninth.not-bad.org)
 * @license   https://github.com/PsyduckMans/PHPX-ConsistentHash/blob/master/LICENSE MIT
 * @author    Psyduck.Mans
 */

namespace PHPX\ConsistentHash;

/**
 * Class TargetUnit
 * @package PHPX\ConsistentHash
 */
class TargetUnit {

    /**
     * @var Target
     */
    private $target;

    /**
     * @var int
     */
    private $weight;

    /**
     * @param Target $target
     * @param int $weight
     * @throws RuntimeException
     */
    public function __construct(Target $target, $weight=1) {
        #if DEVELOPMENT
        if(!(is_integer($weight) || is_float($weight))) {
            throw new RuntimeException('The weight of Target is not a integer');
        }
        if(is_nan($weight)) {
            throw new RuntimeException('The weight of Target is NAN');
        }
        if($weight<=0) {
            throw new RuntimeException('The weight of Target is not a positive real number');
        }
        #endif
        $this->target = $target;
        $this->weight = $weight;
    }

    /**
     * @param \PHPX\ConsistentHash\Target $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return \PHPX\ConsistentHash\Target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param int $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }
}