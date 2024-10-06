<?php

/*
	manage websites
*/
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/website.class.php");
include("_include/grid_callbacks.php");


print $ambiente->setPosizione( "{Websites}" );

$obj = new Website();



$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");



switch ($command) {
	case "modifica":
	case "duplica":
		$risultato = $obj->getDettaglio( $parameter, $command );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2reload" :
	case "modificaStep2" :
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			if ($command != "modificaStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$parameter}");
		}
		break;
	case "eliminaSelezionati":
		$risultato = $obj->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif ($risultato=="-2") {
			$html = returnmsg("{You can't delete a website with positions.}","jsback");
		} else {
			$html = returnmsgok("{Deleted.}","load ".$_SERVER['SCRIPT_NAME']."");
		}
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2reload":
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			if ($command != "aggiungiStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id=".$id."");
		}
}


if ($html=="") {
	$html = loadTemplateAndParse ("template/elenco.html");
	$bodyclass="";
	if($session->get("idprofilo")>=20) $bodyclass .='admin ';
	if($session->get("idprofilo")==5) $bodyclass.='advertiser';

	$html = str_replace("##corpo##", ($obj->elenco($combotipo,$combotiporeset,$keyword)), $html);
	$html = str_replace("##keyword##", $keyword, $html);
	if($session->get("idprofilo")==5) {
		$html = str_replace("##bottoni1##","", $html);
		$html = str_replace("##bottoni2##","", $html);
	} else {
		$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"{Add new item}\" class='aggiungi'></a>", $html);
		$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
	}
	$html = str_replace("##combotipo##", $obj->getHtmlcombotipo($combotipo), $html);
	$html = str_replace("##bodyclass##", $bodyclass, $html);

}


print translateHtml($html);
