<?php
namespace PGCelda\Data;

use PGCelda\Utils\CFG;
use PGCelda\Utils\Logger;
use PGCelda\Utils\Utils;

class DataHandler {
	public static $DATA = array();
	
	public static function loadData() {
		Logger::logString("Loading Data");
		$files = DataHandler::findFiles(CFG::get("data_dir"));
		sort($files);
		foreach($files as $filename) {
			if (preg_match('/\/\_/i', $filename)) {
				continue;
			}
			
			DataHandler::loadFile($filename);
		}
	}
	
	public static function addData($type, BaseData $object) {
		DataHandler::$DATA[$type][$object->getId()] = $object;
		DataHandler::$DATA[$type][$object->getName()] = $object;
	}
	
	public static function getObject($type, $parameters=array(), $ignore_records=array()) {
		if (is_string($parameters)) {
			if (!isset(DataHandler::$DATA[$type][$parameters])) {
				return NULL;
			}
			
			return DataHandler::$DATA[$type][$parameters];
		}
		elseif (!isset(DataHandler::$DATA[$type])) {
			return NULL;
		}
		
		$percents = array();
		foreach(DataHandler::$DATA[$type] as $data_record) {
			if (isset($ignore_records[$data_record->getId()])) {
				continue;
			}
			$percents[$data_record->getId()] = $data_record->getUsePercent($parameters["rank"]);
		}
		#print_r($percents);
		
		$record_ids = array();
		foreach($percents as $record_id => $percent) {
			for($i=0; $i<$percent; $i++) {
				#echo "Here: ".DataHandler::$DATA[$type][$record_id]->getName()."\n";
				$record_ids[] = $record_id;
			}
		}
		#print_r($record_ids);
		
		if (count($record_ids) > 0) {
			for($i=0; $i<7; $i++) {
				Utils::mt_shuffle($record_ids);
			}
			
			$record_id = array_pop($record_ids);
			
			return DataHandler::$DATA[$type][$record_id];
		}
		
		return NULL;
		
		#throw new \Exception("Invalid record ids: ".var_export($record_ids, true));
	}
	
	protected static function loadFile($filename) {
		Logger::logString("Loading file '".$filename."'");
			
		$filedata = file_get_contents($filename);
		$data = json_decode($filedata);
		if (is_null($data)) {
			print_r($filedata);
			echo "\n";
			throw new \Exception("Invalid JSON data for file '".$filename."'");
		}
		elseif (!isset($data->type)) {
			echo "No datatype set for file '".$filename."'\n";
			print_r($data);
			echo "\n";
			throw new \Exception("No datatype set for file '".$filename."'");
		}
		
		switch($data->type) {
			case "biome":
				$biome = new DataBiome($data);
				break;
			case "color":
				if (isset($data->colors)) {
					foreach($data->colors as $color) {
						$color = new DataColor($color);
					}
				}
				break;
			case "puzzle":
				$puzzle = new DataPuzzle($data);
				break;
		}
	}

	protected static function findFiles($dir, $files=array()) {
		if (is_dir($dir)) {
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if ($file != "." && $file != "..") {
					if (is_dir($dir."/".$file)) {
						if (!preg_match('/\.svn/', $dir."/".$file)) {
							$files = DataHandler::findFiles($dir."/".$file, $files);
						}
					}
					else {
						$files[] = $dir."/".$file;
					}
				}
			}
			closedir($dh);
		}
		
		return $files;
	}
}
?>