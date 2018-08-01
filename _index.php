<?php
require_once(dirname(__FILE__)."/vendor/autoload.php");

use PGCelda\PGCelda;
use PGCelda\Utils\CFG;
use PGCelda\Utils\Logger;

try {
	CFG::set("DEBUG_MODE", true);
	//*
	$seed = time();
	$seed = 1532579602;
	echo "Seed: ".$seed."!\n";
	$override_seed = NULL;
	#$override_seed = true;
	#$override_seed = 1532662374;
	#$override_seed = 1532662482;
	
	$world_size = 16;
	$regions = 5;
	//*/
	
	$game = new PGCelda();
	if (!$game->loadWorld($seed)) {
		Logger::logString("Beginning generating world");
		$successful = false;
		do {
			#try {
				$successful = $game->generateWorld($seed, $override_seed, $world_size, $regions);
				if (is_null($successful)) {
					die;
				}
			/*}
			catch (Exception $e) {
				echo "  ERROR: ".$e->getMessage()."\n";
			}
			*/
		} while(!$successful);
		Logger::logString("Finished generating world");
	}
	$world = $game->getWorld();
	#print_r($world);
	#$game->createBiomeColorImage();
	$game->createWorldImage();
	$game->createWorldRoomsPathImage();
	$game->createWorldRoomsPuzzlesImage();
	$game->createWorldBorderlessImage();
	$game->createWorldRegionsImage();
	$game->createPuzzlesTreeImage();
}
catch (\Exception $e) {
	Logger::logString("ERROR: ".$e->getMessage());
}
?>