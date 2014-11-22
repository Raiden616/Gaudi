<?php
class Sys_Plugins {
    private static $plugins = array();

    public static function Register($folderName,$title = null) {
        // If foldername already taken, error
        if (array_key_exists($folderName,self::$plugins)) {
                return false;
        }

        // Default title to folder name
        if (is_null($title)) {
                $tile = $folderName;
        }

        $path = SERROOT."/plugins/$folderName";
        $indexPath = "$path/index.php";
        if (!is_dir($path)/* || !file_exists($indexPath) || !is_readable($indexPath)*/) {
                return false;
        }

        self::$plugins[$folderName] = array(
                'title' => $title,
                'path' => $path
                );
/*
        if (Router::isCMS()) {
                $path = "$path/_cms/";
        } else {
                $path = "$path/";
        }*/

        Router::addController($folderName,$path);
        //require_once("$path/index.php");

        return true;
    }

    public static function isRegistered($name) {
        if (array_key_exists($name,self::$plugins)) {
                $path = SERROOT."/plugins/$name";
                return $path;
        }
        return false;
    }

    public static function getPlugins() {
        return self::$plugins;
    }

    /*
     * Get an array of all valid locations for "routes.php" files
     */
    public static function routeFiles() {
        $files = array();

        foreach (self::$plugins as $f) {
            $routePath = null;
            if (GROUP == "admin") {
                    $routePath = "{$f['path']}/_cms/routes.php";
            } else {
                    $routePath = "{$f['path']}/routes.php";
            }
            if (!is_null($routePath) && file_exists($routePath) && is_readable($routePath)) {
                $files[] = $routePath;
            }
        }

        return $files;
    }
    
    /*
     * Get all paths
     */
    public static function getPaths() {
        $paths = array();
        foreach (self::$plugins as $p) {
            $path = null;
            if (Router::isCMS()) {
                $path = "{$p['path']}/_cms";
            } else {
                $path = $p['path'];
            }
            if (!is_null($path) && file_exists($path) && is_dir($path)) {
                $paths[] = $path;
            }
        }
        return $paths;
    }
    
    /*
     * Get an array of all valid plugin paths, where they include a "models' directory
     */
    public static function modelLocations() {
        $paths = array();
        
        foreach (self::getPaths() as $p) {
            if (is_dir("$p/models")) {
                $paths[] = $p;
            }
        }
        
        return $paths;
    }
    
    /*
     * Get an array of all valid plugin paths, where they include a "controllers" directory
     */
    public static function controllerLocations() {
        $paths = array();
        
        foreach (self::getPaths() as $p) {
            if (is_dir("$p/controllers")) {
                $paths[] = $p;
            }
        }
        
        return $paths;
    }
    
    /*
     * Get an array of all valid plugin paths, where they include a "views" directory
     */
    public static function viewLocations() {
        $paths = array();
        foreach (self::getPaths() as $p) {
            if (is_dir("$p/views")) {
                $paths[] = $p;
            }
        }
        return $paths;
    }
}