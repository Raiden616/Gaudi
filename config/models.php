<?php
// Core libraries used by the framework
chdir(MODEL_DIR);
//require_once('htmlpurifier440/HTMLPurifier.auto.php');

/** Required libraries - custom added */


/* Start System Libraries */
chdir("_system");

// Main model library
require_once("sql.php");
require_once("model.php");

//require_once("misc.php");

require_once("html.php");
require_once("view.php");

//require_once("form.php");
//require_once("formvalidator.php");
//require_once("tabledisplay.php");

//require_once("user.php");

//require_once("file.php");


chdir("../../");


/**
 * Register custom models below
 */
//Sys_Models::Register("Example_Class_Name","Example model");
?>
