<?php
/*
	All modules must be registered with the router before they can be called by a user. Register like this:
	
				$router->addroute($x);
	
	where $x equals the url path, excluding file extension.
	
	Feel free to enclose routes with if statements and the like.
 */

// Default "index" route
$router->addroute('index');


//$router->addroute('contact');
//
//// Authorisation Routes
//if (!User::isLoggedIn()) {
//	$router->addroute('auth/register');
//	$router->addroute('auth/login');
//	$router->addroute('auth/forgottenpassword');
//	$router->addroute('auth/verify/*/*');
//}
//if (User::isLoggedIn()) {
//	$router->addroute('auth/logout');
//	
//	// User Account Management
//	$router->addroute('user/manage');
//	$router->addroute('user/manage/password');
//	$router->addroute('user/manage/email');
//}

?>
