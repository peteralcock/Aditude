<?php
/*

	handler for geo ip updates

*/

$root="../../../";
include($root."src/_include/config.php");
include("../php-zip/Zip.php");
include("_include/constants.class.php");


$obj = new Constants();

$html=""; $command="";

$f = "";

if (isset($_GET["op"])) {
	$command = $_GET["op"];
	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
} else if (isset($_POST["op"])) {
	$command = $_POST["op"];
	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
}

switch ($command) {
	case "aggiorna":
		$html = $obj->aggiornadb($parameter);
		break;
	/*case "update":
		$html = $obj->geoIpUpdate();
		break;
	case "download":
		$html = $obj->getNewCSV();
		break;*/
}

	

print $html;

?>