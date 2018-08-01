<?php
namespace PGCelda\Utils;

class BinaryWriter {
	
	public function saveWorld() {
		if (trim($this->id) == "" || $this->id < 0) {
			throw new \Exception("Invalid world");
		}
		
		$world_dir = CFG::get("worlds_dir")."/world".$this->id;
		if (!is_dir($world_dir)) {
			mkdir($world_dir);
		}
		
		$filename = $world_dir."/world".$this->id.".wld";
		if ($fp = fopen($filename, "wb")) {
			$this->logString("Began saving world '".$filename."'");
			
			$this->logString("Writing Header");
			$this->writeString($fp, (string) chr(0x89).chr(0x49).chr(0x53).chr(0x4F).chr(0x0D).chr(0x0A).chr(0x1A).chr(0x0A));
			
			$this->logString("Writing Version");
			$this->writeString($fp, "ISOEASEL".IsoEasel::VERSION);
			
			$this->logString("Writing Seed");
			$this->writeString($fp, str_pad($this->seed, 16, "0", STR_PAD_LEFT));
			
			$this->logString("Writing World Id");
			$this->writeString($fp, $this->id);
			
			$this->logString("Writing World Name");
			$this->writeString($fp, $this->name);
			
			$this->logString("Writing Landmass Data");
			$this->writeString($fp, count($this->landmasses));		//landmass count
			foreach($this->landmasses as $landmass_id => $landmass) {
				$this->writeString($fp, $this->landmass_id);		//landmass id
				if ($landmass instanceof Landmass) {
					$landmass->saveLandmass();
				}
			}
			
			fclose($fp);
			
			$this->logString("Finished saving world '".$filename."'");
			
			return true;
		}
		
		return false;
	}
	
	protected function readString($fp) {
		$string = NULL;
		$size = ord(fread($fp, 1));
		if ($size > 0) {
			$string = fread($fp, $size);
		}
		
		return $string;
	}
	
	protected function writeString($fp, $string) {
		$size = strlen($string);
		if ($size > 0) {
			fwrite($fp, chr($size));
			fwrite($fp, $string, $size);
		}
		
		return true;
	}
}
?>