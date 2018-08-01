<?php
namespace PGCelda\Data;

use PGCelda\World\WorldRegion;
use PGCelda\Utils\Logger;
use PGCelda\Utils\Utils;

class PuzzleTree {
	protected $region = NULL;
	protected $puzzle = NULL;
	protected $branches = array();
	protected $total_depth = -1;
	protected $total_length = -1;
	
	public function __construct(WorldRegion $region, DataPuzzle $puzzle) {
		#print_r($puzzle);
		$this->region = $region;
		$this->puzzle = $puzzle;
	}
	
	public function getRegion() {
		return $this->region;
	}
	
	public function getPuzzle() {
		return $this->puzzle;
	}
	
	public function getBranches() {
		return $this->branches;
	}
	
	public function addBranch(PuzzleTree $puzzle_tree) {
		$this->branches[] = $puzzle_tree;
	}
	
	public function createBranch(WorldRegion $region, DataPuzzle $puzzle, PuzzleTree $parent, $indent=0) {
		$rand_value = mt_rand(0, 100);
		$check_value = 100 - (count($parent->getBranches()) * 25);
		#Logger::logString(str_repeat("\t", $indent)."Puzzle Tree Depth: ".$indent." - ".count($parent->getBranches())." - ".$rand_value." < ".$check_value."!");
		//TODO: Determine chance for branch creation based on how many branches in parent
		if (count($parent->getBranches()) == 0 || $rand_value < $check_value) {
			return $this->createLeaf($region, $puzzle, $parent);
		}
		elseif (count($parent->getBranches()) > 0) {
			$branches = $parent->getBranches();
			for($k=0; $k<7; $k++) {
				Utils::mt_shuffle($branches);
			}
			$branch = array_pop($branches);
			
			return $this->createBranch($region, $puzzle, $branch, $indent + 1);
		}
	}
	
	public function createLeaf(WorldRegion $region, DataPuzzle $puzzle, PuzzleTree $parent) {
		$puzzle_tree = new PuzzleTree($region, $puzzle);
		
		$parent->addBranch($puzzle_tree);
		
		return true;
	}
	
	public function getTotalDepth() {
		$this->total_depth = 0;
		if (count($this->getBranches()) > 0) {
			$this->getBranchesMaxDepth($this->getBranches());
		}
		
		return $this->total_depth + 1;
	}
	
	protected function getBranchesMaxDepth($branches, $depth=1) {
		if ($this->total_depth < $depth) {
			$this->total_depth = $depth;
		}
		
		foreach($branches as $branch) {
			if (count($branch->getBranches()) > 0) {
				$this->getBranchesMaxDepth($branch->getBranches(), $depth + 1);
			}
		}
	}
	
	public function getTotalLength() {
		$this->total_length = 0;
		if (count($this->getBranches()) > 0) {
			$this->getBranchesMaxLength($this->getBranches());
		}
		
		return $this->total_length + 1;
	}
	
	protected function getBranchesMaxLength($branches) {
		foreach($branches as $branch) {
			$this->total_length++;
			if (count($branch->getBranches()) > 0) {
				$this->getBranchesMaxLength($branch->getBranches());
			}
		}
	}
	
	public function __toString() {
		$output = $this->getPuzzle()->getId()."\n";
		$output .= $this->branchesToString($this->getBranches());
		
		return $output;
	}
	
	protected function branchesToString($branches, $indent=1) {
		$output = "";
		foreach($branches as $branch) {
			$output .= str_repeat("\t", $indent).$branch->getPuzzle()->getId()."\n";
			if (count($branch->getBranches()) > 0) {
				$output .= $this->branchesToString($branch->getBranches(), $indent + 1);
			}
		}
		
		return $output;
	}
}
?>