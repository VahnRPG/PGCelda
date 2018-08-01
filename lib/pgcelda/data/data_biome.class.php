<?php
namespace PGCelda\Data;

use PGCelda\Data\BaseData;
use PGCelda\Data\DataColor;

class DataBiome extends BaseData {
	protected $tileset = "";
	protected $modifiers = array();
	
	public function __construct($data) {
		parent::__construct($data);
		$this->processData($data);
		DataHandler::addData("biomes", $this);
	}
	
	public function getUsePercent($parameters=array()) {
		if (is_numeric($this->use_percents)) {
			return $this->use_percents;
		}
		elseif (is_array($this->use_percents)) {
			$rank = (isset($parameters["rank"]) ? trim($parameters["rank"]) : "0");
			
			$env = \Lisphp_Environment::full();
			foreach($this->use_percents as $use_percent) {
				$lisp_code = "(use mt_rand) (let ((rank ".$rank.")) ".$use_percent.")";
				#echo $lisp_code."\n";
				$program = new \Lisphp_Program($lisp_code);
				$use_percent = (int) $program->execute($env);
				if ($use_percent > 0) {
					#echo " Results: '".var_export($use_percent, true)."'\n";
					return $use_percent;
				}
			}
		}
		
		return NULL;
	}
	
	protected function processData($data) {
		/*
		print_r($data);
		die;
		*/
		if (isset($data->tileset)) {
			$this->tileset = $data->tileset;
		}
	}
	
	protected function processModifiers() {
	}
}
?>