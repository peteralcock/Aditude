<?php
/*

	ajax call handler for banners

*/

$root="../../../";
include($root."src/_include/config.php");
include("_include/banner.class.php");

$obj = new Banner();
$obj->uploadDir = $root."data/dbimg/media/";
$obj->max_files= 2;

$html=""; $command="";

if (isset($_GET["op"])) {
	$command = $_GET["op"];
	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
} else if (isset($_POST["op"])) {
	$command = $_POST["op"];
	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
}

if (isset($_GET["combotipo"])) {
	$combotipo = $_GET["combotipo"];
} else $combotipo="";

if (isset($_GET["combotiporeset"])) {
	$combotiporeset = $_GET["combotiporeset"];
} else $combotiporeset="";

if (isset($_GET["keyword"])) {
	$keyword= $_GET["keyword"];
} else $keyword="";

if (isset($_GET["combobanner"])) {
	$combobanner = $_GET["combobanner"];
} else $combobanner="";

if (isset($_GET["combobannerreset"])) {
	$combobannerreset = $_GET["combobannerreset"];
} else $combobannerreset="";

if (isset($_GET["enddate"])) {
	$enddate = $_GET["enddate"];
} else $enddate= null;

if (isset($_GET["startdate"])) {
	$startdate = $_GET["startdate"];
} else $startdate= null;


//esegue eventuali comandi passati
switch ($command) {
	case "go":
		$obj->setStato($parameter,"A");
		$html = "ok";
		break;
	case "pause":
		$obj->setStato($parameter,"P");
		$html = "ok";
		break;
	case "region":
		$html = $obj->getListRegion($_GET['country']);
		break;
	case "city":
		$html = $obj->getListCity($_GET['country'],$_GET['region']);
		break;
	case "posizione":
		$html = $obj->getPosInfo($parameter);
		break;
	case "coinbase":
		$html = $obj->coinbase_getCharge($parameter);
		break;
    case "manual":
        $html = $obj->manual_pay($parameter);
        break;

}



print translateHtml( $html );

?>