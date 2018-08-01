<?php
namespace PGCelda\World;

use PGCelda\Data\DataBiome;
use PGCelda\Utils\Vector2;
use PGCelda\World\Rooms\BaseRoom;
use PGCelda\World\Rooms\RoomBridge;
use PGCelda\World\Rooms\RoomRegion;
use PGCelda\World\Rooms\RoomVoid;
use PGCelda\World\Rooms\RoomWall;

class WorldRegion {
	public static $REGIONS = array();
	
	protected $world = NULL;
	protected $id = -1;
	protected $rank = -1;
	protected $name = "";
	protected $biome = NULL;
	protected $base_point = NULL;
	protected $bridge_room = NULL;
	protected $points = array();
	protected $total_rooms = 0;
	protected $rooms = array();
	
	public function __construct(World $world, $id=NULL) {
		#$this->world = $world;
		if (trim($id) == "") {
			$id = "region_id-".md5(uniqid("", true));
		}
		$this->id = $id;
		WorldRegion::$REGIONS[$id] = $this;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getRank() {
		return $this->rank;
	}
	
	public function setRank($rank) {
		$this->rank = $rank;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getBiome() {
		return $this->biome;
	}
	
	public function setBiome(DataBiome $biome) {
		$this->biome = $biome;
	}
	
	public function getBasePoint() {
		return $this->base_point;
	}
	
	public function getBridgeRoom() {
		return $this->bridge_room;
	}
	
	public function getPoints() {
		return $this->points;
	}
	
	public function getRooms() {
		return $this->rooms;
	}
	
	public function getPuzzleCount() {
		return floor($this->total_rooms / 2);
	}
	
	public function setBasePoint($x, $y) {
		$this->base_point = new Vector2($x, $y);
		$this->addPoint($x, $y);
	}
	
	public function addBridgeRoom(RoomBridge $bridge_room) {
		$this->bridge_room = $bridge_room;
	}
	
	public function addPoint($x, $y, $room=NULL) {
		if (in_array(new Vector2($x, $y), $this->points)) {
			print_r($this->points);
			print_r(debug_backtrace());
			die;
		}
		$this->points[] = new Vector2($x, $y);
		if (is_null($room)) {
			$room = new RoomRegion($this, $x, $y);
		}
		$this->addRoom($room);
	}
	
	public function addRoom(BaseRoom $room) {
		$point = $room->getPoint();
		$this->rooms[$point->y][$point->x] = $room;
		ksort($this->rooms[$point->y]);
		ksort($this->rooms);
		if (!($room instanceof RoomWall) && !($room instanceof RoomVoid)) {
			$this->total_rooms++;
		}
	}
}
?>