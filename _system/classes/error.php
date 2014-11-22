<?php
class Error {
	public static function _sysError($message = null,$showifLive = false) {
		// Log error in file
		self::log($message);
		
		// Handle what is displayed to the user
		if (($showifLive || DEV == "true") && !is_null($message)) {
			// Displays passed message if site is in DEV mode
			print(nl2br("$message<br/>"));
		} else {
			// If site in LIVE mode or no message passed, display generic error page
			echo "Fatal error in system: Unable to generate page. Installation appears to be incomplete or corrupted.";
		}
                exit;
		return false;
	}
	
	// A nice erroring function for user-facing bits.
	// Logs an error in the log file and displays a friendly error screen.
	public static function custom($msg = null,$title = "An error has occured") {
		if(is_null($msg)) {
                    return self::_404();
		}
		self::log("UNREGISTERED ERROR - \"$msg\"");

		$page = new Page();
		$page->header("title",$title);
		$page->assign('heading',$title);
		$page->assign('content',"<p>$msg</p>");
		//$page->render();
		//exit;
                return $page;
	}
	
	public static function _404() {
		$route = ROUTE;
		
		if (Router::isCMS() && !User::authCheck('admin')) {
			chdir(SERROOT); // Break out of CMS as user is not signed in
		}
		
		if (Router::isCMS()) { // Display CMS error
			$page = new Page('_errors/404');
			$page->header('title','Error 404');
		} else {
			$tpl = self::getTemplate('404');	// Get the view object relating to 404.
			if (!$tpl) {	// If none exists, make one from the file (this is the default)
				$page = new Page("errors/404");
				$page->header('title',"Error 404");
			} else {	// If got one, then build a page and use the provided HTML
				$page = new Page();
				$page->header('title',"Error 404");
				$page->assign('content',$tpl['content']);
			}
		}
		//$page->render();
		
		//return;
                return $page;
	}
	
	public static function _registration() {
		$page = new Page();
		$page->header('title','Registration Error');
		$page->assign('heading',"registration error");
		$page->assign('content',"There has been an error with your registration. Apologies for the inconvenience; please try again later.");
		//$page->render();
		//exit;
                return $page;
	}
	
	public static function _login() {
		$page = new Page();
		$page->header('title','Login Error');
		$page->assign('heading',"login error");
		$page->assign('content',"There has been an error with your login. Apologies for the inconvenience; please try again later.");
		//$page->render();
		//exit;
                return $page;
	}
	
	/* Model handlers */
	private static function getTemplate($str = "404") {
		$model = new Model('gaudi_templates','name');
		if ($model->read(array($str,"error"),array('name','type'))) {
			return $model;
		} else {
			return false;
		}
	
		//$qry = new MySQLQuery("SELECT `title`,`html` as content FROM `templates` WHERE `type` = 'error' AND `name` = '%s' LIMIT 0,1",$str);
		//$row = $qry->fetch();
		
		//return $row;
	}
	
	public static function log($error) {
		$log = new Log();
                // Suppress failures to log
                // "Log" directory must be manually created to activate logs.
		@$log->write($error);
	}
}

class Log {
	private $errLog = "/logs/error.log";
	
	public function write($error,$destination = null) {
		if (!is_null($destination)) {
			$this->errLog = $destination;
		}
		$destination = SERROOT.$this->errLog;
		$res = error_log(
			date("Y-m-d H:i:s",time())." $error\n",
			3,
			$destination
		);
		return $res;
	}
}
?>
 
