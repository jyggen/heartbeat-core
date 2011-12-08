<?php
class Arr
{

	static public function keyToLower($array)
	{

		$output = array();

		foreach ($array as $key => $value) {

			$key = mb_strtolower($key, 'UTF-8');

			if (is_array($value) === true) {

				$value = self::keyToLower($value);

			}

			$output[$key] = $value;

		}

		return $output;

	}

	static public function keyToCamel($array, $upper=false)
	{

		$output = array();
		$array  = self::keyToLower($array);


		foreach ($array as $key => $value) {

			$key = str_replace(array('-', '_'), ' ', $key);
			$key = ucwords($key);
			$key = str_replace(' ', '', $key);

			if ($upper === false) {

				$key = lcfirst($key);

			}


			if (is_array($value) === true) {

				$value = self::keyToCamel($value);

			}

			$output[$key] = $value;

		}//end foreach

		return $output;

	}

}