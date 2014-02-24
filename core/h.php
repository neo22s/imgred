<?php
/**
 * Core functions helper
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Helper
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */
class H{ 
    //@todo refactor all functions with coding standards and comments
    
    /**
     * Core start up
     */
    public static function start()
    {
        spl_autoload_register('H::autoload'); // custom autoload

        //start debug and profiler
        log::error_reporting(DEBUG);
        log::add('bootstrap: starts app');    
        
        //init session
        H::ob_start();
        session_start();
    }
    
    /**
     * Loads the given class
     * @param $class
     */
    public static function autoload($class)
    {
        $class=strtolower($class);
        //first we try to load the class from the core
    	if(file_exists(CORE_PATH.$class.'.php'))
        {
            require_once CORE_PATH.$class.'.php';
        }
        //trying from the Vendors
        elseif(file_exists(VENDOR_PATH.$class.'.php'))
        {
            require_once VENDOR_PATH.$class.'.php';
        }
        //trying from the APP models
        elseif(file_exists(MODELS_PATH.$class.'.php'))
        {
            require_once MODELS_PATH.$class.'.php';
        }
        //nothing found :S
        else 
        {
            trigger_error('H::autoload class: '.$class,E_USER_ERROR);
        }
        log::add('H::autoload class: '.$class);
    }
    
    public static function clean_request()
    { //clean all the post and get variables
        $_POST   = array_map('H::filterData', $_POST);
    	$_GET    = array_map('H::filterData', $_GET);
    	$_COOKIE = array_map('H::filterData', $_COOKIE);
    	log::add('H::clean_request');
    }

    public static function filterData($data)
    {//filters the vars recursive
    	if(is_array($data))	$data = array_map('H::filterData', $data);
    	else $data = H::clean($data);
    	return $data;
    }

    public static function clean($var)
    {//request string cleaner
    	$var = h::mynl2br($var);//removing nl
    	if(get_magic_quotes_gpc()) $var = stripslashes($var); //removes slashes
    	if(DB::isloaded()) $var = mysql_real_escape_string($var); //sql injection
    	$var = strip_tags($var);//whitelist of html tags
    	return $var;//returning clean var
    }
    
    public static function mynl2br($var)
    {
    	return str_replace(array('\\r\\n','\r\\n','r\\n','\r\n', '\n', '\r'), '<br />', nl2br($var));
    }

    public static function redirect($url)
    {//simple header redirect
            header('Location: '.$url);//redirect header
            die();
    }
    
    public static function friendly_url($url)
    {//post slug
    	$url= strtolower(h::replace_accents($url));
        $url = str_replace(array('http://', 'https://', 'www.'), '', $url);//erase http/https and wwww, we do shorter the url
    	return preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('','-',''),$url);
    }
    
    public static function replace_accents($var)
    { //replace for accents catalan spanish and more
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        $var= str_replace($a, $b,$var);
        return $var;
    } 
    
    //check correct url formation
    public static function isURL($url)
    {
    	$pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
    	if(preg_match($pattern, $url) > 0) return true;
    	else return false;
    }
    //end check url
    
    public static function ob_start()
    {
        if (extension_loaded('zlib') && !DEBUG) 
        {//check extension is loaded and debug enabled
            if(!ob_start('ob_gzhandler'))//start HTML compression, if not normal buffer input mode  
            {
                ob_start();
            } 
        }
        else //normal output in case could not load extension or debug mode
        {
             ob_start();
        }
        log::add('H::ob_start');
    }
      
}

/**
 * Common functions shared 
 */

    /**
     * request get alias
     * @param $name
     */
    function G($name)
    {
    	return $_GET[$name];
    }
    
    /**
     * request post alias
     * @param $name
     */
    function P($name)
    {
    	return $_POST[$name];
    }