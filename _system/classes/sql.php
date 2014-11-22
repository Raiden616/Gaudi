<?php
/*
	SQL library for secure and easy-to-manage database querying.
	
	Contains the following classes:
		 - MySQLQuery - use for executing MySQL queries
		 - MSSQLQuery - use for executing MSSQL queries
	
	All classes must extend SQLConnector and 
		 - initialise(string $qry,array $params) - $qry = the query to run, $params = array of parameters
		 - execute() - run a query
		 - read() - get a single row as a one-dimensional array
		 - readAll() - get all rows as a two-dimensional array
	
	
	***********************************************
	Usage:
	
		Before deciding which object to use, set the appropriate configuration settings in /config/config.php.
	
		------- MySQLQuery -------
		
		new MySQLQuery(string $qry [, mixed $...]);
			OR
		new MySQLQuery(string $qry [, array $params]);
		
		Pass parameters using sprintf notation. Guide here: http://uk3.php.net/manual/en/function.sprintf.php
		
		Sample INSERT query:
		
			$qry = new MySQLQuery("INSERT INTO `table` (`foo`,`bar`) VALUES ('%s','%s');","FooVal","BarVal");
			$qry->execute();
	
		Sample SELECT query:
			
			$qry = new MySQLQuery("SELECT `id`,`foo`,`bar` FROM `table` WHERE `id` = %d;",3);
			$row = $qry->fetch();
			var_dump($row);
			
			Output:
			
				array(
						'id' => "3",
						'foo' => "FooVal",
						'bar' => "BarVal"
					)
					
		Sample SELECT query to get all rows:
		
			$qry = new MySQLQuery("SELECT `id`,`foo`,`bar` FROM `table` ORDER BY ID DESC LIMIT 0,3");
			$rows = $qry->fetchAll();
			var_dump($rows);
			
			Output:
				array(
					0 => array(
						'id' => "3",
						'foo' => "FooVal",
						'bar' => "BarVal"
					),
					1 => array(	
						...
					),
					2 => array(
						...
					)
				)
	
	
		------- MSSQLQuery -------
		
		new MSSQLQuery(string $qry [, mixed $...]);
			OR
		new MSSQLQuery(string $qry [, array $params]);
		
		Pass parameters using MSSQL prepared statement notation.
		
		Sample INSERT query:
		
			$qry = new MSSQLQuery("INSERT INTO [table] (foo,bar) VALUES (?,?);","FooVal","BarVal");
			$qry->execute();
			
		Sample SELECT query:
		
			$qry = new MSSQLQuery("SELECT [id],[foo],[bar] FROM [table] WHERE [id] = ?;",3);
			$row = $qry->fetch();
			var_dump($row);
		
			Output:
			
				array(0) {
						'id' => "3",
						'foo' => "FooVal",
						'bar' => "BarVal"
					}
					
		Sample SELECT query to get all rows:
		
			$qry = new MySQLQuery("SELECT TOP 3 [id],[foo],[bar] FROM [table] ORDER BY [id] DESC;");
			$rows = $qry->fetchAll();
			var_dump($rows);
			
			Output:
				array(
					0 => array(
						'id' => "3",
						'foo' => "FooVal",
						'bar' => "BarVal"
					),
					1 => array(	
						...
					),
					2 => array(
						...
					)
				)
		
	
	(c) Clark Sirl 2011
	2014 - MSSQL functionality added
*/
abstract class SQLConnector {
	protected $link = null;
	
	protected $host = DB_HOST;
	protected $username = DB_USER;
	protected $password = DB_PASS;
	protected $db = DB_NAME;
	
	protected $qry = "";
        
        protected $report = false;
	
	// SQL connector classes must implement following methods
	abstract public function initialise($qry,$params);
	abstract public function execute();
	abstract public function fetch();
	abstract public function fetchAll();
	
	// Constructor
	function __construct($qry) {
            if (!empty($this->host)) {
		$qryParams = array();
		
		$numParams = func_num_args();
		$params = func_get_args();
		
		if ($numParams > 1) {
			// Support for if params are already array
			if (is_array($params[1])) {
				array_unshift($params[1],$params[0]);
				$params = $params[1];
				$numParams = count($params);
			}
			
			// At this point, $params includes the SQL query as its first argument and the rest of the parameters			
			
			// We're only dealing with data, so make sure all the keys are numbers
			$i = 0;
			$temp = array();
			foreach ($params as $v) {
				$temp[$i] = $v;
				$i++;
			}
			$params = $temp;
		
			for ($i = 1; $i < $numParams; $i++) {
				$qryParams[] = $params[$i];
			}
		}
		
		$this->initialise($qry,$qryParams);
            }
	}
        
        public function initialised() {
            return !(is_null($this->link));
        }
	
	// All-purpose methods
	public function fetchJson() {
	/* RETURNS FIRST ROW AS JSON */
		// Execute the query
		$row = $this->fetch();
		
		// Json encode
		return $this->jSon($row);
	}
	
	public function jSon($rows) {
		return json_encode($rows);
	}
        
        public function report() {
            $this->report = !$this->report;;
        }
}

/*******************************************
*	MySQL connector
*******************************************/
class MySQLQuery extends SQLConnector{
	public function initialise($qry,$params) {
            // Connect to the database before you do anything
            $link = new mysqli($this->host,$this->username,$this->password,$this->db);
            if (mysqli_connect_error()) { Error::_sysError("MYSQL CONNECTION ERROR -<br/>".$link->connect_error); } else {
                    $this->link = $link;
            }
            //$link->query("set names 'utf8'");
            //if (!mysqli_select_db($this->db)) { Error::_sysError(mysqli_error()); }

            // Escape the parameters
            foreach ($params as $k => $v) {
                    $params[$k] = $this->link->real_escape_string($v);
            }

            // Run sprintf to apply the parameters into the query
            array_unshift($params,$qry);
            $qry = call_user_func_array('sprintf',$params);

            // Store the query at object level
            $this->qry = $qry;
            
	}

	public function execute() {
            if ($this->initialised()) {
		$result = $this->link->query($this->qry);

                // Debug code
                if (DEV == "true" && $this->report) {
                    echo "<br/><br/>-- Executing SQL Query<br/>";
                    echo $this->qry."<br/><br/>";
                }
                
		if ($result) {
			$this->result = $result;
		} else {
			$err = "MYSQL ERROR - \n".$this->link->error;
			if (DEV == "true") {
				Error::_sysError($err);
			}
		}
               
		return $this->link->insert_id;
            }
            return false;
	}

	public function fetch() {
            // Execute the query
            if ($this->execute()) {

		// Get first row
		$row = mysqli_fetch_assoc($this->result);
		return $row;
            }
            return null;
	}

	public function fetchALL() {
            // Execute the query
            if ($this->execute()) {

		$result = array();
		// Get first row
		while($row = mysqli_fetch_assoc($this->result)) {
			array_push($result,$row);
		}
		return $result;
            }
            return array();
	}
}


/*******************************************
*	MSSQL / Microsoft SQL Server ODBC connector
*******************************************/
class MSSQLQuery extends SQLConnector {
	private $result = null;
	
	private $driver = ODBC_DRIVER;
	private $parameters = array();
	
	public function initialise($qry,$params) {
		// Connect to the server before you do anything
		
		// Assemble the connection string
		$strConnectionString = "DRIVER={$this->driver};SERVER={$this->host};DATABASE={$this->db};";
		
		$this->link = odbc_connect($strConnectionString,$this->username,$this->password);
		if (!$this->link) {	// SQL unable to connect
			return;
		}
		
		foreach ($params as $k => $v) {		
			// If NULL value, make it an empty string
			if (is_null($v)) {
				$v = "";
			}
			
			// All good, so push to object level
			$this->parameters[] = $v;
		}
		
		// Store the query at object level
		$this->qry = $qry;
	}
	
	public function execute() {
            if ($this->initialised()) {
		// SQL escaping is done here using prepared statements
		$rResult = odbc_prepare($this->link,$this->qry);
		$success = odbc_execute($rResult,$this->parameters);
                
                // Debug code
                if (DEV == "true" && $this->report) {
                    echo "<br/><br/>-- Executing SQL Query<br/>";
                    echo $this->qry."<br/><br/>";
                }
		
		if (!$success) {	// Failure to execute
			$err = "ODBC error: "+odbc_errormsg($this->link);
			if (DEV == "true") {
				Error::_sysError($err);
			}
			//die("odbc_execute failed");
                        return false;
		}
		
		$this->result = $rResult;
		
		// Get the last inserted ID
		$lastID = odbc_exec($this->link,"SELECT @@IDENTITY AS pmIDENT;");
		$lastID = odbc_fetch_array($lastID)['pmIDENT'];
		return is_numeric($lastID) ? intval($lastID) : $lastID;
            }
            return false;
	}

	// Returns single row as array
	// Specify a row index to return that particular row
	public function fetch($index = 1) {
	/* RETURNS SINGLE ROW AS ARRAY */
            // Execute the query
            if ($this->execute()) {

		if (is_null($this->result)) {
                    return null;
		}
		
		// Get first row
		
		$fieldCount = odbc_num_fields($this->result);
		$row = array();
		if (odbc_fetch_row($this->result,$index)) {
			for ($i = 1; $i <= $fieldCount; $i++) {
				$fieldData = trim(odbc_result($this->result,$i));
				$row[odbc_field_name($this->result,$i)] = $fieldData;
			}
		}
		return $row;
            }
            return null;
	}
	
	public function fetchAll() {
	/* RETURNS ALL ROWS AS ARRAY OF ARRAYS */
            // Execute the query
            if ($this->execute()) {
		
		if (is_null($this->result)) {
			die ("no set result object");
		}
		
		$result = array();
		
		$fieldCount = odbc_num_fields($this->result);

		while (odbc_fetch_row($this->result)) {
			$row = array();
			for ($i = 1; $i <= $fieldCount; $i++) {
				$fieldData = trim(odbc_result($this->result,$i));
				$row[odbc_field_name($this->result,$i)] = $fieldData;
			}
			array_push($result,$row);
		}
		
		return $result;
            }
            
            // The rest of the application doesn't care
            // if there's a database or not.
            // So if we can't connect to one, just return
            // an empty set of results.
            return array();
	}
}
?>
 
