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

	static public function orderBySubkey(&$array, $key, $asc=SORT_ASC)
	{

		$sortFlags = array(
		              SORT_ASC,
		              SORT_DESC,
		             );

        if (in_array($asc, $sortFlags) === false) {

            throw new Exception('sort flag only accepts SORT_ASC or SORT_DESC');

		}

		$arr = $array;

        usort(
			$arr,
			function(array $a, array $b) use ($key, $asc, $sortFlags) {

				if (is_array($key) === false) {

					if (isset($a[$key]) === false || isset($b[$key]) === false) {

						throw new Exception('sort on non-existent keys');

					}

					if ($a[$key] === $b[$key]) {

						return 0;

					}

					if (($asc === SORT_ASC ^ $a[$key] < $b[$key])) {

						return 1;

					} else {

						return -1;

					}

				} else {

					foreach ($key as $subKey => $subAsc) {

						if (in_array($subAsc, $sortFlags) === false) {

							$subKey = $subAsc;
							$subAsc = $asc;

						}

						if (isset($a[$subKey]) === false
							|| isset($b[$subKey]) === false
						) {

							throw new Exception('sort on non-existent keys');

						}

						if ($a[$subKey] === $b[$subKey]) {

							continue;

						}

						if (($subAsc === SORT_ASC ^ $a[$subKey] < $b[$subKey])) {

							return 1;

						} else {

							return -1;

						}

					}//end foreach

					return 0;

				}//end if

			}
		);

	}

}