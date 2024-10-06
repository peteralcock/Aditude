<?php
$root="../../../";
include($root."src/_include/config.php");
include("_include/scheduledfixdb.class.php");

//::aggiorno posizione::
print $ambiente->setPosizione("Debugger");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<!-- utilizzo gli stili globali -->
		<link rel="stylesheet" type="text/css" href="<?php echo $root;?>src/template/stile.css">
		<!-- includo le funzioni javascript generiche -->
		<script language="JavaScript" src="<?php echo $root;?>src/template/comode.js"></script>
	</head>
	<body>
		<div class="corpo">
			

<div class='errore'>
	<h1>Handle log files (Actual logs file size: <?php echo $logger->logsize()?>)</h1>
	{<a href="logs.php">show logs file</a>} {<a href='logs.php?delete'>delete logs</a>}
</div>
<?php
if (!Connessione()) trigger_error($conn->error); else CollateConnessione();
$a = new scheduledfixdb(DEFDBNAME);
$temp = explode("/",LOGS_FILENAME);
$dir = str_replace( $temp[count($temp)-1], "fixdb_" . DOMINIODEFAULT . ".log" , LOGS_FILENAME );
$a->fixDbLogFile= $root.$dir;
?>
<div class='errore'>
	<h1>Scheduled fix db (Last fix: <?php echo $a->lastfix();?>)</h1>
	<?php
	if(isset($_GET['fixdb'])) $a->checkAndFix(); else echo "<a href='?fixdb'>Fix db (optimize/repair tables)? Yes</a>";
	?>
</div>
<div class='errore'>
	<h1>Dump info</h1>
	<?php
	echo $logger->dump_info();
	?>
</div>
<div class='errore'>
	<h1>PHP INFO</h1>
	<?php
	phpInfo();
	?>
</div>




		</div>
		<script language="javascript">
		checkTop();
		</script>

	</body>
</html>
