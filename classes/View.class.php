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
		
		$bto       = '';
		$backtrace = debug_backtrace();
		$ignore    = array(
		              'Request::backtrace()',
		              'trigger_error()',
		              'Request::serveErrorPage()',
		              'Request::error()',
    				  'Request::errorHandler()',
		             );

		ksort($backtrace);

		foreach ($backtrace as $key => $value) {

			if (array_key_exists('class', $value) === true) {

				$call = $value['class'].'::';

			} else {

				$call = '';

			}

			$call .= $value['function'].'()';

			if (in_array($call, $ignore) === false) {

				$bto .= '<strong>'.$call.'</strong>';

				if (array_key_exists('line', $value) === true) {

					$bto .= ' called on line '.$value['line'];

				} else {

					$bto .= '';

				}

				if (array_key_exists('file', $value) === true) {

					$file = str_replace(ROOT, '', $value['file']);
					$bto .= ' in <strong>'.$file.'</strong>';

				} else {

					$bto .= '';

				}

				$bto .= "\n";

			}//end if

		}//end foreach

		exit($bto);
		
		try {
			
			Twig_Autoloader::register();
			
			$this->_loader = new Twig_Loader_Filesystem(TEMPLATE_DIR);
			$this->_engine = new Twig_Environment(
				$this->_loader,
				array(
				 'cache' => CACHE_DIR,
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