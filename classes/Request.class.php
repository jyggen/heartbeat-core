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
		
		// If no ID or slug is defined there's nothing to validate!
		if (self::$id === null || self::$slug === null) {
			
			// Return 404.
			self::serveNotFound();
			exit(1);

		}
		
		// Get database.
		$model = Database::getInstance();
		
		// Deobfuscate the ID and then obfuscate it back.
		$id      = Num::obfuscate(self::$id, true, false, $table);
		$reverse = Num::obfuscate($id, false, false, $table);
		
		// Make sure the ID is reversable.
		if (self::$id !== $reverse) {
			
			// Return 404 with debug message.
			self::serveNotFound('forged ID detected');
			exit(1);

		}
		
		// Make sure the ID exists in the DB.
		if ($model->recordExistsInDB($table, array('id' => $id)) === false) {
			
			// Return 404.
			self::serveNotFound();
			exit(1);

		}
		
		// Retrive the supplied field from the DB.
		$sql = 'SELECT `'.$slugField.'`
				AS slug FROM `'.$table.'`
				WHERE id = ?
				LIMIT 1';

		$data = $model->query($sql, array($id), true);
		
		// Generate an URL slug based upon it.
		$slug = Str::slug($data['slug']);
		
		
		// Make sure the slugs matches.
		if ($slug !== self::$slug) {
			
			// Replace the invalid slug with the correct version.
			$suf  = preg_quote(self::$slug.'.'.self::$id);
			$path = $_SERVER['PATH_INFO'];
			$url  = preg_replace('/'.$suf.'/', $slug.'.'.self::$id, $path);
			
			// Redirect to the correct URL.
			self::redirect($url, true);
			exit();
		
		// Else update self::$id to the deobfuscated ID.
		} else {

			self::$id = $id;
		
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

	static public function serveErrorPage($msg)
	{
		
		// Protocol version (i.e. HTTP/1.0).
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		
		// Send 503 Service Temporarily Unavailable.
		header($protocol.' 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 3600');
		
		// Friendly error message (should be localizable!).
		$err = 'Ett oväntat fel har uppstått, var god försök igen senare.';
		
		// Send it to the error template handler.
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
			
			// Retrieve the current controller and view.
			$controller = Controller::getInstance();
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
		
		// In case something went wrong with the abouve code;
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