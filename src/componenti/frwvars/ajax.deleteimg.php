<?php
/*
	delete file, for gallery items
*/
$root="../../../";
include($root."src/_include/config.php");
if($session->get("idutente")!="") {
	if(isset($_GET['f']) && isset($_GET['div0'])) {
		$f = base64_decode($_GET['f']); // file names are base64 encoded
		$div0 = base64_decode($_GET['div0']);
		if($f && $div0) die( deletefilegallery($f,$div0) ); else die("ko3");
	} else die("ko2");
}
die("ko");