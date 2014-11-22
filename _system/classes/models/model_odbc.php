<?php

/*
 * MSSQL Model
 */
class Model extends SuperModel {
    public function initialise($tableName,$idField = "id") {
            // Make sure table exists
			$bCached = false;
			foreach (self::$cached_Tables as $t) {
				if ($t == $tableName) {
					$bCached = true;
					break;
				}
			}
			if (!$bCached) {
				$qry = new MSSQLQuery("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE ?;",$tableName);
				$tables = $qry->fetchAll();
				if (!empty($tables)) {
					self::$cached_Tables[] = $tableName;
				} else {
					//Error::_sysError("Attempt to initialise model on non-existent table $tableName.");
					return false;
				}
			}
			$this->tableName = $tableName;
			
			// Get all columns
			$qry = new MSSQLQuery("SELECT COLUMN_NAME,*,COLUMNPROPERTY(object_id(TABLE_NAME),COLUMN_NAME,'IsIdentity') AS isIdentity FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?;",$tableName);
			$attributes = $qry->fetchAll();
			// Check ID field exists
			$identityField = "";
			$bFoundIDCount = 0;
			$bRequiredIDCount = is_array($idField) ? count($idField) : 1;
			foreach ($attributes as $a) {
				// Is this an auto-incrementing field?
				$bAutoInc = isset($a['isIdentity']) && $a['isIdentity'] == "1";
				if (is_array($idField)) {	// If we are looking for more than one field
					if (in_array($a['COLUMN_NAME'],$idField)) {
						$bFoundIDCount++;
					}
				} else {	// We are just looking for one field
					if ($a['COLUMN_NAME'] == $idField) {
						$bFoundIDCount++;
						// If this is an auto-incrementing field, then mark the model as having an auto-increment ID
						$this->auto_increment = $bAutoInc;
					}
				}
				
				// Make the column entry
				$this->addColumn($a['COLUMN_NAME'],$a['DATA_TYPE'],$bAutoInc);
				$this->object_data[$a['COLUMN_NAME']] = null;
			}

			if ($bFoundIDCount == $bRequiredIDCount) {	// Found ID field
				$this->idField = $idField;
			} else {
				// Error
				//@Error::_sysError("Attempt to initialise model for table $tableName using non-existent ID field $idField.");
				return false;
			}
			
            return true;
    }

    public function getAttributes() {
            return $this->columns;
    }

    public function getAttributeType($columnName) {
            if (array_key_exists($columnName,$this->columns)) {
                    return $this->columns[$columnName]->type;
            }
            return false;
    }

    // Checks the data type of a specified column and returns an acceptable value for that column
    public function castAsType($value,$columnName) {
            $type = $this->getAttributeType($columnName);	// Get the type from the logged column information

            // Switch on the type
            switch ($type) {
                    // Integer types
                    case "bigint":
                    case "int":
                    case "tinyint":
                            $intVal = intval($value);
                            if (is_numeric($intVal)) {	// Converted successfully
                                    return $intVal;
                            } else {	// Didn't work, so just return 0
                                    return 0;
                            }
                            break;

                    // Date/time types
                    case "datetime":
                            $dtVal = empty($value) ? "now" : $value;	// If empty, then we obviously want the current time

                            // We pass it as a string, but it must be formatted correctly
                            $dt = new DateTime($dtVal,new DateTimeZone(TIMEZONE));
                            return $dt->format("Y-m-d H:i:s.000");
                            break;

                    // String types (default)
                    case "varchar":
                    default:
                            return (string)$value;
            }
    }

    // Rattles through every property and ensures that it is of an appropriate type to pass
    public function ensureTypes() {
            foreach ($this->object_data as $k => $v) {
				if (is_null($v)) {	continue;	}
                    $this->object_data[$k] = $this->castAsType($v,$k);
            }
    }
    
    protected function getFieldString() {
        $string = "";
        
        $i = 0;
        foreach ($attributes = $this->getAttributes() as $a) {
            // If we have an object joined, we don't want to get the foreign key
            if (!empty($this->joinedForeignKeyField) && $this->joinedForeignKeyField == $a->name) {  continue;   }
            
            if ($i > 0) {   $string .= ","; }
            $string .= "[{$this->tableName}].[{$a->name}]";
            $i++;
        }
        
        // Append the joined fields
        if (!is_null($this->joined) && $this->joined instanceof MySQLModel) {    // A valid model is joined
            $string .= $this->joined->getFieldString();
        }
        
        return $string;
    }
    
    protected function getFromString($bIncludeTableName = true) {
        $string = $bIncludeTableName ? "[{$this->tableName}]" : "";
        
        // Append the join
        if ($this->isJoined()) {   // A valid model is joined
            $string .= " INNER JOIN [{$this->joined->tableName}] ON [{$this->joined->tableName}].[{$this->joined->idField}] = [{$this->tableName}].[{$this->joinedForeignKeyField}]";
            
            $string .= $this->joined->getFromString(false);
        }
        
        return $string;
    }

    protected function getFullColumnName($name) {
        $name = "";
        if ($this->hasColumn($name)) {
            $name = "[{$this->tableName}].[$name]";
        } else {    // Check the joined model (this will happen recursively)
            if ($this->isJoined()) {
                $name = $this->joined->getFullColumnName($name);
            }
        }
        
        return $name;
    }

    public function makeForm() {
            $form = new Form("POST");

            $attributes = $this->getAttributes();
            foreach ($attributes as $k => $v) {	// Strip out character length strings from sql column types
                    $attributes[$k]['Type'] = trim(preg_replace('/\s*\([^)]*\)/', '', $v['Type']));
            }

            foreach ($attributes as $a) {
                    if ($a['Field'] == "id") { continue; }

                    // TODO: Create certain field parameters based on type

                    $e = new Input($a['Field']);
                    $e->label(ucfirst($a['Field']));
                    if (isset($this[$a['Field']]) && !is_null($this[$a['Field']])) {
                            $e->value($this[$a['Field']]);
                    }
                    // TODO: Fathom required fields from keys
                    $form->addElement($e);
            }

            $e = new Submit('save');
            $e->value("Save");
            $form->addElement($e);

            return $form;
    }

    /* CRUD methods */

    public function save() {
		if (!empty($this->id)) {
			$qry = new MSSQLQuery("SELECT count(*) AS count FROM [{$this->tableName}] WHERE [{$this->idField}] = ?",$this->id);
            $qry = $qry->fetch();
			
			if (intval($qry['count']) > 0) {
				return $this->update();
			}
		}
		return $this->create();
    }

    public function create() {
            $this->ensureTypes();

			$insert = array();
			foreach ($this->object_data as $k => $v) {
				if (($k == $this->idField && $this->auto_increment)
						||
					is_null($v)
				) {	continue;	}
				$insert[$k] = $v;
			}
            //$insert = $this->object_data;
            //if (!is_null($this->id)) { $insert[$this->idField] = $this->id; }

            // Assemble the query
            $qry = "INSERT INTO [{$this->tableName}] (";
            $i = 0;
            foreach ($insert as $k => $v) {
                    if ($i > 0) {	$qry .= ",";	}
                    $i++;
                    $qry .= "[$k]";
            }
            $qry .= ") VALUES (";
            $i = 1;
            foreach ($insert as $k => $v) {
                    if ($i > 1) {	$qry .= ",";	}
                    $qry .= "?";
                    $i++;
            }
            $qry .= ");";

            // Run the query
            $qry = new MSSQLQuery($qry,$insert);
            $id = $qry->execute();
            if (is_numeric($id) && $this->auto_increment) {	$this->id = $id;	}	// Update ID field if returned

            return true;
    }

    public function update() {
            $this->ensureTypes();
            $insert = $this->data;
            if (!is_null($this->id)) {	$insert[$this->idField] = $this->id;	}

            // Assemble the query
            $qry = "UPDATE [{$this->tableName}] SET ";
            $i = 0;
            foreach ($insert as $k => $v) {
                    if ($this->auto_increment && $k == $this->idField) {	// This is an auto-incrementing identity field - do not touch
                            continue;
                    }
                    if ($i > 0) { $qry .= ","; }
                    $qry .= "[$k] = ?";
                    $i++;
            }
            $qry .= " WHERE [{$this->idField}] = ?";
            $params = $insert;
            $params[] = $this->id;	// Append the filter parameter

            // Run the query
            $qry = new MSSQLQuery($qry,$params);
            $qry = $qry->execute();

            return $qry;
    }

    /**
     * Read data from saved instance into this instantiation
     */
    public function read($searchVal = null,$searchField = null) {
        $searchField = is_null($searchField) ? $this->idField() : $searchField;
        
        // The fields to get
        // If we are joining, then this will get a full list of all fields from all joined tables
        $qry = "SELECT ";
        $qry .= $this->getFieldString();
        
        // Get a from string
        // If we are joining, this will sort out the join
        $qry .= " FROM ";
        $qry .= $this->getFromString();
        
        // If we have specified a value to search, then use it
        // First, we decide on the fields to search
        if (is_null($searchField)) {    // Search field is null, use ID field
            $searchField = $this->idField;
        }
        
        if (!is_null($searchVal)) {
            $qry .= " WHERE ";

            // If we are searching more than one field (including if no search
            // field has been specified, but we are using a composite primary key)
            // then make sure we have passed in an array as searchVal.
            // If not, then error.
            if (is_array($searchField)) {
                if (!is_array($searchVal)   // Have not passed in array
                        || 
                   (is_array($searchVal) && count($searchVal) != count($searchField))) {    // Array passed, but count doesn't match
                    Error::_sysError("Cannot read from table {$this->tableName}; search value expected as valid array.");
                }
                // All good
                $i = 0;
                foreach ($searchField as $field) {
                    if ($i > 0) {   $qry .= " AND ";    }
                    $qry .= "[$field] = ?";
                    $i++;
                }
            } else {
                if (is_array($searchVal)) {
                    $searchVal = isset ($searchVal[0]) ? $searchVal[0] : "";
                }
                $qry .= "[$searchField]  = ?";
            }
        }
        
        // Apply order clauses in order they were added
        $qry .= " ORDER BY ";
        if (empty($this->orders)) {
            if (is_array($this->idField)) {
                foreach ($this->idField as $id) {
                    $this->addOrder($id);
                }
            } else {
                $this->addOrder($this->idField);    // Always have one order
            }
        }
        $i = 0;
        foreach ($this->orders as $o) {
            if ($i > 0) {   $qry .= ", ";   }
            $qry .= "[{$o->orderField}] {$o->orderClause}";
            $i++;
        }
        
        // Run the query
        $qry = new MSSQLQuery($qry,$searchVal);
        return $this->handleResults($qry);
    }

    /**
     * Delete the current item from the database
     */
    public function delete() {
            $qry = new MSSQLQuery("DELETE FROM [{$this->tableName}] WHERE [{$this->idField}] = ?;",$this->id);
            $qry->execute();

            return true;
    }
} 

?>
