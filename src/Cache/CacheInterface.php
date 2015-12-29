<?php

namespace Zi\Cache;

/**
 * CacheInterface
 */
interface CacheInterface {

    public function open();

    public function get($key, $expires);

    public function set($key, $value, $expires);

    public function delete($key);

    public function gc();
}
