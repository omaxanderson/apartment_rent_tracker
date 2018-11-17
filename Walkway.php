<?php

require_once("Database.php");
require_once("Apartment.php");

class Walkway extends Apartment {

	protected $APARTMENT_ID = 1;
	protected $url = 'https://www.thewalkway.com/floorplans';
	protected $resp;
	protected $data;

	/* @function parseData
	 * @param $data the HTML or JSON data that is to be parsed
	 * @return string if successfully parsed, false if unsuccessful
	 */ 
	function parseData() {
		$data = $this->resp;
		$startIdx = strpos($data, "pageData =");

		while ($data[$startIdx++] != '{' && $startIdx < strlen($data)) {}
		$startIdx--;

		$tracker = array(
			'{' => 0,
			'}' => 0,
		);

		for ($i = $startIdx; true ; $i++) {
			if (isset($tracker[$data[$i]])) {
				$tracker[$data[$i]]++;
			}
			if (!($tracker['{'] - $tracker['}'])) {
				// need to do some regex replacements
				$subString = substr($data, $startIdx, ++$i - $startIdx);

				// turn this into a valid JSON string
				$json = preg_replace("/(\w+):/i", '"$1":', $subString);
				$json = preg_replace('/"https":/', 'https:', $json);

				$data = json_decode($json, true);
				if (!$data) {
					echo __FILE__ . "Error parsing JSON string: " . __LINE__ . PHP_EOL;
					return false;
				}

				break;
			}
		}
		$this->data = $data;
		//var_export($this->data);
		return $this->data;
	}

	/* @function getFloorPlans inserts new rows into the floorplan_rent table
	 * @return prints error when necessary, else returns true
	 */
	function getFloorPlans() {
		$floorPlans = $this->data['floorplans'];
		$db = new db();
		// for each floorplan insert a floorplan row and/or a data row
		foreach ($floorPlans as $floorplan) {
			$floorPlanId = $db->fetchOne("floorplan_id", 
				"floorplan", 
				" floorplan_apartment_id = $this->APARTMENT_ID AND floorplan_name = '" . $floorplan['name'] . "'"
			);

			if (!$floorPlanId) {
				$this->createFloorplan($floorplan);
				$floorPlanId = $db->fetchOne("floorplan_id", "floorplan", " floorplan_name = '" . $floorplan['name'] . "'");
			}

			$params = array(
				'min_rent' => $floorplan['lowPrice'],
				'max_rent' => $floorplan['highPrice'],
				'available' => count($floorplan['unitList']),
				'floorplan_name' => $floorplan['name'],
				'floorplan_id' => $floorPlanId['floorplan_id'],
			);
			if ($floorplan['availableDate']) {
				$params['available_date'] = date("YmdHis", strtotime($floorplan['availableDate']));
			}
			try {
				$insertResult = $db->insert("floorplan_rent", $params);
				if (!$insertResult) {
					echo __FILE__ . " An error occurred on insert: " . __LINE__ . PHP_EOL;
					exit();
				}
			} catch (Exception $e) {
				echo __FILE__ . "Erro occurred on insert: " . $e . PHP_EOL;
			}
		}

		$db->close();
	}

	function createFloorplan($floorplan) {
		// need to insert this floor plan into the db
		$params = array(
			'floorplan_name' => $floorplan['name'],
			'floorplan_apartment_id' => $this->APARTMENT_ID,
			'square_feet' => $floorplan['sqft'],
			'beds' => $floorplan['beds'],
			'baths' => $floorplan['baths'],
		);
		$table = "floorplan";
		try {
			$insertResult = (new db())->insert($table, $params);
			if (!$insertResult) {
				echo __FILE__ . " Insert new floorplan failure. Line " . __LINE__ . PHP_EOL;
				var_dump($params);
				exit();
			}
		} catch (Exception $e) {
			echo __FILE__ . " Error occurred during floorplan insert" . __LINE__ . PHP_EOL;
			exit();
		}
		return true;
	}

	function getApartmentId() {
		return $this->APARTMENT_ID;
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
}
?>
