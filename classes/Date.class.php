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

			return '0 seconds ago';

		}

		$a = array(
			  31104000 => array(
						   'year',
						   'years',
						  ),
			  2592000  => array(
						   'month',
						   'months',
						  ),
			  86400    => array(
						   'day',
						   'days',
						  ),
			  3600     => array(
						   'hour',
						   'hours',
						  ),
			  60       => array(
						   'minute',
						   'minutes',
						  ),
			  1        => array(
						   'second',
						   'secons',
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

				return $r.' '.$str.' ago';

			}

		}//end foreach

	}

}