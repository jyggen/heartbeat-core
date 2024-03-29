<?php
date_default_timezone_set('Europe/Stockholm');
setlocale(LC_ALL, 'sv_SE.utf8');
set_error_handler(array('Request', 'errorHandler'));

if (DEBUG === false) {

	error_reporting(0);
	ini_set('display_errors', 0);

} else {

	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 1);

}

if (function_exists('import') === false) {

	function import($path, $folder='classes')
	{
	
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		
		if (is_file(PATH_APP.$folder.DIRECTORY_SEPARATOR.$path.'.php') === true) {
			
			include_once PATH_APP.$folder.DIRECTORY_SEPARATOR.$path.'.php';
			
		} else {
		
			include_once PATH_CORE.$folder.DIRECTORY_SEPARATOR.$path.'.php';		
		
		}

	}

}

import('jyggen/Database.class', 'libraries');
import('Twig/Autoloader', 'libraries');
import('phpass/PasswordHash', 'libraries');

Database::$settings['hostname'] = DB_HOSTNAME;
Database::$settings['username'] = DB_USERNAME;
Database::$settings['password'] = DB_PASSWORD;
Database::$settings['database'] = DB_DATABASE;