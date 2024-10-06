<?php
//----------------------------------------------------------------------------
// AdAdmin Config file
// This software it's not free, you can buy it on CodeCanyon. Thank you.
// Author page: https://codecanyon.net/user/giuliopons
// Discussion: https://github.com/giuliopons/adadmin/discussions
//----------------------------------------------------------------------------



// YOUR SETTINGS
//----------------------------------------------------------------------------

// database configuration
define("WEBDOMAIN","");
define("DEFDBNAME","");
define("DEFUSERNAME","");
define("DEFDBPWD","");
// database end configuration


//
// language translation csv file  (it and en available)
// user generated transaltions can be found here:
// https://github.com/giuliopons/adadmin
define("LANGUAGEFILE","en.lang.txt");


// If you want to customize your graphic experience you can change
// the theme folder and duplicate files to a new folder and modify
// the stile.css file.
// read more here: https://github.com/giuliopons/adadmin/discussions/24
// graphic theme folder, available themes are: basic_theme | deepblue_theme
define("DOMINIODEFAULT","deepblue_theme");



define("DB_PREFIX","");


//----------------------------------------------------------------------------
// these are more settings, pay attention changing these settings!
//----------------------------------------------------------------------------

ini_set('default_charset', 'UTF-8');
setlocale(LC_CTYPE, 'it_IT.UTF-8');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

//
// first componente to open
//
define("PRIMO_COMPONENTE_DA_MOSTRARE","BANNER");


//
// extra user panel informations
//
////define("EXTRA_USER_LINK","frwextrauserdata/index.php");
//define("DELETE_USER_LINK",true); // if false blocks user deletion

//
// error logs
//
define("LOGS_FILENAME", "data/logs/log.txt");	// log file (store some informations, used with $root + LOGS_FILENAME)
define("SHOW_ERRORS", true);	// show errors
define("SEND_ERRORS_MAIL", "");	// if filled with an email send error to this email
define("STOP_ON_ERROR", false);	// if true die after an error


//
// ENCRYPRTION KEY TO PREVENT FRAUDOLENT CLICKS
// If missing, no encryption is applied
DEFINE("ENCRYPTIONKEY",	"CHECKMEOUT-CHANGE-THIS");


//
// auto detect folder with / at beginning
//
$currentdir = __FILE__;
$currentdirAr = explode("/",str_replace('\\','/',$currentdir));   // WORKS ALSO ON WINDOWS
$currentdir = $currentdirAr[ count($currentdirAr) - 2];
$currentdir = "/".ltrim($currentdir,"/");
if( !stristr($_SERVER['REQUEST_URI'] , $currentdir."/"))  $currentdir = ".";
if($currentdir!=".") {
	$currentdir = str_replace( strstr($_SERVER['REQUEST_URI'], $currentdir), "" , $_SERVER['REQUEST_URI']  ).$currentdir;
}
define("PONSDIR",$currentdir);

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $tmpurl = "https";  else $tmpurl = "http"; 
$tmpurl .= "://"; 
$tmpurl .= $_SERVER['HTTP_HOST']; 
$tmpurl .= $currentdir != "." ? $currentdir : ""; 
define("WEBURL",$tmpurl); 

//
// FIXES
//
//define("FORCE_SQL_INVALID_DATES",true);
//define("FORCE_SQL_INVALID_INTEGERS",true);

//
// CUSTOM LOGIN CLASS
//
//define("CUSTOM_LOGIN_CLASS", "/amb/src/_include/login-custom.class-example.php");
?>
