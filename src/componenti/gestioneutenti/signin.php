<?php
$public = true;

/*

	public page for user sign in process

*/


$root="../../../";

//
// require classes to send mail with SMTP
require $root.'src/componenti/PHPMailer/src/Exception.php';
require $root.'src/componenti/PHPMailer/src/PHPMailer.php';
require $root.'src/componenti/PHPMailer/src/SMTP.php';

//
include($root."src/_include/config.php");

if($session->get("idutente")) {
	$session->finish();
}

include($root."src/_include/formcampi.class.php");
include("_include/user.class.php");
include("_include/gestioneutenti.class.php");
include("_include/mioprofilo.class.php");




if(!defined("PAYMENTS") || !PAYMENTS) die("Not available");

print $ambiente->setPosizione( "{New account}" );

$io = new mioprofilo();

$html="";

if (isset($_GET["op"])) {
	$command = $_GET["op"];
	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
} else if (isset($_POST["op"])) {
	$command = $_POST["op"];
	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
}

if(!isset($command) || $command=="") {$command = "modifica"; }
if(!isset($parameter) || $parameter=="") {$parameter = $session->get("idutente"); }


if (isset($command)) {

	switch ($command) {
	case "code":
		$gu = new gestioneutenti("frw_utenti",40,"cognome","asc",0);
		$risultato = $gu->confirmSignIn($parameter);
		if($risultato == "") $html = returnmsgok("{User created, now you can login.} ","link ".$root);
			else $html = returnmsgok("{Check code not found or user already confirmed.}","link ".$root);
		break;
	case "modifica":
		$html = $io->getDettaglioSignIn();
		break;
	case "modificaStep2":

		$gu = new gestioneutenti("frw_utenti",40,"cognome","asc",0);

		$risultato = $gu->insertNewUser($_POST);
		if ($risultato!="") {
			$msg = $risultato;
			if($risultato == 1) $msg = translateHtml("{Email already used. <a href='%s'>Password recovery?</a>}");
			if($risultato == 2) $msg = "{Not a valid email.}";
			if($risultato == 3) $msg = "{Please, complete mandatory fields.}";
			if($risultato == 5) $msg = translateHtml("{Username already used. <a href='%s'>Password recovery?</a>}");

			$msg = str_replace("%s",WEBURL."/src/resetpassword.php",$msg);

			$html = returnmsg("{Error}<br>".$msg,"jsback");
		} else $html = returnmsgok("{Please check your email to confirm email address.}","link ".$root);
		break;

	}

}

print translateHtml($html);
?>