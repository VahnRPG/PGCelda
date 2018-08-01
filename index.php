<html>
<head>
<title>PGCelda - A Procedurally Generated World at your Fingertips</title>
</head>
<body>
<?php
require_once(dirname(__FILE__)."/vendor/autoload.php");

use PGCelda\PGCelda;
use PGCelda\Utils\CFG;
use PGCelda\Utils\Logger;

function replace_root_dir($path) {
	/*
	echo "Here1: ".CFG::get("root_dir")."!<br>";
	echo "Here2: ".$path."!<br>";
	echo "Here3: ".str_replace(CFG::get("root_dir"), ".", $path)."!<br>";
	//*/
	return str_replace(CFG::get("root_dir"), ".", $path);
}

try {
	CFG::set("DEBUG_MODE", false);
	//*
	$seed = time();
	$seed = 1532579602;
	echo "Seed: ".$seed."!<br>";
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
	echo "Biome Colors:<br><img src=\"".replace_root_dir($game->createBiomeColorImage())."\"><hr>";
	echo "World Image:<br><img src=\"".replace_root_dir($game->createWorldImage())."\"><hr>";
	echo "Room Path:<br><img src=\"".replace_root_dir($game->createWorldRoomsPathImage())."\"><hr>";
	echo "Room Path + Puzzles:<br><img src=\"".replace_root_dir($game->createWorldRoomsPuzzlesImage())."\"><hr>";
	echo "Borderless World:<br><img src=\"".replace_root_dir($game->createWorldBorderlessImage())."\"><hr>";
	echo "Regions:<br><img src=\"".replace_root_dir($game->createWorldRegionsImage())."\"><hr>";
	echo "Puzzle Tree:<br><img src=\"".replace_root_dir($game->createPuzzlesTreeImage())."\"><hr>";
}
catch (\Exception $e) {
	Logger::logString("ERROR: ".$e->getMessage());
}
?>
</body>
</html>