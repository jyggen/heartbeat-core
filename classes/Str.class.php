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

}