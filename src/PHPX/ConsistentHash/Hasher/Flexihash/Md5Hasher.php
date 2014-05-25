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
 * Class Md5Hasher
 * Uses MD5 to hash a value into a 32bit binary string data address space.
 *
 * @package PHPX\ConsistentHash\Hasher\Flexihash
 */
class Md5Hasher implements Hasher {

    /* (non-phpdoc)
	 * @see PHPX\ConsistentHash\Hasher::hash()
     */
    public function hash($string)
    {
        return substr(md5($string), 0, 8); // 8 hexits = 32bit

        // 4 bytes of binary md5 data could also be used, but
        // performance seems to be the same.
    }
}