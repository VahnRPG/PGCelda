<?php
namespace PGCelda\Utils;

class CFG {
	protected static $config = array();
	
	public static function get($name, $default=NULL) {
		return isset(CFG::$config[$name]) ? CFG::$config[$name] : $default;
	}
	
	public static function has($name) {
		return array_key_exists($name, CFG::$config);
	}
	
	public static function set($name, $value) {
		CFG::$config[$name] = $value;
	}
	
	public static function add($parameters=array()) {
		CFG::$config = array_merge(CFG::$config, $parameters);
	}
	
	public static function getAll() {
		return CFG::$config;
	}
	
	public static function clear() {
		CFG::$config = array();
	}
}

CFG::set("DEBUG_MODE", false);

$root_dir = realpath(dirname(__FILE__)."/../../..");
CFG::set("root_dir", $root_dir);

CFG::set("data_dir", CFG::get("root_dir")."/data");
CFG::set("puzzles_data_dir", CFG::get("data_dir")."/puzzles");
CFG::set("region_data_dir", CFG::get("data_dir")."/regions");
CFG::set("region_biomes_data_dir", CFG::get("region_data_dir")."/biomes");

CFG::set("images_dir", CFG::get("root_dir")."/images");
CFG::set("generated_images_dir", CFG::get("images_dir")."/generated_images");
CFG::set("items_images_dir", CFG::get("images_dir")."/items");
CFG::set("objects_images_dir", CFG::get("images_dir")."/objects");
CFG::set("puzzles_images_dir", CFG::get("images_dir")."/puzzles");
CFG::set("rooms_images_dir", CFG::get("images_dir")."/rooms");
CFG::set("sprites_images_dir", CFG::get("images_dir")."/sprites");
?>