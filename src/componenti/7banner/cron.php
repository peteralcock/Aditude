<?php
/*

	handle jobs (cron)

	1. coinbase check open payments for confirmation

*/

$public = true;
$root="../../../";
require $root.'src/componenti/PHPMailer/src/Exception.php';
require $root.'src/componenti/PHPMailer/src/PHPMailer.php';
require $root.'src/componenti/PHPMailer/src/SMTP.php';

//
include($root."src/_include/config.php");
include($root."src/componenti/gestioneutenti/_include/user.class.php");
include("_include/banner.class.php");



/*
	1. coinbase check open payments for confirmation
*/
if( COINBASE_API_KEY != "") {
	$obj = new Banner();
	$obj->uploadDir = $root."data/dbimg/media/";
	$obj->max_files= 2;
	$sql = "select data_creazione, id_coinbase,id_banner, cd_banner as id from ".DB_PREFIX."7banner_ordini inner join ".DB_PREFIX."7banner on cd_banner=id_banner where id_coinbase<>'' and fl_stato='W' order by data_creazione ASC limit 0,10";
	$rs = $conn->query($sql);
	while($row = $rs->fetch_array()) {
		$out = $obj->coinbase_checkTransaction($row, $public);
		echo $row["data_creazione"]." ".$row["id_coinbase"]." #".$row['id_banner']." ==> ".$out."<br>";
	}
}




?>