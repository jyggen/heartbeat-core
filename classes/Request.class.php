<?php
class Request
{

	static public $slug;
	static public $id;
	static public $controller;
	static public $method;

	static public function route()
	{

		// Don't allow direct access to the php files, use rewrites instead!
		if ($_SERVER['REQUEST_URI'] === $_SERVER['PHP_SELF']) {

			self::serveNotFound();

		}

		// If no path is supplied (eg. domain root).
		if (isset($_SERVER['PATH_INFO']) === false) {

			self::$controller = 'IndexController';
			self::$method     = 'index';
			$args             = null;
			self::$slug       = null;
			self::$id         = null;

		} else {

			// Split path by folder.
			$path = explode('/', substr($_SERVER['PATH_INFO'], 1));

			// If a controller is requested (should be unless domain root).
			if (isset($path[0]) === true) {

				// Set request controller to the requested controller.
				self::$controller = ucfirst($path[0]).'Controller';

			// Otherwise.
			} else {

				// Set request controller to the default (IndexController).
				self::$controller = 'IndexController';

			}

			// If a method is requested.
			if (isset($path[1]) === true) {

				// Set request method to the requested method.
				self::$method = $path[1];

			// Otherwise.
			} else {

				// Set request method to the default (index).
				self::$method = 'index';

			}

			// Defaults.
			self::$slug = null;
			self::$id   = null;

			// If any arguments are supplied in the request, save them!
			if (isset($path[2]) === true) {

				$args = $path[2];

			// Otherwise null 'em!
			} else {

				$args = null;

			}

			// If arguments were supplied.
			if ($args !== null) {

				// Split the argument and save w/e the first key is as the slug.
				$args       = explode('.', $args);
				self::$slug = $args[0];

				// If we have a second argument (after the dot), save it as the ID.
				if (isset($args[1]) === true) {

					self::$id = $args[1];

				// Otherwise, set ID to null.
				} else {

					self::$id = null;

				}

			}

			// Unset a few vars. Kinda needlessly, but we don't need them anyway.
			unset($args);
			unset($path);

		}//end if

		// If requested controller doesn't exist, throw 404.
		if (class_exists(self::$controller) === false) {

			self::serveNotFound('"'.self::$controller.'" not found');
			exit(1);

		}

		// If requested method doesn't exist in the controller, throw 404.
		if (is_callable(array(self::$controller, self::$method)) === false) {

			self::serveNotFound('"'.self::$method.'" not found');
			exit(1);

		}

		// Call requested method in requested controller.
		$controller = self::$controller;
		$controller = $controller::getInstance();
		call_user_func(array($controller, self::$method));

	}

	static public function validate($table, $slugField)
	{

		if (self::$id === null || self::$slug === null) {

			self::serveNotFound();
			exit(1);

		}

		$model   = Database::getInstance();
		$id      = generateId(self::$id, true, false, $table);
		$reverse = generateId($id, false, false, $table);

		if (self::$id !== $reverse) {

			self::serveNotFound('forged ID detected');
			exit(1);

		}

		if ($model->recordExistsInDB($table, array('id' => $id)) === false) {

			self::serveNotFound();
			exit(1);

		}

		$sql = 'SELECT `'.$slugField.'`
				AS slug FROM `'.$table.'`
				WHERE id = ?
				LIMIT 1';

		$data = $model->query($sql, array($id), true);
		$slug = strSlug($data['slug']);

		if ($slug !== self::$slug) {

			$suf  = preg_quote(self::$slug.'.'.self::$id);
			$path = $_SERVER['PATH_INFO'];
			$url  = preg_replace('/'.$suf.'/', $slug.'.'.self::$id, $path);

			self::redirect($url, true);
			exit();

		}

		self::$id = $id;

	}

	static public function restrict($adminOnly=false)
	{

		if ($adminOnly === true && UserModel::userIsAdmin() === false) {

			self::serveUnauthorized();

		} else if (UserModel::userIsLoggedIn() === false) {

			self::serveUnauthorized();

		}

	}

	static public function redirect($url, $sendThreeZeroOne=false)
	{

		if ($sendThreeZeroOne === true) {

			header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');

		}

		header('Location: '.$url);
		exit();

	}

	static public function errorHandler($errno, $errstr, $errfile, $errline)
	{

		$err  = $errno;
		$err  = $errstr.'</p>';
		$err .= '<p>on line <strong>'.$errline.'</strong>';
		$err .= ' in <strong>'.$errfile.'</strong>';

		self::serveErrorPage($err);
		exit(1);

	}

	static public function serveErrorPage($msg)
	{

		$protocol = $_SERVER['SERVER_PROTOCOL'];

		header($protocol.' 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 3600');

		$err = 'Ett oväntat fel har uppstått, var god försök igen senare.';

		self::error(
			array(
			 'normal' => $err,
			 'debug'  => $msg,
			)
		);

		exit(0);

	}

	static public function serveNotFound($msg='404 Not Found')
	{

		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$request  = $_SERVER['REQUEST_URI'];

		header($protocol.' 404 Not Found');
		header('Status: 404 Not Found');

		$err  = 'Sidan <strong>'.$request.'</strong> kunde inte hittas. ';
		$err .= 'Kontrollera att du skrivit rätt i ';
		$err .= 'adressfältet och försök igen.';

		$debug  = SYSTEM.' returned <strong>'.$msg.'</strong> ';
		$debug .= 'on <strong>'.$request.'</strong>.';

		self::error(
			array(
			 'normal' => $err,
			 'debug'  => $debug,
			)
		);

		exit(0);

	}

	static public function serveUnauthorized($msg='401 Unauthorized')
	{

		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$request  = $_SERVER['REQUEST_URI'];

		header($protocol.' 401 Unauthorized');
		header('Status: 401 Unauthorized');

		$err  = 'Du nekas tillträde till <strong>'.$request.'</strong>. ';
		$err .= 'Kontrollera att du är inloggad och försök igen.';

		$debug  = SYSTEM.' returned <strong>'.$msg.'</strong> ';
		$debug .= 'on <strong>'.$request.'</strong>.';

		self::error(
			array(
			 'normal' => $err,
			 'debug'  => $debug,
			)
		);

		exit(0);

	}

	static public function backtrace()
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

					$file = str_replace(PATH_ROOT, '', $value['file']);
					$bto .= ' in <strong>'.$file.'</strong>';

				} else {

					$bto .= '';

				}

				$bto .= "\n";

			}//end if

		}//end foreach

		return $bto;

	}
	
	static protected function error($messages)
	{

		try {

			$controller = Controller::getInstance();
			$view       = View::getInstance();

			if (DEBUG === false) {

				$view->define('errorMsg', '<p>'.$messages['normal'].'</p>');

			} else {

				$msg  = '<p>'.$messages['debug'].'</p>';
				$msg .= '<pre><u>Backtrace</u>:'."\n".self::backtrace().'</pre>';

				$view->define('errorMsg', $msg);

			}

			$view->render('error');

		} catch (Exception $e) {

			$view = new View;

			if (DEBUG === false) {

				$view->define('errorMsg', '<p>'.$messages['normal'].'</p>');

			} else {

				$msg  = '<p>'.$messages['debug'].'</p>';
				$msg .= '<pre><u>Backtrace</u>:'."\n".self::backtrace()."\n";
				$msg .= '<u>Template Engine</u>:'."\n".$e->getMessage().'</pre>';

				$view->define('errorMsg', $msg);

			}

			$view->render('error_simple');

		}//end try

	}
	
}