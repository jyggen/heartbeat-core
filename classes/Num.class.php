<?php
class Num
{

	static public function obfuscate(
		$string,
		$toId=false,
		$minLength=false,
		$password=null
	)
	{

		$index = 'bcdfghjklmnpqrstvwxz023456789BCDFGHJKLMNPQRSTVWXZ';

		if ($password !== null) {

			$len = strlen($index);
			for ($n = 0; $n < $len; $n++) {

				$i[] = substr($index, $n, 1);

			}

			$password = hash('sha256', $password);

			if (strlen($password) < strlen($index)) {

				$password = hash('sha512', $password);

			}

			$len = strlen($index);
			for ($n = 0; $n < $len; $n++) {

				$p[] = substr($password, $n, 1);

			}

			array_multisort($p, SORT_DESC, $i);
			$index = implode($i);

		}//end if

		$base = strlen($index);

		if ($toId === true) {

			$string = strrev($string);
			$result = 0;
			$length = (strlen($string) - 1);

			for ($t = 0; $t <= $length; $t++) {

				$bcpow   = bcpow($base, ($length - $t));
				$result += (strpos($index, substr($string, $t, 1)) * $bcpow);

			}

			if (is_numeric($minLength) === true) {

				$minLength--;

				if ($minLength > 0) {

					$result -= pow($base, $minLength);

				}

			}

			$result = sprintf('%F', $result);
			$result = substr($result, 0, strpos($result, '.'));
			$result = (($result / 2) / 45389);

		} else {

			$string = (($string * 45389) * 2);

			if (is_numeric($minLength) === true) {

				$minLength--;

				if ($minLength > 0) {

					$string += pow($base, $minLength);

				}

			}

			$result = '';

			for ($t = floor(log($string, $base)); $t >= 0; $t--) {

				$bcp    = bcpow($base, $t);
				$a      = (floor($string / $bcp) % $base);
				$result = $result.substr($index, $a, 1);
				$string = ($string - ($a * $bcp));

			}

			$result = strrev($result);

		}//end if

		return $result;

	}

	static public function formatBytes($bytes=0, $decimals=0)
	{

		$quant = array(
	              'TB' => 1099511627776, // pow( 1024, 4)
	              'GB' => 1073741824, // pow( 1024, 3)
	              'MB' => 1048576, // pow( 1024, 2)
	              'kB' => 1024, // pow( 1024, 1)
                  'B ' => 1, // pow( 1024, 0)
	             );

		foreach ($quant as $unit => $mag) {

			if (doubleval($bytes) >= $mag) {

				return sprintf('%01.'.$decimals.'f', ($bytes / $mag)).' '.$unit;

			}

		}

		return false;

	}

}