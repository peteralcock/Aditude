<?php
$root="../";
include("_include/config.php");

$logger->addlog( 2 , "{ending session user #".$session->get("idutente")."}" );
$session->finish();
setcookie("comein","",time()-3600,"/");
print $ambiente->loadLogin("See you soon.");

?>
