<?php
//
//
//
//
//
	$VERSION_NUMBER = "4.2.7d";
//
//
//
//
// V.4.2.3 constants renamed, install process fixes, code cleaning
// V.4.2.5 notify admin for new user, register as a webmaster, fixed a bug on ENCRIPTIONKEY
// V.4.2.6 PHP 8.2 support, fixed a bug on coinbase process, code cleaning (also separation of functions and classes in users and banners), new date format YYYY/MM/DD, support for strong passwords, fixed a bug on select list positions in banner list, fixed a bug in the dashboard when advertiser or webmaster hasn't any banner to see, bug on show views and clicks increment in banner list, minor css fixes in dashboard, on some apps hte tracking click comes without the referrer this cause clicks to be 0 in the graph (now fixed)
// V.4.2.6b bug on payments filters (webmaster added), empty grid message fixed, bug fixed on menu
// V.4.2.6c bug fixed for icons in the menu, missing id_payment bug, default component bug, fixed geoip limit country setting, fixed br in manual payment settings
// V.4.2.6d bug fixed on banner position info
// V.4.2.6e bug fixed on cognome missing field
// V.4.2.6f bug on vignette mode in desktop
// V.4.2.6g bug on vignette mode countdown if 0 is placed in timer
// V.4.2.6h bug fixed on banner prices and fixed video responsive banner template in example folder
// V.4.2.7 added honeypot on sign in registration to limit spam, code comments, a better upload banner button, fixed a bug on advertiser delete function, added a field on the My Profile to let the webmaster input its payment details. Fixed inheritance code on My Profile functionalities.
// V.4.2.7a bug on personifica function to log in as another user
// V.4.2.7b bug on banner upload image behviour, code refactoring on clients and minor fixes to code
// V.4.2.7c bug on installation if wrong db credentials are set, added ctrl + / ctrl - / ctrl+0 shortcodes to zoom in and out, fixed a bug with encoding in email subjects
// V.4.2.7d administrator now can delete other administrators

$conn = false;
if(!isset($root)) die("no root");

$public = isset($public) ? $public : false;  // $public = false (default, for pages that need login)


//
//
// manage pons-settings file to simplify installation process
if(!file_exists($root."pons-settings.php")) {	
	if(file_exists($root."pons-settings-install.php")) {
		rename($root."pons-settings-install.php", $root."pons-settings.php");
		if(!file_exists($root."pons-settings.php")) {
			die("<pre>"."\n\n".
				"Can't rename pons-settings-install.php to pons-settings.php please check permissions for this file.\n\n".
				"PHP must be allowed to read and write on pons-settings*.php files and on some folders under data folder.\n\n".
				"</pre>");
		}
	}
}

include($root."pons-settings.php");


//
// array that contains translations labels
$langArrayLabels = array();


if(!DEFINED("LANGUAGEFILE")) {
	die("<pre>Old configuration found.\nPlease, open yuur pons-settings.php file and add this:\n\n\t\tdefine(\"LANGUAGEFILE\",\"en.lang.txt\");\n\n</pre>");
}

if(DEFINED("JQUERYINCLUDE")) {
	die("<pre>Old configuration found.\nPlease, open yuur pons-settings.php file and remove this:\n\n\t\tdefine(\"JQUERYINCLUDE\",\"...lot of code here ...\");\n\n</pre>");
}

if(!DEFINED("INSTALLER")) {
	DEFINE("INSTALLER","install");
}



//
//	works like old php deprecated function "magic_quotes_gpc"
//	make all variables in get and post with slashes
//
if ( phpversion() > '5.4' || !get_magic_quotes_gpc()) {
	// funzione ricorsiva per l'aggiunta degli slashes ad un array  
	function magicSlashes($element) {
		if (is_array($element)) return array_map("magicSlashes", $element); else return addslashes($element);  
	}
	// Aggiungo gli slashes a tutti i dati GET/POST/COOKIE  
	if (isset ($_GET)     && count($_GET))    $_GET    = array_map("magicSlashes", $_GET);  
	if (isset ($_POST)    && count($_POST))   $_POST   = array_map("magicSlashes", $_POST);  
	//if (isset ($_COOKIES) && count($_COOKIES))$_COOKIE = array_map("magicSlashes", $_COOKIE);  
}

if (function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Rome');
if( phpversion() >= '5.0' ) @ini_set('zend.ze1_compatibility_mode', '0');// for PHP 5 compatibility

include($root."src/_include/comode.php");

include($root."src/_include/cryptor.class.php");
include($root."src/_include/logger.class.php");

$logger = new logger();


// check writing permissions
writehere($root."data/dbimg/demofiles", false);
writehere($root."data/dbimg/media", false);
if(file_exists($root."data/geoip")) writehere($root."data/geoip");
writehere($root. str_replace(basename(LOGS_FILENAME),"lock.txt", LOGS_FILENAME), false);
writehere($root."data/logs/log.txt");



$lockupdate = $root. str_replace(basename(LOGS_FILENAME),"lock.txt", LOGS_FILENAME);
if(!Connessione() && file_exists($lockupdate)) {
	// start installation

	if( !stristr( $_SERVER['REQUEST_URI'] , "/".INSTALLER."/" )) {
		if(isset($_GET['modificaStep2'])) $op = "?op=modificaStep2&fromconfig=".rand(1,11111); else $op ="?no1&rnd=".rand(1,111111);
		echo "<script>document.location.href='".$root."src/componenti/".INSTALLER."/index.php".$op."';</script>";
		die;
	}
} else {

	if(!Connessione() && WEBDOMAIN!="") {
		die("DB SERVER DOWN");
	}

	if(!Connessione() && WEBDOMAIN=="") {
		if( !stristr( $_SERVER['REQUEST_URI'] , "/".INSTALLER."/" )) {
			$op="?op2=startinstall";
			echo "<script>document.location.href='".$root."src/componenti/".INSTALLER."/index.php".$op."';</script>";
			die;
		}
	}

	// db connected ok
	// check tables

	
	
	if (!table_exists(DB_PREFIX."frw_vars") || file_exists($lockupdate) ) {
		// install needed

		
		if( !stristr( $_SERVER['REQUEST_URI'] , "/".INSTALLER."/" )) {
			if(isset($_GET['modificaStep2'])) $op = "?op=modificaStep2&fromconfig2=".rand(1,11111); else $op ="?no2&rnd=".rand(1,111111);
			echo "<script>document.location.href='".$root."src/componenti/".INSTALLER."/index.php".$op."';</script>";
			die;
		}

	}


	CollateConnessione();
}



include($root."src/_include/session.class.php");
include($root."src/_include/ambiente.class.php");

$ambiente = new ambiente();

$session=new session();

header('Content-type: text/html; charset=utf-8');

if(!defined("SERVER_NAME")) {
    
		// all variables that starts with CONST_ from table frw_vars become constants
		if(!mysqli_connect_errno() && table_exists(DB_PREFIX."frw_vars")) {
			$sql = "select * from ".DB_PREFIX."frw_vars WHERE de_nome like 'CONST_%'";
			$rs = $conn->query($sql) or trigger_error($conn->error);
			while($riga = $rs->fetch_array()) {
				$NAME =str_replace("CONST_","",$riga['de_nome']);
				if($riga['de_value'] == "true") $riga['de_value'] = true;
				if($riga['de_value'] == "false") $riga['de_value'] = false;
				define($NAME, $riga['de_value']);
			}
		}
	
} else {
	/*
	// why?
	foreach($_SESSION as $k=>$v) {
		if(preg_match("/^CONST\_/",$k)) {
			$NAME =str_replace("CONST_","",$k);
			define($NAME, $v);
		}
	}
	*/
}


//
// jquery inclusion and other stuff in <head> tag.
define("JQUERYINCLUDE",'
	<link rel="stylesheet" type="text/css" href="'.$root.'src/template/stile.css?ver='.$VERSION_NUMBER.'"><!-- common styles -->
	<link rel="stylesheet" type="text/css" href="'.$root.'data/'.DOMINIODEFAULT.'/stile.css?ver='.$VERSION_NUMBER.'"><!-- theme style -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
	<link href="'.$root.'src/icons/fontello/css/fontello.css?ver='.$VERSION_NUMBER.'" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Titillium+Web" rel="stylesheet">
	<link rel="icon" type="image/png" href="'.$root.'data/'.DOMINIODEFAULT.'/favicon.png" />
');

//
// these are variable replaced automatically in templates 
// (used in calls to loadTemplateAndParse)
// this array can be modified runtime to add more replaces
// -----------------------------------------------------------------------------------------------------------------
$defaultReplace = array(
	"##root##"=>$root,
	"##DOMINIO##"=>DOMINIODEFAULT,
	"##JQUERYINCLUDE##"=>JQUERYINCLUDE,
	"##PONSDIR##"=>PONSDIR,
	"##rand##"=>rand(1,9999),
	"##VER##"=>$VERSION_NUMBER,
	"##SERVER_NAME##"=>defined("SERVER_NAME") ? SERVER_NAME : "not_provided",
	"##classes##"=>"profile". $session->get("idprofilo"),
);



include($root."src/_include/login.class.php");
$login = new login();


//
// check login
// -----------------------------------------------------------------------------------------------------------------
if ( ( !$public && !$login->logged() )      ) {

	//
	//	if not logged load login form
	//	this if is mandatory
	//
	$session->finish();

	print $ambiente->loadLogin("");
	die;

}



// ---------------------------------------------------------------------------------
// trigger error is this function doesn't exist in PHP it's needed

if(!function_exists("mb_detect_encoding")) trigger_error("You need to activate php 'MBSTRING'.");

// ---------------------------------------------------------------------------------


