<?php
//
// change status of a job
//

$root="../../../../";
include($root."src/_include/config.php");
include("../_include/gestioneutenti.class.php");
$obj = new gestioneutenti();
$id = (integer)$_GET['id'];
$op = (integer)$_GET['op'];
if($op!=1 && $op!=0) die("errore");
$out = $obj->toggleStato($id,$op);
$ar = array(
    "0"=>"<a class='labelred' href=\"javascript:;\" onclick=\"setStato(this,'1',".$id.")\">{OFF}</a>",
    "1"=>"<a class='labelgreen' href=\"javascript:;\" onclick=\"setStato(this,'0',".$id.")\">{ON}</a>"
);
echo translateHtml($ar[$op]);
?>