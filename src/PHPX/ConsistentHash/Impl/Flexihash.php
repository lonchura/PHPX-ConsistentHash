<?php
/**
 * PHP Extension Library (https://github.com/PsyduckMans/PHPX-ConsistentHash)
 *
 * @link      https://github.com/PsyduckMans/PHPX-ConsistentHash for the canonical source repository
 * @copyright Copyright (c) 2014 PsyduckMans (https://ninth.not-bad.org)
 * @license   https://github.com/PsyduckMans/PHPX-ConsistentHash/blob/master/LICENSE MIT
 * @author    Psyduck.Mans
 */

namespace PHPX\ConsistentHash\Impl;

use PHPX\ConsistentHash\RuntimeException;
use PHPX\ConsistentHash\StdInterface;
use PHPX\ConsistentHash\Target;
use PHPX\ConsistentHash\TargetUnit;

/**
 * Class Flexihash
 * @package PHPX\ConsistentHash\Impl
 * @ref Flexihash(https://github.com/pda/flexihash)
 */
class Flexihash implements StdInterface {

    /**
     * The number of positions to hash each target to.
     * @var int
     */
    public static $DEFAULT_REPLICAS = 64;

    /**
     * The number of positions to hash each target to.
     *
     * @var int
     */
    private $replicas = 64;

    /**
     * The hash algorithm, encapsulated in a \PHPX\ConsistentHash\Hasher implementation.
     * @var \PHPX\ConsistentHash\Hasher
     */
    private $hasher;

    /**
     * Internal counter for current number of targets.
     * @var int
     */
    private $targetCount;

    /**
     * Targets set
     *
     * @var array
     *      {
     *          identity => TargetUnit,
     *          ...
     *      }
     */
    private $targetSet = array();

    /**
     * Internal map of positions (hash outputs) to targets
     *
     * @var array
     *      {
     *          position => targetIdentity,
     *          ...
     *      }
     */
    private $positionToTarget = array();

    /**
     * Internal map of targets to lists of positions that target is hashed to.
     *
     * @var array
     *      {
     *          targetIdentity => [ position, position, ... ],
     *          ...
     *      }
     */
    private $targetToPositions = array();

    /**
     * Whether the internal map of positions to targets is already sorted.
     *
     * @var boolean
     */
    private $positionToTargetSorted = false;

    /**
     * Constructor
     *
     * @param \PHPX\ConsistentHash\Hasher $hasher
     * @param int $replicas Amount of positions to hash each target to.
     * @throws RuntimeException
     */
    public function __construct(\PHPX\ConsistentHash\Hasher $hasher, $replicas = null) {
        if (!$replicas) {
            $replicas = self::$DEFAULT_REPLICAS;
        }
        #if DEVELOPMENT
        else {
            if(!is_integer($replicas) || $replicas<1) {
                throw new RuntimeException('The replicas of Target is not an integer');
            }
        }
        #endif
        $this->hasher = $hasher;
        $this->replicas = $replicas;
    }

    /**
     * Add a target.
     *
     * @param TargetUnit $targetUnit
     * @throws \PHPX\ConsistentHash\RuntimeException
     * @return $this
     * @chainable
     */
    public function addTarget(TargetUnit $targetUnit)
    {
        $targetIdentity = $targetUnit->getTarget()->getIdentity();
        $targetWeight = $targetUnit->getWeight();

        #if DEVELOPMENT
        if (isset($this->targetSet[$targetIdentity]))
        {
            throw new RuntimeException("Target '$targetIdentity' already exists.");
        }
        #endif
        $this->targetSet[$targetIdentity] = $targetUnit;
        $this->targetToPositions[$targetIdentity] = array();

        // hash the target into multiple positions
        for ($i = 0; $i<round($this->replicas*$targetWeight); $i++)
        {
            $position = $this->hasher->hash($targetIdentity.$i);
            $this->positionToTarget[$position] = $targetIdentity;   // lookup
            $this->targetToPositions[$targetIdentity][]= $position; // target removal
        }

        $this->positionToTargetSorted = false;
        $this->targetCount++;

        return $this;
    }

    /**
     * Add a list of targets.
     *
     * @param \Iterator<TargetUnit> $targets
     * @chainable
     */
    public function addTargets(\Iterator $targets)
    {
        foreach($targets as $targetUnit) {
            $this->addTarget($targetUnit);
        }
        return $this;
    }

    /**
     * Remove a target.
     *
     * @param \PHPX\ConsistentHash\Target $target
     * @throws \PHPX\ConsistentHash\RuntimeException
     * @return $this
     * @chainable
     */
    public function removeTarget(Target $target)
    {
        $targetIdentity = $target->getIdentity();
        if(!isset($this->targetToPositions[$targetIdentity])) {
            throw new RuntimeException("Target '$targetIdentity' does not exist.");
        }

        foreach($this->targetToPositions[$targetIdentity] as $position) {
            unset($this->positionToTarget[$position]);
        }

        unset($this->targetToPositions[$targetIdentity]);
        unset($this->targetSet[$targetIdentity]);

        $this->targetCount--;

        return $this;
    }

    /**
     * A list of all targets
     *
     * @return \Iterator<TargetUnit>
     */
    public function getAllTargets()
    {
        return array_values($this->targetSet);
    }

    /**
     * Looks up the target for the given resource.
     *
     * @param string $resource
     * @throws \PHPX\ConsistentHash\RuntimeException
     * @return TargetUnit
     */
    public function lookup($resource)
    {
        $targets = $this->lookupList($resource, 1);
        #if DEVELOPMENT
        if (empty($targets)) {
            throw new RuntimeException('No targets exist');
        }
        #endif
        return current($targets);
    }

    /**
     * Get a list of targets for the resource, in order of precedence.
     * Up to $targetCount targets are returned, less if there are fewer in total.
     *
     * @param string $resource
     * @param int $targetCount
     * @throws \PHPX\ConsistentHash\RuntimeException
     * @return \Iterator<TargetUnit>
     */
    public function lookupList($resource, $targetCount = 1)
    {
        #if DEVELOPMENT
        if (!$targetCount) {
            throw new RuntimeException('Invalid count of target requested');
        }
        #endif

        $results = new \ArrayIterator();
        // handle no targets
        if (empty($this->targetSet)) {
            return $results;
        }

        // optimize single target
        if ($this->targetCount == 1) {
            return new \ArrayIterator(array_values($this->targetSet));
        }

        // hash resource to a position
        $resourcePosition = $this->hasher->hash($resource);

        $resultFlags = array();
        $collect = false;

        $this->_sortPositionTargets();

        // search values above the resourcePosition
        foreach ($this->positionToTarget as $position => $targetIdentity) {
            // start collecting targets after passing resource position
            if (!$collect && $position > $resourcePosition) {
                $collect = true;
            }

            // only collect the first instance of any target
            if ($collect && !in_array($targetIdentity, $resultFlags)) {
                $resultFlags[]= $targetIdentity;
                $results->append($this->targetSet[$targetIdentity]);
            }

            // return when enough results, or list exhausted
            if (count($results) == $targetCount || count($results) == $this->targetCount)
            {
                return $results;
            }
        }

        // loop to start - search values below the resourcePosition
        foreach ($this->positionToTarget as $position => $targetIdentity) {
            if (!in_array($targetIdentity, $resultFlags))
            {
                $resultFlags[]= $targetIdentity;
                $results->append($this->targetSet[$targetIdentity]);
            }

            // return when enough results, or list exhausted
            if (count($results) == $targetCount || count($results) == $this->targetCount)
            {
                return $results;
            }
        }

        // return results after iterating through both "parts"
        return $results;
    }

    /**
     * Sorts the internal mapping (positions to targets) by position
     */
    private function _sortPositionTargets() {
        // sort by key (position) if not already
        if (!$this->positionToTargetSorted)
        {
            ksort($this->positionToTarget, SORT_REGULAR);
            $this->positionToTargetSorted = true;
        }
    }
}