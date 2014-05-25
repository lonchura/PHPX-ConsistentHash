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
 * Interface StdInterface
 * @package PHPX\ConsistentHash
 */
interface StdInterface {

    /**
     * @param TargetUnit $targetUnit
     * @chainable
     */
    public function addTarget(TargetUnit $targetUnit);

    /**
     * @param \Iterator<TargetUnit> $targets
     * @chainable
     */
    public function addTargets(\Iterator $targets);

    /**
     * Remove a target.
     * @param \PHPX\ConsistentHash\Target
     * @return
     * @chainable
     */
    public function removeTarget(Target $target);

    /**
     * A list of all targets
     * @return \Iterator<TargetUnit>
     */
    public function getAllTargets();

    /**
     * Looks up the target for the given resource.
     * @param string $resource
     * @return TargetUnit
     */
    public function lookup($resource);

    /**
     * Get a list of targets for the resource, in order of precedence.
     * Up to $targetCount targets are returned, less if there are fewer in total.
     *
     * @param string $resource
     * @param int $targetCount
     * @return \Iterator<TargetUnit>
     */
    public function lookupList($resource, $targetCount=1);
}