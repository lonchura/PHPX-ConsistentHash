<?php
/**
 * PHP Extension Library (https://github.com/PsyduckMans/PHPX-ConsistentHash)
 *
 * @link      https://github.com/PsyduckMans/PHPX-ConsistentHash for the canonical source repository
 * @copyright Copyright (c) 2014 PsyduckMans (https://ninth.not-bad.org)
 * @license   https://github.com/PsyduckMans/PHPX-ConsistentHash/blob/master/LICENSE MIT
 * @author    Psyduck.Mans
 */

namespace PHPX\ConsistentHash\Hasher\Flexihash;

use PHPX\ConsistentHash\Hasher;

/**
 * Class Crc32Hasher
 * Uses CRC32 to hash a value into a signed 32bit int address space.
 * Under 32bit PHP this (safely) overflows into negatives ints.
 *
 * @package PHPX\ConsistentHash\Hasher\Flexihash
 */
class Crc32Hasher implements Hasher {

    /* (non-phpdoc)
	 * @see PHPX\ConsistentHash\Hasher::hash()
     */
    public function hash($string)
    {
        return crc32($string);
    }
}