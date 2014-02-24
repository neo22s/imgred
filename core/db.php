<?php
/**
 * database layer
 * requires wrapper cache class to use caching
 * @package     JAF
 * @subpackage  Core
 * @category    Helper
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

class DB {
    
	private $dbh;//data base handler
	private static $query_cache_status=FALSE; //cache deactivated by default
	private $query_counter=0;//count queries
	private $query_cache_counter=0;//count cached queries
	private $insert_last_id;//last insert ID for mysql_insert_id()
    private static $instance;//Instance of this class
    private static $my_error;//error message that is triggered
    
	    /**
	     * Singleton
	     * Always returns an instance
	     * @param $dbuser
	     * @param $dbpass
	     * @param $dbname
	     * @param $dbhost
	     * @param $dbcharset
	     * @param $dbtimezone
	     * @param $dbconnectiontype
	     * @return DB instance
	     */
	    public static function get_instance($dbuser='', $dbpass='', $dbname='', $dbhost='',$dbcharset='utf8',$dbtimezone=NULL,$dbconnectiontype=NULL)
	    {
	        if (!self::isloaded())//doesn't exists the isntance
	        {
	        	 self::$instance = new self($dbuser, $dbpass, $dbname, $dbhost,$dbcharset,$dbtimezone,$dbconnectiontype);//goes to the constructor
	        }
	        return self::$instance;
	    }
	    
	    /**
	     * tells you if a connection is loaded
	     * @return boolean loaded
	     */
	    public static function isloaded()
	    {
	         if (!isset(self::$instance))
	         {
	             return FALSE;
	         }     
	         return TRUE;       
	    }
	    
	 	/**
	 	 * Prevent users to clone the instance
	 	 */
	    public function __clone()
	    {
	       $this->sql_error('Clone is not allowed.');
	    }
	    
		/**
		 * DB Constructor - connects to the server
		 * @param unknown_type $dbuser
		 * @param $dbpass
		 * @param $dbname
		 * @param $dbhost
		 * @param $dbcharset
		 * @param $dbtimezone
		 * @param $dbconnectiontype
		 */
		private function __construct($dbuser, $dbpass, $dbname, $dbhost,$dbcharset='utf8',$dbtimezone=NULL,$dbconnectiontype=NULL)
		{
		    //we allow persistent connection if selected
			if ($dbconnectiontype=='persistent') 
			{
			    $this->dbh = @mysql_pconnect($dbhost,$dbuser,$dbpass);
			}
			else 
			{
			    $this->dbh = @mysql_connect($dbhost,$dbuser,$dbpass);
			}
			
			if (!$this->dbh)
			{
				$this->sql_error('<ol><li><b>Error establishing a database connection!</b>
									<li>Are you sure you have the correct user/password?
									<li>Are you sure that you have typed the correct hostname?
									<li>Are you sure that the database server is running?</ol>');
			}
			
			$this->set_db($dbname);
			$this->set_charset($dbcharset);
			$this->set_timezone($dbtimezone);		
		}
		
		/**
		 * object destruct
		 */
		public function __destruct() 
		{
		    $this->close_connection();
		}
		
		/**
		 * Select a DB (if another one needs to be selected)
		 * @param $db
		 */
		public function set_db($dbname)
		{
			if ( !@mysql_select_db($dbname,$this->dbh))
			{
				$this->sql_error('<ol><li><b>Error selecting database <u>'.$dbname.'</u>!
									</b><li>Are you sure it exists?
									<li>Are you sure there is a valid database connection?</ol>');
			}
			log::add('DB::set_db | mysql_select_db: '.$dbname);
		}
		
		/**
		 * set the charset connection
		 * @param $dbcharset
		 */
		public function set_charset($dbcharset)
		{
		    $this->query('SET NAMES '.$dbcharset);
		}
		
		/**
		 * set the timezone connection
		 * @param $dbtimezone
		 */
		public function set_timezone($dbtimezone)
		{
		    if ($dbtimezone!==NULL)
			{
			    $this->query('SET time_zone =  \''.$dbtimezone.'\'');
			}
		} 
		
		/**
		 * Closes DB connection
		 */
		public function close_connection()
		{
			if (isset($this->dbh))
			{
				mysql_close();
				unset($this->dbh);
			}
			
			$msg = $this->query_counter.' queries generated in '.Benchmark::showTimer(5).'s';
			if ($this->query_cache_counter > 0)
			{
			    $msg.= ' and '.$this->query_cache_counter.' queries cached';
			}

			log::add('DB::close_connection : '.$msg);
		}
		
		/**
		 * executes a query
		 * @param $query
		 * @return handler
		 */
		public function query($query) 
		{
			$this->query_counter++;
			$result=@ mysql_query($query) 
			            or $this->sql_error('('.mysql_errno().') 
			            				in line '.__LINE__.' error:'.mysql_error().' 
										<br/>Query: '. $query.' <br/>File: '. $_SERVER['PHP_SELF'] );
			            
			log::add('DB::query | '.$query);
			return $result;
		}
				
        /**
         * insert into DB
         * @param $into table name
         * @param $insert values to 
         * @return integer last id or false
         */
		public function insert($table, $values) 
		{  
            $keys   = array_keys($values);
            $query = 'INSERT INTO '.$table.' (`'.implode('`,`', $keys).'`) VALUES (\''.implode('\',\'', $values).'\')';
            log::add('DB::insert'); 
            if ($this->query($query)) 
            {
            	$this->set_last_id(mysql_insert_id($this->dbh));
            	return $this->get_last_id();
            }
            else
            {//not succeeded  
                return FALSE;
            }
        } 
        
        /**
         * delete from
         * @param $table
         * @param $where
         * @return boolean
         */
        public function delete($table, $where=NULL) 
        {  
        	if ($where!==NULL)
        	{
        	    $where = ' WHERE ' . $where;   
        	}
            $query = 'DELETE FROM ' . $table . $where; 
            log::add('DB::delete'); 
            return ($this->query($query))? TRUE:FALSE; 
         } 
         
        /**
         * update values
         * @param $table
         * @param $values
         * @param $where
         * @return boolean
         */
        public function update($table,$values, $where=NULL)
        {
			$temp='';				
			foreach ($values as $f => $v)
			{
			    $temp.= '`'.$f.'`=\''.$v.'\',';  
			}
			$values = substr($temp,0,-1);  
			
        	if ($where!==NULL)
        	{
        	    $where = ' WHERE ' . $where;   
        	}
            $query = 'UPDATE '. $table. ' SET '.$values. $where;
            log::add('DB::update');
            return ($this->query($query))? TRUE:FALSE;
        } 
                         
        /**
         * From a given query returns an array,  uses cache if enabled
         * @param $query
         * @param $type
         * @return array
         */
		public function get_array($query,$type='assoc')
		{
			log::add('DB::get_array | type: '. $type);	
			//get values from cache if enabled
			$values = (self::$query_cache_status)? Cache::cache($query):NULL;
			
			if ($values==NULL) //not value from cache found
			{
				$result=$this->query($query);
				if (mysql_num_rows($result)>0)
				{
					$values=array();
    				if ($type=='object')//@todo check this//if type is object and the cache is activated we use assoc since object can't be cached
    				{
    					$type='assoc';
    					log::add('Fetch mode changed to object, if cache is activated not possible to use.');
    				}
					switch ($type)
					{
						case 'assoc':
							while($row = mysql_fetch_assoc($result))  array_push($values, $row);  
						break;
						case  'row':
							while($row = mysql_fetch_row($result))	   array_push($values, $row);  
						break;
						case 'object':
							while($row = mysql_fetch_object($result))  array_push($values, $row);  
						break;
						case 'value':
							$row     = mysql_fetch_row($result);
							$values  = $row[0];//return value
						break;	
						default:
							$this->sql_error('Not recognized fetch mode: '.$type);
						break;
					} 
					
					if (self::$query_cache_status)//save cache
					{
						Cache::cache($query, $values);
						log::add('DB::get_array | values saved in cache');
					}
				}
				else
				{
				    $values=NULL;
				    log::add('DB::get_array | query wth 0 rows');
				}
				mysql_free_result($result);//freeing memory
			}
			else//found in cache
			{
				$this->query_cache_counter++;
				log::add('DB::get_array | retrieved from cache query:'. $query);
			}
			return $values;
		}
		
		/**
		 * return the 1st value from a field of a query
		 * @param $query
		 * @return value
		 */
		public function get_value($query)
		{
			return $this->get_array($query,'value');
		}
		
		/**
		 * set last id
		 * @param $id last id
		 */
		private function set_last_id($id)
		{
			if (is_numeric($id))
			{
			    $this->insert_last_id=$id;
			}
			else
			{
			    $this->insert_last_id=FALSE;
			    //will return false in case can't retrieve last id
			}
		}
		
		/**
		 * gets last ID
		 * @return mysql last inserted ID
		 */
		public function get_last_id()
		{
			return $this->insert_last_id;
		}

		/**
		 * sets cache active or inactive
		 * @param boolean $state
		 */
		public static function set_cache($state)
		{			
		    //@todo check if loaded better
	        //important to to check that the class exists	
		    if ($state && class_exists('cache'))
			{
			    self::$query_cache_status=TRUE;
			}
			else
			{
			    self::$query_cache_status=FALSE;
			}
			
		} 
			
		/**
		 * get the number of queries count
		 * @param $type of counter
		 * @return integer
		 */
		public function get_counter($type='queries')
		{
			switch($type)
			{
				case 'queries':
					return $this->query_counter;
				break;
				case 'cache':
					return $this->query_cache_counter;
				break;
			}		
		}
		
    	/**
         * triggers an error for SQL
         * @param $error message to give
         */
        private function sql_error($error=NULL)
        {
            self::$my_error = ($error!==NULL)? $error:mysql_error($this->dbh);
            trigger_error('[SQL]', E_USER_ERROR);
        }
        
        /**
         * get mysql error registered
         * 
		 * @return message error
         */
        public static function get_error()
        {
           return self::$my_error; 
        }
}