<?php

// test
require_once('Walkway.php');
require_once('Elan.php');
require_once('CalhounBeachClub.php');

echo PHP_EOL . "starting Walkway" . PHP_EOL;
$walkway = new Walkway();
$data = $walkway->sendRequest()->parseData();
$walkway->getFloorPlans();

echo "starting Elan" . PHP_EOL;
$elan = new Elan();
$data = $elan->sendRequest()->parseData();
$elan->getFloorPlans();

// Uncomment to try to reinsert floorplans
// $numRows = $elan->insertAllFloorplans();
// echo $numRows['affected'] . " rows out of " . $numRows['total'] . "     inserted." . PHP_EOL;


echo "starting Calhoun Beach Club" . PHP_EOL;
$cbc = new CalhounBeachClub();
$cbc->sendRequest()->parseData();
$cbc->getFloorPlans();

echo "done: " . date("Y/m/d H:i:s") . PHP_EOL;

?>
