<?php
/**
*Benchmark class, it's a good solution for testing your web applications and scripts!
*also it can be a permanent tool you can integrate into your systems that will indicate the performance of your app/script etc.
* Test Time and memory consumption!
* Example usage:
*
* <code>
* require('Benchmark.class.php');
* Benchmark::startTimer();
* echo Benchmark::showTimer(5) . ' sec. ';
* echo Benchmark::showMemory('kb') . ' kb' ;
* </code>
*
*@author David Constantine Kurushin
*@link http://www.zend.com/en/store/education/certification/authenticate.php/ClientCandidateID/ZEND015209/RegistrationID/238001337
*@version 10.12.2010
*@package Benchmark
*@copyright Copyright (c) 2010, David Constantine Kurushin
 */
class Benchmark{

	/**
	*Class Fields startTime and stopTime
	*@staticvar double $sartTime holds the starting time
	*@staticvar double $stopTime holds the ending time
	 */
	public static $startTime=null,$stopTime=null;
	
	/**
	*Private static method returns the current microtime
	*@staticvar stime holds the starting time
	*@return full microtime 
	 */
	private static function getm(){
		return array_sum(explode(" ",microtime()));
	}

	/**
	*Public static method the starting time
	*@static
	 */
	public static function startTimer(){//Benchmark::startTimer();
		self::$startTime=self::getm();
		}

	/**
	*Private static method sets the ending time
	*@static
	 */
	public static function stopTimer(){//Benchmark::stopTimer();
			self::$stopTime=self::getm();
	}

	/**
	*Public static method,
	*Calculate the time elapsed from starting to stopping in second
	*@static
	*@param integer $round set how much numbers you want after Zero, default is 10
	*@return double the time elapsed from starting to stopping in seconds
	 */
	public static function showTimer($round=10){//echo Benchmark::showTimer(5);
		if(self::$startTime===null)
			die ('Before using <b>Benchmark::showTimer()</b> , you should first start the timer by <b>Benchmark::startTimer</b>!');
		else 
			self::$stopTime=self::getm();
		return number_format( (self::$stopTime - self::$startTime) , $round, '.', '');
	}
	
	/**
	*Public static method,
	*Calculate the memory that used/alocated to your script
	*@static
	*@param String $string you should choose the format you want, 'mb'/'kb'/'bytes' default if bytes!
	*@param integer $round set how much numbers you want after Zero, default is 3
	*@return double amount of memory your script consume
	 */
	public static function showMemory($string ='bytes', $round=3){//echo Benchmark::showMemory('kb');
		$result=null;
		switch($string){
			case 'mb': $result = round(memory_get_usage()/1048576,$round);
			break;
			case 'kb': $result = round(memory_get_usage()/1024, $round);
			break;
			default: $result = memory_get_usage();
			break;
		}
		return $result;
	}

}