<?php

namespace Zi;

use Zi\Config;
use Zi\Cache;

/**
 * 外部访问 cache
 * TODO
 *  扩展: 支持多种缓存并行
 */
class Cache {

    // 多缓存操作器
    private static $_cacheHandlers = array();
    //切换缓存类型
    private static $_cacheDriver;

    public static function get($key, $expires = 0) {
        self::_init();
        return self::$_cacheHandlers[self::$_cacheDriver]->get($key, $expires);
    }

    public static function set($key, $value, $expires = 0) {
        self::_init();
        return self::$_cacheHandlers[self::$_cacheDriver]->set($key, $value, $expires);
    }

    /**
     * 切换缓存类型
     */
    public static function selectDriver($cacheType = null) {
        self::$_cacheDriver = $cacheType ? $cacheType : Config::get('common.cacheType', 'file');
    }

    private static function _init() {
        if (!self::$_cacheDriver)
            self::selectDriver(); // 选择默认的
        if (isset(self::$_cacheHandlers[self::$_cacheDriver]))
            return;

        switch (self::$_cacheDriver) {
            case 'file':
                self::$_cacheHandlers[self::$_cacheDriver] = new Cache\CacheFile();
                break;
            case 'memcache':
                self::$_cacheHandlers[self::$_cacheDriver] = new Cache\CacheMemcache();
                break;
            case 'memcached':
                self::$_cacheHandlers[self::$_cacheDriver] = new Cache\CacheMemcached();
                break;
            case 'redis':
                self::$_cacheHandlers[self::$_cacheDriver] = new Cache\CacheRedis();
                break;
            default :
                self::$_cacheHandlers[self::$_cacheDriver] = new Cache\CacheFile();
        }
        self::$_cacheHandlers[self::$_cacheDriver]->open();
    }

}
