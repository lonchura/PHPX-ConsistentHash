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
 * Interface Hasher
 * @package PHPX\ConsistentHash
 */
interface Hasher {

    /**
     * Hashes the given string into a 32bit address space.
     *
     * Note that the output may be more than 32bits of raw data, for example
     * hexidecimal characters representing a 32bit value.
     *
     * The data must have 0xFFFFFFFF possible values, and be sortable by
     * PHP sort functions using SORT_REGULAR.
     *
     * @param string
     * @return mixed A sortable format with 0xFFFFFFFF possible values
     * @ref Flexihash(https://github.com/pda/flexihash)
     */
    public function hash($string);
}