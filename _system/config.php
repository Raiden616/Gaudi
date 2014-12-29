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

/* System configuration */
define("SERROOT",getcwd()."/..");
define("URLROOT","/");
define("TIMEZONE","UTC");
	$directory_self = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
define ("DIRROOT",$_SERVER['DOCUMENT_ROOT'].$directory_self);
define ("WEBROOT","htdocs");
define ("UPLOADDIR",SERROOT."/uploads");
define ("MODEL_DIR","models");

/* SQL Settings */
	// Set the databsae engine to one of the following supported engines:
	//	 - MySQL
	//	 - MSSQL
define ("DATABASE_ENGINE","MySQL");
	// MSSQL-server specific settings
define ("ODBC_DRIVER","SQL Server");	// ODBC driver to use in connection string

/* CMS SETTINGS */
define ("CMSROUTE","admin");

/**
 * Class of methods to get and update configuration keys from database
 */
class Configuration {
	private static $config = array(	// Define array of keys
		"SITENAME" => "",
		"SITETITLE" => "",
		
		"WEBMASTER_NAME" => "Clark Sirl",
		"WEBMASTER_EMAIL" => "clark@clarksirl.co.uk",
		"AUTO_EMAIL" => "no-reply@clarksirl.co.uk",
		
		"META_TITLE" => "Home Page",
		"META_DESC" => "Home Page",
		"META_KEYW" => "Home Page"
	);
	
	/**
	 * Function to update an individual key
	 */
	public static function update($key,$value) {
		if (array_key_exists($key,self::$config) && !empty($value)) {	// Restrict to those in pre-set array
			$config = new Model("config","key");
			$config['key'] = $key;
			$config['val'] = $value;
			return $config->save();
		} else {
			return false;
		}
	}
	
	/**
	 * Get all config keys
	 */
	public static function getAll() {
		$config = self::$config;
	
		$model = new Model('gaudi_system','key');
		while ($model->read()) {
			if (isset($config[$model['key']])) {
				$config[$model['key']] = $config['val'];
			}
		}
		
		return $config;
	}
	
	/**
	 * Get the config value for an individual key
	 */
	public static function get($key) {
		if (!array_key_exists($key,self::$config)) {
			return false;
		}
		
		$model = new Model('config','key');
		if ($model->read($key)) {
			return $model['val'];
		} else {	// Doesn't exist in database, return default
			return self::$config[$key];
		}
	}
        
        /**
         * Set the default value for a key
         */
        public static function set($key,$val) {
            self::$config[$key] = $val;
        }
}

?>
