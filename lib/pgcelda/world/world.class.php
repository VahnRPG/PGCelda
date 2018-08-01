<?php
namespace PGCelda\World;

use PGCelda\Data\DataHandler;
use PGCelda\Data\DataPuzzle;
use PGCelda\Data\PuzzleTree;
use PGCelda\Utils\Logger;
use PGCelda\Utils\Utils;
use PGCelda\World\Rooms\BaseRoom;
use PGCelda\World\Rooms\RoomBridge;
use PGCelda\World\Rooms\RoomRegion;
use PGCelda\World\Rooms\RoomVoid;
use PGCelda\World\Rooms\RoomWall;

class World {
	const VOID_ID = -3;
	const WALL_ID = -2;
	const BRIDGE_ID = -1;
	
	public $id = -1;
	public $name = "";
	
	protected $seed = NULL;
	protected $world_size = NULL;
	protected $sqr_world_size = NULL;
	protected $total_regions = NULL;
	
	protected $world = array();
	protected $regions = array();
	protected $region_ids = array();
	protected $wall_rooms = array();
	protected $bridges = array();		//Key should be formatted "Region_1_Id:Region_2_Id"
	protected $puzzle_tree = NULL;
	
	public function __construct($seed=NULL, $override_seed=NULL, $world_size=NULL, $total_regions=NULL) {
		if (is_null($seed) || trim($seed) == "" || !is_numeric($seed)) {
			$seed = time();
		}
		$this->seed = $seed;
		mt_srand($this->seed);
		//*
		if (is_bool($override_seed) || trim($override_seed) != "") {
			if (is_bool($override_seed)) {
				$override_seed = time();
			}
			Logger::logString("Running seed: ".var_export($override_seed, true));
			mt_srand($override_seed);
		}
		//*/
		$this->id = $this->seed;
		$this->name = $this->generateName();
		
		if (is_null($world_size) || trim($world_size) == "" || !is_numeric($world_size)) {
			$world_size = 16;
		}
		$this->world_size = $world_size;
		$this->sqr_world_size = pow($this->world_size, 2);
		
		if (is_null($total_regions) || trim($total_regions) == "" || !is_numeric($total_regions)) {
			$total_regions = 5;
		}
		$this->total_regions = $total_regions;
	}
	
	public static function loadWorldId($world_id) {
		$c = __CLASS__;
		$world = new $c;
		if ($world->loadWorld($world_id)) {
			return $world;
		}
		
		return NULL;
	}
	
	public function generateName() {
		$output = "Test";
		
		return $output;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = trim($name);
	}
	
	public function getSize() {
		return $this->world_size;
	}
	
	public function getWorld() {
		return $this->world;
	}
	
	public function getRegions() {
		return $this->regions;
	}
	
	public function getPuzzleTree() {
		return $this->puzzle_tree;
	}
	
	protected $data_loaded = false;
	
	public function generate() {
		if (!$this->data_loaded) {
			try {
				DataHandler::loadData();
				$this->data_loaded = true;
			}
			catch(\Exception $e) {
				#throw new \Exception($e->getMessage());
				Logger::logString("ERROR: ".$e->getMessage());
				
				return NULL;
			}
		}
		
		try {
			Logger::logString("Resetting world");
			$this->resetWorld();
			Logger::logString(" Generating regions");
			$this->generateRegions();
			Logger::logString("  Generating bridges");
			$this->generateBridges();
			Logger::logString("   Generating region order");
			$this->generateRegionRank();
			Logger::logString("    Verifying path");
			$this->verifyPath();
			
			$this->invalid_rooms = array();
			$valid_path = false;
			do {
				Logger::logString("     Generating region room exits");
				$this->generateRegionRoomExits();
				Logger::logString("      Verifying region room exits");
				$valid_path = $this->verifyRegionRoomExits();
			} while(!$valid_path);
			
			Logger::logString("      Decorating regions");
			$this->processRegionDecorations();
			Logger::logString("       Processing room regions");
			$this->processRoomRegions();
			Logger::logString("        Generating puzzle tree");
			$this->processPuzzleTree();
			
			Logger::logString(" Finished generating world");
			
			return true;
		}
		catch (\Exception $e) {
			#throw new \Exception($e->getMessage());
			Logger::logString("ERROR: ".$e->getMessage());
			
			return false;
		}
	}
	
	protected function resetWorld() {
		$this->world_void = array();
		$this->world = array();
		for($y=0; $y<$this->world_size; $y++) {
			$this->world[$y] = array();
			for($x=0; $x<$this->world_size; $x++) {
				$this->world[$y][$x] = World::VOID_ID;
				$this->world_void[$y][$x] = World::VOID_ID;
			}
		}
	}
	
	protected function generateRegions() {
		WorldRegion::$REGIONS = array();
		BaseRoom::$ROOMS = array();
		$tiles = floor($this->sqr_world_size / $this->total_regions);
		
		$region_sizes = array();
		$this->regions = array();
		for($i=0; $i<$this->total_regions; $i++) {
			$region = new WorldRegion($this);
			$region->setRank($i);
			#Logger::logString("Building region ".$region->getRank()." - '".$region->getId()."'");
			
			if ($this->world_void == array()) {
				throw new \Exception("All rooms taken while generating region ".$region->getRank()." - '".$region->getId()."'");
			}
			
			$this->regions[$i] = $region;
			$this->region_ids[$region->getId()] = $region;
			
			$count = 0;
			do {
				$x = mt_rand(0, $this->world_size - 1);
				$y = mt_rand(0, $this->world_size - 1);
			} while($this->world[$y][$x] != World::VOID_ID);
			$this->world[$y][$x] = $region->getId();
			$region->setBasePoint($x, $y);
			unset($this->world_void[$y][$x]);
			if ($this->world_void[$y] == array()) {
				unset($this->world_void[$y]);
			}
			
			$count = 0;
			for($j=0; $j<$tiles; $j++) {
				$rand_points = $region->getPoints();
				for($k=0; $k<7; $k++) {
					Utils::mt_shuffle($rand_points);
				}
				$point = array_pop($rand_points);
				#Logger::logString(" Building region ".$region->getRank()." from point: ".$point->x.",".$point->y."!");
				
				$rand_checks = Utils::$CHECKS4;
				for($k=0; $k<7; $k++) {
					Utils::mt_shuffle($rand_checks);
				}
				
				$found = false;
				foreach($rand_checks as $check) {
					$check_x = $point->x + $check[0];
					$check_y = $point->y + $check[1];
					if (!isset($this->world[$check_y][$check_x])) {
						continue;
					}
					elseif ($this->world[$check_y][$check_x] == World::VOID_ID) {
						$found = true;
						$this->world[$check_y][$check_x] = $region->getId();
						$region->addPoint($check_x, $check_y);
						unset($this->world_void[$check_y][$check_x]);
						if ($this->world_void[$check_y] == array()) {
							unset($this->world_void[$check_y]);
						}
						break;
					}
				}
				if (!$found && $count++ < $tiles) {
					#Logger::logString("   Not found from point: ".$point->x.", ".$point->y."!");
					$j--;
				}
			}
			if (count($region->getPoints()) <= 0) {
				throw new \Exception("Invalid region '".$region->getRank()."' room points");
			}
			elseif (count($region->getPoints()) <= 6) {
				throw new \Exception("Regenerating because region '".$region->getRank()."' only has ".count($region->getPoints())." room(s)");
			}
			
			$region_sizes[$region->getRank()] = count($region->getPoints());
			foreach($region->getPoints() as $point) {
				foreach(Utils::$CHECKS8 as $check) {
					$check_x = $point->x + $check[0];
					$check_y = $point->y + $check[1];
					if (!isset($this->world[$check_y][$check_x])) {
						continue;
					}
					elseif ($this->world[$check_y][$check_x] == World::WALL_ID || $this->world[$check_y][$check_x] == $region->getId()) {
						continue;
					}
					else {
						$room = new RoomWall($region, $check_x, $check_y);
						$region->addPoint($check_x, $check_y, $room);
						$this->wall_rooms[$check_y][$check_x] = $room;
						$this->world[$check_y][$check_x] = World::WALL_ID;
						unset($this->world_void[$check_y][$check_x]);
						if ($this->world_void[$check_y] == array()) {
							unset($this->world_void[$check_y]);
						}
					}
				}
			}
		}
		
		$avg_size = floor(array_sum($region_sizes) / $this->total_regions * .7);
		foreach($region_sizes as $region_id => $region_size) {
			if ($region_size < $avg_size) {
				$region = $this->regions[$region_id];
				throw new \Exception("Region ".$region->getRank()." too small - ".$region_size." < ".$avg_size);
			}
		}
	}
	
	protected function generateBridges() {
		$exits = array();
		$this->bridges = array();
		foreach($this->regions as $region) {
			#Logger::logString("Checking region: ".$region->getId());
			if (isset($exits[$region->getId()])) {
				continue;
			}
			
			#Logger::logString(" Checking region: ".$region->getId()."!");
			$skipped = 0;
			$found = false;
			$points = 0;
			foreach($region->getPoints() as $point) {
				$points++;
				foreach(Utils::$CHECKS4 as $check) {
					$check_x = $point->x + $check[0];
					$check_y = $point->y + $check[1];
					if (!isset($this->world[$check_y][$check_x])) {
						continue;
					}
					elseif (in_array($this->world[$check_y][$check_x], array(World::VOID_ID, $region->getId()))) {
						continue;
					}
					elseif ($this->world[$check_y][$check_x] == World::WALL_ID) {
						$check_x2 = $point->x + ($check[0] * 2);
						$check_y2 = $point->y + ($check[1] * 2);
						if (isset($this->world[$check_y2][$check_x2]) && !in_array($this->world[$check_y2][$check_x2], array(World::VOID_ID, World::WALL_ID, World::BRIDGE_ID, $region->getId()))) {
							$check_region = $this->region_ids[$this->world[$check_y2][$check_x2]];
							if (isset($this->bridges[$region->getId().":".$check_region->getId()]) || isset($this->bridges[$check_region->getId().":".$region->getId()])) {
								$skipped++;
								continue;
							}
							
							#Logger::logString(" Building bridge at: ".$check_x.", ".$check_y." - ".$region->getId()." (".$region->getRank().") -> ".$check_region->getId()." -> (".$check_region->getRank().")!");
							$this->world[$check_y][$check_x] = World::BRIDGE_ID;
							$bridge = new RoomBridge($region, $check_x, $check_y);
							
							$dir_1 = -1;
							$dir_2 = -1;
							if ($check[0] < 0) {
								$dir_1 = 1;
								$dir_2 = 4;
							}
							elseif ($check[0] > 0) {
								$dir_1 = 4;
								$dir_2 = 1;
							}
							elseif ($check[1] < 0) {
								$dir_1 = 8;
								$dir_2 = 2;
							}
							elseif ($check[1] > 0) {
								$dir_1 = 2;
								$dir_2 = 8;
							}
							BaseRoom::$ROOMS[$point->y][$point->x]->addExit($dir_1, $check_x, $check_y);
							BaseRoom::$ROOMS[$check_y2][$check_x2]->addExit($dir_2, $check_x, $check_y);
							$bridge->addExit($dir_1, $point->x, $point->y);
							$bridge->addExit($dir_2, $check_x2, $check_y2);
							
							$exits[$region->getId()] = true;
							$exits[$check_region->getId()] = true;
							$this->bridges[$region->getId().":".$check_region->getId()] = $bridge;
							
							$found = true;
							break 2;
						}
					}
				}
			}
			if (count($this->regions) > 1 && $skipped == 0 && !$found) {
				throw new \Exception("Invalid region - bridge check: ".$region->getRank()." - ".$points." - ".count($region->getPoints()));
			}
		}
	}
	
	protected function generateRegionRank() {
		$ignore_ids = array(World::VOID_ID, World::WALL_ID);
		$grid = array();
		for($y=0; $y<$this->world_size; $y++) {
			$grid[$y] = array();
			for($x=0; $x<$this->world_size; $x++) {
				$grid[$y][$x] = (!in_array($this->world[$y][$x], $ignore_ids) ? 1 : $this->world[$y][$x]);
			}
		}
		
		$region_paths = array();
		for($i=0; $i<$this->total_regions; $i++) {
			#Logger::logString("  Verifying paths from ".$this->regions[$i]->getBasePoint()."!");
			$region = $this->regions[$i]->getBasePoint();
			$region_paths[$i] = array();
			for($j=0; $j<$this->total_regions; $j++) {
				if ($j == $i) {
					continue;
				}
				
				$next_region = $this->regions[$j]->getBasePoint();
				
				$astar_grid = new \BlackScorp\Astar\Grid($grid);
				$astar = new \BlackScorp\Astar\Astar($astar_grid);
				$astar->blocked($ignore_ids);
				$nodes = $astar->search($astar_grid->getPoint($region->y, $region->x), $astar_grid->getPoint($next_region->y, $next_region->x));
				if (count($nodes) > 0) {
					$region_paths[$i][$j] = count($nodes);
				}
			}
		}
		
		$region_paths = $this->buildRegionPath($region_paths, 0);
		$regions = array();
		foreach($region_paths as $order => $region_data) {
			list($region_id, $next_region) = $region_data;
			$region = $this->regions[$region_id];
			$region->setRank($order);
			$regions[$order] = $region;
			
			$order++;
			$region = $this->regions[$next_region];
			$region->setRank($order);
			$regions[$order] = $region;
		}
		$this->regions = $regions;
		
		$bridges = array();
		foreach($this->bridges as $key => $bridge) {
			list($region1_id, $region2_id) = explode(":", $key, 2);
			$region1 = $this->region_ids[$region1_id];
			$region2 = $this->region_ids[$region2_id];
			if ($region1->getRank() > $region2->getRank()) {
				$bridge->setRegion($region1);
				$region1->addBridgeRoom($bridge);
				$key = $region1->getId().":".$region2->getId();
			}
			else {
				$bridge->setRegion($region2);
				$region2->addBridgeRoom($bridge);
				$key = $region2->getId().":".$region1->getId();
			}
			$bridges[$key] = $bridge;
		}
		$this->bridges = $bridges;
		
		if (count($this->regions) < $this->total_regions) {
			throw new \Exception("Invalid exits generated");
		}
	}
	
	protected function buildRegionPath($region_paths, $start_id, $path=array(), $ignore_paths=array()) {
		$min_region = -1;
		$min_steps = 9999999999999;
		foreach($region_paths[$start_id] as $region_id => $steps) {
			if (in_array($region_id, $ignore_paths)) {
				continue;
			}
			if ($steps < $min_steps) {
				$min_region = $region_id;
				$min_steps = $steps;
			}
		}
		if ($min_region > -1) {
			foreach($region_paths[$start_id] as $region_id => $steps) {
				if ($region_id != $min_region) {
					unset($region_paths[$start_id][$region_id]);
				}
			}
			
			$path[] = array($start_id, $min_region);
			$ignore_paths[$start_id] = $start_id;
			$ignore_paths[$min_region] = $min_region;
			$path = $this->buildRegionPath($region_paths, $min_region, $path, $ignore_paths);
		}
		
		return $path;
	}
	
	protected function verifyPath() {
		$ignore_ids = array(World::VOID_ID, World::WALL_ID);
		$grid = array();
		for($y=0; $y<$this->world_size; $y++) {
			$grid[$y] = array();
			for($x=0; $x<$this->world_size; $x++) {
				$grid[$y][$x] = (!in_array($this->world[$y][$x], $ignore_ids) ? 1 : $this->world[$y][$x]);
			}
		}
		
		for($i=0; $i<$this->total_regions; $i++) {
			if (!isset($this->regions[$i + 1])) {
				continue;
			}
			
			#Logger::logString("  Verifying path from ".$this->regions[$i]->getBasePoint()." (".$i.") to ".$this->regions[$i + 1]->getBasePoint()." (".($i + 1).")");
			$region = $this->regions[$i]->getBasePoint();
			$next_region = $this->regions[$i + 1]->getBasePoint();
			
			$astar_grid = new \BlackScorp\Astar\Grid($grid);
			$astar = new \BlackScorp\Astar\Astar($astar_grid);
			$astar->blocked($ignore_ids);
			$nodes = $astar->search($astar_grid->getPoint($region->y, $region->x), $astar_grid->getPoint($next_region->y, $next_region->x));
			if (count($nodes) <= 0) {
				throw new \Exception("Verifying path from ".$this->regions[$i]->getBasePoint()." (".$i.") to ".$this->regions[$i + 1]->getBasePoint()." (".($i + 1).") failed");
			}
		}
	}
	
	protected function generateRegionRoomExits() {
		foreach($this->regions as $region) {
			if (is_array($this->invalid_rooms) && $this->invalid_rooms != array()) {
				if (!isset($this->invalid_rooms[$region->getRank()])) {
					continue;
				}
			}
			
			$points = $region->getPoints();
			$rooms = $region->getRooms();
			foreach($points as $point) {
				$room = $rooms[$point->y][$point->x];
				if (World::checkPlaceholderRegions($this->world[$point->y][$point->x])) {
					continue;
				}
				
				if (is_array($this->invalid_rooms) && $this->invalid_rooms != array()) {
					if (!isset($this->invalid_rooms[$region->getRank()][$point->y][$point->x])) {
						continue;
					}
				}
				
				do {
					$near_rooms = 0;
					foreach(Utils::$CHECKS4 as $check) {
						$check_x = $point->x + $check[0];
						$check_y = $point->y + $check[1];
						if (isset($rooms[$check_y][$check_x]) && !World::checkPlaceholderRegions($this->world[$check_y][$check_x], false)) {
							if ($room->hasExit($check_x, $check_y)) {
								if (isset($this->invalid_rooms[$region->getRank()][$point->y][$point->x])) {
									$this->invalid_rooms[$region->getRank()][$point->y][$point->x] += 25;
								}
								continue;
							}
							$near_rooms++;
							
							$check_rate = 20;
							if (isset($this->invalid_rooms[$region->getRank()][$point->y][$point->x])) {
								$check_rate = $this->invalid_rooms[$region->getRank()][$point->y][$point->x];
							}
							elseif (count($room->getExits()) > 1) {
								$check_rate = 3;
							}
							elseif (count($room->getExits()) > 0) {
								$check_rate = 5;
							}
							
							$check_value = mt_rand(1, 100);
							if ($check_value < $check_rate) {
								#Logger::logString("Check rate for point ".$point." to ".$check_x.",".$check_y." - ".$check_value." < ".$check_rate."!");
								$check_room = $rooms[$check_y][$check_x];
								
								$dir_1 = -1;
								$dir_2 = -1;
								if ($check[0] < 0) {
									$dir_1 = 1;
									$dir_2 = 4;
								}
								elseif ($check[0] > 0) {
									$dir_1 = 4;
									$dir_2 = 1;
								}
								elseif ($check[1] < 0) {
									$dir_1 = 8;
									$dir_2 = 2;
								}
								elseif ($check[1] > 0) {
									$dir_1 = 2;
									$dir_2 = 8;
								}
								
								$room->addExit($dir_1, $check_x, $check_y);
								$check_room->addExit($dir_2, $point->x, $point->y);
								
								if (isset($this->invalid_rooms[$region->getRank()])) {
									unset($this->invalid_rooms[$region->getRank()][$point->y][$point->x]);
									if ($this->invalid_rooms[$region->getRank()][$point->y] == array()) {
										unset($this->invalid_rooms[$region->getRank()][$point->y]);
									}
									if ($this->invalid_rooms[$region->getRank()] == array()) {
										unset($this->invalid_rooms[$region->getRank()]);
									}
								}
							}
							elseif (isset($this->invalid_rooms[$region->getRank()][$point->y][$point->x])) {
								$this->invalid_rooms[$region->getRank()][$point->y][$point->x] += 25;
							}
						}
					}
					if (count($room->getExits()) >= $near_rooms) {
						break;
					}
				} while(count($room->getExits()) <= 1);
			}
		}
	}
	
	protected function verifyRegionRoomExits() {
		foreach($this->regions as $region) {
			$grid = array();
			for($y=0; $y<(2 * $this->world_size + 1); $y++) {
				$grid[$y] = array();
				for($x=0; $x<(2 * $this->world_size + 1); $x++) {
					$grid[$y][$x] = World::VOID_ID;
				}
			}
			
			$points = $region->getPoints();
			foreach($points as $point) {
				$room = BaseRoom::$ROOMS[$point->y][$point->x];
				if ($room instanceof RoomVoid || $room instanceof RoomWall) {
					continue;
				}
				
				#Logger::logString("Starting point: ".$point."!");
				#print_r($room);
				$exits = $room->getExits();
				for($i=0; $i<4; $i++) {
					$check_x = 2 * $point->x + 1;
					$check_y = 2 * $point->y + 1;
					$grid[$check_y][$check_x] = 1;
					#Logger::logString(" Checking point: ".$check_x.",".$check_y."!");
					switch ($i) {
						case 0:
							$check_x--;
							break;
						case 1:
							$check_y++;
							break;
						case 2:
							$check_x++;
							break;
						case 3:
							$check_y--;
							break;
					}
					$exit_dir = pow(2, $i);
					
					$check_value = World::VOID_ID;
					if (isset($this->world[ceil($check_y / 2)][ceil($check_x / 2)]) && !World::checkPlaceholderRegions($this->world[ceil($check_y / 2)][ceil($check_x / 2)])) {
						$check_value = 1;
					}
					#echo "Here: ".$exit_dir." - ".(isset($exits[$exit_dir]) ? 1 : World::VOID_ID)." (".(isset($grid[$check_y][$check_x]) ? $grid[$check_y][$check_x] : "_").") -> ".$check_x.",".$check_y."!\n";;
					if (isset($exits[$exit_dir])) {
						#print_r($room);
						#echo "Here: ".$exit_dir." - ".(isset($exits[$exit_dir]) ? 1 : World::VOID_ID)." (".(isset($grid[$check_y][$check_x]) ? $grid[$check_y][$check_x] : "_").") -> ".$check_x.",".$check_y."!\n";;
						$grid[$check_y][$check_x] = 1;
					}
				}
			}
			#print_r(json_encode($grid));
			#echo "\n";
			
			$region_point = $region->getBasePoint();
			foreach($points as $point) {
				$room = BaseRoom::$ROOMS[$point->y][$point->x];
				if ($room instanceof RoomVoid || $room instanceof RoomWall) {
					continue;
				}
				
				$astar_grid = new \BlackScorp\Astar\Grid($grid);
				$astar = new \BlackScorp\Astar\Astar($astar_grid);
				$astar->blocked(array(World::VOID_ID, World::WALL_ID, World::BRIDGE_ID));
				$nodes = $astar->search($astar_grid->getPoint(2 * $region_point->y + 1, 2 * $region_point->x + 1), $astar_grid->getPoint(2 * $point->y + 1, 2 * $point->x + 1));
				if (count($nodes) <= 0) {
					if (!isset($this->invalid_rooms[$region->getRank()][$point->y][$point->x])) {
						$this->invalid_rooms[$region->getRank()][$point->y][$point->x] = 0;
					}
					$this->invalid_rooms[$region->getRank()][$point->y][$point->x] += 25;
					Logger::logString("Verifying path from ".$region_point." to ".$point." failed - ".$this->invalid_rooms[$region->getRank()][$point->y][$point->x]);
					break;
				}
			}
		}
		
		if (is_array($this->invalid_rooms) && $this->invalid_rooms != array()) {
			return false;
		}
		
		return true;
	}
	
	protected function processRegionDecorations() {
		$used_biomes = array();
		$regions = $this->regions;
		krsort($regions);
		foreach($regions as $region) {
			$parameters = array(
				"rank" => (($region->getRank() + 1) / count($regions) * 100),
			);
			$biome = DataHandler::getObject("biomes", $parameters, $used_biomes);
			$used_biomes[$biome->getId()] = $biome->getId();
			
			#$biome->processModifiers();
			$region->setBiome($biome);
		}
	}
	
	protected function processRoomRegions() {
		for($y=0; $y<$this->world_size; $y++) {
			for($x=0; $x<$this->world_size; $x++) {
				$room_regions = array(
					1 => NULL,
					2 => NULL,
					4 => NULL,
					8 => NULL,
				);
				$id = $this->world[$y][$x];
				#Logger::logString("Room: ".$x.", ".$y." - ".$id."!");
				if ($id == World::VOID_ID) {
					continue;
				}
				elseif ($id == World::WALL_ID || $id == World::BRIDGE_ID) {
					foreach(Utils::$CHECKS4 as $check) {
						$check_x = $x + $check[0];
						$check_y = $y + $check[1];
						
						$check_region_id = World::VOID_ID;
						if (!isset($this->world[$check_y][$check_x])) {
							$check_region_id = $id;
						}
						else {
							$check_region_id = $this->world[$check_y][$check_x];
						}
						
						#Logger::logString(" Here: ".$check_x.",".$check_y." - ".$check_region_id."!");
						if ($check[0] < 0) {
							$room_regions[8] = (trim($room_regions[8]) == "" || World::checkPlaceholderRegions($room_regions[8]) ? $check_region_id : $room_regions[8]);
							$room_regions[1] = (trim($room_regions[1]) == "" || World::checkPlaceholderRegions($room_regions[1]) ? $check_region_id : $room_regions[1]);
						}
						elseif ($check[0] > 0) {
							$room_regions[4] = (trim($room_regions[4]) == "" || World::checkPlaceholderRegions($room_regions[4]) ? $check_region_id : $room_regions[4]);
							$room_regions[2] = (trim($room_regions[2]) == "" || World::checkPlaceholderRegions($room_regions[2]) ? $check_region_id : $room_regions[2]);
						}
						elseif ($check[1] < 0) {
							$room_regions[8] = (trim($room_regions[8]) == "" || World::checkPlaceholderRegions($room_regions[8]) ? $check_region_id : $room_regions[8]);
							$room_regions[4] = (trim($room_regions[4]) == "" || World::checkPlaceholderRegions($room_regions[4]) ? $check_region_id : $room_regions[4]);
						}
						elseif ($check[1] > 0) {
							$room_regions[1] = (trim($room_regions[1]) == "" || World::checkPlaceholderRegions($room_regions[1]) ? $check_region_id : $room_regions[1]);
							$room_regions[2] = (trim($room_regions[2]) == "" || World::checkPlaceholderRegions($room_regions[2]) ? $check_region_id : $room_regions[2]);
						}
					}
				}
				else {
					foreach($room_regions as $key => $val) {
						$room_regions[$key] = $id;
					}
				}
				
				BaseRoom::$ROOMS[$y][$x]->setRoomRegions($room_regions);
			}
		}
	}
	
	protected function processPuzzleTree() {
		$regions = $this->regions;
		$ranks = array();
		foreach($regions as $region) {
			$ranks[$region->getRank()] = $region;
		}
		krsort($ranks);
		$region_order = array_keys($ranks);
		
		$total_puzzles = 0;
		$region_ranks = array();
		$rank_order = array();
		foreach($region_order as $order => $region_rank) {
			$rank_order[$region_rank] = $order;
			$region = $ranks[$region_rank];
			$region_data[$order] = array(
				"region" => $region,
				"rank" => $region->getRank(),
				"puzzles" => 0,
				"total_puzzles" => $region->getPuzzleCount(),
				"locked" => false,
			);
			$total_puzzles = $region_data[$order]["total_puzzles"];
		}
		$total_puzzles += count($this->bridges);
		#print_r($region_data);
		#print_r($region_order);
		#die;
		
		//Regions should be accessed via '$region_data[$order]' to ensure the data persists
		
		$parameters = array(
			"rank" => (($region->getRank() + 1) / count($regions) * 100),
			"type" => "end game",
		);
		$end_game = DataHandler::getObject("puzzles", "end game");
		$this->puzzle_tree = new PuzzleTree($region_data[0]["region"], $end_game);
		$room = $this->findPuzzleRoom($region_data[0]["region"]);
		$room->setPuzzle($end_game);
		$region_data[0]["puzzles"]++;
		Logger::logString("End game puzzle in room: ".$room->getId()." -> ".$room->getPoint()."!");
		
		$region_count = count($this->regions);
		foreach($region_order as $order => $region_rank) {
			$region = $region_data[$order]["region"];
			do {
				$puzzle_region_rank = $region_rank;
				//Determine next region by y / (2^x)
				$total = pow(2, $region_count);
				$next_percent = mt_rand(1, $total) / $total;
				#echo "Here: ".$total." - ".$next_percent."!\n";
				for($i=($region_count - 1); $i>=0; $i--) {
					#echo " Here ".$i.": ".$total." - ".(pow(2, $i) / $total)." <= ".$next_percent."!\n";
					if (pow(2, $i) / $total <= $next_percent) {
						$puzzle_region_rank = $i;
						break;
					}
				}
				
				Logger::logString("Processing puzzle from region ".$region->getRank()."!");
				$puzzle_region = $this->regions[$puzzle_region_rank];
				
				$generic_puzzle = DataHandler::getObject("puzzles", "generic");
				$this->puzzle_tree->createBranch($puzzle_region, $generic_puzzle, $this->puzzle_tree);
				$room = $this->findPuzzleRoom($puzzle_region);
				$room->setPuzzle($generic_puzzle);
				Logger::logString("  Generating puzzle in region: ".$puzzle_region->getRank()." -> ".$room->getPoint()."!");
				
				$puzzle_order = $rank_order[$puzzle_region->getRank()];
				$region_data[$puzzle_order]["puzzles"]++;
				
				if ($region_data[$puzzle_order]["puzzles"] > $region_data[$puzzle_order]["total_puzzles"]) {
					print_r($region_data);
					print_r($region_order);
					throw new \Exception("Too many puzzles generated for ".$puzzle_order." - ".$region_data[$puzzle_order]["puzzles"]." > ".$region_data[$puzzle_order]["total_puzzles"]."\n");
				}
				elseif ($region_data[$puzzle_order]["puzzles"] == $region_data[$puzzle_order]["total_puzzles"] - 1) {
					if ($puzzle_region->getRank() == 0) {
						$start_game = DataHandler::getObject("puzzles", "start game");
						$this->puzzle_tree->createBranch($puzzle_region, $start_game, $this->puzzle_tree);
						$room = $this->findPuzzleRoom($puzzle_region);
						$room->setPuzzle($start_game);
						Logger::logString("Start game puzzle in room: ".$room->getId()." -> ".$room->getPoint()."!");
					}
					else {
						$locked_puzzle = DataHandler::getObject("puzzles", "locked bridge");
						$this->puzzle_tree->createBranch($puzzle_region, $locked_puzzle, $this->puzzle_tree);
						$bridge_room = $puzzle_region->getBridgeRoom();
						if (!$bridge_room) {
							echo "Invalid bridge rooms\n";
							print_r($puzzle_region);
							die;
						}
						$bridge_room->setPuzzle($locked_puzzle);
					}
					$region_data[$puzzle_order]["puzzles"]++;
					$region_data[$puzzle_order]["locked"] = true;
					$region_count--;
				}
			} while(!$region_data[$order]["locked"]);
		}
		
		//add start room
		//$start_game = new Puzzle();
		//$this->puzzle_tree->createBranch($start_game);
		$region_data[$region_order[count($region_order) - 1]]["puzzles"]++;
	}
	
	protected function findPuzzleRoom($region) {
		$rooms = array();
		foreach($region->getPoints() as $point) {
			if (World::checkPlaceholderRegions($this->world[$point->y][$point->x])) {
				continue;
			}
			
			$rooms[] = BaseRoom::$ROOMS[$point->y][$point->x];
		}
		
		$room = NULL;
		do {
			if (count($rooms) <= 0) {
				echo "No valid rooms for puzzles: ".$region_id."?\n";
				print_r($this->regions[$region_id]);
				die;
			}
			
			for($k=0; $k<7; $k++) {
				Utils::mt_shuffle($rooms);
			}
			$room = array_pop($rooms);
		} while(!is_null($room->getPuzzle()));
		
		return $room;
	}
	
	public static function checkPlaceholderRegions($region_id, $include_bridges=true) {
		$ignore_ids = array(
			World::VOID_ID,
			World::WALL_ID,
		);
		if ($include_bridges) {
			$ignore_ids[] = World::BRIDGE_ID;
		}
		
		return (in_array($region_id, $ignore_ids));
	}
}
?>