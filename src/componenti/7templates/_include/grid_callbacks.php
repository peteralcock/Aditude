<?php
/*
    function for grid list
*/

function show_template_title($v,$id) {
    global $obj;
    $link = str_replace("##id_template##",$id,$obj->linkmodifica);
    return '<a href="'. $link.'" title="{Edit}"><u>'.$v.'</u></a>';
 }

