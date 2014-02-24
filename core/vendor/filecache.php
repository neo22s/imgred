<?php
/**
 * fileCache class, caches variables in standalone files if value is too long or uses unique file for small ones
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Cache
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

class fileCache {
	private $cache_path;//path for the cache
	private $cache_expire;//seconds that the cache expires
	private $application=array();//application object like in ASP
 	private $application_file;//file for the application object
 	private $application_write=FALSE;//if application write is TRUE means there was changes and we need to write the app file
	private $start_time=0;//application start time
 	private $content_size=64;//this is the max size can be used in APP cache if bigger writes independent file
	private static $instance;//Instance of this class
	    
    /**
     * Always returns an instance
     * @param $exp_time
     * @param $path
     */
    public static function get_instance($exp_time=3600,$path='cache/')
    {
        if (!isset(self::$instance))
        {//doesn't exists the isntance
        	 self::$instance = new self($exp_time,$path);//goes to the constructor
        }
        return self::$instance;
    }
    
	/**
	 * cache constructor, optional expiring time and cache path
	 * @param $exp_time
	 * @param $path
	 */
	private function __construct($exp_time=3600,$path='cache/')
	{
	    $this->start_time=microtime(TRUE);//time starts
		$this->cache_expire=$exp_time;
		if ( ! is_writable($path) ) trigger_error('Path not writable:'.$path,E_USER_ERROR);
		else $this->cache_path=$path;
		$this->APP_start();//starting application cache
	}
	
	public function __destruct()
	{
    	log::add('fileCache::destruct');
		$this->APP_write();//on destruct we write if needed
	}
	
	// Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
	
	/**
	 * deletes cache from folder
	 * @param $older_than time to delete
	 */
	public function deleteCache($older_than=NULL)
	{
		if (!is_numeric($older_than)) $older_than=0;
		$files = scandir($this->cache_path); 
		foreach($files as $file)
		{			
			if (strlen($file)>2 && time() > (filemtime($this->cache_path.$file) + $older_than) ) 
			{
				unlink($this->cache_path.$file);//echo "<br />-".$file; 
			}
		}
		log::add('fileCache::delete all cache');
	}
	
	/**
	 * writes or reads the cache
	 * @param $key
	 * @param $value
	 * @return value
	 */
	public function cache($key, $value=NULL)
	{
		if ($value!==NULL)
		{//wants to write
			if (strlen(serialize($value)) > $this->content_size )
			{//write independent file it's a big result
			    log::add('fileCache::cache function write in file key:'. $key);
				$this->put($key, $value);
			}
			else 
			{
			    log::add('fileCache::cache function write in APP key:'. $key);
			    $this->APP($key,$value);//write in the APP cache
			}
		}
		else{//reading value
			if ( $this->APP($key)!==NULL )
			{
			    log::add('fileCache::cache function read APP key:'. $key);
			    return $this->APP($key);//returns from app cache
			}
			else 
			{
			    log::add('fileCache::cache function read file key:'. $key);
			    return $this->get($key);//returns from file cache
			}
		}
	}
	
	/**
	 * deletes a key from cache
	 * @param $name
	 */
	public function delete($name)
	{
		if ( $this->APP($name)!==NULL )
		{//unset the APP var
    	    log::add('fileCache::unset APP key:'. $name);
    		unset($this->application[md5($name)]);
        	$this->application_write=TRUE;//says that we have changes to later save the APP
    	}
		elseif ( file_exists($this->fileName($name)) )
		{//unlink filename
		    log::add('fileCache::unset File key:'. $name);
			unlink($this->fileName($name));			
		}
	}
	
	// Overloading for the variables and automatically cached
	 	public function __set($name, $value) 
	 	{
	 		$this->cache($name, $value);
	    }
	
	    public function __get($name) 
	    {
	        return $this->cache($name);
	    }
	
	    public function __isset($name) 
	    {
	        return ($this->get($name)!==NULL)?TRUE:FALSE;
	    }
	
	    public function __unset($name) 
	    {//echo "Unsetting '$name'\n";
	    	$this->delete($name);
	    }
	//end overloads
	
	//////////Cache for files individually///////////////////
	
		/**
		 * creates new cache files with the given data
		 * @param $key
		 * @param $data
		 */
	    private function put($key, $data)
		{
			if ( $this->get($key)!= $data )
			{//only write if it's different
				$values = serialize($data);

				$filename = $this->fileName($key);
                $cache_dir=$this->cache_path.substr($filename,0,2).'/';
                if (!is_dir($cache_dir))
                {//creating the directory
		            umask(0000);
                    mkdir($cache_dir, 0755,TRUE);
		        }
                $filename = $cache_dir.$filename;

				$file = fopen($filename, 'w');
			    if ($file)
			    {//able to create the file
			        log::add('fileCache::writting key: '.$key.' file: '.$filename);
			        fwrite($file, $values);
			        fclose($file);
			    }
			    else  log::add('fileCache::unable to write key: '.$key.' file: '.$filename);
			}//end if different
		}
		
		/**
		 * returns cache for the given key 
		 * @param $key
		 * @return value / NULL if not found
		 */
		private function get($key)
		{
			$filename  = $this->fileName($key);            
            $cache_dir = $this->cache_path.substr($filename,0,2).'/';
            $filename  = $cache_dir.$filename;

			if (!file_exists($filename) || !is_readable($filename))
			{//can't read the cache
			    log::add('fileCache::can\'t read key: '.$key.' file: '.$filename);
				return NULL;
			}
			
			if ( time() < (filemtime($filename) + $this->cache_expire) ) 
			{//cache for the key not expired
				$file = fopen($filename, 'r');// read data file
		        if ($file)
		        {//able to open the file
		            $data = fread($file, filesize($filename));
		            fclose($file);
		            log::add('fileCache::reading key: '.$key.' file: '.$filename);
		            return unserialize($data);//return the values
		        }
		        else
		        {
		            log::add('fileCache::unable to read key: '.$key.' file: '.$filename);
		            return NULL;
		        }
			}
			else
			{
			    log::add('fileCache::expired key: '.$key.' file: '.$filename);
			    unlink($filename);	
			    return NULL;//was expired you need to create new
			}
	 	}
		
	 	/**
	 	 * returns the filename for the cache
	 	 * @param $key
	 	 * @return string
	 	 */
		private function fileName($key)
		{
			return md5($key);
		}
 	//////////END Cache for files individually///////////////////
 	
	//////////Cache for APP variables///////////////////
	
	 	/**
	 	 * load variables from the file
	 	 * @param $app_file name of the file is gonna be stored
	 	 */
		private function APP_start ($app_file='application')
		{
			$this->application_file=$app_file;		
			
		    if (file_exists($this->cache_path.$this->application_file))
		    { // if data file exists, load the cached variables
		        //erase the cache every X minutes
			    $app_time=filemtime($this->cache_path.$this->application_file)+$this->cache_expire;
			    if (time()>$app_time)
			    {
			        log::add('fileCache::deleting APP file: '.$this->cache_path.$this->application_file);
			        unlink ($this->cache_path.$this->application_file);//erase the cache
			    }
			    else
			    {//not expired
			        $filesize=filesize($this->cache_path.$this->application_file);
	                if ($filesize>0)
	                {
	                    $file = fopen($this->cache_path.$this->application_file, 'r');// read data file
	                    if ($file)
	                    {
	                        log::add('fileCache::reading APP file: '.$this->cache_path.$this->application_file);
	                        $data = fread($file, $filesize);
	                        fclose($file);
        		            $this->application = unserialize($data);// build application variables from data file
	                    }//en if file could open
	                }//end if file size
			    
			    }     
	        }
	        else  
	        {//if the file does not exist we create it
	            log::add('fileCache::creating APP file: '.$this->cache_path.$this->application_file);
	            fopen($this->cache_path.$this->application_file, 'w');
	        }
			 
		}
		
		/**
		 * write application data to file
		 */
		private function APP_write()
		{
			if ($this->application_write)
			{
			    $data = serialize($this->application);
			    $file = fopen($this->cache_path.$this->application_file, 'w');
			    if ($file)
			    {
			        log::add('fileCache::writting APP file: '.$this->cache_path.$this->application_file);
			        fwrite($file, $data);
			        fclose($file);
			    }
			}
		}
		
		/**
		 * returns the value form APP cache or stores it
		 * @param $var
		 * @param $value
		 */
		private function APP($var,$value=NULL){
			if ($value!==NULL)
			{//wants to write
				if (is_array($this->application))
				{
				    if ( array_key_exists(md5($var), $this->application) )
				    {//exist the value in the APP
					    $write=FALSE;//we don't need to wirte
					    if ($this->application[md5($var)]!=$value)$write=TRUE;//but exists and is different then we write
				    }
				    else $write=TRUE;//not set we write!
				}
				else $write=FALSE;
	
				if ($write)
				{
				    log::add('fileCache::writting APP key:'.$var);
					$this->application[md5($var)]=$value;
					$this->application_write=TRUE;//says that we have changes to later save the APP
				}
			}
			else 
			{//reading	
				if ( !is_array($this->application) || ! array_key_exists(md5($var), $this->application) )
				{
				    log::add('fileCache::nothing found for APP key:'.$var);
				    return NULL;//nothing found not in array
				}
				else
				{
                    log::add('fileCache::reading APP key:'.$var);				
				    return $this->application[md5($var)];//return value
				}
			}
		}
    //////////End Cache for APP variables///////////////////
}
