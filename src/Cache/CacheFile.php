<?php

namespace Zi\Cache;

use Zi\Supports\File;

class CacheFile implements CacheInterface {

    private $_ext = '.cache.php'; //扩展名

    public function __construct() {
        
    }

    public function open() {
        return true;
    }

    public function set($key, $var, $expires) {
        $var = serialize($var);
        return File::write($key . $this->_ext, $var);
    }

    public function get($key, $expires) {
        $key .= $this->_ext;
        if (($expires == 0 && File::exists($key)) || File::isValid($key, $expires)) {
            $_out = File::fetch($key);
            if (false !== ($_serialize = unserialize($_out))) {
                return $_serialize;
            }
            return $_out;
        }
        return false;
    }

    public function exists($key) {
        return File::exists($key . $this->_ext);
    }

    public function delete($key) {
        if (is_array($key)) {
            foreach ($key as $_k => $_v) {
                $key[$_k] = $_v . $this->_ext;
            }
        } else {
            $key .= $this->_ext;
        }
        return File::delete($key);
    }

    /**
     * 磁盘上可清空缓存目录
     * */
    public function gc() {
        return File::rmdir();
    }

}
