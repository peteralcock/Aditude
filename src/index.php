<?php

$root = "../";
include("_include/config.php");


$nome = execute_scalar("select nome from ".DB_PREFIX."frw_componenti 
		inner join ".DB_PREFIX."frw_extrauserdata on cd_default_component=id
		where cd_user=".$session->get("idutente"), PRIMO_COMPONENTE_DA_MOSTRARE );


//
// if there isn't a component to show redirect to home layout
// (maybe never used)
if($nome=="") {
	$html = loadTemplateAndParse(
		$root."data/".DOMINIODEFAULT."/layout-home.php",
		$defaultReplace
	);

	echo $html;

} else {
	//
	// if there is a component to show redirect to it
	$r = execute_row("select * from ".DB_PREFIX."frw_componenti where nome='".addslashes($nome)."'");
	if(isset($r['urlcomponente'])) {
		header("Location: ".$root."src/".$r['urlcomponente']);
		die;
	}
}


?>