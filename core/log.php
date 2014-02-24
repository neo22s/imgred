<?php
/**
 * Logging and profinling class
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Helper
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */
class log{
    
    private static $log_array;//log storage
    
    /**
     * Ads a log entry
     * @param $msg to be added
     */
    public static function add($msg)
    {
        //timer not started
        if(Benchmark::$startTime===null)
        {
            Benchmark::startTimer();
        }
        
        if (DEBUG)
        {
            self::$log_array[]=array(time(),Benchmark::showTimer(5),Benchmark::showMemory('mb',5),$msg);
        }
    }
    
    /**
     * Returns the entire log depends the type
     * @param $return_type
     * @return mixed
     */
    public static function show_logs($return_type='HTML')
    {
        //not in debug mode so we return nothing.
        if (!DEBUG)
        {
            return FALSE;
        }
        log::add('log::show_logs '.$return_type);
        switch ($return_type)
        {
            case 'HTML':
                $i=1;
                $return = '<br /><br /><br />
                		<table border="1">
                		<tr><th>N<th>Time Stamp<th>Time Elapsed<th>Memory Usage<th>Message</tr>';
                foreach(self::$log_array as $l)
                {
                    $return .= '<tr>
                    		<td>'.$i.'
                    		<td>'.date('d-m-Y - H:i:s',$l[0]).'
                    		<td>'.$l[1].'s
                    		<td>'.$l[2].'mb
                    		<td>'.$l[3].'
                    	  </tr>';
                    $i++;
                }
                
                return $return .'</table>';
                
                break;
            case 'DUMP':
                var_dump(self::$log);
                break;
            default:
                return self::$log;
        }
      
    }
    
    /**
     * returns a summary of the framework usage
     * @return string
     */
    public static function summary()
    {
        $db=DB::get_instance();
        $ret='Page generated the '.date('d M Y H:i:s').' in '.Benchmark::showTimer(3).'s. Total queries: '.$db->get_counter().'.';
        if (DEBUG)//all the info
        {
            $ret.=' Total cached queries: '.$db->get_counter('cache').'. Memory usage: '.Benchmark::showMemory('mb',5).'mb.';
        }
        Benchmark::stopTimer();
        if (DEBUG)
        {
            return $ret;
        }
        else
        {
            return '<!--'.$ret.'-->';
        }
    }
    
    
	/**
	 * sets the error system handling
	 * @param $debug boolean
	 */
    public static function error_reporting($debug=NULL)
    {        
        set_error_handler('log::error_handler');
        register_shutdown_function('log::fatal_error'); 
        
        //error display
        if (!$debug)
        {//do not display any error message
            error_reporting(0);
            ini_set('display_errors','off');
        }
        else
        {//displays error messages and debug
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors','on');
        }
    }
    
	/**
	 * error_handler
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 */
	public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_USER_ERROR:
            	if ($errstr == '[SQL]')// handling an sql error
            	{
                    $error_body= DB::get_error() . '<br />PHP ' . PHP_VERSION . ' (' . PHP_OS . ')<br />\n
                    				Aborting...<br />\n';
                } 
                else 
                {
        	    	$error_body= '<b>ERROR CRITICAL</b> ['.$errno. ']'.$errstr.'<br />\n
        	       		  		Fatal error on line ' .$errline. ' in file ' .$errfile. '
        	       				 , PHP ' . PHP_VERSION . ' (' . PHP_OS . ')<br />\n
        	         			Aborting...<br />\n';
                }		
        
           		if (DEBUG)
           		{
           		   echo $error_body; 
           		} 
        		elseif (!DEBUG)
        		{
        		    $msg='JAF: '. $error_body;
        		    error_log($msg, 0);
        		    error_log($msg, 1, EMAIL_ERROR);
        		}
        		
                exit(1);
                break;
        
            case E_USER_WARNING:
               	if (DEBUG)
               	{
               	    log::add('<b>ERROR WARNING</b>['.$errno. ']'.$errstr.'<br />\n');   
               	}
                break;
        
            case E_USER_NOTICE:
                if (DEBUG)
                {
                    log::add('<b>NOTICE</b> ['.$errno. ']'.$errstr.'<br />\n');   
                }
                break;
        
           default:
                if (DEBUG) 
                {
                    log::add('Unknown error type: ['.$errno. ']'.$errstr.'<br />\n');   
                }
                break; 
        }
        
    
        return true;
    }
    
    /**
     * Handles fatal error (shutdown functionS)
     * @return
     */ 
    public static function fatal_error() 
    { 
        $last_error = error_get_last(); 
        
        if( $last_error['type'] == 1 || 
            $last_error['type'] == 4 || 
            $last_error['type'] == 16 ||
            $last_error['type'] == 64 || 
            $last_error['type'] == 256 || 
            $last_error['type'] == 4096) 
        { 
            log::error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        } 
    }  
	
}