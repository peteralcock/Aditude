<?php
/*
	this is a super admin component
	to manage component items in the menu
*/
$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/componenti.class.php");

function  showModules($p) {
	global $conn;
	$sql = "SELECT distinct ".DB_PREFIX."frw_moduli.label FROM `".DB_PREFIX."frw_com_mod` 
		INNER JOIN ".DB_PREFIX."frw_moduli on idmodulo = ".DB_PREFIX."frw_moduli.id
        WHERE ".DB_PREFIX."frw_com_mod.idcomponente='".$p."'";
	return concatenaId($sql,", ");
}

// position string in html
print $ambiente->setPosizione( "{Menu item}" );

$obj = new Componenti();

$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");


switch ($command) {
	case "profila":
		$risultato = $obj->profila( $parameter );
		if ($risultato=="0") $html = returnmsg("Non sei autorizzato.");
			else if ($risultato=="1") $html = returnmsg("Il componente non ha funzionalit&agrave;...");
			else if ($risultato=="2") $html = returnmsg("il componente non &egrave; installato in nessun modulo...");
			else if ($risultato=="3") $html = returnmsg("il componente non &egrave; associato a nessun profilo...");
			else $html = returnmsgok("Le funzionalit&agrave; del componente sono state distribuite agli utenti abilitati.<br><br>".$risultato);
		break;
	case "modificaf":
		$risultato = $obj->getDettaglioF( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("Non sei autorizzato.");
		} else $html = $risultato;
		break;
	case "modificafStep2":
		$risultato = $obj->updateAndInsertF($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else if($risultato=="1") {
			$html = returnmsg ("Il componente a cui questa funzionalit&agrave; appartiene non &egrave; associato ad alcun modulo. La funzionalità non &egrave; stata inserita.","back");
		} else $html = returnmsgok("La funzionalit&agrave; &egrave; stata modificata.","load index.php?op=modifica&id=".$_POST['idcomponente']);
		break;
	case "modifica":
		$risultato = $obj->getDettaglio( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2":
		$risultato = $obj->updateAndInsert($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else if($risultato=="1") {
			$html = returnmsg ("{Menu item label or slug already existing.}","jsback");
		} else if(stristr($risultato,"5|")) {
			$idmodule = str_replace("5|","",$risultato);
			$html = returnmsgok("{Done.}","load ../frwmoduli/index.php?op=modifica&id=".$idmodule);
		} else $html = returnmsgok("{Done.}","load index.php");


		break;
		
	case "elimina":
		$risultato = $obj->deleteC($parameter);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else {
			$goto = "index.php";
			if(isset($_GET['cd_module'])) {
				$idmodule = $_GET['cd_module'];
				$goto = "../frwmoduli/index.php?op=modifica&id=".$idmodule;
			}
			$html = returnmsgok("{Done.}","load ". $goto);
		}
		break;
	case "eliminaf":
		$risultato = $obj->deleteF($parameter);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("La funzionalit&agrave; &egrave; stata rimossa.","back");
		break;
	case "aggiungif":
		$risultato = $obj->getDettaglioF("",$parameter);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		
		break;
	case "aggiungifStep2":
		$risultato = $obj->updateAndInsertF($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else if($risultato=="1") {
			$html = returnmsg ("Il componente a cui questa funzionalit&agrave; appartiene non &egrave; associato ad alcun modulo. La funzionalit&agrave; non &egrave; stata inserita.","back");
		} else $html = returnmsgok("La funzionalit&agrave; &egrave; stata inserita.","back");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		
		break;
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else if($risultato=="1") {
			$html = returnmsg ("{Menu item label or slug already existing.}","jsback");
		} else if(stristr($risultato,"5|")) {
			$idmodule = str_replace("5|","",$risultato);
			$html = returnmsgok("{Done.}","load ../frwmoduli/index.php?op=modifica&id=".$idmodule);
		} else $html = returnmsgok("{Done.}","load index.php");
		break;

}


if ($html=="") {
	$html = loadTemplateAndParse ("template/elenco.html");
	$html = str_replace("##corpo##", ($obj->elenco($combotipo,$combotiporeset,$keyword)), $html);
	$html = str_replace("##keyword##", $keyword, $html);
	$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"{Add new item}\" class='aggiungi'></a>", $html);
	$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
	$html = str_replace("##combotipo##", $obj->getHtmlcombotipo($combotipo), $html);
}


print translateHtml($html);
