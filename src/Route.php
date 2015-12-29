<?php

namespace Zi;

use Zi\Log;

/**
 * 简单路由
 * 
 * -----------------------------------------------
 * GET:
 * http://domain.com/       ==> 匹配 IndexController 的 index 方法
 * http://domain.com/Home   ==> 匹配 HomeController 的 index 方法
 * http://domain.com/home   ==> 匹配 IndexController 的 home 方法
 * http://domain.com/home/test/11/tmp1
 *        ==> 匹配 HomeController->test($id,$tmp1='',$tmp2='tmp2') 方法,并将uri去除控制器和方法名后的参数一次传入方法中
 *            以上示例: $id=11,$tmp1='tmp1',$tmp2='tmp2'(默认值);
 * POST: 以post数组传参,接收后放入方法的形参中
 * 
 * 
 * -----------------------------------------------
 */
class Route {

    const VERSION = 'v1.0.1';

    public static $halts = false;
    private static $_aryUri;
    public static $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    );

    public static function dispatch() {
        $_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        self::$_aryUri = explode('/', trim($_uri, '/'));
        list($_controllerName, $_actionName) = self::_getControllAndActionName();
        //var_dump(self::$_aryUri);
        try {
            $_controllerFullName = 'App\Controllers\\' . $_controllerName . 'Controller';
            $_rfmtd = new \ReflectionMethod($_controllerFullName, $_actionName);
            if (!$_rfmtd->isPublic())
                self::_404();
            $_params = $_rfmtd->getParameters();
            $_aryArgs = self::_getArgsByParams($_params);
            $_controller = new $_controllerFullName;
            $_controller->__CONTROLLER__ = $_controllerName;
            $_controller->__ACTION__ = $_actionName;
            $_controller->__init__();
            $_controller->uriParams = self::$_aryUri;
            $_tpl = $_rfmtd->invokeArgs($_controller, $_aryArgs);
            if ($_tpl === false)  // 此时不使用模板文件
                exit;
            if ($_tpl == 404)
                self::_404();
            $_controller->display($_tpl);
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

    private static function _getControllAndActionName() {
        if (count(self::$_aryUri) == 1 && self::$_aryUri[0] == '') { // 匹配 根目录
            $_controllerName = 'Index';
            $_actionName = 'index';
        } else if (count(self::$_aryUri) == 1) {  // 只有一级目录，跳转到Index控制器
            if (ucfirst(self::$_aryUri[0]) == self::$_aryUri[0]) { // 大写字母开头，当做访问该控制器index方法
                $_controllerName = array_shift(self::$_aryUri);
                $_actionName = 'index';
            } else {
                $_controllerName = 'Index';
                $_actionName = array_shift(self::$_aryUri);
            }
        } else {
            $_controllerName = ucfirst(array_shift(self::$_aryUri));
            if (!$_actionName = array_shift(self::$_aryUri)) {
                $_actionName = 'index';
            }
        }
        return array($_controllerName, $_actionName);
    }

    private static function _getArgsByParams(& $_params) {
        $_method = $_SERVER['REQUEST_METHOD'];
        $_aryRequests = null;
        if ($_method == 'GET') { // GET 请求直接按照顺序传递参数到控制器方法中
            $_aryRequests = $_GET;
        } else if ($_method == 'POST') { // POST 请求按照键名匹配
            $_aryRequests = $_POST;
        } else {
            throw new \Exception($_method . ' is not support yet');
            exit;
        }
        $_aryArgs = array();
        foreach ($_params as $_k1 => $_param) {
            $_key = $_param->getName();
            if (!$_aryRequests) {
                $_aryArgs[$_key] = isset(self::$_aryUri[$_k1]) ? self::$_aryUri[$_k1] :
                        ($_param->isDefaultValueAvailable() ? $_param->getDefaultValue() : null);
            } else {
                $_aryArgs[$_key] = isset($_aryRequests[$_key]) ? $_aryRequests[$_key] :
                        ($_param->isDefaultValueAvailable() ? $_param->getDefaultValue() : null);
            }
        }
        return $_aryArgs;
    }

   
}
