<?php
$public = true;


//
// show docs
//
$root="../../../";
include($root."src/_include/config.php");
include("_include/docs.class.php");


//::aggiorno posizione::
print $ambiente->setPosizione( "{docs}" );

$io = new docs();

$html="";

if (isset($_GET["op"])) {
	$command = $_GET["op"];
	if (isset($_GET["id"])) $parameter = $_GET["id"]; else $parameter="";
} else if (isset($_POST["op"])) {
	$command = $_POST["op"];
	if (isset($_POST["id"]))	$parameter = $_POST["id"]; else $parameter="";
}

if(!isset($command) || $command=="") {$command = "show"; }
if(!isset($parameter) || $parameter=="") {$parameter = $session->get("idutente"); }

//
// run command
if (isset($command)) {

	switch ($command) {
	case "show":
		$risultato = $io->getDocs();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	}

}

print ($html);
?>