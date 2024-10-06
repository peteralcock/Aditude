<?php
$root="../../";
include($root."_include/config.php");
?>
<html>
<head>
<style>
body {
	font-family: arial;
	font-size: 8pt;

}
</style>
<script language="JavaScript" src="../../template/comode.js"></script>
</head>
<body>
<?php
//::aggiorno posizione::
print $ambiente->setPosizione("Configurazione / debugger / logs");

if (isset($_GET['delete'])) $logger->deleteLog();
echo $logger->displayLog();
?>
</body>
</html>