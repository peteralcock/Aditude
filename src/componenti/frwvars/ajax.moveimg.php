<?php
/*
	move file, for gallery items
*/
$root="../../../";
include($root."src/_include/config.php");
if($session->get("idutente")!="") {
	if(isset($_GET['da']) && isset($_GET['a']) && isset($_GET['div0'])) {
		$da = base64_decode($_GET['da']);
		$a = base64_decode($_GET['a']);
		$div0 = base64_decode($_GET['div0']);
		if($da && $a && $div0) die ( spostafilegallery($da,$a,$div0) ); else die("ko3");
	} else die("ko2");
}
die("ko");