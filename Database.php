<?php 

	class db {

		protected $host = "127.0.0.1";
		protected $user = "root";
		protected $password = "maxanderson1";
		protected $database = "apartments";
		protected $conn;

		public function __construct() {
			$this->db_connect();
		}

		function db_connect() {
			$this->conn = mysqli_connect($this->host, $this->user, $this->password, $this->database); 
			if (!$this->conn) {
				echo "Error! Unable to connect to database.";
				exit();
			}
		}

		function insert($table, array $values = array()) {
			$sql = "INSERT INTO $table (" . implode(array_keys($values), ",") . 
				") VALUES ('" . implode(array_values($values), "','") . "')";
			if (!$this->conn->query($sql)) {
				echo "Error inserting into $table" . PHP_EOL;
				echo $this->conn->error . PHP_EOL;
				echo $sql . PHP_EOL;
				return false;
			} 
			return true;
		}

		function error() {
			return $this->conn->error;
		}

		function query($sql) {
			$result = $this->conn->query($sql);
			return $result;
		}

		function fetchOne($columns, $table, $where = "") {
			$sql = "SELECT $columns
				FROM $table
				WHERE $where
				LIMIT 1";
			$result = $this->conn->query($sql);
			if (!$result->num_rows) {
				//echo "no results found";
				return false;
			}
			$row = $result->fetch_assoc();
			return $row;
		}

		function close() {
			$this->conn->close();
		}

	}

?>

