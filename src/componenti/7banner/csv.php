<?php
/*

	csv table export handler

*/


$root="../../../";
include($root."src/_include/config.php");
include("_include/banner.class.php");
include("_include/dashboard.class.php");

if (isset($_GET["combobanner"])) {
	$combobanner = $_GET["combobanner"];
} else $combobanner="";

if (isset($_GET["combobannerreset"])) {
	$combobannerreset = $_GET["combobannerreset"];
} else $combobannerreset="";

if (isset($_GET["id"]))	$id = $_GET["id"]; else $id="";

if (isset($_GET["enddate"])) {
	$enddate = $_GET["enddate"];
} else $enddate= null;

if (isset($_GET["startdate"])) {
	$startdate = $_GET["startdate"];
} else $startdate= null;



$obj = new Banner();
$dashboardObj = new Dashboard($obj);


$csv = $dashboardObj->getCsvData($id,$combobanner,$combobannerreset,$startdate,$enddate);
$filename = "export-".$startdate."-".$enddate.".csv";

// open raw memory as file so no temp files needed, you might run out of memory though
$f = fopen('php://memory', 'w'); 
fwrite($f, $csv); 
// reset the file pointer to the start of the file
fseek($f, 0);
// tell the browser it's going to be a csv file
header('Content-Type: text/csv; charset=utf-8');
// tell the browser we want to save it instead of displaying it
header('Content-Disposition: attachment; filename="'.$filename.'";');
// make php send the generated csv lines to the browser
fpassthru($f);


