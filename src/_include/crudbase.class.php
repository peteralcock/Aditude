<?php
/*
    generic class for c.r.u.d. behaviour
*/

class CrudBase {

	var $tbdb;	//table

	var $start;	// start from...
	var $omode;	// order mode asc|desc
	var $oby;	// order by field
	var $ps;	// page size

	var $gestore;

	protected $ambiente;	// ambiente object for output

	function __construct ($tbdb,$ps,$oby,$omode,$start) {

        global $session,$root;
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;

		// setVariabile used GET > POST > SESSION > default value
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);

		// save values in session
		if(isset($_GET['combotipo'])) $session->register($this->tbdb."combotipo",$_GET['combotipo']);
		if(isset($_GET['combotiporeset'])) $session->register($this->tbdb."combotiporeset",$_GET['combotiporeset']);

	}

	public function setAmbiente($ambiente) {
		$this->ambiente = $ambiente;
	}


}