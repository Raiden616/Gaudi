<?php

/*
 * MySQL Model
 */
class Model extends SuperModel{
    public function initialise($tableName,$idField = "id") {
        // Make sure table exists
		$bCached = false;
		foreach (self::$cached_Tables as $t) {
			if ($t == $tableName) { // Found the table record in the cache
				$bCached = true;
				break;
			}
		}
		if (!$bCached) {    // If it wasn't cached, get it from the database
			$qry = new MySQLQuery("SHOW TABLES LIKE '%s';",$tableName);
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
		$qry = new MySQLQuery("SHOW COLUMNS FROM `$tableName`;");
		$attributes = $qry->fetchAll();
		
		// Check ID field exists
		$identityField = "";
		$bFoundIDCount = 0;
		$bRequiredIDCount = is_array($idField) ? count($idField) : 1;
		foreach ($attributes as $a) {
			// Is this an auto-incrementing field?
			$bAutoInc = $a['Extra'] == "auto_increment";
		
			if (is_array($idField)) {	// If we are looking for more than one field
				if (in_array($a['Field'],$idField)) {
					$bFoundIDCount++;
				}
			} else {	// We are just looking for one field
				if ($a['Field'] == $idField) {
					$bFoundIDCount++;
					// If this is an auto-incrementing field, then mark the model as having an auto-increment ID
					$this->auto_increment = $bAutoInc;
				}
			}
			
			// Make the column entry
			$this->addColumn($a['Field'],$a['Type'],$bAutoInc);
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
    
    protected function getFieldString() {
        $string = "";
        
        $i = 0;
        foreach ($attributes = $this->getAttributes() as $a) {
            // If we have an object joined, we don't want to get the foreign key
            if (!empty($this->joinedForeignKeyField) && $this->joinedForeignKeyField == $a->name) {  continue;   }
            
            if ($i > 0) {   $string .= ","; }
            $string .= "`{$this->tableName}`.`{$a->name}`";
            $i++;
        }
        
        // Append the joined fields
        if (!is_null($this->joined) && $this->joined instanceof MySQLModel) {    // A valid model is joined
            $string .= $this->joined->getFieldString();
        }
        
        return $string;
    }
    
    protected function getFromString($bIncludeTableName = true) {
        $string = $bIncludeTableName ? "`{$this->tableName}`" : "";
        
        // Append the join
        //if (!is_null($this->joined) && $this->joined instanceof MySQLModel) {   // A valid model is joined
        if ($this->isJoined()) {
            $string .= " INNER JOIN `{$this->joined->tableName}` ON `{$this->joined->tableName}`.`{$this->joined->idField}` = `{$this->tableName}`.`{$this->joinedForeignKeyField}`";
            
            $string .= $this->joined->getFromString(false);
        }
        
        return $string;
    }
    
    protected function getFullColumnName($name) {
        $name = "";
        if ($this->hasColumn($name)) {
            $name = "`{$this->tableName}`.`$name`";
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
        $insert = $this->data;
        if (!is_null($this->id)) { $insert[$this->idField] = $this->id; }

        // Root out null values
        foreach ($insert as $k => $v) {
                if (is_null($v)) {
                        unset($insert[$k]);
                }
        }

        // Exit if no values left
        if (empty($insert)) {
                return false;
        }

        $qry = "INSERT INTO `{$this->tableName}` (";
        $i = 0;
        foreach ($insert as $k => $v) {
                if ($i > 0) { $qry .= ","; }
                $i++;
                $qry .= "`$k`";
        }
        $qry .= ") VALUES (";
        $i = 1;
        foreach ($insert as $k => $v) {
                if ($i > 1) { $qry .= ","; }
                $qry .= ($k == "id" ? "'%$i\$u'" : "'%$i\$s'");
                $i++;
        }
        $qry .= ")";
        $qry .= " ON DUPLICATE KEY UPDATE ";
        $i = 1;
        foreach ($insert as $k => $v) {
                if ($i > 1) { $qry .= ","; }
                $qry .= "`$k` = '%$i\$s'";
                $i++;
        }

        $qry = new MySQLQuery($qry,$insert);

        $qry = $qry->execute();

        if (is_numeric($qry) && $this->auto_increment) { $this->id = $qry; } // Update ID field, if the ID field is an auto_incrementing integer

        return $qry;
    }

    /**
     * Read data from saved instance into this instantiation
     */
    public function read($val = null,$attr = null) {
        $attr = is_null($attr) ? $this->idField() : $attr;
        
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
        if (is_null($attr)) {    // Search field is null, use ID field
            $attr = $this->idField;
        }
        
        if (!is_null($val)) {
            $qry .= " WHERE ";
        
            // If we are searching more than one field (including if no search
            // field has been specified, but we are using a composite primary key)
            // then make sure we have passed in an array as val.
            // If not, then error.
            if (is_array($attr)) {
                if (!is_array($val)   // Have not passed in array
                        || 
                   (is_array($val) && count($val) != count($attr))) {    // Array passed, but count doesn't match
                    Error::_sysError("Cannot read from table {$this->tableName}; search value expected as valid array.");
                }
                // All good
                $i = 0;
                foreach ($attr as $field) {
                    if ($i > 0) {   $qry .= " AND ";    }
                    $qry .= "`$field` = '%s'";
                    $i++;
                }
            } else {
                if (is_array($val)) {
                    $val = isset ($val[0]) ? $val[0] : "";
                }
                $qry .= "`$attr`  = '%s'";
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
            $qry .= "`{$o->orderField}` {$o->orderClause}";
            $i++;
        }
        
        // Run the query
        $qry = new MySQLQuery($qry,$val);
        return $this->handleResults($qry);
    }

    /*
     * Delete the current item from the database
     */
    public function delete() {
        $qry = new MySQLQuery("DELETE FROM `{$this->tableName}` WHERE `{$this->idField}` = '%".($this->auto_increment ? "u" : "s")."'",$this->id);
        $qry->execute();

        return true;
    }
}
