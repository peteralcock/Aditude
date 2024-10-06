<?php
//
// callback to show visibility in second level grid
//
function  showVisibility($p) {
	global $conn,$session;
	$sql = "SELECT distinct ".DB_PREFIX."frw_profili.de_label FROM `".DB_PREFIX."frw_profili_funzionalita` 
		INNER JOIN ".DB_PREFIX."frw_profili on cd_profilo = ".DB_PREFIX."frw_profili.id_profilo
		INNER JOIN ".DB_PREFIX."frw_funzionalita on cd_funzionalita = ".DB_PREFIX."frw_funzionalita.id 
		WHERE ".DB_PREFIX."frw_funzionalita.idcomponente='".$p."'
		".($session->get("idprofilo")<999999 ? " AND id_profilo<999999 " : "");
	return concatenaId($sql,", ");
}

function show_modulename($v,$id) {
    global $obj;
	$link = str_replace("##id##",$id,$obj->linkmodifica);
	return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
 }

 ?>