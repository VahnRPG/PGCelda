<?php
namespace PGCelda\Data;

abstract class BaseData {
	protected $id = -1;
	protected $name = "";
	protected $color = NULL;
	protected $use_percents = NULL;
	
	public function __construct($data) {
		if (is_null($data)) {
			throw new \Exception("Invalid data");
		}
		
		if (isset($data->id) && trim($data->id) != "") {
			$this->id = $data->id;
		}
		else {
			$this->id = "data_id-".md5(uniqid("", true));
		}
		
		if (isset($data->name) && trim($data->name) != "") {
			$this->name = $data->name;
		}
		else {
			$this->name = $this->id;
		}
		
		if (!($this instanceof DataColor) && isset($data->color)) {
			$this->color = new DataColor($data->color);
		}
		
		if (isset($data->use_percents)) {
			$this->use_percents = $data->use_percents;
		}
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getColor() {
		return $this->color;
	}
	
	abstract public function getUsePercent($parameters=array());
	abstract protected function processData($data);
}
?>