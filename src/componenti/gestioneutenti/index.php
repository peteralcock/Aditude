<?php
/*

	controller for user manager

*/


$root="../../../";

//
// require classes to send mail with SMTP
require $root.'src/componenti/PHPMailer/src/Exception.php';
require $root.'src/componenti/PHPMailer/src/PHPMailer.php';
require $root.'src/componenti/PHPMailer/src/SMTP.php';

//
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include("_include/user.class.php");
include("_include/gestioneutenti.class.php");
include("_include/grid_callbacks.php");

print $ambiente->setPosizione( "{Users}" );
    
$gu = new gestioneutenti("frw_utenti",40,"cognome","asc",0);


if (isset($ARRAY_EXTRA_USER_LABELS)) 
    $gu->scegliDaInsiemeLabelProfili=$ARRAY_EXTRA_USER_LABELS;

$html="";

$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");


switch ($command) {
	case "modifica":
		$risultato = $gu->getDettaglioNew( $parameter );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "modificaStep2" :
		$risultato = $gu->updateAndInsert($_POST);
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
		$risultato = $gu->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = returnmsgok("{Deleted.}","load ".$_SERVER['SCRIPT_NAME']."");
		break;

	case "aggiungi":
		$risultato = $gu->getDettaglioNew();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;
		break;
	case "aggiungiStep2":
		$risultato = $gu->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			if ($command != "aggiungiStep2reload") $html = returnmsgok("{Done.}","reload");
				else $html = returnmsgok("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id=".$id."");
		}
		break;

	case "personifica":
		$user = execute_row("SELECT ".DB_PREFIX."username, password FROM frw_utenti where id='$parameter'");
		if($user && in_array( $session->get("idprofilo"), array(20,999999) )) {
			$cr = new cryptor();
			$login->actionurl = $root."src/login.php";
			$out = $login->getLoginForm("Autologin");
			$out = str_replace('<input name="password" type="password"','<input name="password" type="password" value="'.$cr->decrypta($user['password']).'"',$out);
			$out = str_replace('<input name="utente"','<input name="utente" value="'.$user['username'].'"',$out);
			$out = str_replace('</form>','</form><script>document.getElementById("loginform").submit();</script>',$out);
			$logger->addlog( 2 , "{fine sessione utente ".$session->get("username").", id=".$session->get("idutente")."}" );
			$session->finish();
			echo $out;
		} 
		die;

}



if ($html=="") {

	$html = loadTemplateAndParse ("../gestioneutenti/template/elenco.html");
	$elenco = $gu->elencoUtenti($combotipo,$combotiporeset,$keyword);
	if($elenco!="0") {
		$html = str_replace("##corpo##", ($elenco), $html);
		$html = str_replace("##keyword##", $keyword, $html);
		$html = str_replace("##bottoni2##","<a href=\"$gu->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
		$html = str_replace("##combotipo##", $gu->getHtmlcombotipo($combotipo), $html);

		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
			if( in_array( $session->get("idprofilo"), array(20,999999) )) {
				$html = str_replace("##aggiungi##","<a href=\"$gu->linkaggiungi\" class='aggiungi' title=\"{Add new item}\"></a>",$html);
			} else {
				$html = str_replace("##aggiungi##","",$html);
			}
		}
	} else {
		$url = getDefaultComponentAddress();
		$html = returnmsg ("{You're not authorized.}","link ../../".$url);
	}
}

print translateHtml($html);