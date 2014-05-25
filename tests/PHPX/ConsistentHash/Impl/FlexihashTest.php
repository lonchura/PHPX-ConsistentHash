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
use PHPX\ConsistentHash\Hasher;
use PHPX\ConsistentHash\Target;
use PHPX\ConsistentHash\TargetUnit;

/**
 * Class FlexihashTest
 * @package PHPX\ConsistentHash\Impl
 * @ref Flexihash(https://github.com/pda/flexihash)
 */
class FlexihashTest extends \PHPUnit_Framework_TestCase {

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

    public function testGetAllTargetsEmpty() {
        $this->assertEquals($this->hashSpace->getAllTargets(), array());
    }

    public function testAddTargetThrowsExceptionOnDuplicateTarget() {
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
               ->method('getIdentity')
               ->will($this->returnValue('t-a'));

        $this->hashSpace->addTarget(new TargetUnit($target, 1));
        $this->setExpectedException('\PHPX\ConsistentHash\RuntimeException');
        $this->hashSpace->addTarget(new TargetUnit($target, 2));
    }

    public function testAddTargetAndGetAllTargets() {
        $targets = array('t-a', 't-b', 't-c');
        foreach($targets as $targetIdentity) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue($targetIdentity));

            $this->hashSpace->addTarget(new TargetUnit($target, 1));
        }

        $i = 0;
        foreach($this->hashSpace->getAllTargets() as $targetUnit) {
            $this->assertEquals($targets[$i++], $targetUnit->getTarget()->getIdentity());
        }
    }

    public function testAddTargetsAndGetAllTargets() {
        $targets = array('t-a', 't-b', 't-c');
        $targetUnits = new \ArrayIterator();
        foreach($targets as $targetIdentity) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue($targetIdentity));

            $targetUnits->append(new TargetUnit($target));
        }
        $this->hashSpace->addTargets($targetUnits);

        $i = 0;
        foreach($this->hashSpace->getAllTargets() as $targetUnit) {
            $this->assertEquals($targets[$i++], $targetUnit->getTarget()->getIdentity());
        }
    }

    public function testRemoveTarget() {
        $targets = array('t-a', 't-b', 't-c');
        foreach($targets as $index=>$targetIdentity) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                   ->method('getIdentity')
                   ->will($this->returnValue($targetIdentity));

            $this->hashSpace->addTarget(new TargetUnit($target));
        }

        // target move
        $targetMove = $this->getMock('\PHPX\ConsistentHash\Target');
        $targetMove->expects($this->any())
                   ->method('getIdentity')
                   ->will($this->returnValue('t-b'));
        $this->hashSpace->removeTarget($targetMove);

        $i = 0;
        $leftTargets = array('t-a', 't-c');
        foreach($this->hashSpace->getAllTargets() as $targetUnit) {
            $this->assertEquals($leftTargets[$i++], $targetUnit->getTarget()->getIdentity());
        }
    }

    public function testRemoveTargetFailsOnMissingTarget() {
        $targetUnregistered = $this->getMock('\PHPX\ConsistentHash\Target');
        $targetUnregistered->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue('not-there'));

        $this->setExpectedException('\PHPX\ConsistentHash\RuntimeException');
        $this->hashSpace->removeTarget($targetUnregistered);
    }

    public function testHashSpaceRepeatableLookups() {
        foreach (range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $this->hashSpace->addTarget(new TargetUnit($target));
        }

        $this->assertEquals($this->hashSpace->lookup('t1'), $this->hashSpace->lookup('t1'));
        $this->assertEquals($this->hashSpace->lookup('t2'), $this->hashSpace->lookup('t2'));
    }

    public function testHashSpaceLookupsAreValidTargets() {
        $targets = new \ArrayIterator();
        $targetIdentitys = array();
        foreach(range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $targets->append(new TargetUnit($target));
            $targetIdentitys[] = "target$i";
        }

        $this->hashSpace->addTargets($targets);

        foreach(range(1, 10) as $i) {
            $this->assertTrue(in_array($this->hashSpace->lookup("r$i")->getTarget()->getIdentity(), $targetIdentitys),
                'target must be in list of targets');
        }
    }

    public function testHashSpaceConsistentLookupsAfterAddingAndRemoving() {
        foreach(range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $this->hashSpace->addTarget(new TargetUnit($target));
        }

        $results1 = array();
        foreach(range(1, 100) as $i) {
            $results1[] = $this->hashSpace->lookup("r$i")->getTarget()->getIdentity();
        }

        $newTarget = $this->getMock('\PHPX\ConsistentHash\Target');
        $newTarget->expects($this->any())
               ->method('getIdentity')
               ->will($this->returnValue("new-target"));
        $this->hashSpace
             ->addTarget(new TargetUnit($newTarget))
             ->removeTarget($newTarget)
             ->addTarget(new TargetUnit($newTarget))
             ->removeTarget($newTarget);

        $results2 = array();
        foreach(range(1, 100) as $i) {
            $results2[] = $this->hashSpace->lookup("r$i")->getTarget()->getIdentity();
        }

        // This is probably optimistic, as adding/removing a target may
        // clobber existing targets and is not expected to restore them.
        $this->assertEquals($results1, $results2);
    }

    public function testHashSpaceConsistentLookupsWithNewInstance() {
        foreach(range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $this->hashSpace->addTarget(new TargetUnit($target));
        }

        $results1 = array();
        foreach(range(1, 100) as $i) {
            $results1[] = $this->hashSpace->lookup("r$i")->getTarget()->getIdentity();
        }

        $hashSpace2 = new Flexihash(new Crc32Hasher());
        foreach(range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $hashSpace2->addTarget(new TargetUnit($target));
        }

        $results2 = array();
        foreach(range(1, 100) as $i) {
            $results2[] = $hashSpace2->lookup("r$i")->getTarget()->getIdentity();
        }

        $this->assertEquals($results1, $results2);
    }

    public function testGetMultipleTargets() {
        foreach(range(1, 10) as $i) {
            $target = $this->getMock('\PHPX\ConsistentHash\Target');
            $target->expects($this->any())
                ->method('getIdentity')
                ->will($this->returnValue("target$i"));
            $this->hashSpace->addTarget(new TargetUnit($target));
        }

        $targets = $this->hashSpace->lookupList('resource', 2);

        $this->assertTrue($targets instanceof \Iterator, 'lookupList return type not \Iterator');
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals(
            current($targets)->getTarget()->getIdentity(),
            next($targets)->getTarget()->getIdentity()
        );
    }

    public function testGetMultipleTargetsWithOnlyOneTarget() {
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("single-target"));
        $this->hashSpace->addTarget(new TargetUnit($target));

        $targets = $this->hashSpace->lookupList('resource', 2);

        $this->assertTrue($targets instanceof \Iterator, 'lookupList return type not \Iterator');
        $this->assertEquals(count($targets), 1);
        $this->assertEquals(current($targets)->getTarget()->getIdentity(), 'single-target');
    }

    public function testGetMoreTargetsThanExist() {
        $target1 = $this->getMock('\PHPX\ConsistentHash\Target');
        $target1->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("target1"));

        $target2 = $this->getMock('\PHPX\ConsistentHash\Target');
        $target2->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("target2"));

        $this->hashSpace->addTarget(new TargetUnit($target1));
        $this->hashSpace->addTarget(new TargetUnit($target2));

        $targets = $this->hashSpace->lookupList('resource', 4);

        $this->assertTrue($targets instanceof \Iterator, 'lookupList return type not \Iterator');
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals(
            current($targets)->getTarget()->getIdentity(),
            next($targets)->getTarget()->getIdentity()
        );
    }

    public function testGetMultipleTargetsNeedingToLoopToStart() {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t1"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(20);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t2"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(30);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t3"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(40);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t4"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(50);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t5"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(35);
        $targets = $hashSpace->lookupList('resource', 4);

        $i = 0;
        $expectedTargets = array('t4', 't5', 't1', 't2');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }
    }

    public function testGetMultipleTargetsWithoutGettingAnyBeforeLoopToStart() {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t1"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(20);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t2"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(30);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t3"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(100);
        $targets = $hashSpace->lookupList('resource', 2);

        $i = 0;
        $expectedTargets = array('t1', 't2');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }
    }

    public function testGetMultipleTargetsWithoutNeedingToLoopToStart() {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t1"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(20);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t2"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(30);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t3"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(15);
        $targets = $hashSpace->lookupList('resource', 2);

        $i = 0;
        $expectedTargets = array('t2', 't3');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }
    }

    public function testFallbackPrecedenceWhenServerRemoved() {
        $mockHasher = new MockHasher();
        $hashSpace = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $target = $this->getMock('\PHPX\ConsistentHash\Target');
        $target->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t1"));
        $hashSpace->addTarget(new TargetUnit($target));

        $mockHasher->setHashValue(20);
        $target2 = $this->getMock('\PHPX\ConsistentHash\Target');
        $target2->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t2"));
        $hashSpace->addTarget(new TargetUnit($target2));

        $mockHasher->setHashValue(30);
        $target3 = $this->getMock('\PHPX\ConsistentHash\Target');
        $target3->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue("t3"));
        $hashSpace->addTarget(new TargetUnit($target3));

        $mockHasher->setHashValue(15);
        $this->assertEquals($hashSpace->lookup('resource')->getTarget()->getIdentity(), 't2');
        $targets = $hashSpace->lookupList('resource', 3);
        $i = 0;
        $expectedTargets = array('t2', 't3', 't1');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }

        $hashSpace->removeTarget($target2);
        $targets = $hashSpace->lookupList('resource', 3);
        $i = 0;
        $expectedTargets = array('t3', 't1');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }

        $hashSpace->removeTarget($target3);
        $targets = $hashSpace->lookupList('resource', 3);
        $i = 0;
        $expectedTargets = array('t1');
        foreach($targets as $targetUnit) {
            $this->assertEquals($targetUnit->getTarget()->getIdentity(), $expectedTargets[$i++]);
        }
    }
}

class MockHasher implements Hasher
{
    private $_hashValue;

    public function setHashValue($hash)
    {
        $this->_hashValue = $hash;
    }

    public function hash($value)
    {
        return $this->_hashValue;
    }

}