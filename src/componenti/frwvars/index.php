<?php
/*
	manages variables on db
*/

$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/vars.class.php");

$ambiente->setPosizione("{All settings}");

$obj = new Vars();

$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");



switch ($command) {
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else $html = returnmsgok("Record modificato.","reload");
		break;
	case "elimina":
		$risultato = $obj->deleteItem($parameter);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} else $html = returnmsgok("Record rimosso.","load index.php");
		break;
	case "eliminaSelezionati":
		$risultato = $obj->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} else $html = returnmsgok("Record eliminati","load index.php");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} else $html = $risultato;

		break;
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else $html = returnmsgok("Record inserito.","reload");
		break;
}



if ($html=="") {
	$elenco = $obj->elenco($combotiporeset,$keyword);
	if($elenco!="0") {
		$html = loadTemplateAndParse("template/elenco.html");
		$html = str_replace("##corpo##", $elenco, $html);
		$html = str_replace("##keyword##", $keyword, $html);
		$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"".$obj->linkaggiungi_label."\" class=\"aggiungi\"></a>", $html);
		$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"".$obj->linkeliminamarcate_label."\" class=\"elimina\"></a>", $html);
	} else {
		$html = returnmsg("Non sei autorizzato.");
	}
}


print translateHtml($html);