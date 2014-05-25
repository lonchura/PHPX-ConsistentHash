PHPX-ConsistentHash
===================

PHPX/ConsistentHash is a PHP library which implements [http://en.wikipedia.org/wiki/Consistent_hashing consistent hashing], which is most useful in distributed caching.
The implement of \PHPX\ConsistentHash\Impl\Flexihash refer to Flexihash(https://github.com/pda/flexihash)

Usage Example
-------------

<pre>
&lt;?php
use PHPX\ConsistentHash\Hasher\Flexihash\Crc32Hasher;
use PHPX\ConsistentHash\Impl\Flexihash;
use PHPX\ConsistentHash\TargetUnit;
use PHPX\ConsistentHash\Target\HostTarget;

$hashSpace = new Flexihash(new Crc32Hasher());

// bulk add
$targetUnits = new \ArrayIterator(array(
    new TargetUnit(new HostTarget('redis-a', 6379), 1),
    new TargetUnit(new HostTarget('redis-b', 6379), 3),
    new TargetUnit(new HostTarget('redis-c', 6379), 2.5)
));
$hashSpace->addTargets($targetUnits);

// simple lookup
$hashSpace->lookup('object-a'); // <TargetUnit>"redis-a"
$hashSpace->lookup('object-b'); // <TargetUnit>"redis-b"

// add and remove
$weight = 20.3;
$hashSpace
  ->addTarget(new TargetUnit(new HostTarget('redis-d', 6379), $weight))
  ->removeTarget(new HostTarget('redis-a', 6379));

// lookup with next-best fallback (for redundant writes)
$hashSpace->lookupList('object', 2); // <\Iterator>[<TargetUnit>"redis-b", <TargetUnit>"redis-d"]

// remove redis-b, expect object to hash to redis-d
$hashSpace->removeTarget(new HostTarget('redis-b', 6379));
$hashSpace->lookup('object'); // <TargetUnit>"redis-d"
</pre>