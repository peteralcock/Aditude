<?php
/*
    function for grid list
*/


function show_campaign_value($v) {
	return ($v > 0 ? MONEY.numberf($v,2,".",",") : "");
}


function show_campaign_title($v,$id) {
    global $obj,$session;
    if( ($session->get("idprofilo")==5 && PAYMENTS=="ON") || $session->get("idprofilo") >= 20) {
        $link = str_replace("##id_campagna##",$id,$obj->linkmodifica);
        return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
    } else {
        return $v;
    } 
 }