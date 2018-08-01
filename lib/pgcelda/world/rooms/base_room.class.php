<?php
namespace PGCelda\World\Rooms;

use PGCelda\Data\DataPuzzle;
use PGCelda\Utils\Vector2;
use PGCelda\World\WorldRegion;

abstract class BaseRoom {
	public static $ROOMS = array();
	
	protected $region = NULL;
	protected $id = -1;
	protected $point = NULL;
	protected $room_regions = array();
	protected $exits = array();
	protected $exit_points = array();
	protected $max_exits = 4;
	
	public function __construct(WorldRegion $region, $x, $y, $id=NULL) {
		$this->region = $region;
		#$this->region = $region->getId();
		$this->point = new Vector2($x, $y);
		if (trim($id) == "") {
			$id = "room_id-".md5(uniqid("", true));
		}
		$this->id = $id;
		BaseRoom::$ROOMS[$y][$x] = $this;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getRegion() {
		return $this->region;
	}
	
	public function setRegion(WorldRegion $region) {
		$this->region = $region;
	}
	
	public function getPoint() {
		return $this->point;
	}
	
	abstract public function getPuzzle();
	abstract public function setPuzzle(DataPuzzle $puzzle);
	
	public function getRoomRegions() {
		return $this->room_regions;
	}
	
	public function setRoomRegions(array $room_regions) {
		$this->room_regions = $room_regions;
	}
	
	public function getExits() {
		return $this->exits;
	}
	
	public function addExit($direction_id, $x, $y) {
		if (count($this->exits) >= $this->max_exits) {
			return NULL;
		}
		$this->exits[$direction_id] = new Vector2($x, $y);
		$this->exit_points[$y][$x] = $direction_id;
	}
	
	public function hasExit($x, $y) {
		return (isset($this->exit_points[$y][$x]));
	}
}
?>