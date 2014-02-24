<?php
/**
 * Wrapper Cache class for filecache, memcache, APC, Xcache and eaccelerator
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Cache
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

class Cache {
	private $cache_params;//extra params for external caches like path or connection option memcached
	private $cache_expire;//seconds that the cache expires
	private $cache_type;//type of cache to use
	private $cache_external; //external instance of cache, can be fileCache or memcache
	private static $instance;//Instance of this class
	    
    /**
     * Always returns an instance
     * @param $type of cache to be loaded
     * @param $exp_time when expires the cache
     * @param $params extra params to load the cache
     */
    public static function get_instance($type='auto',$exp_time=3600,$params='cache/')
    {
        if (!isset(self::$instance))//doesn't exists the isntance
        {
        	 self::$instance = new self($type,$exp_time,$params);//goes to the constructor
        }
        return self::$instance;
    }
    
	/**
	 * cache constructor, optional expiring time and cache path
	 * @param $type
	 * @param $exp_time
	 * @param $params
	 */
	private function __construct($type,$exp_time,$params)
	{
		$this->cache_expire=$exp_time;
		$this->cache_params=$params;
		$this->set_cache_type($type);
		log::add('cache::construct | '.$type.'-->'.$this->cache_type.' | '.$exp_time.' | '.$params);
	}
	
	public function __destruct() 
	{
		unset($this->cache_external);
	}
	
	/**
	 * Prevent users to clone the instance
	 */ 
    public function __clone(){
        $this->cache_error('Clone is not allowed.');
    }
	
	/**
	 * deletes all the cache 
	 * @return cache specific return for action
	 */
	public function clear()
	{
	    log::add('cache::clear | '.$this->cache_type);
		switch($this->cache_type)
		{
			case 'eaccelerator':
		    	@eaccelerator_clean();
                return @eaccelerator_clear();
	        break;

		    case 'apc':
		    	return apc_clear_cache('user');
			break;

		    case 'xcache':
	    		return xcache_clear_cache(XC_TYPE_VAR, 0);
		   	break;

		    case 'memcache':
		    	return @$this->cache_external->flush();
	        break;
	        
	        case 'filecache':
		     	return $this->cache_external->deleteCache();
	        break;
		}	
	}
	
	/**
	 * writes or reads the cache
	 * @param $key to read
	 * @param $value to write
	 * @param $ttl time expire
	 * @return mixed
	 */	
    public static function cache($key, $value=NULL,$ttl=NULL)
	{
		if ($value!=NULL)//wants to write
		{
		    log::add('cache::cache | action write key: '.$key);
			if (empty($ttl))
			{
		    	$ttl=self::$instance->cache_expire;
			} 
			return self::$instance->put($key, $value,$ttl);
		}
		else//reading value
		{
		    log::add('cache::cache | action read key: '.$key);
		    return self::$instance->get($key);
		}
	}
	
	
	/**
	 * creates new key
	 * @param $key to store
	 * @param $data value to be writen
	 * @param $ttl expire time
	 * @return cache specific return for action
	 */
	private function put($key,$data,$ttl=NULL )
	{
	    log::add('cache::put | '.$key);
		if (empty($ttl)) $ttl=$this->cache_expire;
		switch($this->cache_type)
		{
			case 'eaccelerator':
		    	return eaccelerator_put($key, serialize($data), $ttl);
	        break;

		    case 'apc':
		    	return apc_store($key, $data, $ttl);
			break;

		    case 'xcache':
	    		return xcache_set($key, serialize($data), $ttl);
		   	break;

		    case 'memcache':
                $data=serialize($data);
		    	if (!$this->cache_external->replace($key, $data, false, $ttl))//key exists just 'refresh' it
		    	{
		    	    return $this->cache_external->set($key, $data, false, $ttl);  //new key
		    	}
	        break;
	        
	        case 'filecache':
		     	return $this->cache_external->cache($key,$data);
	        break;
		}	
    }
    
	/**
	 * get cache for the given key
	 * @param $key
	 * @return value from $key
	 */
	private function get($key)
	{
		switch($this->cache_type)
		{
			case 'eaccelerator':
		    	$data =  @unserialize(eaccelerator_get($key));
	        break;

		    case 'apc':
		    	$data =  apc_fetch($key);
			break;

		    case 'xcache':
	    		$data =  @unserialize(xcache_get($key));
		   	break;

		    case 'memcache':
		    	$data = @unserialize($this->cache_external->get($key));
	        break;
	        
	        case 'filecache':
		     	$data = $this->cache_external->cache($key);
	        break;
		}	
		log::add('cache::get | '.$key);
		return $data;
 	}
 	
 	/**
 	 * delete key from cache 
 	 * @param $key
 	 * @return cache specific return for action
 	 */
 	public function delete($key)
 	{
 	    log::add('cache::delete | '.$key);
 	    switch($this->cache_type)
 	    {
			case 'eaccelerator':
		    	return eaccelerator_rm($key);
	        break;

		    case 'apc':
		    	return apc_delete($key);
			break;

		    case 'xcache':
	    		return xcache_unset($key);
		   	break;

		    case 'memcache':
		    	return $this->cache_external->delete($key);
	        break;
	        
	        case 'filecache':
		     	return $this->cache_external->delete($key);
	        break;
		}	
 	
 	}
    /*
    * Overloading for the Application variables and automatically cached
    */
 	public function __set($name, $value) 
 	{
 		$this->put($name, $value, $this->cache_expire);
    }

    public function __get($name) 
    {
        return $this->get($name);
    }

    public function __isset($key) //@todo check this carefully
    {
        return ($this->get($key)!==NULL)?TRUE:FALSE;
    }

    public function __unset($name) 
    {
        $this->delete($name);
    }
	//end overloads
	
    /**
     * get cache type
     * @return string cache type 
     */
	public function get_cache_type()
	{
	    return $this->cache_type;
	}	
	
	/**
	 * sets the cache if its installed if not triggers error
	 * @param $type of cache to load
	 * 
 	 */
	public function set_cache_type($type)
	{
	    $this->cache_type=strtolower($type);
		
		switch($this->cache_type)
		{
			case 'eaccelerator':
		    	if (function_exists('eaccelerator_get')) $this->cache_type = 'eaccelerator';
		    	else $this->cache_error('eaccelerator not found');  	
	        break;

		    case 'apc':
		    	if (function_exists('apc_fetch')) $this->cache_type = 'apc' ;
		    	else $this->cache_error('APC not found');  
			break;

		    case 'xcache':
	    		if (function_exists('xcache_get')) $this->cache_type = 'xcache' ;
	    		else $this->cache_error('Xcache not found'); 
		   	break;

		    case 'memcache':
		    	if (class_exists('Memcache')) $this->init_memcache();
		    	else $this->cache_error('memcache not found'); 
	        break;
	        
	        case 'filecache':
		     	if (class_exists('filecache'))$this->init_filecache();
		     	else $this->cache_error('fileCache not found'); 
	        break;
	        
	        case 'auto'://try to auto select a cache system
		    	if     (function_exists('eaccelerator_get'))$this->cache_type = 'eaccelerator';                                       
				elseif (function_exists('apc_fetch'))    	$this->cache_type = 'apc' ;                                     
				elseif (function_exists('xcache_get'))  	$this->cache_type = 'xcache' ;                                        
				elseif (class_exists('Memcache'))			$this->init_memcache();
				elseif (class_exists('fileCache'))			$this->init_filecache();
				else $this->cache_error('not any compatible cache was found');
	        break;
	        
	        default://not any cache selected or wrong one selected
	        	if (isset($type)) $msg='Unrecognized cache type selected <b>'.$type.'</b>';
	        	else $msg='Not any cache type selected';
	        	$this->cache_error($msg);  	
	        break;
		}
		log::add('cache::set_cache_type | '.$this->cache_type);
	}	
	
	/**
	 * get instance of the memcache class
	 * 
	 */
	private function init_memcache()
	{
    	if (is_array($this->cache_params))
    	{
    		$this->cache_type = 'memcache';
    		$this->cache_external = new Memcache;
    		foreach ($this->cache_params as $server) 
    		{
    			$server['port'] = isset($server['port']) ? (int) $server['port'] : ini_get('memcache.default_port'); 
            	$server['persistent'] = isset($server['persistent']) ? (bool) $server['persistent'] : true; 
    			$this->cache_external->addServer($server['host'], $server['port'], $server['persistent']);
    		}
    	}
    	else
    	{
    	    $this->cache_error('memcache needs an array, example: 
    				wrapperCache::GetInstance(\'memcache\',30,array(array(\'host\'=>\'localhost\')));');  
    	} 
    	log::add('cache::init_memcache');
    }
    
    /**
     * get instance of the filecache class
     * 
     */
 	private function init_filecache()
 	{
    	$this->cache_type = 'filecache';
    	$this->cache_external = fileCache::get_instance($this->cache_expire,$this->cache_params);
    	log::add('cache::init_filecache');
    }
    
    /**
     * returns the available cache
     * @param $return_format
     */
	public function get_available_cache($return_format='html')
	{
		$avCaches	= array();
		$avCaches[] = array('eaccelerator', function_exists('eaccelerator_get'));                                       
		$avCaches[] = array('apc',          function_exists('apc_fetch')) ;                                     
		$avCaches[] = array('xcache',       function_exists('xcache_get'));                                        
		$avCaches[] = array('memcache',     class_exists('Memcache'));
		$avCaches[] = array('fileCache',    class_exists('fileCache'));
		
		log::add('cache::get_available_cache | '.print_r($avCaches,1));
		
		if ($return_format=='html')
		{
			$ret='<ul>';
			foreach ($avCaches as $c)
			{
				$ret.='<li>'.$c[0].' - ';
				if ($c[1]) $ret.='Found/Compatible';
				else $ret.='Not Found/Incompatible';
				$ret.='</ll>';
			}
			return $ret.'</ul>';
		}
		else 
		{
		    return $avCaches;
		}	
	}
	
	/**
	 * triggers an error
	 * @param $msg
	 */
    private function cache_error($msg)
    {
    	trigger_error('<br /><b>wrapperCache error</b>: '.$msg.
	        		'<br />If you want you can try with \'auto\' for auto select a compatible cache. 
	        		<br />Or choose a supported cache from list:'.$this->get_available_cache(), E_USER_ERROR);
    }
}