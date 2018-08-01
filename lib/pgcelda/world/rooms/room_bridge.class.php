<?php
namespace PGCelda\World\Rooms;

use PGCelda\Data\DataPuzzle;
use PGCelda\World\WorldRegion;

class RoomBridge extends BaseRoom {
	protected $puzzle = NULL;
	
	public function __construct(WorldRegion $region, $x, $y, $id=NULL) {
		parent::__construct($region, $x, $y, $id);
		
		$this->max_exits = 2;
	}
	
	public function getPuzzle() {
		return $this->puzzle;
	}
	
	public function setPuzzle(DataPuzzle $puzzle) {
		$this->puzzle = $puzzle;
	}
}
?>