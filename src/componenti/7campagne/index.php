<?php
/*

	manage campaigns

*/


$root="../../../";
include($root."src/_include/config.php");
include($root."src/_include/grid.class.php");
include($root."src/_include/formcampi.class.php");
include($root."src/_include/crudbase.class.php");
include("../7banner/_include/banner.class.php");
include("_include/campagne.class.php");
include("_include/grid_callbacks.php");



// update position in html
print $ambiente->setPosizione( "{Campaigns}" );

$obj = new Campagne();
$obj->setAmbiente( $ambiente );	// bind the ambiente


$command = getpost("op", null);
$parameter = getpost("id", null);
$combotipo = get("combotipo","");
$combotiporeset = get("combotiporeset","");
$keyword = get("keyword","");



switch ($command) {
case "modifica":
case "duplica":
	$obj->getDettaglio( $parameter, $command );
	break;
case "modificaStep2reload" :
case "modificaStep2" :
	$obj->updateAndInsert($_POST,$_FILES);
	break;
case "eliminaSelezionati":
	$obj->eliminaSelezionati($_POST);
	break;
case "aggiungi":
	$obj->getDettaglio();
	break;
case "aggiungiStep2reload":
case "aggiungiStep2":
	$obj->updateAndInsert($_POST,$_FILES);
	case null:
		default:
			$obj->elenco($combotipo,$combotiporeset,$keyword);
	
	}


print translateHtml( $ambiente->loadAndParse() );
