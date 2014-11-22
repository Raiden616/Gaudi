<?php

/* 
 * Copyright (C) 2014 Clark Sirl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Start session
session_start();

// Include system configuration
require_once('../config/config.php');
chdir(SERROOT); // Move to the server route, as defined in config.php

chdir("_system");   // Move to system file
// Include system files
require_once('response.php');
require_once('classes.php');
require_once('model.php');
require_once('bootstrap.php');
require_once('router.php');
require_once('plugin.php');

// Perform initialisation checks
require_once('init.php');

chdir("../");

// Apply site configuration
foreach (Configuration::getAll() as $k => $v) {
	define ($k,$v);
}

// Register plugins
// Iterate through plugins and register them to Sys_Plugins
$pluginDir = SERROOT."/plugins";
$pluginDirs = scandir($pluginDir);
foreach ($pluginDirs as $pd) {
    // Ignore "." and "..", and files that aren't directories
    if ($pd == "." || $pd == ".." || !is_dir("$pluginDir/$pd")) {    continue;   }
    Sys_Plugins::Register($pd); // Register the plugin
}

// Get route (if none specified, use "index"
if (!empty($_GET['route'])) {
	$route = rtrim($_GET['route'],'/');
} else {
	$route = 'index';
}

// Explode the route and figure out the "group"
$r = explode("/",$route);
define("GROUP",$r[0]);
// Get output format
if (!empty($_GET['opformat'])) {
	$opformat = $_GET['opformat'];
} else {
	$opformat = 'html';
}
define ("OPFORMAT",$opformat);

// Instantiate main router
$router = new Router();

// Handle the CMS
if (GROUP == CMSROUTE) {
	// If this is an admin route, load the cms instead
	chdir("_cms"); // Switch to CMS directory
	// Reform route
	$newRoute = array();
	foreach ($r as $k => $v) {
		if ($k != 0) {
			$newRoute[$k-1] = $v;
		}
	}
	if (empty($newRoute)) {
		$newRoute[0] = "index";
	}
	$r = $newRoute;
	$route = implode("/",$r);
}

define ("ROUTE",$route);

// Include routes
require_once('config/routes.php');
// Include plugin routes
foreach (Sys_Plugins::routeFiles() as $r) {
    require_once($r);
}

/*
require_once(SERROOT.'/config/plugins.php');
foreach (Sys_Plugins::routeFiles() as $f) {
	require_once($f);
}
*/
/*
// Handle file requests
if ($r[0] == "file" && isset($r[1])) {
	__autoload("File");
	$contr = "Controller_File";
	if (method_exists($contr,$r[1])) {
		$method = $r[1];
		if (isset($r[2])) {
			unset($r[0]);
			unset($r[1]);
			call_user_func_array(array($contr,$method),$r);
		} else {
			call_user_func(array($contr,$method));
		}
		exit;
	}
}*/

// Run router
$router->route($route);
echo $router->output();
exit;
?>
