<?php
header("Content-Type: application/json");

$root="../../";
include($root."src/_include/config.php");

loadLanguageLabels();
echo json_encode(array($langArrayLabels));

?>