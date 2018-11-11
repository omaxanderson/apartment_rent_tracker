<?php

	require_once("Apartment.php");
	require_once("Database.php");

	class CalhounBeachClub extends Apartment {

		protected $url = "https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json..rent.asc.html";
		protected $extraUrls = array(
			"https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json.2-bedroom.rent.asc.html",
			"https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json.3-bedroom.rent.asc.html",
			"https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json.4-bedroom.rent.asc.html",
			"https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json.penthouse.rent.asc.html",
			"https://www.calhounbeachclub.com/en/apartments/floor-plans/jcr:content/par/floorplanlister.json.studio.rent.asc.html",
		);

		protected $APARTMENT_ID = 3;

		function parseData() {
			// send request to extra urls first
			$this->sendAdditionalRequests();

			preg_match_all('/aptName.*\n\s+([A-z ]+)(?:.*\n){1,35}.*Bedrooms<.*(?:\n.*){1,5}.*>([0-9\.]+)<(?:.*\n){1,9}.*>Bathrooms<(?:.*\n){1,3}.*>([0-9\.]+)<(?:.*\n){1,9}.*>Sq.*Ft.*\n.*>([0-9]+)<(?:.*\n){1,15}.*Price(?:.*\n){1,5}.*>\$([0-9]+)[- ]+\$([0-9]+)(?:.*\n){1,10}.*Available(?:.*\n){1,6}.*>([0-9]+)/', $this->resp, $outputArray);

			// match indexes:
			// 	1: name
			// 	2: bedrooms
			// 	3: bathrooms
			// 	4: sq ft
			// 	5: min rent
			// 	6: max rent
			// 	7: available
			$data = array();
			for ($i = 0; $i < count($outputArray[1]); $i++) {
				$data[] = array(
					'floorplan_name' => trim($outputArray[1][$i]),
					'beds' => $outputArray[2][$i],
					'baths' => $outputArray[3][$i],
					'square_feet' => $outputArray[4][$i],
					'min_rent' => $outputArray[5][$i],
					'max_rent' => $outputArray[6][$i],
					'available' => $outputArray[7][$i],
				);
			}

			$this->data = $data;

			return $this->data;
		}

		function createFloorplan($floorplan) {
			$params = array(
				'floorplan_name' => $floorplan['floorplan_name'],
				'floorplan_apartment_id' => $this->APARTMENT_ID,
				'square_feet' => $floorplan['square_feet'],
				'beds' => $floorplan['beds'],
				'baths' => $floorplan['baths'],
			);

			$db = new db();
			try {
				$result = $db->insert("floorplan", $params);
				if (!$result) {
					echo "Error inserting row" . PHP_EOL;
					return false;
				}
			} catch (Exception $e) {
				echo "Error: " . $e . PHP_EOL;
				return false;
			}
			$db->close();

			$id = $this->getFloorplanId($floorplan['floorplan_name']);

			echo "successfully entered " . $floorplan['floorplan_name'] . " into floorplan" . PHP_EOL;
			return $id;
		}

		function getApartmentInfo($column) {

		}

		function getFloorplanId($floorplanName) {
			$sql = sprintf("SELECT floorplan_id 
				FROM floorplan
				WHERE floorplan_name = '%s'
				AND floorplan_apartment_id = %d",
				$floorplanName,
				$this->APARTMENT_ID
			);

			$db = new db();
			$result = $db->query($sql)->fetch_assoc()['floorplan_id'];
			$db->close();

			return $result;
		}

		function getFloorPlans() {
			$floorplans = $this->data;
			$db = new db();

			foreach ($floorplans as $floorplan) {
				$floorplanId = $this->getFloorplanId($floorplan['floorplan_name']);
				if (!$floorplanId) {
					$floorplanId = $this->createFloorplan($floorplan);
					// If still not inserted return
					if (!$floorplanId) {
						return false;
					}
				}

				$floorplan['floorplan_id'] = $floorplanId;
				unset($floorplan['beds']);
				unset($floorplan['baths']);
				unset($floorplan['square_feet']);

				try {
					$result = $db->insert("floorplan_rent", $floorplan);

					if (!$result) {
						echo "Error - row not inserted" . PHP_EOL;
						continue;
					} 
				} catch (Exception $e) {
					echo "Error - row not inserted" . PHP_EOL;
				}
				echo "Successfully entered " . $floorplan['floorplan_name'] . " into floorplan_rent" . PHP_EOL;
			}
		}

		function getApartmentId() {

		}

		function sendAdditionalRequests() {
			foreach ($this->extraUrls as $url) {
				// Get cURL resource
				$curl = curl_init();

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					 CURLOPT_RETURNTRANSFER => 1,
					 CURLOPT_URL => $url,
					 CURLOPT_USERAGENT => 'Chrome'
				));

				// Send the request & save response to $resp
				$this->resp .= curl_exec($curl);

				// Close request to clear up some resources
				curl_close($curl);
			}
		}

	}

?>

