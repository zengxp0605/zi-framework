<?php

namespace Zi;

define('LOG_PATH', '../data/runtime/log/');

/**
 * log4p
 * log的特殊配置
 */
class Log {

    /**
     * 写入日志
     * @param type $word
     * @param type $path
     * @return type
     */
    public static final function write($word = '', $path = 'log.txt') {
        return self::fileWrite($path
                        , '执行日期：' . strftime('%Y-%m-%d %H:%M:%S', time())
                        . "\n" . print_r($word, true) . "\n"
                        , FILE_APPEND | LOCK_EX, LOG_PATH);
    }

    /**
     * 开发者调试
     * @param type $word
     * @return type
     */
    public static final function debug($word) {
        return self::write($word, 'debug.txt');
    }

    /**
     * 输出信息，通常是程序自我监控级别
     * @param type $word
     * @return type
     */
    public static final function info($word) {
        return self::write($word, 'info.txt');
    }

    /**
     * 输出警告
     * @param type $word
     * @return type
     */
    public static final function warn($word) {
        return self::write($word, 'warn.txt');
    }

    /**
     * 输入运行期错误
     * @param type $word
     * @return type
     */
    public static final function error($word) {
        return self::write($word, 'error.txt');
    }

    /**
     * 输入致命错误
     * @param type $word
     * @return type
     */
    public static final function fatal($word) {
        return self::write($word, 'fatal.txt');
    }

    /**
     * 递归创建目录
     * @param type $dir
     * @return type
     */
    public static function mkdir($dir, $root = LOG_PATH) {
        if (!is_dir(LOG_PATH))
            mkdir(LOG_PATH, 0777, true);
        return is_dir($root . $dir) || (self::mkdir(dirname($dir)) && mkdir($root . $dir, 0777));
    }

    /**
     * 写入缓存
     * @param <type> $cacheId
     * @param <type> $cacheContent
     * @return <type>
     */
    public static function fileWrite($cacheId, $cacheContent, $flags = LOCK_EX, $root = LOG_PATH) {
        if (self::mkdir(dirname($cacheId), $root)) {
            $_file = $root . $cacheId;
            $_fpc = file_put_contents($_file, $cacheContent, $flags);
            @chmod($_file, 0777);
            return $_file;
        }
        return false;
    }

}
