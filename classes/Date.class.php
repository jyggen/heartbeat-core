<?php
class Date
{

	static public function strftime($date, $format)
	{

		return ucwords(strftime($format, strtotime($date)));

	}
	
	static public function elapsedTime($ptime)
	{

		if (is_numeric($ptime) === false) {

			$ptime = strtotime($ptime);

		}

		$etime = (time() - $ptime);

		if ($etime < 1) {

			return '0 sekunder sedan';

		}

		$a = array(
			  31104000 => array(
						   'år',
						   'år',
						  ),
			  2592000  => array(
						   'månad',
						   'månader',
						  ),
			  86400    => array(
						   'dag',
						   'dagar',
						  ),
			  3600     => array(
						   'timme',
						   'timmar',
						  ),
			  60       => array(
						   'minut',
						   'minuter',
						  ),
			  1        => array(
						   'sekund',
						   'sekunder',
						  ),
			 );

		foreach ($a as $secs => $str) {

			$d = ($etime / $secs);

			if ($d >= 1) {

				$r = round($d);

				if ($r > 1) {

					$str = $str[1];

				} else {

					$str = $str[0];

				}

				return $r.' '.$str.' sedan';

			}

		}//end foreach

	}

}