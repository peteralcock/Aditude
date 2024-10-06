<?php
/*

	handles settings constants on database

*/
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/constants.class.php");

$ambiente->setPosizione("{Settings}");

$obj = new Constants();

$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");


switch ($command) {
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else $html = returnmsgok("{Done.}","reload");
		break;

}


if ($html=="") {
	$elenco = $obj->elenco($combotiporeset,$keyword);
	if($elenco!="0") {
		$html = loadTemplateAndParse("template/elenco.html");
		$html = str_replace("##corpo##", $elenco, $html);

		$email_settings = $obj->elenco($combotiporeset,$keyword,'email_settings');
		$html = str_replace("##corpo_email##", $email_settings, $html);

		$geoip_settings = $obj->elenco($combotiporeset,$keyword,'geoip_settings');
		$html = str_replace("##corpo_geoip##", $geoip_settings, $html);

		$payments_settings = $obj->elenco($combotiporeset,$keyword,'payments_settings');
		$html = str_replace("##corpo_payments##", $payments_settings, $html);

		$html = str_replace("##keyword##", $keyword, $html);

		$fopen = ini_get("allow_url_fopen");
		if($fopen == "1" || strtolower($fopen) == "on") $fopen="on";
			else $fopen = "off";
		$html = str_replace("##ALLOW_URL_FOPEN##", $fopen, $html);

		if(hasModule("BANNER")) {
			$html = str_replace("##BANNERMODULE##", "", $html);		
		}
		if(hasModule("TIMY")) {
			$html = str_replace("##BANNERMODULE##", "display:none", $html);		
		}

		

		$ip = getIP();
		
		$row = getIP2LocationRow($ip);
		$html = str_replace("##ip##", $ip , $html);
		if(isset($row['country_name'])) {
			$loc = $row['country_name']. ($row['region_name']!="" ? ", ".$row['region_name'] :"") .($row['city_name']!="" ? ", ".$row['city_name'] :"") ;
		} else {
			$loc = "{Location not found.}";
		}
		$html = str_replace("##geoip##", $loc, $html);
		$html = str_replace("##ips##", number_format( execute_scalar("select count(1) from ip2location_db3",0) )." + ".number_format( execute_scalar("select count(1) from ip2location_db3_ipv6",0) ) , $html);

		$status = "";

		$token = getVarSetting("CONST_GEOIP_TOKEN");
		if ($token=="") {
			$status .= " {Missing IP2Location token}";
		}

		$date = getVarSetting("GEO_IP_START_DATE");
		if($date!="") {
			$status .= " {Loading geo ip records...} ";
		} else {
			$date = getVarSetting("GEO_IP_UPDATE");
			if($date!="") {
				$status .= "<b>{Updated on} ". $date."</b>. ";
			} else {
				$status .= " {Geo ip data not yet imported} ";
			}
		}
			
		$STEP = getVarSetting("GEO_IP_STEP");
		if ($STEP!="" && $STEP!="fine|ipv6") {
			// recover update interrupted
			$html = str_replace("#STEP#", "{Recover import}", $html);
		} else {
			$html = str_replace("#STEP#", "{Get update}", $html);
		}

		$html = str_replace("##status##", $status, $html);
		$html = str_replace("##PATH##", PONSDIR, $html);

	} else {
		$html = returnmsg("{You're not authorized.}");
	}
}


print translateHtml($html);
