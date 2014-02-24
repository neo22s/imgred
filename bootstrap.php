<?php
 if ( !defined('__DIR__') ) define('__DIR__', dirname(__FILE__));
/**
 * Application bootstrap
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Bootstrap
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

//check installation requirements, like KO does @todo
require_once '../config.php';//configuration by environment
require_once CORE_PATH.'/h.php';//include core files

H::start();//core start up


/**
 * Load Vendors
 */
    //language locales @todo maybe needs to be loaded after the routes?
    //i18n::load(LOCALE,CHARSET);
    
    //start cache
    if (CACHE_ACTIVE)
    {
        Cache::get_instance(CACHE_TYPE,CACHE_EXPIRE,CACHE_DATA_FILE);
    }
    
    //start DB connection Not a vendor
    DB::get_instance(DB_USER,DB_PASS,DB_NAME,DB_HOST,DB_CHARSET);//,DB_TIMEZONE,'persistent'
    DB::set_cache(CACHE_ACTIVE);

/**
 * Load plugins hooks @todo
 */
    

//prevent attacks, hacks, injections, xss etc...
H::clean_request();
//after cleaning we can start working ;)

//adding APP routes and dispatching
$routes = Array
(
	'[any].html'         => array('home','page'),
	'[any]/[any]/[any]/' => array(FALSE,FALSE)//default MVC route
);
Router::add($routes);
Router::dispatch();

//finishing, @todo maybe at footer?
echo log::show_logs('HTML');
