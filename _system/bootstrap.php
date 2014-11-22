<?php
/*

	SYSTEM FILE - DO NOT EDIT

	
	This script handles the autoloading of classes.
*/

// Register the autoload method with the spl register, so that PHP knows to call it when a non-existent class is called
spl_autoload_register('__autoload');

function __autoload($class_name) {
	$class_name = strtolower($class_name);
	
	// Checks through files in the "model" and "model/auto" directory
        // for matching classes
	$files = array();
        // Check the main models folder
	array_push($files,SERROOT.'/models/auto/'.$class_name.'.php');
	array_push($files,SERROOT.'/models/'.$class_name.'.php');
        // Check each plugin "model" folder
        foreach(Sys_Plugins::modelLocations() as $dir) {
            array_push($files,"$dir/models/$class_name.php");
        }
        // Check each plugin root folder
        foreach(Sys_Plugins::getPaths() as $dir) {
            array_push($files,"$dir/$class_name.php");
        }
	
	// Here we include any relevant files
	foreach ($files as $class_file) {
            if(file_exists($class_file) && is_readable($class_file)) {  // Found a valid file
    		require_once($class_file);  // Include it
            	break;  // Don't include any more
            }
	}
	// Finally, after including all the files it can, it checks if the class now exists and returns true or false for that.
	//return class_exists($class_name);
	if (class_exists($class_name)) {    // If we have included the right file
		return true;
	}
	
	return false;
}

spl_autoload_extensions('.php,.inc');
?>
