<?php
/*
	class to manage campaigns
*/

class Campagne extends CrudBase{

	var $linkaggiungi;	// link to add
	var $linkmodifica;	// link to edit
	var $linkmodifica_label;
	var $linkeliminamarcate;	//link to delete

	function __construct ($tbdb="7banner_campagne",$ps=20,$oby="id_campagna",$omode="desc",$start=0) {
		global $session,$root;

		parent::__construct($tbdb,$ps,$oby,$omode,$start);

		// link above in the panel
		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_campagna##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("CAMPAGNE","SETTA_SOLO_SE_ESISTE");

	}

	// for advertiser
	function getIdCliente() {
		global $session;
		return execute_scalar("select id_cliente from ".DB_PREFIX."7banner_clienti where cd_utente='".$session->get("idutente")."'");
	}

	/*
		show campaigns grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("CAMPAGNE")) {
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
			
			if($session->get("idprofilo")==5 && PAYMENTS!="ON") $t->checkboxForm= false;
				else $t->checkboxForm= true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);


			// key field id for links
			$t->chiave="id_campagna";


			// SQL
			if ($session->get("idprofilo") == 5) {

				$t->campi="id_campagna,de_titolo,fl_status,q,v";
				$t->titoli="{Campaign ID},{Campaign title},{Status},{Number of banners},{Campaign value}";
				$t->query="SELECT A.id_campagna,A.de_titolo,B.de_nome,fl_status,(SELECT count(*) from ".DB_PREFIX."7banner WHERE cd_campagna=id_campagna) q,
						(SELECT SUM(nu_price) from ".DB_PREFIX."7banner WHERE cd_campagna=id_campagna) v
						from ".$this->tbdb." as A 
						inner join ".DB_PREFIX."7banner_clienti as B on cd_cliente = id_cliente and id_cliente='".$this->getIdCliente()."'
					";

			} else {
				$t->campi="id_campagna,de_titolo,de_nome,fl_status,q,v";
				$t->titoli="{Campaign ID},{Campaign title},{Client name},{Status},{Number of banners},{Campaign value}";
				$t->query="SELECT A.id_campagna,A.de_titolo,B.de_nome,fl_status,(SELECT count(*) from ".DB_PREFIX."7banner WHERE cd_campagna=id_campagna) q,
						(SELECT SUM(nu_price) from ".DB_PREFIX."7banner WHERE cd_campagna=id_campagna) v
						from ".DB_PREFIX."".$this->tbdb." as A 
						inner join ".DB_PREFIX."7banner_clienti as B on cd_cliente = id_cliente
					";
			}

			$where = " 1=1 ";
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" A.cd_cliente='".$combotipo."'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				if ($session->get("idprofilo") == 5) {
					$where.="  ((A.de_titolo like '%{$keyword}%'))";
				} else {
					$where.="  ((A.de_titolo like '%{$keyword}%') OR (B.de_nome like '%{$keyword}%'))";
				}
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->addScegliDaInsieme("fl_status",
				array(
					"1"=>"<span class='labelgreen'>{ON}</span>",
					"0"=>"<span class='labelred'>{OFF}</span>",
					"2"=>"<span class='labelred'>{TOTALLY OFF}</span>"
				)
			);
			$t->arFormattazioneTD=array(
				"q" => "numero",
				"v" => "numero",
			);
			$t->addCampi("v","show_campaign_value");
			$t->addCampi("de_titolo","show_campaign_title");
			$t->addComando("../7banner/index.php?op=stats&combobanner=-999|##id_campagna##","stats","{Stats}");

			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";



			// if payments are not active, advertiser can't edit or create campaigns
			if($session->get("idprofilo")==5 && PAYMENTS!="ON") {
				$bottoni1 = "";
				$bottoni2 = "";
			} else {
				$bottoni1 = "<a href=\"".$this->linkaggiungi."\" title=\"{Add new item}\" class='aggiungi'></a>";
				$bottoni2 = "<a href=\"".$this->linkeliminamarcate."\" title=\"{Delete selected items}\" class='elimina'></a>";
			}

			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if($session->get("idprofilo")==5) $bodyclass.='advertiser';

			//
			// template filling
			$this->ambiente->setTemplate("template/elenco.html");
			$this->ambiente->setKey("##bodyclass##", $bodyclass);
			$this->ambiente->setKey("##corpo##", $html );
			$this->ambiente->setKey("##keyword##", $keyword);
			$this->ambiente->setKey("##bottoni1##",$bottoni1);
			$this->ambiente->setKey("##bottoni2##",$bottoni2);
			$this->ambiente->setKey("##combotipo##", $this->getHtmlcombotipo($combotipo));


		} else {

			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}	

	}


	/*
		show campaign detail form
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$conn;

		// if payments are not active the advertiser can't
		// create or edit campaigns
		if($session->get("idprofilo")==5 && PAYMENTS!="ON") {
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}

		if ($session->get("CAMPAGNE")) {
			if ($id!="") {
				/*
					modify
				*/
				$dati = $this->getDati($id);

				$action = "modificaStep2";



			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$dati["fl_status"] = 1;
				$action = "aggiungiStep2";

			}

			

			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if($session->get("idprofilo")==5) $bodyclass.='advertiser';


			// form construction
			$objform = new form();

			//------------------------------------------------
			//combo clients
			$sql = "select id_cliente,de_nome from ".DB_PREFIX."7banner_clienti ".
				($session->get("idprofilo")==5 ? " where id_cliente='".$this->getIdCliente()."'" : "")
				."order by de_nome";
			$rs = $conn->query($sql) or die($conn->error.$sql);
			if($rs->num_rows > 1 || $rs->num_rows == 0) $ar[""]="--{choose}--";
			while($riga = $rs->fetch_array()) $ar[$riga['id_cliente']]=$riga['de_nome'];
			$cd_cliente = new optionlist("cd_cliente",($dati["cd_cliente"]),$ar);
			$cd_cliente->obbligatorio=1;
			$cd_cliente->label="'{Client}'";
			$objform->addControllo($cd_cliente);
			//------------------------------------------------

			if($dati['fl_status']=='1') 
				$stati = array("1"=>"{ON}" ,"0"=>"{OFF}","2"=>"{TOTALLY OFF}"); 
			else $stati= array("1"=>"ON" ,"0"=>"OFF");
			$fl_status = new optionlist("fl_status",$dati["fl_status"],$stati );
			$fl_status->obbligatorio=1;
			$fl_status->label="'{Status}'";
			$objform->addControllo($fl_status);

			$de_titolo = new testo("de_titolo",$dati["de_titolo"],50,50);
			$de_titolo->obbligatorio=1;
			$de_titolo->label="'{Campaign title}'";
			$objform->addControllo($de_titolo);

			$id_obj = new hidden("id",$dati["id_campagna"]);
			$op = new hidden("op",$action);
			


			// template filling
			$this->ambiente->setTemplate("template/dettaglio.html");
			$this->ambiente->setKey("##STARTFORM##", $objform->startform());
			$this->ambiente->setKey("##id##", $id_obj->gettag());
			$this->ambiente->setKey("##op##", $op->gettag());
			$this->ambiente->setKey("##fl_status##", $fl_status->gettag());
			$this->ambiente->setKey("##cd_cliente##", $cd_cliente->gettag(),);
			$this->ambiente->setKey("##de_titolo##", $de_titolo->gettag(),);
			$this->ambiente->setKey("##gestore##", $this->gestore,);
			$this->ambiente->setKey("##ENDFORM##", $objform->endform(),);
			$this->ambiente->setKey("##bodyclass##", $bodyclass,);


		} else {
			
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}
	}

	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX.$this->tbdb." where id_campagna='{$id}'");
	}


	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// files --> array _FILES

		global $session,$conn;
		if ($session->get("CAMPAGNE")) {

			// check for payments active for advertiser
			if($session->get("idprofilo")==5 && PAYMENTS!="ON") {
				return "0";
			}

			// constrain client for advertiser
			if($session->get("idprofilo")==5) $arDati['cd_cliente'] = $this->getIdCliente();

	

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/
				if($arDati["fl_status"] == 2) {
					$arDati["fl_status"] = 0;
					$sql = "update ".DB_PREFIX."7banner set fl_stato='S' where cd_campagna='".$arDati["id"]."';";
					$conn->query($sql) or die($conn->error.$sql);
				}

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_titolo='##de_titolo##',
					cd_cliente='##cd_cliente##', 
					fl_status='##fl_status##'
					where id_campagna='##id##'";
				$sql= str_replace("##cd_cliente##",$arDati["cd_cliente"],$sql);
				$sql= str_replace("##de_titolo##",$arDati["de_titolo"],$sql);
				$sql= str_replace("##fl_status##",$arDati["fl_status"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (cd_cliente,de_titolo,fl_status) values('##cd_cliente##','##de_titolo##','##fl_status##')";
				$sql= str_replace("##cd_cliente##",$arDati["cd_cliente"],$sql);
				$sql= str_replace("##de_titolo##",$arDati["de_titolo"],$sql);
				$sql= str_replace("##fl_status##",$arDati["fl_status"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				$html= "ok|".$id;

			}

			//
			// ok response
			if($arDati['op'] == "modificaStep2reload" || $arDati['op'] == "aggiungiStep2reload")
				$this->ambiente->loadMsg("{Done.}","load ".$_SERVER['SCRIPT_NAME']."?op=modifica&id={$id}", OK_MSG);
			else
				$this->ambiente->loadMsg("{Done.}","reload", OK_MSG);
				

		} else {
			
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}
		return $html;
	}


	function getHtmlcombotipo($def="") {
		global $conn, $session;
		//------------------------------------------------
		//combo filter
		$sql = "select id_cliente,de_nome,count(*) as c from ".DB_PREFIX.$this->tbdb."
					inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente 
					".($session->get("idprofilo")==5 ? "and id_cliente='".$this->getIdCliente()."'" : "" )."
					group by id_cliente";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");

		while($riga = $rs->fetch_array()) {
			if ($riga['id_cliente']=="") $riga['c']=0;
			$arFiltri[$riga['id_cliente']]= $riga['de_nome']." (".$riga['c'].")";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}


	function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare

		global $session,$conn,$root;
		if ($session->get("CAMPAGNE")) {

			$q = execute_scalar("select count(1) from ".DB_PREFIX."7banner 
				inner join ".DB_PREFIX."7banner_campagne on cd_campagna=id_campagna
				inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente
				where id_campagna='".$id."'");
			if($q > 0) {
				return "-2";
			}

			$sql="DELETE FROM ".DB_PREFIX."7banner_campagne where id_campagna='$id' ".
				($session->get("idprofilo")==5 ? " and cd_cliente='".$this->getIdCliente()."' " : "");
			$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");


			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST

		global $session;
		if ($session->get("CAMPAGNE")) {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$out = $this->deleteItem($p[$i]);
				if($out == "-2") {
					$html = "-2";
					break;
				}
			}

			//
			// ok response
			if($html=="-2") {
				$this->ambiente->loadMsg("{You can't delete a campaign with banners.}","jsback", ERR_MSG);
			} else 
				$this->ambiente->loadMsg("{Deleted.}","load ".$_SERVER['SCRIPT_NAME'], OK_MSG);
			
		} else {
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
	}


}