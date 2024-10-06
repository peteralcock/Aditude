<?php
/*
	
	manage compoents in a module
	this is a admin component
	to build the items in menu

	23-03-2024
	This component is in use partially with superadmin

*/

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/commod.class.php");

$ambiente->setPosizione("{Add existing component}");

$obj = new Commod();

$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$cd = get("cd_item","");

$path = "";

switch ($command) {
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter, $cd );
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato 1.","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2" :
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato 2.","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$html = returnmsgok("Record modificato.","load {$path}index.php?op=modifica&id={$_POST['cd_item']}");
		}
		break;
	case "elimina":
		$risultato = $obj->deleteItem($parameter, $cd);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato 3.","jsback");
		} else $html = returnmsgok("Record rimosso.","load {$path}index.php?op=modifica&id={$risultato}");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio(null,$cd);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato 5.","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato 6.","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			$html = returnmsgok("Record inserito.","load {$path}index.php?op=modifica&id=".$_POST['cd_item']."");
		}
		break;
}

print translateHtml( $html );
