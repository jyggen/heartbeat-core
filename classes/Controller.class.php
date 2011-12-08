<?php
class Controller
{

	protected $_view;
	protected static $_instance = false;

	public static function getInstance()
	{

		if (self::$_instance === false) {

			$class = get_called_class();
			self::$_instance = new $class;

		}

		return self::$_instance;

	}

	public function __construct()
	{

		$this->_view = View::getInstance();

		$server = array();
		$server = Str::htmlEntities($_SERVER);
		$server = Arr::keyToCamel($server);

		$get = array();
		$get = Str::htmlEntities($_GET);
		$get = Arr::keyToCamel($get);

		$post = array();
		$post = Str::htmlEntities($_POST);
		$post = Arr::keyToCamel($post);

		$session = array();
		$session = Str::htmlEntities($_SESSION);
		$session = Arr::keyToCamel($session);

		$this->_view->addGlobal('server', $server);
		$this->_view->addGlobal('get', $get);
		$this->_view->addGlobal('post', $post);
		$this->_view->addGlobal('session', $session);
		$this->_view->addGlobal('system', SYSTEM);
		$this->_view->addGlobal('version', VERSION);
		$this->_view->addGlobal('token', Str::guid());
		
		echo 'qweqweqwe:'.Request::backtrace();
		echo '<hr>';
		
		self::$_instance =& $this;

	}

}