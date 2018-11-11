<?php

	abstract class Apartment {
		protected $url;

		function sendRequest($debug = false) {
			if (!$debug) {
				// Get cURL resource
				$curl = curl_init();

				// Set some options - we are passing in a useragent too here
				curl_setopt_array($curl, array(
					 CURLOPT_RETURNTRANSFER => 1,
					 CURLOPT_URL => $this->url,
					 CURLOPT_USERAGENT => 'Chrome'
				));

				// Send the request & save response to $resp
				$resp = curl_exec($curl);

				// Close request to clear up some resources
				curl_close($curl);
			} else {
				$resp = file_get_contents("walkway_html.html");
			}

			$this->resp = $resp;
			return $this;
		}

		//abstract function sendRequest($debug = false);
		abstract function parseData();
		abstract function getApartmentInfo($column);
		abstract function getFloorPlans();
		abstract function getApartmentId();
		abstract function createFloorplan($floorplan);
	}

?>

