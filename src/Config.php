<?php

namespace Zi;

/**
 * 获取配置项 php|json
 *  eg:
 *  Config::setFileFormat('json');
 *  var_dump(Config::get('database.host'));
 */
class Config {

    const VERSION = '1.0.0';

    // 缓存目录
    private static $_configPath = '../config/';
    // 文件格式
    private static $_fileFormat = 'php';
    //键值对缓存
    private static $_cacheKeys = array();
    // 文件缓存
    private static $_cacheFiles = array();

    public static function get($key, $default = null) {
        if (isset(self::$_cacheKeys[$key])) {
            return self::$_cacheKeys[$key];
        }
        $_aryKeyPath = explode('.', $key);
        $_fileName = array_shift($_aryKeyPath);
        $_aryConfig = self::_getConfigVal($_fileName);
        $_rs = null;
        if (count($_aryKeyPath) > 0) {
            foreach ($_aryKeyPath as $v) {
                if (isset($_rs[$v])) {
                    $_rs = $_rs[$v];
                } else if (isset($_aryConfig[$v])) {
                    $_rs = $_aryConfig[$v];
                } else {
                    $_rs = null;
                    break;
                }
            }
        } else { // match all
            $_rs = $_aryConfig;
        }
        return (self::$_cacheKeys[$key] = $_rs);
    }

    public static function set($key, $value) {
        self::$_cacheKeys[$key] = $value;
    }

    public static function setConfigPath($path) {
        self::$_configPath = $path;
    }

    /**
     * setFileFormat
     * @param type $format
     */
    public static function setFileFormat($format) {
        self::$_fileFormat = $format;
    }

    public static function getConfigPath($path) {
        return self::$_configPath;
    }

    private static function _getConfigVal($fileName) {
        $_fileName = $fileName . '.' . self::$_fileFormat;
        if (!isset(self::$_cacheFiles[$_fileName])) {
            self::$_cacheFiles[$_fileName] = self::_getValByFormat($_fileName);
        }
        return self::$_cacheFiles[$_fileName];
    }

    private static function _getValByFormat($_fileName) {
        $_aryConfig = null;
        $_fileFullName = self::$_configPath . $_fileName;
        //var_dump($_fileFullName,  file_exists($_fileFullName));
        try {
            if (!file_exists($_fileFullName)) {
                throw new \Exception("Config file '{$_fileName}' is not exists.");
            }
            switch (self::$_fileFormat) {
                case 'php':
                    $_aryConfig = include $_fileFullName;
                    break;
                case 'json':
                    $_aryConfig = json_decode(file_get_contents($_fileFullName), true);
                    break;
            }
        } catch (Exception $e) {
            echo 'Load config file error.';
            exit;
        }

        return $_aryConfig;
    }

}
