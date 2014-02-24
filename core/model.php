<?php
/**
 * Model base class. All models should extend this class.
 *
 * @package     JAF
 * @subpackage  Core
 * @category    Model
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */

abstract class Model {

    private $db; //db instance
	/**
	 * Create a new model instance.
	 *
	 *     $model = Model::factory($name);
	 *
	 * @param   string   model name
	 * @return  Model
	 */
	public static function factory($name)
	{
	    if(file_exists(MODELS_PATH.$name.'.php'))
        {
            require_once MODELS_PATH.$name.'.php';
    		$class = 'Model_'.$name;
    		return new $class;
        }
		else
		{
		    log::add('Model::Factory model not found: '.$name);
		}
	}
	
	//@todo add CRUD, select, insert, update, delete

}