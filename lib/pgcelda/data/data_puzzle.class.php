<?php
namespace PGCelda\Data;

use PGCelda\World\Rooms\BaseRoom;

abstract class PuzzleState {
	const INACTIVE = 0;
	const ACTIVE = 1;
	const SOLVED = 2;
	const FAILED = 3;
}

class DataPuzzle extends BaseData {
	#protected $room;
	protected $state = PuzzleState::INACTIVE;
	protected $required_item;
	protected $give_item = NULL;
	protected $map_image = NULL;
	protected $tree_image = NULL;
	
	public function __construct($data) {
		parent::__construct($data);
		$this->processData($data);
		DataHandler::addData("puzzles", $this);
	}
	
	/*
	public function getRoom() {
		return $this->room;
	}
	
	public function setRoom(BaseRoom $room) {
		$this->room = $room;
	}
	*/
	
	public function getMapImage() {
		return $this->map_image;
	}
	
	public function getTreeImage() {
		return $this->tree_image;
	}
	
	public function getUsePercent($parameters=array()) {
		if (is_int($this->use_percents)) {
			return $this->use_percents;
		}
		elseif (is_array($this->use_percents)) {
			$rank = (isset($parameters["rank"]) ? trim($parameters["rank"]) : 0);
			if (isset($this->use_percents[$rank])) {
				return $this->use_percents[$rank];
			}
		}
		
		return NULL;
	}
	
	protected function processData($data) {
		/*
		print_r($data);
		die;
		*/
		if (isset($data->required_item)) {
			$this->required_item = $data->required_item;
		}
		if (isset($data->give_item)) {
			$this->give_item = $data->give_item;
		}
		if (isset($data->use_percents)) {
			if (is_numeric($data->use_percents)) {
				$this->use_percents = (int) $data->use_percents;
			}
			elseif (is_array($data->use_percents)) {
				$this->use_percents = array();
			}
		}
		if (isset($data->map_image)) {
			$this->map_image = $data->map_image;
		}
		if (isset($data->tree_image)) {
			$this->tree_image = $data->tree_image;
		}
	}
}
?>