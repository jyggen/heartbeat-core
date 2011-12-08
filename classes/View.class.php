<?php
class View
{

protected $_loader;
protected $_engine;
protected $_vars = array();

protected static $_instance = false;
	
	public static function getInstance()
	{
	
		if (self::$_instance === false) {

			self::$_instance = new self();

		}

		return self::$_instance;

	}

	public function __construct()
	{

		try {
			
			Twig_Autoloader::register();
			
			$this->_loader = new Twig_Loader_Filesystem(PATH_APP.'views');
			$this->_engine = new Twig_Environment(
				$this->_loader,
				array(
				 'cache' => PATH_APP.'cache',
				 'debug' => DEBUG,
				 'strict_variables' => true,
				)
			);

		} catch (Exception $e) {

			Request::serveErrorPage($e->getMessage());

		}

	}

	public function addFilter($name, $function)
	{

		try {

			$this->_engine->addFilter(
				$name,
				$obj = new Twig_Filter_Function($function)
			);

		} catch (Exception $e) {

			Request::serveErrorPage($e->getMessage());

		}

	}

	public function addGlobal($name, $var)
	{

		try {

			$this->_engine->addGlobal($name, $var);

		} catch (Exception $e) {

			Request::serveErrorPage($e->getMessage());

		}

	}

	public function define($key, $var)
	{

		if (array_key_exists($key, $this->_vars) === false) {

			$this->_vars[$key] = $var;

		} else {

			trigger_error('"'.$key.'" is already defined', E_USER_ERROR);
			exit(1);

		}

	}

	public function render($template=false)
	{

		if ($template === false) {
	
			$name = substr(Request::$controller, 0, -10);
			$template = strtolower($name.'_'.Request::$method);
	
		}
	
		try {
	
			$template = $this->_engine->loadTemplate($template.'.twig');
			$output = $template->render($this->_vars);
	
			echo $output;
	
		} catch (Exception $e) {
	
			throw new Exception($e->getMessage());
	
		}
	
	}
	
}