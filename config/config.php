<?php
/* Site Settings */
define ("DEV","true");
define ("DEBUG","true");

if (DEV == "false") {
/* Live site settings */
	define("SITEURL","http://www.clarksirl.co.uk");
	define("SERROOT","/home/web");
	
	//DB Authentication
	define("DB_HOST","127.0.0.1");
	define("DB_USER","sqluser");
	define("DB_PASS","sqlpass");
	define("DB_NAME","sqldatabase");
	
} else if (DEV == "true") {
/* Development site settings */
	define("SITEURL","http://gaudi3.local");
	define("SERROOT","/Users/clark/Google Drive/Development/Gaudi_mk3");
	
	//DB Authentication
	define("DB_HOST","");
	define("DB_USER","");
	define("DB_PASS","");
	define("DB_NAME","");
}

/* System configuration */
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

/* Encryption settings */
define("XTEA_KEY","40151ddfdd1a47ace976a476efdfcb5a");
define("PASS_ALG","sha256");
define("PASS_SALT","pdydJn6ECZ.CRRQs^W#pme9205Bxx,GD)j(-mxGHg5ED+");

/* CMS SETTINGS */
	// Ignore this section if you do not use the cms
define ("CMSROUTE","admin");

define("FB_APPID","304964039582113");

/**
 * Class of methods to get and update configuration keys from database
 */
class Configuration {
	private static $keys = array(	// Define array of keys
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
		if (array_key_exists($key,self::$keys) && !empty($value)) {	// Restrict to those in pre-set array
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
		$config = self::$keys;
	
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
		if (!array_key_exists($key,self::$keys)) {
			return false;
		}
		
		$model = new Model('config','key');
		if ($model->read($key)) {
			return $model['val'];
		} else {	// Doesn't exist in database, return default
			return self::$keys[$key];
		}
	}
}

?>
