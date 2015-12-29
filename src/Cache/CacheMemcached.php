<?php

namespace Zi\Cache;

use Memcached;
use Zi\Config;

/**
 * memcached 访问器
 */
class CacheMemcached implements CacheInterface {

    private $_handler = null;
    private $_hosts = null;
    private $_salt = null; //加入salt，避免在memcache中不同项目key一致

    public function __construct() {
        $this->_hosts = Config::get('common.memServers');
        $this->_salt = Config::get('common.memSalt');
    }

    public function open() {
        $this->_handler = new Memcached($this->_salt);
        $this->_handler->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
        $this->_handler->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
        $this->_handler->setOption(Memcached::OPT_TCP_NODELAY, true);
        $this->_handler->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 50);
        $this->_handler->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
        $this->_handler->setOption(Memcached::OPT_RETRY_TIMEOUT, 300);
        $this->_handler->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        $this->_handler->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true);
        $this->_handler->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $this->_handler->setOption(Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
        $this->_handler->setOption(Memcached::OPT_COMPRESSION, false); //关闭压缩功能
        if (!count($this->_handler->getServerList())) {
            if (isset($this->_hosts['aliocs'])) {
                $this->_handler->addServer($this->_hosts['aliocs'][0], $this->_hosts['aliocs'][1]); //添加OCS实例地址及端口号
                $this->_handler->setSaslAuthData($this->_hosts['aliocs'][2], $this->_hosts['aliocs'][3]); //设置OCS帐号密码进行鉴权
            } else if ($this->_hosts['memcache']) {
                $this->_handler->addServer($this->_hosts['memcache'][0], $this->_hosts['memcache'][1]);
            } else if ($this->_hosts['memcached']) {
                $this->_handler->addServers($this->_hosts['memcached']);
            }
        }
        return true;
    }

    public function set($key, $var, $expires) {
        return $this->_handler->set($key, $var, $expires);
    }

    public function get($key, $expires) {
        return $this->_handler->get($key);
    }

    public function exists($key) {
        return $this->get($key, null) === false ? false : true;
    }

    public function delete($key) {
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                $this->_handler->delete($v);
            }
            return true;
        } else {
            return $this->_handler->delete($key);
        }
    }

    /**
     * memcache会自动清理已超时
     * $this->_handler->flush()如果使用会将所有缓存清空
     * @return boolean
     */
    public function gc() {
        return $this->_handler->flush();
    }

}
