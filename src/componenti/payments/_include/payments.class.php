<?php
/*
	Payments log class - these are payments to webmasters
*/

class Payments {

	var $tbdb;	//table

	var $start;	// start row
	var $omode;	// order asc|desc
	var $oby;	// order by field
	var $ps;	// page size

	var $linkmodifica;	// link to edit
	var $linkmodifica_label;
	var $linklog;		// link to view log
	var $linklog_label;

	var $gestore;


	function __construct ($tbdb="7banner_payments",$ps=20,$oby="id_payment",$omode="desc",$start=0) {
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

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_payment##";
		$this->linkmodifica_label = "modifica";
		$this->linklog = "$this->gestore?op=modifica&id=##id_payment##";
		$this->linklog_label = "log";

		checkAbilitazione("PAYMENTS","SETTA_SOLO_SE_ESISTE");

	}

	/*
		show payments to webmaster grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("PAYMENTS")) {
			if($combotiporeset=='reset') {
				// if changed with filter select
				// reset pagination
				$this->start = 0;
			}

			//
			// reactivate filter after save and cancel
			if($combotipo=="" && $combotiporeset=="") {
				$combotipo= setVariabile("combotipo",$combotipo,$this->tbdb);
				$combotiporeset=setVariabile("combotiporeset",$combotipo,$this->tbdb);
				$GLOBALS['combotipo']=$combotipo;
				$GLOBALS['combotiporeset']=$combotiporeset;
			}

			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=false;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			if ($session->get("idprofilo")==10 ) {
				/* webmaster view */
				$t->campi="id_payment,nu_import_webmaster,dt_quando,fl_stato";
				$t->titoli="{Payment ID},{Import},{Date},{Status}";
				$t->query="SELECT id_payment,nu_import_webmaster,dt_quando, fl_stato from ".DB_PREFIX.$this->tbdb." ";
			} else {
				/* administrator view */
				$t->campi="id_payment,webmaster,nu_import_webmaster,dt_quando,fl_stato";
				$t->titoli="{Payment ID},{Webmaster},{Import},{Date},{Status}";
				$t->query="SELECT id_payment,nu_import_webmaster,CONCAT(nome,' ',cognome) as webmaster, dt_quando, fl_stato from ".DB_PREFIX.$this->tbdb." left outer join ".DB_PREFIX."frw_utenti on cd_webmaster=".DB_PREFIX."frw_utenti.id ";
			}

			// key field id for links
			$t->chiave="id_payment";

			$where = " 1=1 ";
			if ($session->get("idprofilo")==10 ) {
				$where .= " AND cd_webmaster='".$session->get("idutente")."' ";
			}
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" fl_stato = '".$combotipo."' ";	
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (nome like '%{$keyword}%' OR cognome like '%{$keyword}%' )";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			if ($session->get("idprofilo")==10 ) {
				$t->addComando($this->linklog,$this->linklog_label,"{Log}");
			}
			if ($session->get("idprofilo")>=20 ) {
				$t->addComando($this->linkmodifica,$this->linkmodifica_label,"{Edit}");
			}
			$t->addCampiDate("dt_quando", DATEFORMAT);
			$t->addCampi("nu_import_webmaster","show_payment_value");
			$t->addScegliDaInsieme("fl_stato",
				array(
					"1"=>"<span class='labelgreen'>{PAID}</span>",
					"0"=>"<span class='labelred'>{NOT PAID}</span>"
				)
			);
			$t->arFormattazioneTD=array(
				"nu_import_webmaster" => "numero",
			);

			$texto = $t->show();

			if (trim($texto)=="") {
				if ($session->get("idprofilo")==10 ) {
					/* webmaster message */
					$texto="{No records. It seems you have not earned any money.}";
				} else {
					/* administrator message */
					$texto="{No records. It seems you don't have to pay anybody.}";
				}
			}

			$html .= $texto."<br/>";

		} else {

			$html = "0";
		}
		return $html;
	}


	/*
		show campaign detail form
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$root,$conn;

		$bodyclass="";
		if($session->get("idprofilo")>=20) $bodyclass .='admin ';
		if($session->get("idprofilo")==10) $bodyclass.='webmaster ';

		if ($session->get("PAYMENTS")) {
			if ($id!="") {
				/*
					modify
				*/
				$dati = $this->getDati($id);
				$action = "modificaStep2";

			} else {
				/*
					insery
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$action = "aggiungiStep2";

			}


			$html = loadTemplateAndParse("template/dettaglio.html");

			$nomewebmaster = execute_scalar("select CONCAT(nome,' ',cognome) from ".DB_PREFIX."frw_utenti where id='".$dati['cd_webmaster']."'");

			// building the form
			$objform = new form();

			$id_obj = new hidden("id",$dati["id_payment"]);
			$op = new hidden("op",$action);

			$stati = array("0"=>"{NOT PAID}" ,"1"=>"{PAID}");

			$fl_stato = new optionlist("fl_stato",(($dati["fl_stato"])), $stati );
			$fl_stato->obbligatorio=0;
			$fl_stato->label="'{Status}'";

			$html = str_replace("##ADMINLABEL##", translateHtml("{Administrator}"), $html);
			$html = str_replace("##WEBMASTERLABEL##", translateHtml("{Webmaster}"), $html);
			$html = str_replace("##bodyclass##", $bodyclass, $html);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##idpagamento##", $dati['id_payment'], $html);
			if(DATEFORMAT == "mm/dd/yyyy") $format = "m/d/Y";
			if(DATEFORMAT == "dd/mm/yyyy") $format = "d/m/Y";
			$html = str_replace("##data##", date(  $format, strtotime( $dati['dt_quando'] ) ), $html);
			if($dati['fl_stato']  == 0 && $session->get("idprofilo")>=20){

				if( $dati['nu_import_webmaster'] == 0) {
					$html = str_replace("##fl_stato##", ($dati['fl_stato']==1 ? "{PAID}" : "{NOT PAID}" ), $html);
				} else {
					$html = str_replace("##fl_stato##", $fl_stato->gettag(), $html);
				}
			}
			if($dati['fl_stato']  == 1 && $session->get("idprofilo")>=20){
				$html = str_replace("##fl_stato##", ($dati['fl_stato']==1 ? "{PAID}" : "{NOT PAID}" ), $html);
			}
			if($session->get("idprofilo")==10) $html = str_replace("##stato##", ($dati['fl_stato']==1 ? "{PAID}" : "{NOT PAID}" ), $html);
			$html = str_replace("##adminmoney##", number_format($dati['nu_import_admin'],2,".",","), $html);
			$html = str_replace("##total##", number_format( $dati['nu_import_webmaster'] + $dati['nu_import_admin'] , 2 ,".",","), $html);
			$html = str_replace("##totalwebmaster##", number_format($dati['nu_import_webmaster'],2,".",","), $html);
			$html = str_replace("##MONEY##", MONEY, $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##nomewebmaster##", $nomewebmaster, $html);
			$html = str_replace("##de_log##", $dati['de_log'], $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);


		} else {
			$html = "0";
		}
		return $html;
	}

	function getDati($id) {
		$sql = "SELECT * from ".$this->tbdb." where id_payment='{$id}'";
		return execute_row($sql);
	}


	// in:
	// arDati--> array _POST from the form
	// files --> array _FILES
	// result:
	//	"" --> ok
	//  "0" --> no permission	
	function updateAndInsert($arDati,$files) {
		global $session,$conn;
		if ($session->get("PAYMENTS")) {

			/*
				prima di salvare verifico che non ci sia 
				un altro utente con lo stesso username o con la stessa email.
			*/

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modifica
				*/

				$paidlog = translateHtml("{Administrator has paid the webmaster}");

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					fl_stato='##fl_stato##',
					de_log= CONCAT(de_log,'".addslashes($paidlog)."')
					where id_payment='##id##'";
				$sql= str_replace("##fl_stato##",$arDati["fl_stato"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$html= "ok|".$id;

				$this->notifyWebmasterPaid( $id );
				

			}


		} else {
			$html="0";	
		}
		return $html;
	}




	/* send an email to webmaster for payment */
	function notifyWebmasterPaid( $id_payment ) {
		$obj = $this->getDati($id_payment);
		$message = nl2br( translateHtml( "<p>{Hi,<br>Administrator sent you %M.<br>Bye.}</p>" ) );
		$message = str_replace("%M", MONEY.number_format($obj['nu_import_webmaster'],2,".",","), $message);
		$subject = "[".SERVER_NAME."] ". translateHtml("{A new payment for you!}");
		$sql = "select de_email from frw_utenti inner join frw_extrauserdata on cd_user=id where cd_user='".$obj['cd_webmaster']."'";
		$de_email = execute_scalar($sql);
		mail_utf8(
			$de_email,
			$subject,
			$message);
	}


	function getHtmlcombotipo($def="") {
		global $conn,$session;
		//------------------------------------------------
		//combo filter
		$sql = "select fl_stato as A,count(*) as c from ".DB_PREFIX.$this->tbdb;
		if ($session->get("idprofilo")==10 ) {
			$sql .= " WHERE cd_webmaster='".$session->get("idutente")."' ";
		}
		$sql.=" group by fl_stato";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			if ($riga['A']=="") $riga['c']=0;
			$arFiltri[$riga['A']]= ($riga['A'] ? "{PAID}" : "{NOT PAID}")." (".$riga['c'].")";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}



}

?>