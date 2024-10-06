<?php
/*
    function for grid list
*/

function show_user_fullname($v,$id) {
    global $gu,$session;
    if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
        $link = str_replace("##id##",$id,$gu->linkmodifica);
        return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
    } else {
        return $v;
    }
 }

 function toggleStato($v,$id) {
    global $session;
    if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
        $ar = array(
            "0"=>"<a class='labelred' href=\"javascript:;\" onclick=\"setStato(this,'1',".$id.")\">{OFF}</a></span>",
            "1"=>"<a class='labelgreen' href=\"javascript:;\" onclick=\"setStato(this,'0',".$id.")\">{ON}</a></span>"
        );
    } else {
        $ar = array(
            "0"=>"<span class='labelred'>{OFF}</span>",
            "1"=>"<span class='labelgreen'>{ON}</span>"
        );
    }
    return $ar[$v];
}

function decrypta($s) { $cr = new cryptor(); return $cr->decrypta($s); }
