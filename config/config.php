<?php
/* Site Settings */
define ("DEV","true");
define ("DEBUG","true");

Configuration::set('SITENAME',    "Gaudi Test Site"       );    // Name for overall website
Configuration::set('SITETITLE',   "Gaudi Test"        );    // Will appear in the title of every page

Configuration::set('WEBMASTER_NAME',  "Clark Sirl"        );    // Your name
Configuration::set('WEBMASTER_EMAIL', "clark@clarksirl.co.uk"     );    // Your email
Configuration::set('AUTO_EMAIL',  "no-reply@clarksirl.co.uk"     );    // Emails sent from the site will be sent from this

Configuration::set('META_TITLE',  "Home Page"     );    // Default title for a page (will appear in the title of every page where a title is not specified)
Configuration::set('META_DESC',   "This is a home page"       );    // Default description for a page
Configuration::set('META_KEYW',   "Home, page, test"      );    // Default keywords for a page

if (DEV == "false") {
/* Live database settings */
	
	//DB Authentication
	define("DB_HOST","127.0.0.1");
	define("DB_USER","sqluser");
	define("DB_PASS","sqlpass");
	define("DB_NAME","sqldatabase");
	
} else if (DEV == "true") {
/* Development database settings */
	
	//DB Authentication
	define("DB_HOST","");
	define("DB_USER","");
	define("DB_PASS","");
	define("DB_NAME","");
}

