<?php
/**
 * MVC Router dispatcher
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Helper
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

class Router{
    private static $routes=array();
    
    /**
     * ads routes arrays to the static var
     * @param $routes array of routes
     */
    public static function add($routes)
    {
        //array merge so we can add routes whenever we want
        self::$routes=array_merge(self::$routes,$routes);
    }
    
    /**
     * From the request URI we get the path
     * @return path of the URL
     */
    public static function get_uri_path()
    {
        $uri = parse_url($_SERVER['REQUEST_URI']);
        log::add('router::get_uri_path: uri:'.$uri); 
        $path = mb_strtolower(substr($uri['path'],1));
        log::add('router::get_uri_path: '.$path); 
        return (!empty($path)) ? $path : FALSE;
    }
	
    /**
     * Dispatch the routes to the right controller action and with params
     */
    public static function dispatch()
    {
        $load_defaults=TRUE;
        $path = router::get_uri_path();//URI path   
        
        if ($path!=FALSE)
        {//there's URI with params
            foreach(self::$routes as $route=>$key)
            {
                $route = self::replace_tags($route);
				
                if (preg_match('#^' . $route . '$#u', $path, $params))
    			{//there's a match
    			    log::add('router::dispatch | pregmatch found: '.$route);
                    if ($key[0]===FALSE && $key[1]===FALSE)
                    {//default for MVC style controller/action/param/
                        $uri            = array_shift($params);
                        $controller     = array_shift($params);
                        $action         = array_shift($params);
                    }
                    else
                    {//another kind of match
                        $controller     = $key[0];
                        $action         = $key[1];
                        $uri            = array_shift($params); 
                    }   
    			    $load_defaults  = FALSE;
    			    break;
    			}//else var_dump($route);
            }
        }
       
        //not any match loading defaults
        if($load_defaults)
        {
            $controller = 'home';
            $action     = 'index';  
            $params     = '';
            //@todo set default in config?
        }
        
        log::add('router::dispatch | controller: '.$controller.' | action: '.$action.' |  params: '.print_r($params,1)); 
        
        if(file_exists(CONTROLLERS_PATH.$controller.'.php'))
        {
            require_once CONTROLLERS_PATH.$controller.'.php';
            $class = $controller.'_Controller';
            if(method_exists($class, $action)) 
            {
                if (is_array($params))
                {
                    call_user_func_array(array($class,$action),$params);
                }
                else
                {
                    call_user_func(array($class,$action));
                }                
            }
        }
        else//@todo what happens if controller doesn't exists?
        {
            
        }    
       
    }
    
    /**
     * replaces URI tags for regex
     * @param $route with tags to be replaced
     * @return route with the replaced tags
     */
    public static function replace_tags($route)
    {
        $wildcard = array('[any]', '[alphanum]',  '[num]',    '[alpha]');
		$regex    = array('(.+)' , '([a-z0-9]+)', '([0-9]+)', '([a-z]+)');
		return str_replace($wildcard, $regex, $route);
    }
    
}