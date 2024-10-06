<?php
$public = true;
//
// handle installer
//
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/formcampi.class.php");
include("_include/install.class.php");


//::aggiorno posizione::
print $ambiente->setPosizione( "{Install AdAdmin}" );

$io = new install();

$html="";

if (isset($_GET["op"])) {
	$command = $_GET["op"];
	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
} else if (isset($_POST["op"])) {
	$command = $_POST["op"];
	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
}

if(!isset($command) || $command=="") {$command = "install"; }
if(!isset($parameter) || $parameter=="") {$parameter = $session->get("idutente"); }

//esegue eventuali comandi passati
if (isset($command)) {

	switch ($command) {
	case "install":
		$risultato = $io->getDettaglioMysql();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else {
			$html = $risultato;
		}
		break;
	case "modificaStep2":
		$risultato = $io->update($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif($risultato=="1") {
			$html = returnmsg("{Can't connect to database.}","jsback");
		} else $html = returnmsgok("{Done.}","reload");
		break;
	}

}

print translateHtml($html);
