<?php
/*
	**********************************************************************
	author: giulio pons - italy
	please, if you use this class send me an email, i don't want
	any money, just curiosity. pons@rockit.it
	**********************************************************************

	when you have a big mysql db it happens that you have to run
	optimize command on any tables and also repair command.

	this class allow to "schedule" those commands.

	this class saves the date when optimization run and
	any time it's called it checks the last time that the
	optimization has runned. If it's passed enaugh time since
	last optimization then runs the optimization on the tables of
	the db.

	you can call this code any time but it will not slow down
	your pages cause it really works only when enaugh time is passed
	since last optimization.

*/


class ScheduledFixDB {
	var $whereToSaveLast;
	var $fixDbLogFile;
	var $dbName;
	var $verbose;
	
	function lastfix() {
		if(file_exists($this->fixDbLogFile)) {
			return date("Y-m-d H:i:s",loadTemplate($this->fixDbLogFile));
		} else return "n.d.";
	}

	function __construct($dbname,$filename="fixdb.log") {
		$this->whereToSaveLast="";
		$this->fixDbLogFile=$filename;
		$this->timeToPass = 3600 * 24; //in seconds
		$this->dbName=$dbname;
		$this->verbose=true;
	}

	function fixTables() {
		global $conn;
		$result = $conn->query("SHOW TABLES FROM ".$this->dbName) or trigger_error($conn->error);

		while ($row = $result->fetch_row()) {
			$sql = "OPTIMIZE TABLE $row[0]";
			$conn->query($sql);
			if ($this->verbose) echo "$sql<br>";
			$sql = "REPAIR TABLE $row[0]";
			$conn->query($sql);
			if ($this->verbose) echo "$sql<br>";

		}
	}

	function saveDate() {
		if ($this->verbose) echo $this->fixDbLogFile." write<br>";
		$f = @fopen($this->fixDbLogFile,'w');
		$d = time();
		@fwrite($f,$d);
	}
	function loadDate() {
		if (file_exists($this->fixDbLogFile)) {
			if ($this->verbose) echo $this->fixDbLogFile." read<br>";
			$f = @fopen ($this->fixDbLogFile, "r");
			$d = @fread ($f, @filesize($this->fixDbLogFile));
			@fclose ($f);
			return $d;
		} else { return -1; }
	}
	function checkAndFix() {
		/*
			check when last fixTables has runned,
			if necessary launch fixTables.
		*/
		if ($this->verbose) echo "check<br>";

		$lastfix = $this->loadDate();
		if (time() - $lastfix > $this->timeToPass) {
			if ($this->verbose) echo "fix db<br>";
			/*
				this script runs only if has passed $this->timeToPass seconds since last time
				this script has runned.
			*/
			$this->fixTables();
			$this->saveDate();
		} else if ($this->verbose) echo "db already fixed<br>";

	}
}

/*
	usage:

	$f = new scheduledfixdb("DBNAME");
	$f->checkAndFix();

*/
?>