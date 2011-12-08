<?php
class Str
{

	static public function htmlEntities($var)
	{

		if (is_array($var) === true) {

			foreach ($var as $key => $value) {

				$var[$key] = self::htmlentities($value);

			}

		} else {

			$var = htmlentities($var);

		}

		return $var;

	}

	static public function appendS($str)
	{

		if (strtolower(substr($str, -1)) !== 's') {

			return $str.'s';

		} else {

			return $str;

		}

	}
	
	static public function guid()
	{

		if (function_exists('com_create_guid') === true) {

			return com_create_guid();

		} else {

			mt_srand(((double) microtime() * 10000));

			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);

			$uuid  = chr(123);
			$uuid .= substr($charid, 0, 8);
			$uuid .= $hyphen;
			$uuid .= substr($charid, 8, 4);
			$uuid .= $hyphen;
			$uuid .= substr($charid, 12, 4);
			$uuid .= $hyphen;
			$uuid .= substr($charid, 16, 4);
			$uuid .= $hyphen;
			$uuid .= substr($charid, 20, 12);
			$uuid .= chr(125);

			return $uuid;

		}//end if

	}
	
	static public function slug($str, $separator='dash', $lowercase=true)
	{
	
		if ($separator == 'dash') {

			$search	 = '_';
			$replace = '-';

		} else {

			$search  = '-';
			$replace = '_';

		}

		$trans = array(
				  '&\#\d+?;'	   => '',
				  '&\S+?;'		   => '',
				  '\s+'			   => $replace,
				  '[^a-z0-9\-\._]' => '',
				  $replace.'+'	   => $replace,
				  $replace.'$'	   => $replace,
				  '^'.$replace	   => $replace,
				  '\.+$'		   => '',
		         );

		$str = strip_tags($str);

		foreach ($trans as $key => $val) {

			$str = preg_replace("#".$key."#i", $val, $str);
			
		}

		if ($lowercase === true) {

			$str = strtolower($str);

		}

		return trim(stripslashes($str));

	}
	
}