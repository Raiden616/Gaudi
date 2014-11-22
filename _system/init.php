<?php
/* Any functions to perform upon initialisation */

function sessionSecure() {	 
	// Get currently set variables
	if (isset($_SESSION['HTTP_USER_AGENT']) && isset($_SESSION['REMOTE_ADDR'])) {
		// Both variables are set, so check them with current values
		if ($_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT'] || $_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR']) {
			// If there is a discrepency, return false.
			return false;
		}
	}
	
	// If all fine, refresh variables
	$_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
	$_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
	
	return true;
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Turn error reporting on/off depending on development mode
if (DEV == "true") {
	error_reporting(E_ALL);
} else {
	error_reporting(0);
}

// Check for session hijacking
if (!sessionSecure()) {
	session_destroy();
	session_regenerate_id();
}
?>