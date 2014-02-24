<?php
/**
 * View class
 *
 * @package     JAF
 * @subpackage  Core
 * @category    View
 * @author      Chema Garrido <chema@garridodiaz.com>
 * @license     GPL v3
 */
class View
{	
    private $vars = array();//variables that are later available to the view
    private $view_name;//view that is gonna be loaded
    
    /**
     * Sets the view we need to render
     * @param $view_name
     */
    public function __construct($view_name)
    {   
        if(file_exists(VIEWS_PATH.$view_name.'.php'))
        {
             $this->view_name=$view_name;			
        }
		else
		{
		    $this->view_name='home';//@todo set default in config?
		    log::add('View::construct view not found: '.$view_name);
		}
    }
    
    /**
     * "Renders" the view and extracts the variables.
     */
    public function render()
	{
	    //load variables to views from controller
        //@todo SEO class
        $seo = new phpSEO($this->content,CHARSET);
        $this->meta_title       = (isset($this->meta_title))        ? $this->meta_title        : 'default title';
        $this->meta_description = (isset($this->meta_description))  ? $this->meta_description  : $seo->getMetaDescription();
        $this->meta_keywords    = (isset($this->meta_keywords))     ? $this->meta_keywords     : $seo->getMaxKeywords();
        unset($seo);
        
	    extract ($this->vars);
        require_once VIEWS_PATH.$this->view_name.'.php';
        log::add('View::render | '.$this->view_name);
	}
	
    public function __set($name, $value) 
    {
        $this->vars[$name] = $value;
    }

    public function __get($name) 
    {
        return (array_key_exists($name, $this->vars)) ? $this->vars[$name] : NULL;
    }

    public function __isset($name)
    {
        return isset($this->vars[$name]);
    }

    public function __unset($name) 
    {
        unset($this->vars[$name]);
    }
}