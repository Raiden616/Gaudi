<?php
/*
	Model library to allow quick interface to a single SQL table with minimal-processing.
	
	(c) Clark Sirl 2013
	2014 - Added support for multiple database engines
*/

/*
 * Top-level abstract class for model.
 * All CRUD model templates should extend this.
 */
abstract class SuperModel extends ArrayObject {
    protected $tableName;
    protected $idField; // Can be array for composite primary key
    protected $auto_increment = false;
    
    protected $used = false;
    protected $returnedRows = array();
    protected $orders = array();
    
    protected $id = null;
    
    protected $columns = array();
    public $object_data = array();
    
    public $joined = null;
    public $joinedForeignKeyField;
    
    protected static $cached_Tables = array();
    
    function __construct($tableName,$idField = "id") {
        $this->initialise($tableName,$idField);
    }
    
    public function toArray() {
            return $this->getArrayCopy();
    }

    public function autoincID() {
            return $this->auto_increment;
    }
    
    public function idField() {
        return $this->idField;
    }
    
    public function ID() {
        return $this->id;
    }
    
    protected function addColumn($name,$type,$bIsIdentity = false) {
        $c = new ModelColumn();
        
        $c->name = $name;
        $c->type = $type;
        $c->identityField = $bIsIdentity;
        
        $this->columns[$name] = $c;
    }
    
    public function addOrder($orderField,$clause = "ASC") {
        if ($this->hasColumn($orderField)) {
            $o = new ModelOrder();
            
            $o->orderField = $orderField;
            $o->orderClause = $clause;
            
            $this->orders[$orderField] = $o;
        }
    }
    
    public function hasColumn($name) {
        if (array_key_exists($name,$this->columns)) {
            return $this->columns[$name]->name == $name;
        }
    }
    
    public function addJoin($model,$foreignKey,$primaryKey = null) {
        if ($model instanceof SuperModel) { // Valid model

            if (!$this->hasColumn($foreignKey)) {   // Invalid foreign key
                //Error::_sysError("Invalid foreign key specified in join.");
                return false;
            }
            
            $this->joined = $model;
            $this->joinedForeignKeyField = $foreignKey;
            $this->joinedPrimaryKey = $primaryKey;
            return true;
        }
        return false;
    }
    protected function isJoined() {
        return !is_null($this->joined) && $this->joined instanceof SuperModel;
    }
    
    // Descend the chain of joins and unset all values.
    protected function clearValues() {
        foreach ($this->object_data as $k => $v) {
            $this->object_data[$k] = null;
        }
        if ($this->isJoined()) {
            $this->joined->clearValues();
        }
    }
    
    // Given a column name and a value for that column,
    // descend the chain of joins and apply that value as required.
    // Will not set same column twice, so if the same column name is given
    // twice, priority will be given the model object highest in the
    // join chain.
    // Returns false if column not found.
    protected function distributeValue($column,$value) {
		if ($column == $this->idField) {
			$this->id = $value;
		}
	
		if (array_key_exists($column,$this->columns)) {
			if (!isset($this->object_data[$column]) || is_null($this->object_data[$column])) {	// No value currently set
				$this->object_data[$column] = $value;
				return true;
			}
		}
        if ($this->isJoined()) {
            return $this->joined->distributeValue();
        }
        return false;
    }
    
    // Clears all values and cached returned rows to enable a new search
    public function reset() {
        $this->clearValues();
        $this->returnedRows = array();
    }
    
    // Handles raw results pulled back from database.
    // Raw results are array of key/value pairs.
    protected function handleResults($qry) {
        $row = array(); // Variable to hold the current return data
        
        if (empty($this->returnedRows)) { // If no cached results, then fetch them from query if not already userd
			if (!$this->used) {
				$this->used = true;
				$this->returnedRows = $qry->fetchAll();
				if (empty($this->returnedRows)) { // No results, so returns false immediately
					return false;
				}
			} else {
				return false;
			}
        }
        
        $row = reset($this->returnedRows);  // Get first row of returned data
        array_shift($this->returnedRows);   // Shift off the first row (so next time we get second)
        
	$this->clearValues();   // First clear all values
        foreach ($row as $k => $v) {    // Iterate through each column in returned row
            $this->distributeValue($k,$v);
        }
	return $this;
    }
    
    /* Array Access overrides */
	
    /**
     * Set an attribute.
     * Overrides ArrayObject::offsetSet() - allows access to Model like $m['key'] = $val;
     */
    public function offsetSet($key,$val) {
            if ($key == $this->idField) {
                    $this->id = $val;
            } else {
                    if (array_key_exists($key,$this->object_data)) {
                            $this->object_data[$key] = $val;
                    }
            }
    }

    /**
     * Get an attribute.
     * Overrides ArrayObject::offsetGet() - allows access to Model like $m['key'];
     */
    public function offsetGet($key) {
            if ($key == $this->idField) {
                    return $this->id;
            } else {
                    return $this->object_data[$key];
            }
    }

    public function offsetExists($index) {
            return isset($this->object_data[$index]);
    }

    public function offsetUnset($index) {
    unset($this->object_data[$index]);
    return true;
    }

    public function getArrayCopy() {
            $array = array();

            $array[$this->idField] = $this->id;
            foreach ($this->object_data as $k => $v) {
                    $array[$k] = $v;
            }

            return $array;
    }
    
    /* Abstract methods */
    
    abstract public function initialise($tableName,$idField = "id");

    // Returns an array of all attributes
    abstract public function getAttributes();
    // Return a string for listing fields
    abstract protected function getFieldString();
    abstract protected function getFromString($bIncludeTableName = true);
    
    // Gets the full name with table name appended.
    // Should return an empty string if column invalid.
    abstract protected function getFullColumnName($name);
    
    // Returns a $form object that can be used to interface with the CRUD methods
    abstract public function makeForm();
    
    // CRUD methods
    abstract public function save();
    abstract public function read($val,$attr);
    abstract public function delete();
    
    public static function readAll($val = null) {
        $array = array();

        $curClass = get_called_class(); // Get name of current class

        $o = new $curClass(); // Create instance of whatever class we are in
        while ($o->read($val)) { // read() will get results as an array of objects
                // We have to add a clone of the object to the array, otherwise further
                // reads will affect the ones already in there.
                $array[] = clone $o; 
        }

        return $array;
    }

    public static function readAllArray($val = null) {
        $array = array();

        $curClass = get_called_class(); // Get name of current class

        $o = new $curClass(); // Create instance of whatever class we are in
        while ($o->read($val)) { // read() will get results as an array of objects
                $array[] = $o->toArray();
        }

        return $array;
    }
}

class ModelColumn {
    public $name;
    public $type;
    public $identityField;
}

class ModelOrder {
    public $orderField;
    public $orderClause = "ASC";
}

// We need to include the relevant file for the database engine
$fileName = "model_mysql";	// Default to MySQL
switch (DATABASE_ENGINE) {
	case "MySQL":
		$fileName = "model_mysql";
		break;
	case "MSSQL":
		$fileName = "model_odbc";
		break;
}
require_once("models/$fileName.php");    // Require the file containing the relevant model

?>