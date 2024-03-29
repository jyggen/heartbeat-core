<?php
class Request
{

	static public $slug;
	static public $id;
	static public $controller;
	static public $method;
	static public $uri;
	static public $routes;

	static public function uri()
	{

		if (static::$uri !== null) {

			return static::$uri;

		}

		// We want to use PATH_INFO if we can.
		if (!empty($_SERVER['PATH_INFO'])) {

			$uri = $_SERVER['PATH_INFO'];

		}

		// Only use ORIG_PATH_INFO if it contains the path
		elseif ( ! empty($_SERVER['ORIG_PATH_INFO']) and ($path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['ORIG_PATH_INFO'])) != '')
		{
			$uri = $path;
		}
		else
		{
			// Fall back to parsing the REQUEST URI
			if (isset($_SERVER['REQUEST_URI']))
			{
				$uri = $_SERVER['REQUEST_URI'];
			}
			else
			{
				throw new Exception('Unable to detect the URI.');
			}

			// Remove the base URL from the URI
			$base_url = parse_url(null, PHP_URL_PATH);
			if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
			{
				$uri = substr($uri, strlen($base_url));
			}

			// If we are using an index file (not mod_rewrite) then remove it
			$index_file = false;
			if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
			{
				$uri = substr($uri, strlen($index_file));
			}

			// When index.php? is used and the config is set wrong, lets just
			// be nice and help them out.
			if ($index_file and strncmp($uri, '?/', 2) === 0)
			{
				$uri = substr($uri, 1);
			}

			// Lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?' and that mod_rewrite
			// is not being used.
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set set everything correctly
			if ( ! empty($matches))
			{
				$uri = $matches[1];
				$_SERVER['QUERY_STRING'] = $matches[2];
				parse_str($matches[2], $_GET);
			}
		}


		static::$uri = $uri;

		return static::$uri;

	}

	static public function route()
	{

		// Don't allow direct access to the php files, use rewrites instead!
		if ($_SERVER['REQUEST_URI'] === $_SERVER['PHP_SELF']) {

			self::serveNotFound();

		}

		$override = false;

		// If no path is supplied (eg. domain root).
		if(empty(self::$routes) === false) {

			foreach(self::$routes as $route => $target) {

				if(preg_match('/' . $route . '/', self::uri())) {

					$override         = true;
					self::$controller = 'IndexController';
					self::$method     = 'index';
					$args             = null;
					self::$slug       = null;
					self::$id         = null;

				}

			}

		}

		if($override === false) {

			if (self::uri() === '/') {

				self::$controller = 'IndexController';
				self::$method     = 'index';
				$args             = null;
				self::$slug       = null;
				self::$id         = null;

			} else {

				// Split path by folder.
				$path = explode('/', substr(self::uri(), 1));

				// If a controller is requested (should be unless domain root).
				if (isset($path[0]) === true && empty($path[0]) === false) {

					// Set request controller to the requested controller.
					self::$controller = ucfirst($path[0]).'Controller';
					unset($path[0]);

				// Otherwise.
				} else {

					// Set request controller to the default (IndexController).
					self::$controller = 'IndexController';

				}

				// If a method is requested.
				if (isset($path[1]) === true) {

					// Set request method to the requested method.
					self::$method = $path[1];
					unset($path[1]);

				// Otherwise.
				} else {

					// Set request method to the default (index).
					self::$method = 'index';

				}

				// Defaults.
				self::$slug = null;
				self::$id   = null;
				$args       = array();

				// If any arguments are supplied in the request, save them!
				if (isset($path[2]) === true) {

					foreach($path as $val) {

						$args[] = $val;

					}

				// Otherwise null 'em!
				} else {

					$args = null;

				}

				// If arguments were supplied.
				if (empty($args) === false) {

					// Save the first argument as the ID.
					self::$id = $args[0];

					// If we have a second argument, save it as the slug.
					if (isset($args[1]) === true) {

						self::$slug = $args[1];

					// Otherwise, set slug to null.
					} else {

						self::$slug = null;

					}

				}

				// Unset a few vars. Kinda needlessly, but we don't need them anyway.
				unset($args);
				unset($path);

			}//end if

		}//end if

		// If requested controller doesn't exist, throw 404.
		if (class_exists(self::$controller) === false) {

			self::serveNotFound('controller "'.self::$controller.'" not found');
			exit(1);

		}

		// If requested method doesn't exist in the controller, throw 404.
		if (is_callable(array(self::$controller, self::$method)) === false) {

			self::serveNotFound('method "'.self::$controller.'::'.self::$method.'" not found');
			exit(1);

		}

		// Call requested method in requested controller.
		$controller = self::$controller;
		$controller = $controller::getInstance();
		call_user_func(array($controller, self::$method));

	}

	static public function validate($table, $slugField)
	{

		// If no ID or slug is defined there's nothing to validate!
		if (self::$id === null || self::$slug === null) {

			// Return 404.
			self::serveNotFound();
			exit(1);

		}

		// Get database.
		$model = Database::getInstance();

		// Make sure the ID exists in the DB.
		if ($model->recordExistsInDB($table, array('id' => self::$id)) === false) {

			// Return 404.
			self::serveNotFound();
			exit(1);

		}

		// Retrive the supplied field from the DB.
		$sql = 'SELECT `'.$slugField.'`
				AS slug FROM `'.$table.'`
				WHERE id = ?
				LIMIT 1';

		$data = $model->query($sql, array(self::$id), true);

		// Generate an URL slug based upon it.
		$slug = Str::slug($data['slug']);


		// Make sure the slugs matches.
		if ($slug !== self::$slug) {

			// Replace the invalid slug with the correct version.
			$suf  = preg_quote(self::$id.'/'.self::$slug);
			$path = $_SERVER['PATH_INFO'];
			$url  = preg_replace('/'.$suf.'/', self::$id.'/'.$slug, $path);

			// Redirect to the correct URL.
			self::redirect($url, true);
			exit();

		}

	}

	// This method depends on a third-party model and should be removed.
	static public function restrict($adminOnly=false)
	{

		// If this page requires admin and the client isn't an admin.
		if ($adminOnly === true && UserModel::userIsAdmin() === false) {

			// Return 401.
			self::serveUnauthorized();

		// Else, verify that we're logged in!
		} else if (UserModel::userIsLoggedIn() === false) {

			// Return 401.
			self::serveUnauthorized();

		}

	}

	static public function redirect($url, $sendThreeZeroOne=false)
	{

		// Send 301 Moved Permanently if requested, otherwise PHP will send 302 Found.
		if ($sendThreeZeroOne === true) {

			header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');

		}

		// Redirect the client.
		header('Location: '.$url);
		exit();

	}

	static public function errorHandler($errno, $errstr, $errfile, $errline)
	{

		// Error message.
		$err  = $errno;
		$err  = $errstr.'</p>';
		$err .= '<p>on line <strong>'.$errline.'</strong>';
		$err .= ' in <strong>'.$errfile.'</strong>';

		// Serve an error page.
		self::serveErrorPage($err);
		exit(1);

	}

	static public function serveJsonObject($msg)
	{

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo json_encode($msg);
		exit;

	}

	static public function serveErrorPage($msg, $type='text')
	{

		// Protocol version (i.e. HTTP/1.0).
		$protocol = $_SERVER['SERVER_PROTOCOL'];

		// Send 503 Service Temporarily Unavailable.
		header($protocol.' 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 3600');

		// Friendly error message (should be localizable!).
		$err = 'Ett oväntat fel har uppstått, var god försök igen senare.';

		if ($type === 'json') {

			self::serveJsonObject($msg);

		} else {

			// Send it to the error template handler.
			self::error(
				array(
				'normal' => $err,
				'debug'  => $msg,
				)
			);

		}

		exit(0);

	}

	static public function serveNotFound($msg='404 Not Found')
	{

		// Server protocol (i.e. HTTP/1.0) and the requested URI.
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$request  = $_SERVER['REQUEST_URI'];

		// Return 404 Not Found.
		header($protocol.' 404 Not Found');
		header('Status: 404 Not Found');

		// Friendly output, should be localizable in the future!
		$err  = 'Sidan <strong>'.$request.'</strong> kunde inte hittas. ';
		$err .= 'Kontrollera att du skrivit rätt i ';
		$err .= 'adressfältet och försök igen.';

		// Debug output.
		$debug  = SYSTEM.' returned <strong>'.$msg.'</strong> ';
		$debug .= 'on <strong>'.$request.'</strong>.';

		// Send it to the error template handler.
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

		// Server protocol (i.e. HTTP/1.0) and the requested URI.
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$request  = $_SERVER['REQUEST_URI'];

		// Return 401 Unauthorized.
		header($protocol.' 401 Unauthorized');
		header('Status: 401 Unauthorized');

		// Friendly output, should be localizable in the future!
		$err  = 'Du nekas tillträde till <strong>'.$request.'</strong>. ';
		$err .= 'Kontrollera att du är inloggad och försök igen.';

		// Debug output.
		$debug  = SYSTEM.' returned <strong>'.$msg.'</strong> ';
		$debug .= 'on <strong>'.$request.'</strong>.';

		// Send it to the error template handler.
		self::error(
			array(
			 'normal' => $err,
			 'debug'  => $debug,
			)
		);

		exit(0);

	}

	static protected function backtrace()
	{

		// PHP backtrace.
		$bto       = '';
		$backtrace = debug_backtrace();

		// Calls to ignore/strip.
		$ignore = array(
		           'Request::backtrace()',
		           'trigger_error()',
		           'Request::serveErrorPage()',
		           'Request::error()',
    			   'Request::errorHandler()',
		          );

		// Should it already be in the correct order?
		ksort($backtrace);

		// For each in the backtrace.
		foreach ($backtrace as $key => $value) {

			// Is this a class call? Prepend the class name!
			if (array_key_exists('class', $value) === true) {

				$call = $value['class'].'::';

			// Otherwise leave it blank.
			} else {

				$call = '';

			}

			// Add the name of the method and () for the looks.
			$call .= $value['function'].'()';

			// Unless this call should be skipped;
			if (in_array($call, $ignore) === false) {

				// Add it to the backtrace output.
				$bto .= '<strong>'.$call.'</strong>';

				// If there's a line number in the backtrace, append it!
				if (array_key_exists('line', $value) === true) {

					$bto .= ' called on line '.$value['line'];

				}

				// If there's a filename in the backtrace, append it!
				if (array_key_exists('file', $value) === true) {

					$file = str_replace(PATH_ROOT, '', $value['file']);
					$bto .= ' in <strong>'.$file.'</strong>';

				}

				// Add a linebreak, should usd PHP_EOL though.
				$bto .= "\n";

			}//end if

		}//end foreach


		// Return the backtrace output.
		return $bto;

	}

	static protected function error($messages)
	{

		// Let's try to display the error in-site.
		try {

			// If we have our own main controller we should use it.
			if(defined('OVERRIDE_CONTROLLER') === true) {

				$controller = OVERRIDE_CONTROLLER;

			// Otherwise, roll with heartbeat default.
			} else {

				$controller = 'Controller';

			}

			// Retrieve the controller and view.
			$controller = $controller::getInstance();
			$view       = View::getInstance();

			// Debug mode?
			if (DEBUG === true) {

				// Developer friendly message with backtrace.
				$msg  = '<p>'.$messages['debug'].'</p>';
				$msg .= '<pre><u>Backtrace</u>:'."\n".self::backtrace().'</pre>';

				$view->define('errorMsg', $msg);

			// Otherwise;
			} else {

				// User friendly message.
				$view->define('errorMsg', '<p>'.$messages['normal'].'</p>');

			}

			// Render error view.
			$view->render('error');

		// In case something went wrong with the above code;
		} catch (Exception $e) {

			// Create a new view object.
			$view = new View;

			// Debug mode?
			if (DEBUG === true) {

				// Developer friendly message with backtrace.
				$msg  = '<p>'.$messages['debug'].'</p>';
				$msg .= '<pre><u>Backtrace</u>:'."\n".self::backtrace()."\n";

				// Also display why in-site rendering failed.
				$msg .= '<u>Template Engine</u>:'."\n".$e->getMessage().'</pre>';

				$view->define('errorMsg', $msg);

			// Otherwise;
			} else {

				// User friendly message.
				$view->define('errorMsg', '<p>'.$messages['normal'].'</p>');

			}

			// Render a simplified error page.
			$view->render('error_simple');

		}//end try

	}

}