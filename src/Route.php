<?php

namespace Zi;

use Zi\Log;

// 简单路由
class Route {

    public static $halts = false;
    public static $routes = array();
    public static $methods = array();
    public static $callbacks = array();
    public static $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    );
    public static $error_callback;
    public static $useAutoRoute = true; // 自动匹配路由

    /**
     * Defines a route w/ callback and method
     */

    public static function __callstatic($method, $params) {

        $uri = dirname($_SERVER['PHP_SELF']) . $params[0];
        $uri = str_replace('\\', '/', $uri); // 自己添加的
    }

    public static function dispatch() {
        $_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $_method = $_SERVER['REQUEST_METHOD'];

        $_aryUri = explode('/', trim($_uri, '/'));
        //var_dump($_aryUri);
        if (count($_aryUri) == 1 && $_aryUri[0] == '') { // 匹配 根目录
            $_controllerName = 'Index';
            $_actionName = 'index';
        } else if (count($_aryUri) == 1) {  // 只有一级目录，跳转到Index控制器
            if (ucfirst($_aryUri[0]) == $_aryUri[0]) { // 大写字母开头，当做访问该控制器index方法
                $_controllerName = array_shift($_aryUri);
                $_actionName = 'index';
            } else {
                $_controllerName = 'Index';
                $_actionName = array_shift($_aryUri);
            }
        } else {
            $_controllerName = ucfirst(array_shift($_aryUri));
            if (!$_actionName = array_shift($_aryUri)) {
                $_actionName = 'index';
            }
        }
        try {
            $_controllerFullName = 'App\Controllers\\' . $_controllerName . 'Controller';
            $_rfmth = new \ReflectionMethod($_controllerFullName, $_actionName);
            if ($_rfmth->isPublic()) {
                $_aryRequests = null;
                if ($_method == 'GET') { // GET 请求直接按照顺序传递参数到控制器方法中
                    $_aryRequests = $_GET;
                } else if ($_method == 'POST') { // POST 请求按照键名匹配
                    $_aryRequests = $_POST;
                } else {
                    exit('not support yet');
                }
                $_params = $_rfmth->getParameters();
                $_aryArgs = array();
                foreach ($_params as $_k1 => $_param) {
                    $_key = $_param->getName();
                    if (!$_aryRequests) {
                        $_aryArgs[$_key] = isset($_aryUri[$_k1]) ? $_aryUri[$_k1] :
                                ($_param->isDefaultValueAvailable() ? $_param->getDefaultValue() : null);
                    } else {
                        $_aryArgs[$_key] = isset($_aryRequests[$_key]) ? $_aryRequests[$_key] :
                                ($_param->isDefaultValueAvailable() ? $_param->getDefaultValue() : null);
                    }
                }
                $_rs = $_rfmth->invokeArgs(new $_controllerFullName, $_aryArgs);
            } else {
                self::_404();
            }
        } catch (\Exception $e) {
//            Log::error($e->getMessage());
            echo $e->getMessage(), '<hr />';
            self::_404();
        }
    }

    private static function _404() {
        echo 404;
        exit;
    }

    private static function _run($controller, $controllerName, $actionName, & $aryUri) {
        //var_dump($aryUri);
        if (count($aryUri) > 0) {
            $controller->uriParams = $aryUri; // 保留备用
            while ($aryUri) {
                $key = array_shift($aryUri);
                $val = array_shift($aryUri);
                $controller->$key = $val;
                //echo $key ,' ||',$val,"<br />";
            }
        }
        //var_dump(get_object_vars($controller));
        // TODO  变量过滤
        $controller->__CONTROLLER__ = $controllerName;
        $controller->__ACTION__ = $actionName;
        try {
            $controller->__init__();
            $_tpl = $controller->$actionName();
            //var_dump($_tpl);
            $controller->display($_tpl);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

}
