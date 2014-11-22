<?php
class Sys_Models {
	private static $models = array();
	
	public static function Register($className,$title = null) {
		// If class already taken, error
		if (array_key_exists($className,self::$models)) {
			Error::_sysError("Use of duplicate library name $fileName");
			return false;
		}
	
		// Default title to className
		if (is_null($title)) {
			$title = $className;
		}
		
		$fileName = strtolower($className);
		
		$path = SERROOT."/".MODEL_DIR."/$fileName.php";
		if (!file_exists($path) || !is_readable($path)) {
			Error::_sysError("Failed to register model $className - no corresponding file found. Make sure the specified class is located inside /models/$fileName.php.");
			return false;
		}
		
		self::$models[$className] = array(
			'className' => $className,
			'title' => $title
			);
		
		return true;
	}
	
	public static function isRegistered($name) {
		foreach (self::$models as $k => $v) {
			$fileName = strtolower($k);
			if ($fileName == $name) {
				return $v;
			}
		}
		
		return false;
	}
	
	public static function files() {
		return self::$models;
	}
}
?>