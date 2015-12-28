<?php

namespace Zi;

// 简单路由
class Route
{

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

	public static $useAutoRoute = true;// 自动匹配路由
    /**
     * Defines a route w/ callback and method
     */
    public static function __callstatic($method, $params) 
    {
        
        $uri = dirname($_SERVER['PHP_SELF']).$params[0];
		$uri = str_replace('\\', '/', $uri); // 自己添加的
        $callback = $params[1];

        array_push(self::$routes, $uri);
        array_push(self::$methods, strtoupper($method));
        array_push(self::$callbacks, $callback);
    }

    /**
     * Defines callback if route is not found
    */
    public static function error($callback)
    {
        self::$error_callback = $callback;
    }
    
    public static function haltOnMatch($flag = true)
    {
        self::$halts = $flag;
    }

    /**
     * Runs the callback for the given request
     */
    public static function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];  

        $searches = array_keys(static::$patterns);
        $replaces = array_values(static::$patterns);

        $found_route = false;
       // var_dump(self::$routes,'<hr />');
        self::$routes = str_replace('//', '/', self::$routes);

        // check if route is defined without regex
        if (in_array($uri, self::$routes)) {
            $route_pos = array_keys(self::$routes, $uri);
            foreach ($route_pos as $route) {

                //using an ANY option to match both GET and POST requests
                if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY') {
                    $found_route = true;

                    //if route is not an object 
                    if(!is_object(self::$callbacks[$route])){

                        //grab all parts based on a / separator 
                        $parts = explode('/',self::$callbacks[$route]); 

                        //collect the last index of the array
                        $last = end($parts);

                        //grab the controller name and method call
                        $segments = explode('@',$last);

                        //instanitate controller
                        $controllerName = new $segments[0]();

                        //call method
                        $controllerName->$segments[1](); 
                        
                        if (self::$halts) return;
                        
                    } else {
                        //call closure
						//die(var_dump(self::$callbacks,$route));
                        call_user_func(self::$callbacks[$route]);
                        
                        if (self::$halts) return;
                    }
                }
            }
        } else {
			if(self::$useAutoRoute){  // 按照规则自动匹配相应的路由
				self::_autoRoute($uri);
			}
            // check if defined with regex
            $pos = 0;
            foreach (self::$routes as $route) {

                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (preg_match('#^' . $route . '$#', $uri, $matched)) {
					
                    if (self::$methods[$pos] == $method) {
                        $found_route = true;
                        array_shift($matched); //remove $matched[0] as [1] is the first parameter.


                        if(!is_object(self::$callbacks[$pos])){

                            //grab all parts based on a / separator 
                            $parts = explode('/',self::$callbacks[$pos]); 

                            //collect the last index of the array
                            $last = end($parts);
							
                            //grab the controller name and method call
                            $segments = explode('@',$last); 

                            //instanitate controller
                            $controllerName = new $segments[0]();

                            //fix multi parameters 
                            if(!method_exists($controllerName, $segments[1])){
                                echo "controller and action not found";
                            }else{
                               call_user_func_array(array($controllerName, $segments[1]), $matched);
                            }

                            //call method and pass any extra parameters to the method
                            // $controllerName->$segments[1](implode(",", $matched)); 
    
                            if (self::$halts) return;
                        } else {
                            call_user_func_array(self::$callbacks[$pos], $matched);
                            if (self::$halts) return;
                        }
                        
                    }
                }
            $pos++;
            }
        }
 
        // run the error callback if the route was not found
        if ($found_route == false) {
            if (!self::$error_callback) {
                self::$error_callback = function() {
                    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
                    echo '404';
                };
            }
            call_user_func(self::$error_callback);
        }
    }
	
	private static function _autoRoute($uri){
		global $globalClassLoader;
		$aryUri = explode('/',trim($uri,'/'));
		$_caseOneLevel = $isHome= false;
		if(count($aryUri) > 0){
			$controllerName = ucfirst($aryUri[0]);
			if($controllerName == '')
				$isHome = true;
			if(isset($aryUri[1])){
				$actionName =  $aryUri[1];
				$aryUri = array_splice($aryUri,2,count($aryUri)); //排除前两个元素
			}else{
				$_caseOneLevel =  true;
				$actionName =  $aryUri[0];
				/*if($controllerName == $actionName){  // 有个bug  Home/home 这种路由访问时,
					$actionName = 'index';
				}*/
				$aryUri = array_splice($aryUri,1,count($aryUri)); //排除首个元素
			}
			$controllerFullName = 'App\Controllers\\'.$controllerName.'Controller';
			$classMap = $globalClassLoader->getClassMap();
			//die(var_dump(isset($classMap[$controllerFullName])));
			if(isset($classMap[$controllerFullName])){  
				$controller = new $controllerFullName();
				if(method_exists($controller,$actionName)){  // 调用控制器内的方法
					self::_run($controller,$controllerName,$actionName,$aryUri);
				}else if(method_exists($controllerFullName,'index') && $_caseOneLevel ){
					self::_run($controller,$controllerName,'index',$aryUri);
				}else{  //
					exit('no 1');
				}
				exit;
			}else if(class_exists('App\Controllers\IndexController',true)){
				$controller = new \App\Controllers\IndexController();
				if(method_exists($controller,$actionName)){ // is_callable()
					self::_run($controller,'Index',$actionName,$aryUri);
				}else if(method_exists($controller,'index') && $isHome){
					self::_run($controller,'Index','index',$aryUri);
				}else{
					exit('no 2');
				}
				exit;
			}
		}
	}

	private static function _run($controller,$controllerName,$actionName,& $aryUri){
			//var_dump($aryUri);
			if(count($aryUri) > 0){
				$controller->uriParams = $aryUri; // 保留备用
				while($aryUri){
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
			try{
				$controller->__init__();
				$_tpl = $controller->$actionName();
				//var_dump($_tpl);
				$controller->display($_tpl);
			}catch(\Exception $e){
				var_dump($e->getMessage());exit;
			}
	}
}
