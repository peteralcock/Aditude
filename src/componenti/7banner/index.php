<?php
/*

	Banners page handler

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
include("../php-zip/Zip.php");
include($root."src/componenti/gestioneutenti/_include/user.class.php");
include("_include/banner.class.php");
include("_include/dashboard.class.php");
include("_include/callback_griglia.php");


//
// update main position
if(isset($_GET["op"]) && ($_GET["op"]=="dashboard"|| $_GET["op"]=="dashboardnew")) $position = "Dashboard"; else $position="Banner";
print $ambiente->setPosizione("{" . $position . "}");


$obj = new Banner();
$obj->uploadDir = $root."data/dbimg/media/";
$obj->maxX= 2000;
$obj->maxY= 1800;
$obj->maxKB = MAXSIZE_UPLOAD; // moved in settings
$obj->max_files= 10;

$objDashboard = new Dashboard($obj);


$html=""; $command="";


$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$combobanner = get("combobanner","");
$combobannerreset = get("combobannerreset","");
$keyword = get("keyword","");

$combosite = get("combosite","");
$enddate = get("enddate",null);
$startdate = get("startdate",null);
$comboclient = get("comboclient","");
$combocampaign = get("combocampaign","");


if($session->get("idprofilo")=="10"){ $command = "dashboardnew";$parameter="";}


// command handler
switch ($command) {
	case "coinbaseredir":
		$risultato = $obj->coinbase_checkTransaction($_REQUEST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","link index.php");
		} else {
			$ar = explode("|",$risultato);
			$val = $ar[0];
			$msg = $ar[1];
			$html = returnmsgok($msg,"link index.php");
		}
		break;



	case "paypalredir":
		$risultato = $obj->paypal_checkTransaction($_REQUEST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","link index.php");
		} else {
			$ar = explode("|",$risultato);
			$val = $ar[0];
			$msg = $ar[1];
			if($val=="-1") {
				$html = returnmsg($msg,"link index.php");
			} elseif($val=="1") {
				$html = returnmsgok($msg,"link index.php");
			} else {
				$html = returnmsg("Error: ".$msg,"link index.php");
			}
		}
		break;

	case "modifica":
	case "duplica":
		$risultato = $obj->getDettaglio( $parameter, $command );
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif ($risultato=="-1") {
			$html = returnmsg("{You can't edit or delete a banner after payment.}","link index.php");
		} elseif (str_replace(strstr($risultato,"|"),"",$risultato)=="-2") {
			$html = returnmsg("{Please, go on and complete the payment}","link ". str_replace("|","",strstr($risultato,"|")));
		} elseif (str_replace(strstr($risultato,"|"),"",$risultato)=="-3") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"link index.php");
		} elseif (str_replace(strstr($risultato,"|"),"",$risultato)=="-4") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"link index.php");
		} else {
			$html = $risultato;
		}
		break;
	
	case "modificaStep2checkout":
	case "modificaStep2reload" :
	case "modificaStep2" :
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			if ($command == "modificaStep2") $html = returnmsgok("Done.","reload");
			if ($command == "modificaStep2reload") $html = returnmsgok("{Done.}","load index.php?op=modifica&id={$parameter}");
			if ($command == "modificaStep2checkout") $html = returnmsgok("{Done.}","load index.php?op=checkout&id={$parameter}");
		}
		break;
	case "elimina":
	case "eliminaSelezionati":
		//if($command == "elimina") $risultato = $obj->deleteItem($parameter);
		$risultato = $obj->eliminaSelezionati($_POST);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif ($risultato=="-1") {
			$html = returnmsg("{You can't edit or delete a banner after payment.}","link index.php");
		} else $html = returnmsgok("{Deleted.}","load index.php");
		break;
	case "aggiungi":
		$risultato = $obj->getDettaglio();
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else $html = $risultato;

		break;

	case "aggiungiStep2checkout":
	case "aggiungiStep2reload":
	case "aggiungiStep2":
		$risultato = $obj->updateAndInsert($_POST,$_FILES);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif(str_replace(strstr($risultato,"|"),"",$risultato)=="-1") {
			$html = returnmsg(str_replace("|","",strstr($risultato,"|")),"jsback");
		} else {
			$id = str_replace( "|","",stristr( $risultato, "|")) ; 
			if ($command == "aggiungiStep2") $html = returnmsgok("{Done.}","load index.php");
			if ($command == "aggiungiStep2reload") $html = returnmsgok("{Done.}","load index.php?op=modifica&id=".$id."");
			if ($command == "aggiungiStep2checkout") $html = returnmsgok("{Done.}","load index.php?op=checkout&id=".$id);
		}
		break;

	case "checkout":
		$risultato = $obj->checkoutForm($parameter);
		$html = $risultato;
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} elseif ($risultato=="-1") {
			$html = returnmsg("{Missing payments configuration}","link index.php?op=modifica&id=".$parameter);
		}
		break;
	
	case "stats":
		$risultato = $objDashboard->getCharts($parameter,$combobanner,$combobannerreset,$startdate,$enddate);
		if ($risultato=="0") {
			$html = returnmsg("{You're not authorized.}","jsback");
		} else {
			$html = $risultato;
		}
		break;
	case "dashboard":
		// $risultato = $obj->getDashboard($startdate,$enddate);
		// if ($risultato=="0") {
		// 	$html = returnmsg("{You're not authorized.}","jsback");
		// } else {
		// 	$html = $risultato;
		// }
		// break;
     case "dashboardnew":
            $risultato = $objDashboard->getDashboardNew($startdate,$enddate,$combosite);
            if ($risultato=="0") {
                $html = returnmsg("{You're not authorized.}","jsback");
            } else {
                $html = $risultato;
            }
            break;        
	default:
		$elenco = $obj->elenco($combotipo,$comboclient,$combocampaign,$combotiporeset,$keyword);
		if($elenco!="0") {

			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if($session->get("idprofilo")==5) $bodyclass.=' advertiser';
			if($session->get("idprofilo")==5 && PAYMENTS=="OFF") $bodyclass.=' nopayments';

			$html = loadTemplateAndParse ("template/elenco.html");
			$html = str_replace("##corpo##", $elenco, $html);
			$html = str_replace("##bodyclass##", $bodyclass, $html);

			$html = str_replace("##keyword##", $keyword, $html);
			
			if($session->get("idprofilo")>5 || 
				(PAYMENTS=="ON" && $session->get("idprofilo")==5) ) {
				$html = str_replace("##bottoni1##","<a href=\"$obj->linkaggiungi\" title=\"{Add new item}\" class='aggiungi'></a>", $html);
				$html = str_replace("##bottoni2##","<a href=\"$obj->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>", $html);
			} else {
				$html = str_replace("##bottoni2##","", $html);
				$html = str_replace("##bottoni1##","", $html);
			}

			$html = str_replace("##combotipo##", $obj->getHtmlcombotipo($combotipo), $html);

			$html = str_replace("##comboclient##", $obj->getHtmlcomboclient($comboclient), $html);

			$html = str_replace("##combocampaign##", $obj->getHtmlcombocampaign($combocampaign), $html);

		} else {
			$html = returnmsg("{You're not authorized.}");
		}

}



print translateHtml($html);

