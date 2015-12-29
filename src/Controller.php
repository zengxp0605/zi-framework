<?php

namespace Zi;

use Zi\Route;

/**
 * Controller
 */
class Controller {

    public $uriParams = '';
    public $__VAP__ = '';  //__VIEW_ABSOLUTE_PATH__
    public $__CONTROLLER__ = ''; // 控制器名
    public $__ACTION__ = ''; // 方法名
    private static $_isDisplayed = false;

    public function __construct() {
        
    }

    public function __init__() {
        $this->__VAP__ = BASE_PATH . 'app/views/';
        $this->__PUBLIC__ = '/';
    }

    public function redirect($uri) {
        $this->uriParams = $this->uriParams ? '/' . implode($this->uriParams, '/') : '';
        header('Location: /' . $uri . $this->uriParams);
    }

    public function display($tpl = null) {
        if (self::$_isDisplayed === true)
            return;
        self::$_isDisplayed = true;
        $tpl = $tpl ? $tpl : "{$this->__CONTROLLER__}/{$this->__ACTION__}";
        $path = "app/views/{$tpl}.php";
        header('Content-type:text/html;charset=utf-8;');
        if (file_exists(BASE_PATH . $path)) {
            include BASE_PATH . $path;
            exit;
        } else {
            exit($path . ' 模板文件不存在!');
        }
    }

    public function __get($name) {
        return property_exists($this, $name) ? $this->$name : null;
    }

}
