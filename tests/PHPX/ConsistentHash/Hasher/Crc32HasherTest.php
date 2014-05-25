<?php
/**
 * PHP Extension Library (https://github.com/PsyduckMans/PHPX-ConsistentHash)
 *
 * @link      https://github.com/PsyduckMans/PHPX-ConsistentHash for the canonical source repository
 * @copyright Copyright (c) 2014 PsyduckMans (https://ninth.not-bad.org)
 * @license   https://github.com/PsyduckMans/PHPX-ConsistentHash/blob/master/LICENSE MIT
 * @author    Psyduck.Mans
 */

namespace PHPX\ConsistentHash\Hasher;
use PHPX\ConsistentHash\Hasher\Flexihash\Crc32Hasher;

/**
 * Class Crc32HasherTest
 * @package PHPX\ConsistentHash\Hasher
 * @ref Flexihash(https://github.com/pda/flexihash)
 */
class Crc32HasherTest extends \PHPUnit_Framework_TestCase {

    public function testHash() {
        $hasher = new Crc32Hasher();
        $result1 = $hasher->hash('test');
        $result2 = $hasher->hash('test');
        $result3 = $hasher->hash('different');

        $this->assertEquals($result1, $result2);
        $this->assertNotEquals($result1, $result3); // fragile but worthwhile
    }
}