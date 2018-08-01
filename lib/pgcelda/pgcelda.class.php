<?php
namespace PGCelda;

use PGCelda\Data\DataHandler;
use PGCelda\Utils\CFG;
use PGCelda\Utils\Logger;
use PGCelda\Utils\Utils;
use PGCelda\Utils\Vector2;
use PGCelda\World\World;
use PGCelda\World\Rooms\BaseRoom;
use PGCelda\World\Rooms\RoomBridge;

class PGCelda {
	const VERSION = "0.0.1";
	
	protected $world = NULL;
	
	public function __construct() {
	}
	
	public function getWorld() {
		return $this->world;
	}
	
	public function loadWorld($world_id) {
		return false;
		
		$this->world = World::getWorld($world_id);
		if ($this->world) {
			return true;
		}
		
		return false;
	}
	
	public function generateWorld($seed=NULL, $world_size=NULL, $total_regions=NULL) {
		if (is_null($this->world)) {
			if (is_null($seed) || trim($seed) == "" || !is_numeric($seed)) {
				$seed = time();
			}
			$this->world = new World($seed, $world_size, $total_regions);
		}
		
		return $this->world->generate();
	}
	
	public function createBiomeColorImage() {
		$width = 200;
		$line_height = 15;
		$biomes = DataHandler::$DATA["biomes"];
		$im = imagecreatetruecolor($width, count($biomes) / 2 * $line_height);
		$color = imagecolorallocate($im, 255, 255, 255);
		imagefill($im, 0, 0, $color);
		
		ob_start();
		
		$count = 0;
		foreach($biomes as $biome_id => $biome) {
			if (preg_match('/^data_id\-/i', $biome_id)) {
				continue;
			}
			
			$color = $biome->getColor()->getImageColor($im);
			#imagestring($im, 5, 0, $count * $line_height, " Region #".$region->getRank()." - ".ucwords("rainforest"), $color);
			imagestring($im, 5, 0, $count * $line_height, " ".ucwords($biome->getName()), $color);
			imagefilledrectangle($im, 0, ($count + 1) * $line_height - 1, $width - 90, ($count + 1) * $line_height - 1, $color);
			imagefilledrectangle($im, $width - 90, $count * $line_height, $width, ($count + 1) * $line_height, $color);
			$count++;
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/biomes.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createWorldImage($with_labels=true) {
		$world = $this->world->getWorld();
		$regions = $this->world->getRegions();
		$world_size = $this->world->getSize();
		$world_scale = 8;
		$im = imagecreatetruecolor($world_size * $world_scale, $world_size * $world_scale);
		$color = imagecolorallocate($im, 48, 48, 48);
		imagefill($im, 0, 0, $color);
		
		$color_ids = array(
			-3 => DataHandler::getObject("colors", "black")->getImageColor($im),
			-2 => DataHandler::getObject("colors", "purple")->getImageColor($im),
			-1 => DataHandler::getObject("colors", "grey")->getImageColor($im),
		);
		
		foreach($regions as $region) {
			$color_ids[$region->getId()] = $region->getBiome()->getColor()->getImageColor($im);
		}
		
		ob_start();
		
		for($y=0; $y<$world_size; $y++) {
			for($x=0; $x<$world_size; $x++) {
				$id = $world[$y][$x];
				for($i=0; $i<$world_scale; $i++) {
					for($j=0; $j<$world_scale; $j++) {
						imagesetpixel($im, ($x * 8) + $i, ($y * 8) + $j, $color_ids[$id]);
					}
				}
			}
		}
		
		$scale = 4;
		$im = imagescale($im, $world_size * $world_scale * $scale, $world_size * $world_scale * $scale, IMG_NEAREST_NEIGHBOUR);
		
		if ($with_labels) {
			$text_color = DataHandler::getObject("colors", "white")->getImageColor($im);
			foreach($regions as $region) {
				$base_point = $region->getBasePoint();
				imagestring($im, 5, $base_point->x * $world_scale * $scale, $base_point->y * $world_scale * $scale, "#".$region->getRank()." Region", $text_color);
				imagestring($im, 5, $base_point->x * $world_scale * $scale, ($base_point->y * $world_scale * $scale) + 15, ucwords($region->getBiome()->getName()), $text_color);
			}
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id.".png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createWorldRoomsPathImage($with_labels=true) {
		$world = $this->world->getWorld();
		$world_size = $this->world->getSize();
		$world_scale = 8;
		$im = imagecreatetruecolor($world_size * $world_scale, $world_size * $world_scale);
		$color = imagecolorallocate($im, 48, 48, 48);
		imagefill($im, 0, 0, $color);
		
		$colors = array(
			-3 => DataHandler::getObject("colors", "black"),
			-2 => DataHandler::getObject("colors", "purple"),
			-1 => DataHandler::getObject("colors", "grey"),
		);
		$regions = $this->world->getRegions();
		foreach($regions as $region) {
			$colors[$region->getId()] = $region->getBiome()->getColor();
		}
		$color_ids = array();
		foreach($colors as $id => $color) {
			$color_ids[$id] = $color->getImageColor($im);
		}
		
		ob_start();
		
		for($y=0; $y<$world_size; $y++) {
			for($x=0; $x<$world_size; $x++) {
				$id = $world[$y][$x];
				
				$room_value = "floor";
				if (isset(BaseRoom::$ROOMS[$y][$x])) {
					$exits = BaseRoom::$ROOMS[$y][$x]->getExits();
					//Subtract 15 from the exits to determine which are closed. If an exit is set, it's open, so don't block it off
					$room_value = str_pad(decbin(15 - array_sum(array_keys($exits))), 4, "0", STR_PAD_LEFT);
				}
				$room_filename = CFG::get("rooms_images_dir")."/room-".$room_value.".png";
				
				$copy_img = imagecreatefrompng($room_filename);
				list($width, $height) = getimagesize($room_filename);
				
				$color = $colors[$id]->getColors();
				imagefilter($copy_img, IMG_FILTER_COLORIZE, $color["red"], $color["green"], $color["blue"], 48);
				
				imagecopy($im, $copy_img, $x * $width, $y * $height, 0, 0, $width, $height);
			}
		}
		
		$scale = 4;
		$im = imagescale($im, $world_size * $world_scale * $scale, $world_size * $world_scale * $scale, IMG_NEAREST_NEIGHBOUR);
		
		if ($with_labels) {
			$border_color = DataHandler::getObject("colors", "white")->getImageColor($im);
			$text_color = DataHandler::getObject("colors", "black")->getImageColor($im);
			
			foreach($regions as $region) {
				$base_point = $region->getBasePoint();
				imagestring($im, 5, $base_point->x * $world_scale * $scale, $base_point->y * $world_scale * $scale, "#".$region->getRank()." Region", $text_color);
				imagestring($im, 5, $base_point->x * $world_scale * $scale, ($base_point->y * $world_scale * $scale) + 15, ucwords($region->getBiome()->getName()), $text_color);
				imagestring($im, 5, $base_point->x * $world_scale * $scale, ($base_point->y * $world_scale * $scale) + 30, $base_point, $text_color);
			}
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id."-room-paths.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createWorldRoomsPuzzlesImage($with_labels=true) {
		$world = $this->world->getWorld();
		$world_size = $this->world->getSize();
		$world_scale = 8;
		$im = imagecreatetruecolor($world_size * $world_scale, $world_size * $world_scale);
		$color = imagecolorallocate($im, 48, 48, 48);
		imagefill($im, 0, 0, $color);
		
		$colors = array(
			-3 => DataHandler::getObject("colors", "black"),
			-2 => DataHandler::getObject("colors", "purple"),
			-1 => DataHandler::getObject("colors", "brown"),
		);
		$regions = $this->world->getRegions();
		foreach($regions as $region) {
			$colors[$region->getId()] = $region->getBiome()->getColor();
		}
		$color_ids = array();
		foreach($colors as $id => $color) {
			$color_ids[$id] = $color->getImageColor($im);
		}
		
		ob_start();
		
		for($y=0; $y<$world_size; $y++) {
			for($x=0; $x<$world_size; $x++) {
				$id = $world[$y][$x];
				
				$puzzle = NULL;
				$room = NULL;
				$room_value = "floor";
				if (isset(BaseRoom::$ROOMS[$y][$x])) {
					$room = BaseRoom::$ROOMS[$y][$x];
					$puzzle = $room->getPuzzle();
					$exits = $room->getExits();
					//Subtract 15 from the exits to determine which are closed. If an exit is set, it's open, so don't block it off
					$room_value = str_pad(decbin(15 - array_sum(array_keys($exits))), 4, "0", STR_PAD_LEFT);
				}
				$room_filename = CFG::get("rooms_images_dir")."/room-".$room_value.".png";
				
				$copy_img = imagecreatefrompng($room_filename);
				list($width, $height) = getimagesize($room_filename);
				
				$color = $colors[$id]->getColors();
				imagefilter($copy_img, IMG_FILTER_COLORIZE, $color["red"], $color["green"], $color["blue"], 48);
				
				imagecopy($im, $copy_img, $x * $width, $y * $height, 0, 0, $width, $height);
				
				if (!is_null($puzzle)) {
					$puzzle_image = CFG::get("images_dir")."/".$puzzle->getMapImage();
					if (trim($puzzle->getMapImage()) != "" && file_exists($puzzle_image)) {
						$copy_img = imagecreatefrompng($puzzle_image);
						list($width, $height) = getimagesize($puzzle_image);
						
						$color = $puzzle->getColor()->getColors();
						imagefilter($copy_img, IMG_FILTER_COLORIZE, $color["red"], $color["green"], $color["blue"], 48);
						imagecopy($im, $copy_img, $x * $width, $y * $height, 0, 0, $width, $height);
					}
				}
			}
		}
		
		$scale = 4;
		$im = imagescale($im, $world_size * $world_scale * $scale, $world_size * $world_scale * $scale, IMG_NEAREST_NEIGHBOUR);
		
		if ($with_labels) {
			$border_color = DataHandler::getObject("colors", "white")->getImageColor($im);
			$text_color = DataHandler::getObject("colors", "black")->getImageColor($im);
			
			foreach($regions as $region) {
				$base_point = $region->getBasePoint();
				imagestring($im, 5, $base_point->x * $world_scale * $scale, $base_point->y * $world_scale * $scale, "#".$region->getRank()." Region", $text_color);
				imagestring($im, 5, $base_point->x * $world_scale * $scale, ($base_point->y * $world_scale * $scale) + 15, ucwords($region->getBiome()->getName()), $text_color);
				imagestring($im, 5, $base_point->x * $world_scale * $scale, ($base_point->y * $world_scale * $scale) + 30, $base_point, $text_color);
			}
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id."-room-puzzles.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createWorldBorderlessImage($with_labels=true) {
		$world = $this->world->getWorld();
		$regions = $this->world->getRegions();
		$world_size = $this->world->getSize();
		$im = imagecreatetruecolor($world_size * 2, $world_size * 2);
		$color = imagecolorallocate($im, 48, 48, 48);
		imagefill($im, 0, 0, $color);
		
		$color_ids = array(
			-3 => DataHandler::getObject("colors", "black")->getImageColor($im),
			-2 => DataHandler::getObject("colors", "purple")->getImageColor($im),
			-1 => DataHandler::getObject("colors", "grey")->getImageColor($im),
		);
		
		foreach($regions as $region) {
			$color_ids[$region->getId()] = $region->getBiome()->getColor()->getImageColor($im);
		}
		
		ob_start();
		
		for($y=0; $y<$world_size; $y++) {
			for($x=0; $x<$world_size; $x++) {
				if (!isset(BaseRoom::$ROOMS[$y][$x])) {
					continue;
				}
				
				$room_regions = BaseRoom::$ROOMS[$y][$x]->getRoomRegions();
				foreach($room_regions as $direction => $region_id) {
					$dir_x = 0;
					$dir_y = 0;
					switch ($direction) {
						case 8:
							$dir_x = 0;
							$dir_y = 0;
							break;
						case 4:
							$dir_x = 1;
							$dir_y = 0;
							break;
						case 2:
							$dir_x = 1;
							$dir_y = 1;
							break;
						case 1:
							$dir_x = 0;
							$dir_y = 1;
							break;
						default:
							throw new \Exception("Invalid direction: ".$direction);
					}
					imagesetpixel($im, ($x * 2) + $dir_x, ($y * 2) + $dir_y, $color_ids[$region_id]);
				}
			}
		}
		
		$scale = 2 * 36;
		$im = imagescale($im, $world_size * $scale, $world_size * $scale, IMG_NEAREST_NEIGHBOUR);
		
		if ($with_labels) {
			$text_color = DataHandler::getObject("colors", "white")->getImageColor($im);
			foreach($regions as $region) {
				$base_point = $region->getBasePoint();
				imagestring($im, 5, $base_point->x * $scale, $base_point->y * $scale, "#".$region->getRank()." Region", $text_color);
				imagestring($im, 5, $base_point->x * $scale, ($base_point->y * $scale) + 15, ucwords($region->getBiome()->getName()), $text_color);
			}
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id."-borderless.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createWorldRegionsImage($with_labels=true) {
		$width = 300;
		$line_height = 15;
		$world = $this->world->getWorld();
		$regions = $this->world->getRegions();
		$im = imagecreatetruecolor($width, count($regions) * $line_height);
		$color = imagecolorallocate($im, 255, 255, 255);
		imagefill($im, 0, 0, $color);
		
		ob_start();
		
		if ($with_labels) {
			$count = 0;
			foreach($regions as $region) {
				$color = $region->getBiome()->getColor()->getImageColor($im);
				imagestring($im, 5, 0, $count * $line_height, " Region #".$region->getRank()." - ".ucwords($region->getBiome()->getName()), $color);
				imagefilledrectangle($im, 0, ($count + 1) * $line_height - 1, $width - 90, ($count + 1) * $line_height - 1, $color);
				imagefilledrectangle($im, $width - 90, $count * $line_height, $width, ($count + 1) * $line_height, $color);
				$count++;
			}
		}
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id."-regions.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	public function createPuzzlesTreeImage() {
		$width = 60;
		$line_height = 10;
		$regions = $this->world->getRegions();
		$puzzle_tree = $this->world->getPuzzleTree();
		Logger::logString("Puzzle Tree: (".$puzzle_tree->getTotalDepth()." x ".$puzzle_tree->getTotalLength().") - (".($puzzle_tree->getTotalDepth() * $width)." x ".($puzzle_tree->getTotalLength() * $line_height).")\n".$puzzle_tree);
		$im = imagecreatetruecolor($puzzle_tree->getTotalDepth() * $width, $puzzle_tree->getTotalLength() * $line_height);
		$color = imagecolorallocate($im, 255, 255, 255);
		imagefill($im, 0, 0, $color);
		
		$color_ids = array(
			-3 => DataHandler::getObject("colors", "black"),
			-2 => DataHandler::getObject("colors", "purple"),
			-1 => DataHandler::getObject("colors", "grey"),
		);
		
		foreach($regions as $region) {
			$color_ids[$region->getId()] = $region->getBiome()->getColor();
		}
		#print_r($color_ids);
		
		ob_start();
		
		#echo $puzzle_tree->getPuzzle()->getId()." -> ".$puzzle_tree->getTotalDepth()."\n";
		#print_r($puzzle_tree->getPuzzle());
		#die;
		$line = 0;
		$this->displayPuzzleIcon($im, $color_ids, $puzzle_tree->getRegion(), $puzzle_tree->getPuzzle(), 0, 0, $line_height);
		$this->processPuzzleTreeBranch($im, $color_ids, $line_height, $line, $puzzle_tree->getBranches());
		#die;
		
		$scale = 4;
		$im = imagescale($im, $puzzle_tree->getTotalDepth() * $width * $scale, $puzzle_tree->getTotalLength() * $line_height * $scale, IMG_NEAREST_NEIGHBOUR);
		
		imagepng($im);
		
		$contents = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		$filename = CFG::get("generated_images_dir")."/world-".$this->world->id."-puzzle-tree.png";
		$fp = fopen($filename, "w");
		fwrite($fp, $contents);
		fclose($fp);
		
		return $filename;
	}
	
	protected function processPuzzleTreeBranch($im, $color_ids, $line_height, &$line, $branches, $indent=1) {
		foreach($branches as $branch) {
			$line++;
			$this->displayPuzzleIcon($im, $color_ids, $branch->getRegion(), $branch->getPuzzle(), $indent, $line, $line_height);
			#echo str_repeat("\t", $indent).$branch->getPuzzle()->getId()." - ".$indent."\n";
			if (count($branch->getBranches()) > 0) {
				$this->processPuzzleTreeBranch($im, $color_ids, $line_height, $line, $branch->getBranches(), $indent + 1);
			}
		}
	}
	
	protected function displayPuzzleIcon($im, $color_ids, $region, $puzzle, $indent, $line, $line_height) {
		$puzzle_image = CFG::get("images_dir")."/".$puzzle->getTreeImage();
		if (trim($puzzle->getMapImage()) != "" && file_exists($puzzle_image)) {
			$copy_img = imagecreatefrompng($puzzle_image);
			list($width, $height) = getimagesize($puzzle_image);
			
			#$color = $puzzle->getColor()->getColors();
			$color = $color_ids[$region->getId()]->getColors();
			#print_r($color);
			imagefilter($copy_img, IMG_FILTER_COLORIZE, $color["red"], $color["green"], $color["blue"], 48);
			imagecopy($im, $copy_img, $indent * $width, $line * $line_height, 0, 0, $width, $height);
			
			$text_color = DataHandler::getObject("colors", "black")->getImageColor($im);
			imagestring($im, 1, $indent * $width + 2, $line * $line_height, $region->getRank(), $text_color);
			imagestring($im, 1, ($indent + 1) * $width, $line * $line_height, " - ".$puzzle->getId()." - ".$puzzle->getName()." - ".$indent, $text_color);
		}
	}
}
?>