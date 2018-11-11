<?php
  1 
  2    require_once('Walkway.php');
  3    require_once('Elan.php');
  4    require_once('CalhounBeachClub.php');
  5 
  6    $walkway = new Walkway();
  7    $data = $walkway->sendRequest()->parseData();
  8 
  9    $walkway->getFloorPlans();
 10    echo $walkway->getApartmentInfo('name') . PHP_EOL;
 11    echo $walkway->getApartmentInfo('address') . PHP_EOL;
 12    //var_export($data);
 13 
 14    $elan = new Elan();
 15    $data = $elan->sendRequest()->parseData();
 16    $elan->getFloorPlans();
 17 
 18    // Uncomment to try to reinsert floorplans
 19 // $numRows = $elan->insertAllFloorplans();
 20 // echo $numRows['affected'] . " rows out of " . $numRows['total'] . "     inserted." . PHP_EOL;
 21 
 22    //echo(json_encode($obj));
 23 
 24    $cbc = new CalhounBeachClub();
 25    $cbc->sendRequest()->parseData();
 26    $cbc->getFloorPlans();
 27 
 28    echo "done" . PHP_EOL;
 29 
 30 ?>
