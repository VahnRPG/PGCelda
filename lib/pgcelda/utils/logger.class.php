<?php
namespace PGCelda\Utils;

use PGCelda\Utils\CFG;

class Logger {
	protected static $log_type = "echo";
	
	public static function logString($string) {
		if (!CFG::get("DEBUG_MODE")) {
			return NULL;
		}
		
		switch (Logger::$log_type) {
			case "echo":
				echo "[".date("Y-m-d H:i:s")."] ".$string."\n";
				break;
		}
	}
}
?>