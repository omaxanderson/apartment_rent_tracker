<?php

require_once('Apartment.php');
require_once('Database.php');

class Elan extends Apartment {

	protected $APARTMENT_ID = 2;
	protected $url = 'https://www.elanuptown.com/floorplans.php?type=elan';
	protected $extraUrls = array(
		'https://www.elanuptown.com/inc/floorplan-slide-generator.php?subtype=townhomes&type=elan-deux&bldgtype=1&duexname=townhomes',
		'https://www.elanuptown.com/inc/floorplan-slide-generator.php?subtype=studio&type=elan-deux&bldgtype=0&duexname=west',
		'https://www.elanuptown.com/inc/floorplan-slide-generator.php?subtype=1+bedroom&type=elan-deux&bldgtype=0&duexname=center',
		'https://www.elanuptown.com/inc/floorplan-slide-generator.php?subtype=2+bedroom&type=elan-deux&bldgtype=0&duexname=center',
	);
	protected $resp;
	protected $data;

	function parseData() {
		$data = $this->resp;
		$startIdx = strpos($data, "var default_mmc_mits");
		while ($data[$startIdx++] != '[' && $startIdx < strlen($data)) {}
		$startIdx --;

		for ($endIdx = $startIdx; true; $endIdx++) {
			if ($data[$endIdx] == ';') {
				$subString = substr($data, $startIdx, $endIdx - $startIdx);

				$json = json_decode($subString, true);
				break;
			}
		}
		$this->data = $json;
		//var_dump($this->data);
		return $this->data;
	}

	function getApartmentInfo($column) {
		$db = new db();
		$sql = "SELECT apartment_$column 
			FROM apartment
			WHERE apartment_id = $this->APARTMENT_ID";
		$result = $db->query($sql);
		$row = $result->fetch_assoc();
		return $row["apartment_$column"];
	}

	/* @function getFloorplanId finds the floorplan id based of the floorplan name
	 * @return int the floorplan id
	 */
	function getFloorplanId($floorplanName) {
		$db = new db();
		// $floorplanName is going to be something like "E2-B3-1 Bedroom"
		preg_match('/([A-z0-9]+)-/', $floorplanName, $buildingArray);
		preg_match('/-([A-z0-9]+)\s*-/', $floorplanName, $fpNameArray);
		preg_match('/-([A-z0-9]+)[ A-z]{0,}$/', $floorplanName, $bedroomArray);

		$fpName = $fpNameArray[1];

		// west: E3
		// center: E2
		switch ($buildingArray[1]) {
		case "TH":
			$buildingCode = "townhomes";
			$fpName = "TH-" . $fpName;
			break;
		case "E2":
			$buildingCode = "center";
			break;
		case "E3":
			$buildingCode = "west";
			break;
		}

		if ($bedroomArray[1] == "Townhome") {
			// this is just hard coded based off what im seeing in the data, this could by dynamic
			$beds = 2;
		} else if ($bedroomArray[1] == "Studio") {
			$beds = 0;
		} else {
			$beds = $bedroomArray[1];
		}
		// build floorplan name
		$sql = sprintf(
			"SELECT floorplan_id
			FROM floorplan
			WHERE floorplan_apartment_id = %d
			AND floorplan_name = '%s'
			AND beds = %d"
			,
				$this->APARTMENT_ID,
				$fpName . "-" . $buildingCode,
				$beds
		);

		$floorplanId = $db->query($sql)->fetch_assoc()['floorplan_id'];
		$db->close();
		return $floorplanId ? array(
			'id' => $floorplanId,
			'name' => $fpName . "-" . $buildingCode,
			'originalName' => $floorplanName
		) : false;
	}

	/* @function getFloorPlans inserts new rows into the floorplan_rent table
	 * @return false on error, else true
	 */
	function getFloorPlans() {
		$floorplans = $this->data;
		$db = new db();
		$ids = array();

		foreach ($floorplans as $floorplan) {
			if (isset($ids[$floorplan['FloorplanName']])) {
				$ids[$floorplan['FloorplanName']]['available']++;
			} else {
				$data = $this->getFloorplanId($floorplan['FloorplanName']); 
				$ids[$floorplan['FloorplanName']] = array(
					'id' => $data['id'],
					'name' => $data['name'],
					'available' => 1
				);
			}
		}

		foreach ($floorplans as $floorplan) {
			$floorplanName = ""; // <BuildingCode>-<FloorplanName>-<Bedrooms>
			$floorplanInfo = $ids[$floorplan['FloorplanName']];

			if (!$floorplanInfo) {
				echo __FILE__ . " ERROR: Floorplan id not found for floorplan name: " . $floorplan['FloorplanName'] . PHP_EOL;
				continue;
				// $this->createFloorplan($floorplan);
				// $floorplanId = $this->getFloorplanId($floorplan['FloorplanName']);
			}

			$params = array(
				'min_rent' => $floorplan['MarketRent'],
				'max_rent' => $floorplan['MarketRent'],
				'available' => $floorplanInfo['available'],
				'floorplan_name' => $floorplanInfo['name'],
				'floorplan_id' => $floorplanInfo['id'],
				'available_date' => date('YmdHis', strtotime(preg_replace("/\\\/", '', $floorplan['ReadyDate'])))
			);

			try {
				$insertResult = $db->insert("floorplan_rent", $params);
				if (!$insertResult) {
					echo __FILE__ . " An error occurred on insert" . PHP_EOL;
				}
			} catch (Exception $e) {
				echo __FILE__ . " Error occurred on insert: " . $e . PHP_EOL;
			}
		}

		$db->close();
	}

	/* @function createFloorplan inserts a floorplan row into the database
	 * @param floorplan - an associative array with the floorplan parameters
	 */
	function createFloorplan($floorplan) {
		$db = new db();
		$sql = sprintf("INSERT INTO floorplan
			(floorplan_name, floorplan_apartment_id, square_feet, beds, baths)
			VALUES ('%s', %d, %d, %d, %d)", 
			$floorplan['name'] . "-" . $floorplan['building'],
			$this->APARTMENT_ID,
			$floorplan['sqft'],
			$floorplan['beds'],
			$floorplan['baths']
		);
		if (!$db->query($sql)) {
			echo $db->error() . PHP_EOL;
			return 0;
		} else {
			return 1;
		}
	}

	function getApartmentId() {
		return $this->APARTMENT_ID;
	}

	/* @function buildFloorplanObject builds the object based of the class extra urls
	 * @return array the object that contains the data for every floorplan offered
	 */
	function buildFloorplanObject() {
		$floorplans = array();
		foreach ($this->extraUrls as $url) {
			$curl = curl_init();

			// Set some options - we are passing in a useragent too here
			curl_setopt_array($curl, array(
				 CURLOPT_RETURNTRANSFER => 1,
				 CURLOPT_URL => $url,
				 CURLOPT_USERAGENT => 'Chrome'
			));

			// Send the request & save response to $resp
			$resp = curl_exec($curl);

			// Close request to clear up some resources
			curl_close($curl);
			if (preg_match('/townhomes/i', $url)) {
				preg_match_all('/(TH-[A-z0-9]+)<.*\n.*>([A-z0-9]+)[A-z ]+\/ ([A-z0-9]+)[A-z ]+<.*\n.*>([0-9]+) SQ/', $resp, $outputArray);
				$floorplanBuildings = "townhomes";
				$floorplanNames = $outputArray[1];
				$floorplanBeds = $outputArray[2];
				$floorplanBaths = $outputArray[3];
				$floorplanSqft = $outputArray[4];
			} else {
				preg_match_all('/elan_deux_([A-z]+).*\n.*>([A-z0-9]+)<\/p>.*\n.*>([A-z0-9]+)[ A-z]+ \/ ([0-9\.]+) Bath.*\n.*>([0-9]+) SQ/', $resp, $outputArray);
				$floorplanBuildings = $outputArray[1];
				$floorplanNames = $outputArray[2];
				$floorplanBeds = $outputArray[3];
				$floorplanBaths = $outputArray[4];
				$floorplanSqft = $outputArray[5];
			}
			// get 

			for ($i = 0; $i < count($floorplanNames); $i++) {
				$floorplans[] = array(
					'building' => is_array($floorplanBuildings) ? 
						$floorplanBuildings[$i] : $floorplanBuildings,
					'name' => $floorplanNames[$i],
					'beds' => $floorplanBeds[$i],
					'baths' => $floorplanBaths[$i],
					'sqft' => $floorplanSqft[$i],
				);
			}
		}

		return $floorplans;
	}

	/* @function insertAllFloorplans pulls the data and parses into complete objects
	 * @return boolean whether or not the insert fully completed
	 */
	function insertAllFloorplans() {
		$floorplans = $this->buildFloorplanObject();
		$numRows = 0;
		$total = 0;

		foreach ($floorplans as $floorplan) {
			$numRows += $this->createFloorplan($floorplan);
			$total++;
		}
		return array(
			'affected' => $numRows,
			'total' => $total,
		);
	}

}

?>
