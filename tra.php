<?php

if (function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Rome');

$root="";
include($root."pons-settings.php");
include($root."src/_include/comode.php");


$idbanner=isset($_GET['b']) ? (integer)$_GET['b'] : 0;
$checkcode=isset($_GET['c']) ? $_GET['c'] : "";
if (!$idbanner) die("no banner");

if (!Connessione()) die(); else CollateConnessione();

redirectBanner($idbanner,$checkcode);

/**
 * Updates clicks and redirects a banner to its designated URL.
 * 
 * @param int $idbanner The ID of the banner to redirect.
 * @param string $checkcode The MD5 hash of the ID and ENCRYPTIONKEY.
 * @return void
 */
function redirectBanner($idbanner,$checkcode) {
	global $conn;
	if(DEFINED("ENCRYPTIONKEY")) {
		if(md5($idbanner . "-" .ENCRYPTIONKEY) != $checkcode) {
			die();
			// bad chcecksum, stops.
		}
	}

	$sql = "UPDATE ".DB_PREFIX."7banner SET nu_clicks = nu_clicks + 1 where id_banner={$idbanner}";
	$conn->query($sql) or die("errore redirect banner (1)");
	$r = execute_row("SELECT de_url FROM ".DB_PREFIX."7banner WHERE id_banner={$idbanner}");

	if(isset($_SERVER['HTTP_REFERER'])){
        $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    } else $host ='';
	
	// stats
	$conn->query($sql = "UPDATE ".DB_PREFIX."7banner_stats SET nu_click=nu_click+1 WHERE id_day='".date("Y-m-d")."' AND cd_banner='".$idbanner."' and de_referrer='".addslashes($host)."'");
	if( $conn->affected_rows == 0 ) {
		// not possible, probably it's missing the $host,
		// so I add a line with 0 views and nu_click
		$posizione = execute_scalar("select cd_posizione from ".DB_PREFIX."7banner where id_banner={$idbanner}",0);
		$conn->query("INSERT IGNORE INTO ".DB_PREFIX."7banner_stats (id_day,nu_pageviews,nu_click,cd_banner,cd_posizione,de_referrer) VALUES ('".date("Y-m-d")."',0,1,'".$idbanner."','".$posizione."','".addslashes($host)."')");
	}
	$s = str_replace("[timestamp]",date("YmdHis"),$r['de_url']);
	$s = str_replace("[RANDOM]",rand(10000,99999).date("YmdHis"),$s);
	$s = str_replace("[randnum]",rand(10000,99999).date("YmdHis"),$s);
	$s = str_replace("[rand]",rand(10000,99999).date("YmdHis"),$s);
	if(!$s) {
		$s="/";
	}
	header("Location: {$s}");
	die;
}




?>