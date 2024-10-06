<?php
/*
    function for grid list
*/

function show_position_name($v,$id) {
    global $obj;
    $link = str_replace("##id_posizione##",$id,$obj->linkmodifica);
    return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
 }