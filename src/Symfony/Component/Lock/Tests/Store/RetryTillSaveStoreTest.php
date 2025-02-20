<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Tests\Store;

use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\RetryTillSaveStore;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @group legacy
 */
class RetryTillSaveStoreTest extends AbstractStoreTestCase
{
    use BlockingStoreTestTrait;

    public function getStore(): PersistingStoreInterface
    {
        $redis = new \Predis\Client(array_combine(['host', 'port'], explode(':', getenv('REDIS_HOST')) + [1 => null]));
        try {
            $redis->connect();
        } catch (\Exception $e) {
            self::markTestSkipped($e->getMessage());
        }

        return new RetryTillSaveStore(new RedisStore($redis), 100, 100);
    }
}
