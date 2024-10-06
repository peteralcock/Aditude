<?php
/*
	class for user object
*/

class User
{

	var $id;
var $MAX_USER_LEVEL;

	function __construct ($id="",$max_user_level=999999) {

		$this->id=$id;
		$this->MAX_USER_LEVEL=$max_user_level;
	}

	function setProfilo($sid) {
		global $conn;
		// 10 = user
		// 20 = administrator
		$id = (integer)$this->id;
		$sql="";
		$sql = "delete from ".DB_PREFIX."frw_ute_fun where idutente='$id' and fl_automatic=1;";
		//echo $sql;
		$conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$sql = "select * from ".DB_PREFIX."frw_profili_funzionalita where cd_profilo='$sid'";
		//echo $sql;
		$rs =  $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		while ($r=$rs->fetch_array()) {
			$sql2 = "insert into ".DB_PREFIX."frw_ute_fun (idutente,idfunzionalita,idmodulo) values('$id','{$r['cd_funzionalita']}','{$r['cd_modulo']}');";
			//echo $sql2;
			$rs2 = $conn->query ($sql2);
		}

	}

	
	/*
		read and write and delete of manual permission on a single user
	*/
	function getManualPermission($id_user,$funzionalita) {
		$sql = "select 1 FROM `".DB_PREFIX."frw_funzionalita` inner join ".DB_PREFIX."frw_ute_fun on idfunzionalita=`".DB_PREFIX."frw_funzionalita`.id WHERE `".DB_PREFIX."frw_funzionalita`.nome='".addslashes($funzionalita)."' and idutente='".$id_user."'";
		return execute_scalar($sql,"0");
	}
	function setManualPermission($id_user,$funzionalita,$idmodulo=18) {
		global $conn;
		$idfunzionalita = execute_scalar( "select id from `".DB_PREFIX."frw_funzionalita` where nome='".addslashes($funzionalita)."'","-1");
		$sql = "insert ignore into `".DB_PREFIX."frw_ute_fun` (idutente,idfunzionalita,idmodulo,fl_automatic) values ('".$id_user."','".$idfunzionalita."','".$idmodulo."',0)";
		$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
	}
	function removeManualPermission($id_user,$funzionalita) {
		global $conn;
		$idfunzionalita = execute_scalar( "select id from `".DB_PREFIX."frw_funzionalita` where nome='".addslashes($funzionalita)."'","-1");
		$sql = "delete from `".DB_PREFIX."frw_ute_fun`where idutente='".$id_user."' and idfunzionalita='".$idfunzionalita."'";
		$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
	}




	function getProfilo($id,$maxid="") {
		// profile list options
		global $session,$conn;
		if ($maxid=="") $maxid=$id;
		$sql= "select * from ".DB_PREFIX."frw_profili where id_profilo<='".$session->get("idprofilo")."' order by id_profilo asc";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$html="";
		while ($r=$rs->fetch_array()) {
			$html.="<option value=\"$r[id_profilo]\"";
			if ($r["id_profilo"]==$id) $html.=" selected";
			$html.=">$r[de_label]";
			$html.="</option>";
		}
		return $html;

	}

	function profiloEditabile($idprof) {
		/*
			true if user can edit
		*/
		global $session,$conn;
		$sql= "select chiedita from ".DB_PREFIX."frw_profili where id_profilo='".$session->get("idprofilo")."'";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$r=$rs->fetch_array();
		return stristr ( $r['chiedita'], ",".$idprof."," );
	}

	function getChiEdita($idprof) {
		/*
			returns the editable profiles
		*/
		global $session,$conn;
		$sql= "select chiedita from ".DB_PREFIX."frw_profili where id_profilo='".$session->get("idprofilo")."'";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$r=$rs->fetch_array();
		return $r['chiedita'];
	}

	function getUserData() {
		// get user data from db
		return execute_row( "select * from ".DB_PREFIX."frw_utenti where id='$this->id'" );
	}

	function existUserWithUsername($username,$notme="") {
		// check if username "not me" exists
		global $conn;

		$sql= "select username from ".DB_PREFIX."frw_utenti where username='$username'";
		if ($notme!="") {
			$sql.=" and id<>'$notme'";
		}
		$rs = $conn->query ($sql);
		if ($rs->num_rows>0) return true; else return false;
	}

	function existUserWithEmail($email,$notme="") {
		// check if email "not me" exists
		global $conn;

		$sql= "select de_email from ".DB_PREFIX."frw_extrauserdata where de_email='".$email."'";
		if ($notme!="") {
			$sql.=" and cd_user<>'$notme'";
		}
		$rs = $conn->query ($sql);
		if ($rs->num_rows>0) return true; else return false;
	}


	function getArrayUtenti($profilicsv = "") {
		global $conn;
		$where = $profilicsv==""? "" : " where cd_profilo in ( {$profilicsv}) and fl_attivo=1";
		$sql = "select id,concat(cognome,' ',nome) as cognomenome from ".DB_PREFIX."frw_utenti {$where} order by cognome";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$arUtenti = array();
		while($riga = $rs->fetch_array()) {
			$arUtenti[$riga['id']]=$riga['cognomenome'];
		}
		return $arUtenti;
	}
}

?>