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

		$this->server = array();
		$this->server = Str::htmlEntities($_SERVER);
		$this->server = Arr::keyToCamel($this->server);

		$this->get = array();
		$this->get = Str::htmlEntities($_GET);
		$this->get = Arr::keyToCamel($this->get);

		$this->post = array();
		$this->post = Str::htmlEntities($_POST);
		$this->post = Arr::keyToCamel($this->post);

		$this->session = array();
		$this->session = Str::htmlEntities($_SESSION);
		$this->session = Arr::keyToCamel($this->session);

		$this->_view->addGlobal('server', $this->server);
		$this->_view->addGlobal('get', $this->get);
		$this->_view->addGlobal('post', $this->post);
		$this->_view->addGlobal('session', $this->session);
		$this->_view->addGlobal('system', SYSTEM);
		$this->_view->addGlobal('version', VERSION);
		$this->_view->addGlobal('token', Str::guid());

		self::$_instance =& $this;

	}

}