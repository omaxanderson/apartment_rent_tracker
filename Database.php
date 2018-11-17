<?php 

class db {

	protected $host;
	protected $user;
	protected $password;
	protected $database;
	protected $conn;

	public function __construct() {
		$config = parse_ini_file('db_config.ini', true);
		$this->host = $config['host'];
		$this->user = $config['user'];
		$this->password = $config['password'];
		$this->database = $config['database'];
		$this->db_connect();
	}

	function db_connect() {
		$this->conn = mysqli_connect($this->host, $this->user, $this->password, $this->database); 
		if (!$this->conn) {
			echo "Error! Unable to connect to database." . PHP_EOL;
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
