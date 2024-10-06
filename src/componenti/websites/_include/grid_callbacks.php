<?php
/*
    function for grid list
*/

function show_website_name($v,$id) {
    global $obj,$session;

    if ($session->get("idprofilo") != 5) {	
        $link = str_replace("##id_sito##",$id,$obj->linkmodifica);
        return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
    } else {
        return $v;
    }


   
 }


 function show_website_link($s) {
	$s1 = $s;
	if(!preg_match("/^http/i",$s)) $s1 = "//".$s;
	return "<a href=\"".htmlspecialchars($s1)."\" class='icon-link linka' target='_blank'>".$s."</a>";
}

