<?php
class Router {
	public $buffer;
        public $rawBuffer;
	private $routes = array();
	
	private static $controllers = array();
	
	public function addroute($route) {
		array_push($this->routes,$route);
	}
	
	public function addAjax($route) {
		// Adds a route that can only be accessed through ajax
		if (self::opFormat() == "ajax") {
			$this->addroute($route);
		}
	}
	
	public static function addController($className,$path) {
		self::$controllers[$className] = $path;
	}
	
	public function route($route) {
            /*
                    ROUTING FUNCTION
                    Picks up the route from the URL (really the query string) and then loads the module, page content, function, etc. as required.
            */
            $expRoute = explode('/',$route);

            // Start the first output buffer
            // Using at output buffer whilst the controller method is running allows us to
            // capture anything echoed during this method and end to a secondary stream.
            // Since you are not supposed to echo anything out in the method, this is useful for capturing
            // errors, etc.
            ob_start();
            
            $controllerOutput = null;

            if ($this->checkRoute($expRoute)) {
                // Run the controller function and get a response.
                // Response will be either:
                //  - Response object: render it according to its own render() method
                //  - boolean: assemble an appropriate response object and render it
                $controllerOutput = $this->loadRoute($expRoute);
                
            } else {    // Not a set route, so try and match it to a page in the model
                
                // Only do this if we are not in the CMS
                if (!self::isCMS()) {   // It is not the CMS
                    // Try and read one from the database
                    $m = new Model('pages');
                    if ($m->read($expRoute)) {  // Found a page in the db
                        // Get the theme for this page, and from that derive
                        // the wrapper, wrapper parts and anything else set to the theme.
                        
                        // Assemble the page object to return using the derived parts
                        $page = new Page();
                        
                    }
                }
                
            }

            $this->rawBuffer = ob_get_contents();
            ob_end_clean();

            // If we have rendered nothing after all that, then render a 404 error
            if (is_null($controllerOutput)) {
                Error::log("Route \"$route\" cannot resolve to controller or page.");
                $controllerOutput = Error::_404();
            }
            
            // Render what we receive
            if ($controllerOutput instanceof System_Controller_Response) { // Is a controller reponse object
                
                if (DEBUG == 'true') {
                    $controllerOutput->setConsole($this->rawBuffer);
                }
                $this->buffer = $controllerOutput->render(self::opFormat());
            } else if ($controllerOutput === true) {

            } else if ($controllerOutput === false ) {

            }
	}

	
	private function checkRoute($route) {
		// This checks to see if the route that has been put in is registered in routes.php
		foreach ($this->routes as $spec) {
			$spec = explode('/',$spec);
			if (count($route) == count($spec)) {
				$match = true;
				for ($i = 0; $i < count($route); $i++) {
					if ($route[$i] != $spec[$i] && $spec[$i] != "*") {
						$match = false;
					}
				}
				if ($match) {					
					return true;
				}
			}
		}
		return false;
	}
	
	private function loadRoute($route,$dir = "") {
            // Set the controllers folder
            $root = "controllers/";
            // First, we assume that route is class/method/par/am/eters
            if (!isset($route[0]) || empty($route[0])) {
                    $class = 'index';
            } else {
                    $class = $route[0];
            }
            
            // Recursively check the "controllers" folder for files matching the passed route
            $path = $root.$dir.$class;
            $className = "Controller_$class";
            if (file_exists($path.".php") && is_readable($path.".php")) {
                    // Finds PHP file
                    require_once($path.".php");
                    if (class_exists($className)) {
                            if (!empty($route[1])) {
                                    $method = $route[1];
                            } else {
                                    $method = 'index';
                            }
                            if (method_exists(ucfirst($className),$method)) {
                                    if (isset($route[2])) {
                                            unset($route[0]);
                                            unset($route[1]);
                                            return call_user_func_array(array(ucfirst($className),$method),$route);
                                    } else {
                                            return call_user_func(array(ucfirst($className),$method));
                                    }
                            }
                    }
            }

            // If we can't find the file but there is a directory, then move to inside that and check again
            if (is_dir($path)) {
                    $newRoute = array();
                    // Take off the first bit of the route and append that to the directory variable
                    for ($i = 1;$i < count($route);$i++) {
                            $newRoute[$i-1] = $route[$i];
                    }
                    $dir .= "$class/";
                    // We now recursively call this method for the new directory. If not found there,
                    // it will repeat the process until either the file is found (load the file), or neither are found (throw 404).
                    return $this->loadRoute($newRoute,$dir);
            }
            // Controller not found
            
            // Check the plugins' controllers (this must be done second so people can overwrite plugin controllers if they see fit)
            foreach(Sys_Plugins::viewLocations() as $dir) {
                $file = "$dir/$viewString.php";
                if (file_exists($file) && is_readable($file)) {
                    $this->content = $file;
                    $this->file = true;
                    return true;
                }
            }

            return Error::_404();
	}
	
	public function output() {
            return $this->buffer;
	}
	
	public static function isCMS($requireAuth = false) {
		if ($requireAuth && !User::authCheck('admin')) {
			return false;
		}

		return GROUP == CMSROUTE && (getcwd() == SERROOT."/".CMSDIR || getcwd() == SERROOT."\\".CMSDIR);
	}
	
	public static function opFormat($check = null) {
		if (!is_null($check)) { return (OPFORMAT == $check); }
		return OPFORMAT;
	}
}
?>
