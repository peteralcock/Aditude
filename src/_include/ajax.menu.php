<?php

$root="../../";
include($root."src/_include/config.php");

if(!$session->get("idutente")) die();

echo $login->headMenu() . $login->getMenu($session->get("idutente")).
	$login->footMenu();

?>