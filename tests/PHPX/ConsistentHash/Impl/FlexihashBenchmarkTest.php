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

use PHPX\ConsistentHash\Hasher\Flexihash\Crc32Hasher;
use PHPX\ConsistentHash\Hasher\Flexihash\Md5Hasher;
use PHPX\ConsistentHash\Target\StringTarget;
use PHPX\ConsistentHash\TargetUnit;

/**
 * Class FlexihashBenchmarkTest
 * @package PHPX\ConsistentHash\Impl
 * @ref Flexihash(https://github.com/pda/flexihash)
 */
class FlexihashBenchmarkTest extends \PHPUnit_Framework_TestCase {
    private $_targets = 10;
    private $_lookups = 1000;

    /**
     * @var \PHPX\ConsistentHash\Impl\Flexihash
     */
    private $hashSpace;

    protected function setUp()
    {
        parent::setUp();
        $this->hashSpace = new Flexihash(new Crc32Hasher());
    }

    protected function tearDown()
    {
        $this->hashSpace = null;
        parent::tearDown();
    }

    public function testAddTargetWithNonConsistentHash() {
        $results1 = array();
        foreach (range(1, $this->_lookups) as $i) $results1[$i] = $this->_basicHash("t$i", 10);

        $results2 = array();
        foreach (range(1, $this->_lookups) as $i) $results2[$i] = $this->_basicHash("t$i", 11);

        $differences = 0;
        foreach (range(1, $this->_lookups) as $i) if ($results1[$i] !== $results2[$i]) $differences++;

        $percent = round($differences / $this->_lookups * 100);
        print("NonConsistentHash: {$percent}% of lookups changed " .
            "after adding a target to the existing {$this->_targets}\n");
    }

    public function testRemoveTargetWithNonConsistentHash() {
        $results1 = array();
        foreach (range(1, $this->_lookups) as $i) $results1[$i] = $this->_basicHash("t$i", 10);

        $results2 = array();
        foreach (range(1, $this->_lookups) as $i) $results2[$i] = $this->_basicHash("t$i", 9);

        $differences = 0;
        foreach (range(1, $this->_lookups) as $i) if ($results1[$i] !== $results2[$i]) $differences++;

        $percent = round($differences / $this->_lookups * 100);
        print("NonConsistentHash: {$percent}% of lookups changed " .
            "after removing 1 of {$this->_targets} targets\n");
    }

    public function testHopeAddingTargetDoesNotChangeMuchWithCrc32Hasher() {
        foreach (range(1,$this->_targets) as $i) {
            $this->hashSpace->addTarget(new TargetUnit(new StringTarget("target$i")));
        }

        $results1 = array();
        foreach (range(1, $this->_lookups) as $i) {
            $results1[$i] = $this->hashSpace->lookup("t$i");
        }

        $this->hashSpace->addTarget(new TargetUnit(new StringTarget("target-new")));

        $results2 = array();
        foreach (range(1, $this->_lookups) as $i) {
            $results2[$i] = $this->hashSpace->lookup("t$i");
        }

        $differences = 0;
        foreach (range(1, $this->_lookups) as $i) if ($results1[$i] !== $results2[$i]) $differences++;

        $percent = round($differences / $this->_lookups * 100);
        print("ConsistentHash: {$percent}% of lookups changed " .
            "after adding a target to the existing {$this->_targets}\n");
    }

    public function testHopeRemovingTargetDoesNotChangeMuchWithCrc32Hasher() {
        foreach (range(1,$this->_targets) as $i) {
            $this->hashSpace->addTarget(new TargetUnit(new StringTarget("target$i")));
        }

        $results1 = array();
        foreach (range(1, $this->_lookups) as $i) {
            $results1[$i] = $this->hashSpace->lookup("t$i");
        }

        $this->hashSpace->removeTarget(new StringTarget("target1"));

        $results2 = array();
        foreach (range(1, $this->_lookups) as $i) {
            $results2[$i] = $this->hashSpace->lookup("t$i");
        }

        $differences = 0;
        foreach (range(1, $this->_lookups) as $i) if ($results1[$i] !== $results2[$i]) $differences++;

        $percent = round($differences / $this->_lookups * 100);
        print("ConsistentHash: {$percent}% of lookups changed " .
            "after removing 1 of {$this->_targets} targets\n");
    }

    public function testHashDistributionWithCrc32Hasher() {
        foreach (range(1,$this->_targets) as $i) {
            $this->hashSpace->addTarget(new TargetUnit(new StringTarget("target$i")));
        }

        $results = array();
        foreach (range(1, $this->_lookups) as $i) {
            $results[$i] = $this->hashSpace->lookup("t$i")->getTarget()->getIdentity();
        }

        $distribution = array();
        foreach ($this->hashSpace->getAllTargets() as $targetUnit) {
            $distribution[$targetUnit->getTarget()->getIdentity()] = count(array_keys($results, $targetUnit->getTarget()->getIdentity()));
        }

        print(sprintf(
            "Distribution of %d lookups per target (min/max/median/avg): %d/%d/%d/%d\n",
            $this->_lookups / $this->_targets,
            min($distribution),
            max($distribution),
            round($this->_median($distribution)),
            round(array_sum($distribution) / count($distribution))
        ));
    }

    public function testHasherSpeed()
    {
        $hashCount = 1000000;

        $md5Hasher = new Md5Hasher();
        $crc32Hasher = new Crc32Hasher();

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; $i++)
            $md5Hasher->hash("test$i");
        $timeMd5 = microtime(true) - $start;

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; $i++)
            $crc32Hasher->hash("test$i");
        $timeCrc32 = microtime(true) - $start;

        print(sprintf(
            "Hashers timed over %d hashes (MD5 / CRC32): %f / %f\n",
            $hashCount,
            $timeMd5,
            $timeCrc32
        ));
    }

    private function _basicHash($value, $targets)
    {
        return abs(crc32($value) % $targets);
    }

    /**
     * @param array $values
     * @internal param array $array list of numeric values
     * @return numeric
     */
    private function _median(array $values)
    {
        $values = array_values($values);
        sort($values);

        $count = count($values);
        $middleFloor = floor($count / 2);

        if ($count % 2 == 1)
        {
            return $values[$middleFloor];
        }
        else
        {
            return ($values[$middleFloor-1] + $values[$middleFloor]) / 2;
        }
    }
}
 