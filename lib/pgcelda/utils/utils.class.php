<?php
namespace PGCelda\Utils;

class Utils {
	public static $CHECKS4 = array(
		array(-1, 0),
		array( 0,-1),
		array( 1, 0),
		array( 0, 1),
	);
	
	public static $CHECKS8 = array(
		array(-1,-1),
		array( 0,-1),
		array( 1,-1),
		
		array(-1, 0),
		array( 0, 0),
		array( 1, 0),
		
		array(-1, 1),
		array( 0, 1),
		array( 1, 1),
	);

	public static function closest_root($number) {
		$val = sqrt($number);
		$floor = floor($val);
		$ceil = ceil($val);
		if ($ceil * $ceil < $number) {
			return $ceil;
		}
		
		return $floor;
	}
	
	public static function mt_shuffle(&$array) {
		for($i=count($array)-1; $i > 0; $i--) {
			$j = mt_rand(0, $i);
			$tmp = $array[$i];
			$array[$i] = $array[$j];
			$array[$j] = $tmp;
		}
	}
}
?>