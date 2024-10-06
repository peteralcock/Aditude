<?php
/*

	controller to manage my own user data

*/

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/formcampi.class.php");
include("_include/user.class.php");
include("_include/mioprofilo.class.php");

print $ambiente->setPosizione( "{My profile}" );

$io = new Mioprofilo();

$html="";

$command = getpost("op", "modifica");
$parameter = getpost("id", $session->get("idutente"));


switch ($command) {
	case "modifica":
		$risultato = $io->getDettaglio();
		
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2":
		if($_SERVER['HTTP_HOST']!="www.zepsec.com" && PONSDIR=="/ambdemo") {
			$html = returnmsg("{This is a demo version, you can't do that.}","jsback");
		} else {
			$risultato = $io->update($_POST,$_FILES);
			if ($risultato=="0") {
				$html = returnmsg("{You're not authorized.}","jsback");
			} elseif($risultato=="2") {
				$html = returnmsg("{Not a valid email.}","jsback");
			} elseif($risultato=="1") {
				$html = returnmsg("{Email already used.}","jsback");
			} else $html = returnmsgok("{Done.}","reload");
			break;
		}
}

print translateHtml($html);