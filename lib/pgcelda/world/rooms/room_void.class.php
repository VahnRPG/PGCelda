<?php
namespace PGCelda\World\Rooms;

use PGCelda\Data\DataPuzzle;
use PGCelda\World\WorldRegion;

class RoomVoid extends BaseRoom {
	public function __construct(WorldRegion $region, $x, $y, $id=NULL) {
		parent::__construct($region, $x, $y, $id);
		
		$this->max_exits = 0;
	}
	
	public function getPuzzle() {
		return NULL;
	}
	
	public function setPuzzle(DataPuzzle $puzzle) {
		return NULL;
	}
}
?>