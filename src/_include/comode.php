<?php
//if(!ini_get('allow_url_fopen')) {
	//echo "Please, set <code>allow_url_fopen=1</code> in your php.ini";
	//die;
//}
// ZERO DATES FIX
// In your MYSQL installation please remove NO_ZERO_IN_DATE and NO_ZERO_DATE from sql_mode setting.
// If you can't modify these parameters, manually set to true this defined constant below:
//if(!DEFINED("FORCE_SQL_INVALID_DATES")) DEFINE("FORCE_SQL_INVALID_DATES",false);
if(!DEFINED("FORCE_SQL_INVALID_INTEGERS")) DEFINE("FORCE_SQL_INVALID_INTEGERS",false);
if(!DEFINED("ZERODATE")) DEFINE("ZERODATE","1970-01-01");

if(!DEFINED("DB_PREFIX")) 	DEFINE("DB_PREFIX","");
if(!DEFINED("ENCRYPTIONKEY")) DEFINE("ENCRYPTIONKEY","defaultdefault");
//
// script for tracking url
// used by ser.php and banner.class.php
DEFINE("BANNERLINKER",		WEBURL . "/tra.php");
/**
 * Encrypt a banner identifier to mask the id so users can't guess different ids and change urls
 * Uses the ENCRIPTYON KEY defined in pons.settings.php
 * 
 * @param integer $id
 */
function encrypt_bannerlink($id) {
	$encoded_link = BANNERLINKER."?b=".$id;
	if(DEFINED("ENCRYPTIONKEY")) {
		$encoded_link .= "&c=".md5($id . "-".ENCRYPTIONKEY);
	}
	return $encoded_link;
}

/**
 * setup connection in global $conn var
 * 
 * @return boolean
 */
function Connessione($WEBDOMAIN = WEBDOMAIN, $DEFUSERNAME = DEFUSERNAME, $DEFDBPWD = DEFDBPWD, $DEFDBNAME=DEFDBNAME) { global $conn; 
	if($WEBDOMAIN=="") return false;
	mysqli_report(MYSQLI_REPORT_OFF);
	$conn = new mysqli($WEBDOMAIN, $DEFUSERNAME, $DEFDBPWD, $DEFDBNAME );
	if ($conn->connect_errno) {
		return false;
	}		
	return true;
}

/**
 * setup collation for proper charset in the database
 * use data in frw_vars table
 * 
 * @return void
 */
function CollateConnessione() {
	global $conn;
	if (table_exists(DB_PREFIX."frw_vars")) {
		$v = getVarSetting("COLLATIONCONNECTIONQUERY");
		if ($v!="") @$conn->query($v);
		if(FORCE_SQL_INVALID_INTEGERS) {
			$conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'STRICT_TRANS_TABLES', ''));");
		}
	}
}

/**
 * get a variable from frw_vars table
 * 
 * @param string $var   var tpo search for in table
 * @param string $def   default value if not found
 * @return string
 */
function getVarSetting($var,$def="") {
	// get var settings and remove comments
	$value = execute_scalar("SELECT de_value FROM ".DB_PREFIX."frw_vars WHERE de_nome='".addslashes($var)."'",$def);
	return preg_replace("/( +)?\/\*(.*)\*\//","",$value);
}

/**
 * check if a module exists in the database
 * 
 * @return boolean
 */
function hasModule($mod) {
	global $conn;
	return execute_scalar("SELECT count(1) as c FROM ".DB_PREFIX."frw_moduli WHERE nome='".addslashes($mod)."' and visibile=1",0) ==1 ? true : false;
}

/**
 * get IP2Location record from database
 * 
 * @param string $ip
 * @return array the row of the table
 */
function getIP2LocationRow($ip) {
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {    
		$ips = ip2long($ip);
		$table = "ip2location_db3";
	} else {
		$ips = (string)gmp_import(inet_pton($ip));
		$table = "ip2location_db3_ipv6";
	}
	return execute_row("SELECT * FROM ".$table." WHERE ip_from <= $ips AND ip_to>=$ips LIMIT 0,1");
}

/**
 * try to set permissions for write on a file
 * 
 * @param string $s the file
 * @param boolean $mustexists
 * @return void
 */
function writehere($s, $mustexists = true) {
	if(file_exists($s) && !is_writable($s)) {
		$bool = chmod($s, 0755); 
		if(!$bool) {
			trigger_error("Can't write on ".$s." make this file/folder writable.");
		}
	} else {
		if($mustexists && !file_exists($s)) {
			trigger_error("Can't find file/folder ".$s);
		}
	}
}

/**
 * Number format using constant in frw_vars
 * 
 * @param number $n
 * @param int $d               decimals
 * @return string
 */
function numberf($n,$d=2) {
	$thousands = ""; $decimals=",";
	if(NUMBERFORMAT == "1000.00") { $thousands = ""; $decimals="."; }
	if(NUMBERFORMAT == "1000,00") { $thousands = ""; $decimals=","; }
	return number_format($n, $d, $decimals, $thousands);
}

/**
 * Date format using constant in frw_vars
 * 
 * @param string $d	           styring containing date from db
 * @return string
 */
function datef($d, $hour=false) {
	return date(phpFormat(DATEFORMAT).( $hour ? " H:i" :""), strtotime($d));
}

/** 
 * map framework date format stored in DATEFORMAT constant to php date format
 * 
 * @param string $fwk_format
 * 
 * @return string
 */
function phpFormat($fwk_format) {
	if(DATEFORMAT=="dd/mm/yyyy") return "d/m/Y";
	if(DATEFORMAT=="mm/dd/yyyy") return "m/d/Y";
	if(DATEFORMAT=="yyyy/mm/dd") return "Y/m/d";
	return $fwk_format;
}

/**
 * get IP of the user
 * 
 * @return string
 */
function getIP() {
	$ip="";
	if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
	else if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");	
	else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
	else $ip = "";
	if(stristr($ip,",")) {$ar = explode(",",$ip); $ip = $ar[0];}
	return $ip;
}

/**
 * get the language labels
 * language from pons.settings.php if not defined in session LANGUAGEFILE
 * 
 * @return void
 */
function loadLanguageLabels() {
	global $langArrayLabels, $root,$session;
	if($session->get("language")=="") $LANGUAGEFILE = LANGUAGEFILE;
		else $LANGUAGEFILE = $session->get("language") . ".lang.txt";
		if ($handle = opendir($root."data/lang")) {
			// Loop through all the files in the folder
			while (false !== ($file = readdir($handle))) {
				// Check if the file ends with the specified string
				if (substr($file, -strlen($LANGUAGEFILE)) === $LANGUAGEFILE) {
					// Open the file
					$filePath = $root."data/lang/" . $file;
					if ($fileHandle = fopen($filePath, 'r')) {
						while (($data = fgetcsv($fileHandle, 2000, ",")) !== FALSE) {
							if(!isset($data[1])) $langArrayLabels[$data[0]] = "MISSING LABEL FOR: ".$data[0];
								else $langArrayLabels[$data[0]] = $data[1];
						}
						fclose($fileHandle);
					}
				}
			}
			// Close the folder
			closedir($handle);
		}
 else {
		die("MISSING LANGUAGE FILE: ".LANGUAGEFILE);
	}
}

/**
 * get default language
 * 
 * from languagefilename string (in pons.settings.php) to language code
 */
function getDefaultLanguage() {
    return explode(".",LANGUAGEFILE)[0];
}

/**
 * apply translation labels to the html provided
 * 
 * @param string $html
 * @return string HTML
 */
function translateHtml($html) {
	global $langArrayLabels;
	if(empty($langArrayLabels)) loadLanguageLabels();
	foreach($langArrayLabels as $label => $v) {
		$html = str_replace("{" . $label . "}", $v, $html) ;
	}
	return $html;
}

/**
 * load a template and parse it, shortcode
 * 
 * @param string $sFilename
 * @param string $sCharset  // not used
 * @return string 
 */
function loadTemplate($sFilename, $sCharset = 'UTF-8') {
	return loadTemplateAndParse($sFilename);
}

/**
 * load a template and parse it
 * 
 * @param string $sFilename
 * @param array $ar  array of replace values
 * 
 * @return string
 */
function loadTemplateAndParse($filename,$ar = array()) { // pass a url to execute PHP, allows both http and https
	global $defaultReplace,$root,$public;
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on") $filename = str_replace("http://","https://",$filename);
	if($public) {
		// public urls do not call the menu
		$defaultReplace["##JQUERYINCLUDE##"] = "<script>var NOTMENU = true; </script>" . $defaultReplace["##JQUERYINCLUDE##"];
	}
	if(empty($ar)) $ar = $defaultReplace;
	$arrContextOptions=array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);  
	if(stristr($filename,"https://")) {
		$contents = file_get_contents($filename, false, stream_context_create($arrContextOptions));
	} else {
		$contents = file_get_contents($filename);
	}

	foreach($ar as $key=>$val) $contents = str_replace($key,$val,$contents);

	// autoload component css and js if present
	if(file_exists($filename.".css") && !stristr($contents,$filename.".css")) {
		$contents = str_replace("</head>","<link rel=\"stylesheet\" href=\"".$filename.".css\" type=\"text/css\" />"."</head>",$contents);
	}
	if(file_exists($filename.".js") && !stristr($contents,$filename.".js")) {
		$contents = str_replace("</head>","<script src=\"".$filename.".js\"></script>"."</head>",$contents);
	}	
	

	return $contents;
}

/**
 * The function `rrmdir` recursively deletes a directory and all its contents in PHP.
 * 
 * @param $dir The parameter "dir" is the directory path that you want to remove, including the
 * directory name.
 */
function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (is_dir($dir."/".$object))
           rrmdir($dir."/".$object);
         else
           unlink($dir."/".$object); 
       } 
     }
     rmdir($dir); 
   } 
 }

/**
 * The function checks if a directory is empty by iterating through its files and subdirectories.
 * 
 * @param $which The parameter "which" is the directory path that you want to check if it is empty or
 * not.
 * 
 * @return boolean
 */
function is_emptydir($which){
	$dh=dir($which);
	$emptydir=true;
	while ($file=$dh->read()) {
		if(substr($file,0,1)==".") continue;
		if(!is_dir($which."/".$file)) {
			$emptydir=false;
			break;
		}
	}
	$dh->close();
	return $emptydir;
}

/**
 * The function is_mobile() checks if the user agent is from a mobile device.
 * used in ser.php to check targeting by device type
 * 
 * @return boolean
 */
function is_mobile() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $is_mobile = false;
    } elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // many mobile devices (all iPhone, iPad, etc.)
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk' ) !== false
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
        || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) {
            $is_mobile = true;
    } else {
        $is_mobile = false;
    }
    return $is_mobile;
}

/**
 * The function checks if the user's operating system matches any of the allowed operating systems.
 * 
 * @param $allowedOSAr An array of operating systems that are allowed.
 * 
 * @return boolean
 */
function is_OS($allowedOSAr) {
	foreach($allowedOSAr as $os) {
		if (preg_match('/' . $os . '/', $_SERVER['HTTP_USER_AGENT']) ){
			return true;
		}
	}
	return false;
}


/**
 * The function is_email checks if an email address is valid and not from a list of common spammer
 * domains.
 * 
 * @param $Address The parameter "Address" is the email address that needs to be validated.
 * 
 * @return boolean
 */
function is_email($Address) { /* verify email address syntax and common spammer domains */
	if(stristr($Address,"@yopmail.com")) return false;
	if(stristr($Address,"@rmqkr.net")) return false;
	if(stristr($Address,"@emailtemporanea.net")) return false;
	if(stristr($Address,"@sharklasers.com")) return false;
	if(stristr($Address,"@guerrillamail.com")) return false;
	if(stristr($Address,"@guerrillamailblock.com")) return false;
	if(stristr($Address,"@guerrillamail.net")) return false;
	if(stristr($Address,"@guerrillamail.biz")) return false;
	if(stristr($Address,"@guerrillamail.org")) return false;
	if(stristr($Address,"@guerrillamail.de")) return false;
	if(stristr($Address,"@sina.com")) return false;
	if(stristr($Address,"@fakeinbox.com")) return false;
	if(stristr($Address,"@tempinbox.com")) return false;
	if(stristr($Address,"@guerrillamail.de")) return false;
	if(stristr($Address,"@guerrillamail.de")) return false;
	if(stristr($Address,"@opayq.com")) return false;
	if(stristr($Address,"@mailinator.com")) return false;
	if(stristr($Address,"@notmailinator.com")) return false;
	if(stristr($Address,"@getairmail.com")) return false;
	if(stristr($Address,"@meltmail.com")) return false;
	if(stristr($Address,"@gmail4u.eu")) return false;
	if(stristr($Address,"@blulapka.pl")) return false;
	if(stristr($Address,"@free-mail4u.eu")) return false;
	if(stristr($Address,"@bestmail365.eu")) return false;
	if(stristr($Address,"@ue90x.com")) return false;
	if(stristr($Address,"@xmaill.com")) return false;
	if(stristr($Address,"@jedna.co.pl")) return false;
	if(preg_match("/@mail([0-9]*)\.top$/",$Address)) return false; 
	if(preg_match("/e90\.biz$/",$Address)) return false; 
	return filter_var($Address, FILTER_VALIDATE_EMAIL);
}

/**
 * The function "setVariabile" retrieves the value of a variable from either the GET, POST, SESSION, or
 * a default value.
 * 
 * @param $nome The "nome" parameter is the name of the variable that you want to set or retrieve its
 * value.
 * @param $valore The "valore" parameter is an optional parameter that specifies the default value for
 * the variable if it is not found in the GET, POST, or SESSION arrays.
 * @param $sessionbase The sessionbase parameter is used to specify a prefix for the session variable
 * name. This is useful when you want to group related session variables together. For example, if
 * sessionbase is set to "user_", then the session variable name will be "user_".
 * 
 * @return string
 */
function setVariabile($nome,$valore="",$sessionbase="") {
	global $session;
	//
	// search input variable, go through  GET > POST > SESSION > default value
	//
	if (isset($_GET[$nome])) {
		$start = $_GET[$nome];
	} else if (isset($_POST[$nome])) {
		$start = $_POST[$nome];
	} else if ($session->get($sessionbase.$nome)!="") {
		$start=$session->get($sessionbase.$nome);
	} else {
		$start=$valore;
	}
	return $start;
}

/**
 * The function "postget" is used to retrieve the value of a variable from either the POST or GET
 * method in PHP, with an optional default value.
 * 
 * @param $nome The parameter "nome" is used to specify the name of the variable that you want to
 * retrieve from either the POST or GET request.
 * @param $valore The parameter "valore" is a default value that will be used if the variable with the
 * given name is not found in either the  or  arrays.
 * 
 * @return string
 */
function postget($nome,$valore="") {
	if (isset($_POST[$nome])) $start= $_POST[$nome];
		elseif (isset($_GET[$nome])) $start= $_GET[$nome];
		else $start=$valore;
	return $start;
}

/** 
 * The function "getpost" is used to retrieve the value of a variable from either the GET or POST
 * 
 * @param $nome 
 * @param $valore
 * 
 * @return string
 * 
 */
function getpost($nome,$valore="") {
	if (isset($_GET[$nome])) $start= $_GET[$nome];
		elseif (isset($_POST[$nome])) $start= $_POST[$nome];
		else $start=$valore;
	return $start;
}

function get($nome,$valore="") {
	if (isset($_GET[$nome])) $start= $_GET[$nome];
		else $start=$valore;
	return $start;
}

/**
 * The function "addslashesonlyquote" is used to escape quotes in a string
 * 
 * @param string $s
 * @return string
 */
function addslashesonlyquote($s) {
	return str_replace('"','\"',$s);
}

/**
 * retrieve the layout msg and fill it with the message and return it
 * 
 * @param string $msg
 * @param string $op
 * @param string $class
 * 
 * @return string
 */
function returnmsg($msg,$op="",$class="err") {
	global $root,$defaultReplace,$session;
	$file = $root."data/".DOMINIODEFAULT."/layout-msg.php";
	$html = loadTemplateAndParse( $file, $defaultReplace );
	if ($op=="back" || $op=="session") {
		$msg .= $session->get("backbutton");
	}elseif ($op=="reload") {
		$msg.=" <span class='loading'><span class='icon-spin5 animate-spin'></span> {Loading...}</span>
			<script language='javascript'>setTimeout(\"document.location.href=document.location.href;\",1000)</script>";
	}elseif ($op=="jsback") {
		$msg.=" <br><br><a href='javascript:history.go(-1)' class='btn'>{Back}</a>";
	}elseif (preg_match("/^(load) /i",$op)) {
		$pageToLoadAr=explode(" ",$op);
		$msg.=" <span class='loading'><span class='icon-spin5 animate-spin'></span> {Loading...}</span>
			<script language='javascript'>setTimeout(\"document.location.href='{$pageToLoadAr[1]}';\",1000)</script>";
	}elseif (preg_match("/^(link) /i",$op)) {
		$pageToLoadAr=explode(" ",$op);
		$msg.=" <br><br><a href='{$pageToLoadAr[1]}' class='btn'><span class='icon-angle-right'></span> {Go on...}</a>";
	}
	$html = str_replace("##msg##",$msg,$html);
	$html = str_replace("##class##",$class,$html);
	return $html;
}

/**
 * return the ok message HTML (Ex: Done.)
 * 
 * @param string $msg
 * @param string $op
 * 
 * @return string
 */
function returnmsgok($msg,$op="") {
	return returnmsg($msg,$op,"ok");
}

/**
 * add $g days to today date time
 * 
 * @param int $g
 * 
 * @return string
 */
function todayadd($g) {
	return dayadd($g,date("Y-m-d H:i:s"));
}

/**
 * add $g days to given date time
 * 
 * @param int $g
 * @param string $dayYmd
 * 
 * @return string
 */
function dayadd($g,$dayYmd) {
	$d = strtotime($dayYmd);
	$cc = 24*60*60*$g + 60*60 + $d;
	return date("Y-m-d",$cc);
}

/**
 * date diff function for old php that doesn't have date_diff()
 * 
 * @param string $d1
 * @param string $d2
 * 
 * @return number
 */
function date_diff2($d1, $d2) { 
	$q = strtotime($d2) - strtotime($d1);
	$d = $q / (60*60*24);
	return $d;
}

/**
 * return date $d in YYYY-mm-dd format
 * 
 * @param string $d
 * 
 * @return string
 */
function TOymd($d='') {
	if ( !$d ) $d = date( "Y-m-d", time());
		else {
			/* From d.m.Y to Ymd */
			$d = substr($d,6,4)."-".substr($d,3,2)."-".substr($d,0,2);
		}
	return $d;
 }

/**
 * return date $d in dd-mm-YYYY format
 * 
 * @param string $d
 * @param string $sep
 * 
 * @return string
 */
function TOdmy($d='',$sep="-") {
	if ( !$d ) $d = date( "Y{$sep}m{$sep}d", time());
	return substr($d,8,2).$sep.substr($d,5,2).$sep.substr($d,0,4);
}

/**
 * convert string date time from YYYY-mm-dd hh:ii:ss to dd.mm.YYYY hh:ii:ss
 * 
 * @param string $d
 * @param string $sep
 * 
 * @return string
 * */
function TOdmyhis($d='',$sep="-") {
	if ( !$d ) $d = date( "Y{$sep}m{$sep}d H:i:s", time());
	return substr($d,8,2).$sep.substr($d,5,2).$sep.substr($d,0,4)." ".substr($d,11,5);
}

/**
 * add $v days to given date $d and apply the format $f
 * 
 * @param string $d
 * @param int $v
 * @param string $f
 * 
 * @return string
 */
function DateAdd($v,$d=null , $f="d/m/Y"){
  $d=($d?$d:date("Y-m-d"));
  return date($f,strtotime($v." days",strtotime($d)));
}
//Then use it:
//echo DateAdd(2);  // 2 days after
//echo DateAdd(-2,0,"Y-m-d");  // 2 days before with gigen format
//echo DateAdd(3,"01/01/2000");  // 3 days after given date
/**
 * execute a query and return the result in a option list HTML string
 * 
 * @param string $nomeCampoChiave
 * @param string $valoreSelezionato
 * @param string $nomeCampoPerlista
 * @param string $sql
 * @param string $ancheQuelloVuoto
 * 
 * @return string
 * 
 */
function getListaForm($nomeCampoChiave, $valoreSelezionato="", $nomeCampoPerLista="label", $sql="", $ancheQuelloVuoto="tutte") {
	/*
		get html list of options from database
		<option value="...">....</option>
		using data from sql query
	*/
	if ($sql=="") return "";
	global $conn;
	$rs = $conn->query ($sql);
	$html="";
	if ($ancheQuelloVuoto!="") $html.="<option value=\"\">$ancheQuelloVuoto</option>\r\n";
	while ($r=$rs->fetch_array()) {
		$html.="<option value=\"{$r[$nomeCampoChiave]}\"";
		if ($r[$nomeCampoChiave]==$valoreSelezionato) $html.=" selected";
		$html.=">$r[$nomeCampoPerLista]";
		$html.="</option>\r\n";
	}
	return $html;
}

/*
		@todo 2024-03-16 NOT USED (comment to remove in future)

		get html list of checkboxes from database
		<option value="...">....</option>
		using data from sql query

function getListaCheckboxForm($nomeCampoChiave, $strValoriSelezionati="", $nomeCampoPerLista="label", $sql="") {
	if ($sql=="") return "";
	global $conn;
	$rs = $conn->query ($sql);
	$html="";
	while ($r=$rs->fetch_array()) {
		$html.="<input type=\"checkbox\" name=\"$nomeCampoChiave"."[]\" value=\"{$r[$nomeCampoChiave]}\"";
		if (stristr($strValoriSelezionati,$r[$nomeCampoChiave])) $html.=" checked";
		$html.=">$r[$nomeCampoPerLista]\r\n";
	}
	return $html;
}
*/

/**
 * manually handle special chars in html strings
 * (can't remember why it's not the standard way)
 * 
 * @param string $s
 * 
 * @return string
 */
function myHtmlspecialchars($s) {
	// special chars handler (italian chars)
	$s = str_replace(chr(242),"&ograve;",$s);
	$s = str_replace(chr(243),"&oacute;",$s);
	$s = str_replace(chr(232),"&egrave;",$s);
	$s = str_replace(chr(233),"&eacute;",$s);
	$s = str_replace(chr(224),"&agrave;",$s);
	$s = str_replace(chr(225),"&aacute;",$s);
	$s = str_replace(chr(236),"&igrave;",$s);
	$s = str_replace(chr(237),"&iacute;",$s);
	$s = str_replace(chr(249),"&ugrave;",$s);
	$s = str_replace(chr(250),"&uacute;",$s);
	$s = str_replace(chr(210),"&Ograve;",$s);
	$s = str_replace(chr(211),"&Oacute;",$s);
	$s = str_replace(chr(200),"&Egrave;",$s);
	$s = str_replace(chr(201),"&Eacute;",$s);
	$s = str_replace(chr(192),"&Agrave;",$s);
	$s = str_replace(chr(193),"&Aacute;",$s);
	$s = str_replace(chr(204),"&Igrave;",$s);
	$s = str_replace(chr(205),"&Iacute;",$s);
	$s = str_replace(chr(217),"&Ugrave;",$s);
	$s = str_replace(chr(218),"&Uacute;",$s);
	//german
	$s = str_replace(chr(223),"&szlig;",$s);
	$s = str_replace(chr(214),"&Ouml;",$s);
	$s = str_replace(chr(246),"&ouml;",$s);
	$s = str_replace(chr(220),"&Uuml;",$s);
	$s = str_replace(chr(252),"&uuml;",$s);
	$s = str_replace(chr(228),"&auml;",$s);
	$s = str_replace(chr(196),"&Auml;",$s);
	$s = str_replace(chr(203),"&Euml;",$s);
	$s = str_replace(chr(235),"&euml;",$s);
	$s = str_replace(chr(207),"&Iuml;",$s);
	$s = str_replace(chr(239),"&iuml;",$s);
	//...add more?...
	$s = str_replace(chr(244),"&ocirc;",$s);
	$s = str_replace(chr(212),"&Ocirc;",$s);
	//generic
	$s = str_replace(chr(174),"&reg;",$s);
	$s = str_replace(chr(169),"&copy;",$s);
	$s = str_replace(chr(145),"&#39;",$s);
	$s = str_replace(chr(146),"&#39;",$s);
	$s = str_replace(chr(147),"&quot;",$s);
	$s = str_replace(chr(148),"&quot;",$s);
	$s = str_replace(chr(234),"&#234",$s);
	$s = str_replace(chr(171),"&#171",$s);
	$s = str_replace(chr(187),"&#187",$s);
	$s = str_replace(chr(945),"&#945",$s);
	return $s;
}

/**
 * send mail with utf8 chars, use php mail or SMTP configured in settings
 * 
 * @param string $to
 * @param string $subject
 * @param string $message
 * 
 * @return bool
 */
function mail_utf8($to, $subject = '(No subject)', $message = '') {
	global $root;
	if(SMTP_SERVER=="") {
		// with empty SMTP_SERVER send emails with mail() command
		$header =   "From: " . SERVER_EMAIL_ADDRESS ."\n".
                    'MIME-Version: 1.0' . "\n" . 
                    'Content-type: text/html; charset=UTF-8'. "\r\n" 
                   ;
		return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header, "-f ". SERVER_EMAIL_ADDRESS);
	} else {
		//Instantiation and passing `true` enables exceptions
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		$mail->CharSet = 'UTF-8';
		try {
			//Server settings
			$mail->isSMTP();
			$mail->Host       = SMTP_SERVER;
			$mail->SMTPAuth   = SMTP_AUTH == "1" ? true : false;
			$mail->Username   = SMTP_USERNAME;
			$mail->Password   = SMTP_PASSWORD;
			$mail->SMTPSecure = SMTP_ENCRYPTION == "SSL" ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port       = SMTP_PORT;
			//Recipients
			$mail->setFrom(SERVER_EMAIL_ADDRESS, SERVER_NAME);
			$mail->addAddress( $to );     //Add a recipient
			//Content
			$mail->isHTML(true);                                  //Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $message;
			$mail->AltBody = strip_tags($message);
			$mail->send();
			return true;
		} catch (Exception $e) {
			global $logger;
			// echo $logger->blocking_error( $mail->ErrorInfo, "javascript:history.back();" );
			trigger_error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}", E_USER_ERROR);
			// echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
			// die;
			return false;
		}
	}
} 

/*
	2024-03-16
	@todo NOT USED, commented out to remove in future

	function linkEmail($mail,$label="<img src='##ROOT##images/email.gif' border='0' align='absbottom' alt='invia mail'>") {
		$mail = trim($mail);
		if ($mail=="") return "";
		global $root;
		$link="<a href=\"mailto:$mail\" title=\"$mail\">$mail ".str_replace("##ROOT##",$root,$label)."</a>";
		return $link;
	}
*/


/**
 * convert from db date time yyyy-mm-dd hh:ii:ss to timestamp
 * (I think it's the same of strtotime($MySqlDate))
 * 
 * @param string $MySqlDate
 * 
 * @return int
 */
function GetTimeStamp($MySqlDate) {
	if($MySqlDate=="") $MySqlDate=ZERODATE;
	$MySqlDate = $MySqlDate." 00:00:00";
	$ar = preg_split("/[ \:\-]/i",$MySqlDate); // split the array
	return mktime((integer)$ar[3],$ar[4],$ar[5],(integer)$ar[1],(integer)$ar[2],(integer)$ar[0]);
}

/**
 * check if the component of the framework can be used by the current user.
 * flags are stored in session.
 * 
 * @param string $componente
 * @param string $settasempre
 * 
 * @return bool
 */
function checkAbilitazione($componente, $settasempre="SETTA_SEMPRE") {
	global $session,$conn;
	if ($session->get($componente) == "") {
		/*
			if no "componente" is specified, get the first time the class is loaded
			and get data from db and put in session permissions for
			next calls
		*/
		$sql = "SELECT ".DB_PREFIX."frw_componenti.nome as componente, ".DB_PREFIX."frw_funzionalita.label, ".DB_PREFIX."frw_funzionalita.nome
				FROM ".DB_PREFIX."frw_funzionalita
				JOIN ".DB_PREFIX."frw_componenti ON ".DB_PREFIX."frw_funzionalita.idcomponente = ".DB_PREFIX."frw_componenti.id
				JOIN ".DB_PREFIX."frw_ute_fun ON idfunzionalita = ".DB_PREFIX."frw_funzionalita.id
				WHERE (".DB_PREFIX."frw_componenti.nome =  '{$componente}') AND ".DB_PREFIX."frw_ute_fun.idutente =  '".$session->get("idutente")."';";
		$rs=$conn->query($sql) or trigger_error($rs->error);
		if($settasempre=="SETTA_SEMPRE") {
			/*
				"SETTA_SEMPRE" means that in any case the value is set to false
				and if the component slug is found, the value is setted with that value
				from db. This can change the way how is controlled the permission 
				in session. If I control only the existance it's better NOT to pass "SETTA_SEMPRE"
				but "SETTA_SOLO_SE_ESISTE"
			*/
			$session->register($componente,"false");
		}
		while($row = $rs->fetch_array()){
			if ($row['componente']==$componente) {
				$session->register($row['nome'],$row['label']);
			} else {
				if($settasempre=="SETTA_SEMPRE") {
					$session->register($row['nome'],"");
				}
			}
		}
		$rs->free();
	}
}

/** 
 * Alphabetically sort Multidimensional arrays by index values of an n dimension array.
 * I have only tested this for sorting an array of up to 6 dimensions by a value within
 * the second dimension. This code is very rough and works for my purposes, but has not
 * been tested beyond my needs.
 * Call function by assigning it to a new / existing array:
 * $row_array = multidimsort($row_array);
 * 
 * @param array $array the array to be sorted
 * @param int $column index (column) on which to sort can be a string if using an associative array
 * @param int $order SORT_ASC (default) for ascending or SORT_DESC for descending
 * @param int $first start index (row) for partial array sort
 * @param int $last stop  index (row) for partial array sort
 * 
 * @return void
 */
function array_qsort2 (&$array, $column=0, $order=SORT_ASC, $first=0, $last= -2) {
	if($last == -2) $last = count($array) - 1;
	if($last > $first) {
		$alpha = $first;
		$omega = $last;
		$guess = $array[$alpha][$column];
		while($omega >= $alpha) {
			if($order == SORT_ASC) {
			while($array[$alpha][$column] < $guess) $alpha++;
			while($array[$omega][$column] > $guess) $omega--;
		} else {
			while($array[$alpha][$column] > $guess) $alpha++;
			while($array[$omega][$column] < $guess) $omega--;
		}
		if($alpha > $omega) break;
		$temporary = $array[$alpha];
		$array[$alpha++] = $array[$omega];
		$array[$omega--] = $temporary;
	}
	array_qsort2 ($array, $column, $order, $first, $omega);
	array_qsort2 ($array, $column, $order, $alpha, $last);
}

/**
 * Another sort function, which has been modified to just call usort
 * 
 * @param array $arr
 * 
 * @return array
 */}
function array_key_multi_sort($arr) {
	//usort($arr, create_function('$a, $b', "return $f(\$a['$l'], \$b['$l']);"));
	usort($arr, "unatcmp");
	return($arr);
}

/**
 * used by array_key_multi_sort
 * 
 * @param array $a
 * @param array $b
 * @param string $l
 * 
 * @return int
 */
function unatcmp($a,$b,$l = null) {
	if(isset($a['posizione'])) $label = "posizione"; else $label = 0;
	return strnatcasecmp($a[ $label ], $b[ $label ]);
}

/**
 * smart sub string that doesn't truncate words if $modo = "donttrun"
 * 
 * @param string $text
 * @param int $maxTextLenght
 * @param string $modo
 * 
 * @return string
 */
function smartsub($text,$maxTextLenght,$modo) {
   $aspace=" ";
   if(strlen($text) > $maxTextLenght ) {
     $text = substr(trim($text),0,$maxTextLenght);
     if ($modo=="donttrun") $text = substr($text,0,strlen($text)-strpos(strrev($text),$aspace));
     $text = $text.'...';
   }
   return $text;
}
/*
function mysql_scalar($sql) {
	return execute_scalar($sql);
}
*/
function NomeImmagine($s) { /* found gif/jpg/jpeg/webp/png/zip... used in banners */
	$ext="";
	if (file_exists("$s.jpg")) { $ext=".jpg"; }
		elseif (file_exists("$s.jpeg")) { $ext=".jpeg"; }
		elseif (file_exists("$s.webp")) { $ext=".webp"; }
		elseif (file_exists("$s.gif")) { $ext=".gif"; }
		//elseif (file_exists("$s.swf")) { $ext=".swf"; }
		elseif (file_exists("$s.png")) { $ext=".png"; }
		elseif (file_exists("$s.zip")) { $ext=".zip"; }
	if (!$ext) { return ""; } else { return $s.$ext; }
}

/**
 * make a query and return a scalar
 * 
 * @param string $sql your query
 * @param string $def default value if not found
 * 
 * @return string
 */
function execute_scalar($sql,$def="") {
	global $conn; 
	if($conn === false) return "";
	if ( $rs = $conn->query($sql) ) { $r = $rs->fetch_array(); $rs->free(); return isset($r[0]) ?$r[0] : $def; } return $def;
}

/**
 * make a query and return a row
 * 
 * @param string $sql your query
 * @param string $def default value if not found
 * 
 * @return array
 */
function execute_row($sql, $def = array() ) {
	global $conn; 
	if ( $rs = $conn->query($sql) ) { 
		$r = $rs->fetch_array(); 
		$rs->free(); 
		return is_array($r) ? $r : $def;
	} 
	return $def;
}

/**
 * like join but with a result from an sql query
 * 
 * @param string $sql
 * @param string $sep
 * 
 * @return string
 */
function concatenaId($sql,$sep = ",") {
	global $conn; $o = "";if ($rs = $conn->query($sql)) while($r=$rs->fetch_row())$o.=($o?$sep:"").$r[0]; else die($conn->error);return $o;
}

/**
 * Looks into $table and $field and gets ENUM options and
 * returns an array to be used in optionlist class
 * 
 * @param string $table
 * @param string $field
 * 
 * @return array
 **/
function set_and_enum_values( $table , $field ){
	global $conn;
	$query = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DEFDBNAME."' AND TABLE_NAME = '".$table."' and COLUMN_NAME='".$field."'";
	$result = $conn->query( $query ) or trigger_error( 'error getting enum field ' . $conn->error );
	$row = $result->fetch_array();
	$arOut = array();
	if(isset($row[0])) {
		$s = preg_replace("/^(enum|set)\('/","",$row[0]);
		$s = preg_replace("/\'\)$/","",$s);
		$s = str_replace("','","\n",$s);
		$ar = explode("\n",$s);
		for ($i=0;$i<count($ar);$i++) $arOut[str_replace("''","'",$ar[$i])]=str_replace("''","'",$ar[$i]);
	}
	return $arOut ;
}

/**
 * Looks into a $table and returns the array of field names 
 * 
 * @param string $table
 * 
 * @return array
 */
function getEmptyNomiCelleAr( $table ){
	global $conn;
	$query = "SELECT COLUMN_NAME
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA =  '".DEFDBNAME."'
		AND TABLE_NAME =   '".$table."'";
	$result = $conn->query( $query ) or trigger_error( 'error: ' . $conn->error );
	$outAr = array();
	while($row = $result->fetch_array()) {
		$outAr += array( $row['COLUMN_NAME'] => "");
	}
	return $outAr ;
}

/**
 * Creates the gallery of objects (or gets the array) uploaded based on id and directory.
 * 
 * @param string $dir where are saved files
 * @param string $prenome xxxx_ where "xxxx" is the id of the connected record on db. After the "_" there are numbers based on order
 * @param string $div string placed before html numberd ids
 * @param string $return "html" | "array"
 * @param boolean $SPOSTA true | false (if true in html put also the arrows to move objects, need proper js also for delete)
 * @param boolean $TRIGGER_ERROR true | false (if true trigger error when missing folder or permissions)
 * 
 * @return string | array
 */
function loadgallery($dir,$prenome,$div="div",$return="html",$SPOSTA=false,$TRIGGER_ERROR=true) {
	if($TRIGGER_ERROR && !is_dir($dir)) trigger_error("File not found: ".$dir);
	if($TRIGGER_ERROR && !is_writeable($dir)) trigger_error("Can't write on folder: ".$dir);
	$c = 0;
	$out = "";
	$a=array();
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if(!preg_match("/\.info$/",$file)) {
					$tipo = "";
					$tipo = ( str_replace(".","", strrchr($file, '.') ) );
					if($tipo!="") {
						if(strpos(" ".$file,$prenome)==1) {
							$a[$c][0]=$dir.$file;
							$a[$c][1]=$file;
							$p = (integer)preg_replace("/[^0-9]/","",stristr($file,"_"));
							$a[$c][2]=$p;
							$a[$c][3]=$tipo;
							$c++;
						}
					}
				}
			}
			closedir($dh);
			$a = array_key_multi_sort($a);	//da testare
			//$a = array_key_multi_sort($a,2);	//da testare
			$prec = "";
			for($i=0;$i<count($a);$i++) {
				$fsize=number_format(filesize($a[$i][0])/1024,2);
				if (in_array($a[$i][3],array('jpg','gif','png','jpeg'))) {
					// thumb immagine
					$size=GetImageSize($a[$i][0]);
					if($size[0]>$size[1]) $outsize = "width:10rem;"; else $outsize = "height:10rem;";
					$filename = $a[$i][0];
					$descr = "$fsize KB, ".strtoupper($a[$i][3])." {$size[0]}x{$size[1]}px";
					//$ar = stat($a[$i][0]);
					//$ar = date("Y-m-d H:i:s",$ar['ctime']);
					//if(date("Y-m-d H:i:s")<=$ar) 
					$filename=$filename.'?'.rand(0,10000); // fuck cache
					$class = "pic";
				} else {
					// file generico per altre estensioni
					$outsize = "";
					$filename = "";
					$descr = "$fsize KB, ".strtoupper($a[$i][3]);
					//$descr.= print_r($a[$i][3],true);
					$class = "zip";
				}
				if(file_exists($a[$i][0].".info"))
					$nome = loadTemplate($a[$i][0].".info")." ";	// se c'e' il .info contiene il nome originale del file uploadato.
				else $nome = $a[$i][1];
				//preg_match("/([0-9]+)_([0-9]+)\.([a-z0-9]+)/i",$a[$i][0],$pos);
				$out .= "<div id='{$div}{$i}' class='divthumbs".($i==0?" first":"")."'>";
				if($i>0 && $SPOSTA) $out.="<a class='msx' href=\"javascript:movefromto('".$a[$i][0]."','".$prec."','{$div}','{$i}')\">&nbsp;</a>";
				$out.= "<div class='divinternothumb'>
						<a title='Click to open {$nome}({$descr})' target='_blank' href=\"".$a[$i][0]."\" class=\"".$class."\">".
						($filename ? "<img src='".$filename."' style='$outsize'/><span class='icon-search'></span>" :
							"<span class='icon-folder' style='$outsize'></span><span class='icon-download-alt'></span>") . "</a>";
				if($SPOSTA) 
					$out.="<a title='Click to delete file.' class='delete' href=\"javascript:elimina('{$a[$i][0]}','{$div}','{$i}')\">";
				else
					$out.="<a title='Click to delete file.' class='delete' href=\"javascript:elimina('{$a[$i][0]}','{$div}{$i}')\">";
				$out.="<span class='icon-trash'></span></a></div>";
				$prec = $a[$i][0]; 
				if($i>0) $out = str_replace("#SUC#",$prec,$out);
				if($i<count($a)-1 && $SPOSTA) $out.="<a class='mdx' href=\"javascript:movefromto('".$a[$i][0]."','#SUC#','{$div}','{$i}')\">&nbsp;</a>";
				$out.="</div>";
			}
		}
	}
	if ($c==0) $out = "";
	return ($return=='html'?$out:$a);
}

/**
 * Unlinks file searching the different extensions and also the .info file created during upload.
 * Pass $f without extension.
 * 
 * @param string $f filename
 * 
 * @return void
 */
function deldbimg($f) {
	$n = NomeImmagine($f);
	if (file_exists($n)) unlink($n);
	if (file_exists($n.'.info')) unlink($n.'.info');
}

/**
 * Handles move commands before and next of items in gallery object. Handles also .info file
 * 
 * @param string $da
 * @param string $a
 * @param string $div0
 * 
 * @return string
 **/ 
function spostafilegallery($da,$a,$div0) {
	preg_match("/([0-9a-z-]+)_([0-9]+)\.([a-z0-9]+)/i",$da,$pos);
	rename($da,str_replace(basename($da),"temp",$da));
	rename($a,$da);
	rename(str_replace(basename($da),"temp",$da),$a);
	if(file_exists($da.".info")) {
		// could not be in older installations
		rename($da.".info",str_replace(basename($da.".info"),"temp.info",$da.".info"));
		rename($a.".info",$da.".info");
		rename(str_replace(basename($da.".info"),"temp.info",$da.".info"),$a.".info");
	}
	$uploadDir = str_replace(basename($da),"",$da);
	$out = loadgallery($uploadDir,$pos[1]."_",$div0,"html",true);
	return "ok|".$out;
}

/**
 * Handles gallery delete items, also .info
 * 
 * @param string $f
 * @param string $div0
 * 
 * @return string
 * 
 **/
function deletefilegallery($f,$div0) {
	if(file_exists($f) && (stristr($f, "/data/") || stristr($f, "userfiles"))) {
		// if ZIP file, removes also folder with unzipped files
		if(preg_match("/\.zip$/i",$f)) {
			$uploadDir = str_replace(basename($f),"",$f);
			$filename = str_replace($uploadDir,"",$f);
			$id = explode("_",$filename);
			$zipFolder = $uploadDir.$id[0];
			rrmdir($uploadDir.$id[0]);
		}
		
		if(file_exists($f.".info")) unlink($f.".info");
		$uploadDir = str_replace(basename($f),"",$f);
		unlink($f);
		preg_match("/([0-9a-z-]+)_([0-9]+)\.([a-z0-9]+)/i",$f,$pos);
		$out = loadgallery($uploadDir,$pos[1]."_",$div0,"html",true);
		return "ok|".$out;
	}
}

/**
 * Unlink a file and check if unlink worked. If not, trigger an error
 * 
 * @param string $s filename
 * 
 * @return void
 */
function unlinkbetter($s) {
	unlink($s);
	if(file_exists($s)) {trigger_error( "Can't delete $s. Fix permission or delete manually."); die; };
}


/**
 * Rename a file and check if rename worked. If not, trigger an error
 * 
 * @param string $from
 * @param string $to
 * 
 * @return void
 */
function renamebetter($from, $to) {
	rename($from,$to);
	if(file_exists($from)) {trigger_error( "Can't rename folder $from in folter $to. Fix permission or rename manually."); die; };
}

/**
 * Uploads a file and performs some checks on size, extension, mime type and dimensions
 * 
 * @param array $files ($_FILES)
 * @param string $campo field name
 * @param string $uploadfile used to build the filename
 * @param array $allowedArrayExt allowed extensions
 * @param int $x Width
 * @param int $y Height
 * 
 * @return string
 */
function uploadFile($files,$campo,$uploadfile,$allowedArrayExt,$x=0,$y=0,$kb=0,$max=1) {
	$msg = ""; //output
	if($files[$campo]['type']!="") {
		$ext = strtolower( str_replace(".","", strrchr($_FILES[$campo]['name'], '.') ) );
		if( !in_array($ext,$allowedArrayExt) ) {
			/*
				file type error
			*/
			$msg = "Only file with these formats: ".implode(", ",$allowedArrayExt)." (your file is {$ext}, mime type: {$files[$campo]['type']}).";
		} else {
			/*
				file type ok, upload
			*/
			if (in_array($ext, array("gif","png","jpg","jpeg","webp")) && $x>0 && $y>0) {
				// check dimensions
				$arDatiImg = GetImageSize($files[$campo]['tmp_name']);
				if($arDatiImg[0]>round($x*1.1) || $arDatiImg[1]>round($y*1.1)) {	//10% tollerance!
					//wrong dimensions
					$msg = "Only images with max this dimensions: {$x}x{$y}.";
				}

			}

			if ($msg=="") {

				if(is_uploaded_file($files[$campo]['tmp_name'])) {
					if(filesize($files[$campo]['tmp_name'])>$kb*1024) {
						//file too big
						$msg = "File too big, max {$kb}kb.";
					} else {
						//ok
						$num = 0;
						$av = false;
						while (!$av){
							for($i=0;$i<count($allowedArrayExt);$i++) {
								if (file_exists($uploadfile.$num.".".$allowedArrayExt[$i])) {
									$av = false;
									$i = count($allowedArrayExt);
									break;
								} else {
									$av = true;
								}
							}
							if (!$av) $num++;
						}

						if ($max > $num) {

							if (move_uploaded_file($files[$campo]['tmp_name'], $uploadfile.$num.".".$ext)) {
								// tutto ok
								chmod($uploadfile.$num.".".$ext, 0755);
								$msg = "";
								$nomefile = $uploadfile.$num.".".$ext.".info";
								$f = fopen($nomefile,'w');
								fwrite($f,$files[$campo]['name']);
								fclose($f);
							} else {
								//attack
								$msg = "File not uploaded (2).";
							}
						
						} else {
							$msg = "Too many files, max {$max}.";

						}
					}
				} else {
					//ko
					$msg = "File not uploaded (1).";
				}
			}
		}
		if($msg!="") {
			$msg = "-1|{$msg}";
		}
	}
	return $msg;
}

/**
 * Check if a table exists
 * 
 * @param string $t table
 * 
 * @return bool
 */
function table_exists($t) {
	$sql = "SELECT COUNT(*)
	FROM information_schema.tables 
	WHERE table_schema = '".DEFDBNAME."' 
	AND table_name = '".$t."'";
	if( execute_scalar($sql) > 0 ) return true; else return false;
}

/**
 * Get default component
 * 
 * @return string
 */
function getDefaultComponentAddress() {
	return execute_scalar("select urlcomponente from ".DB_PREFIX."frw_componenti WHERE nome='".addslashes(PRIMO_COMPONENTE_DA_MOSTRARE)."'","");
}
