<?php

namespace Zi\Cache;

use Zi\Config;
use Predis\Client;

class CacheRedis implements CacheInterface {

    private $_prefix = 'rcache_'; //缓存的前缀
    private $_redis = null;

    public function __construct() {
    }

    public function open() {
        $this->_redis = new Client(Config::get('redis'));
    }

    /**
     * set
     * @param type $key
     * @param type $value
     * @param type $expires 单位 s
     * @return type
     */
    public function set($key, $value, $expires = 0) {
        if ($expires)
            return $this->_redis->setex($this->_prefix . $key, $expires, $value);

        return $this->_redis->set($this->_prefix . $key, $value);
    }

    /**
     * get
     * @param type $key
     * @param type $expires 此参数无作用
     */
    public function get($key, $expires = 0) {
        return $this->_redis->get($this->_prefix . $key);
    }

    public function delete($key) {
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                return $this->_redis->del($this->_prefix . $v);
            }
            return true;
        }
        return $this->_redis->del($this->_prefix . $key);
    }

    public function gc() {
        return false;
    }

}
