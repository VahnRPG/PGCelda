<?php
namespace PGCelda\Data;

use PGCelda\Utils\CFG;

class DataColor extends BaseData {
	protected $colors = array(
		"red" => -1,
		"green" => -1,
		"blue" => -1,
		"alpha" => 255,
	);
	protected $with_alpha = false;
	protected $name = "";
	
	public function __construct($data) {
		parent::__construct($data);
		$this->processData($data);
		DataHandler::addData("colors", $this);
	}
	
	public function getName() {
		if (trim($this->name) == "" || trim($this->name) == trim($this->id)) {
			$this->name = "#".$this->convertNumber($this->colors["red"]).$this->convertNumber($this->colors["green"]).$this->convertNumber($this->colors["blue"]);
		}
		
		return $this->name;
	}
	
	public function getColors() {
		$colors = $this->colors;
		if (!$this->with_alpha) {
			unset($colors["alpha"]);
		}
		
		return $colors;
	}
	
	public function getImageColor($image) {
		$color = imagecolorallocate($image, $this->colors["red"], $this->colors["green"], $this->colors["blue"]);
		
		return $color;
	}
	
	public function setRed($red) {
		$this->colors["red"] = (int) $red;
	}
	
	public function setGreen($green) {
		$this->colors["green"] = (int) $green;
	}
	
	public function setBlue($blue) {
		$this->colors["blue"] = (int) $blue;
	}
	
	public function setAlpha($alpha) {
		$this->with_alpha = true;
		$this->colors["alpha"] = (int) $alpha;
	}
	
	public function getUsePercent($parameters=array()) {
		return NULL;
	}
	
	protected function processData($data) {
		/*
		print_r($data);
		die;
		*/
		$this->setRed($data->red);
		$this->setGreen($data->green);
		$this->setBlue($data->blue);
		if (isset($data->alpha) && trim($data->alpha) != "") {
			$this->with_alpha = true;
			$this->setAlpha($data->alpha);
		}
	}
	
	protected function convertNumber($number) {
		$hex = dechex($number);
		if (strlen($hex) < 2) {
			$hex = "0".$hex;
		}
		
		return $hex;
	}
}
?>