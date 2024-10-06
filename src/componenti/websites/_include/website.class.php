<?php
/*
	class to handle webistes
*/

class Website {

	var $tbdb;	//table

	var $start;	// start row
	var $omode;	// order asc|desc
	var $oby;	// order by field
	var $ps;	// page size

	var $linkaggiungi;	// link to add

	var $linkeliminamarcate;	//link to delete

	var $linkmodifica;	// link to edit
	var $linkmodifica_label;

	var $gestore;


	function __construct ($tbdb="7banner_sites",$ps=20,$oby="id_sito",$omode="desc",$start=0) {
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


		// link above in the panel
		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_sito##";
		$this->linkmodifica_label = "modifica";


		checkAbilitazione("WEBSITES","SETTA_SOLO_SE_ESISTE");

	}

	/*
		websites grid list
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("WEBSITES")) {
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
			
			$t->checkboxForm= $session->get("idprofilo")==5 ? false : true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);


			// key field id for links
			$t->chiave="id_sito";

			if ($session->get("idprofilo") == 5) {
				// advertiser view
				
				$t->campi="de_nomesito,de_text,de_urlsito,fl_status,q";
				$t->titoli="{Site name},{Description},{Site url},{Status},{Active ads}";
				$t->query="SELECT id_sito,A.de_nomesito,A.de_text,A.de_urlsito,A.fl_status,
						(select count(1) from ".DB_PREFIX."7banner_posizioni inner join ".DB_PREFIX."7banner on cd_posizione=id_posizione where cd_sito=id_sito 
							and ".DB_PREFIX."7banner.fl_stato NOT IN ( 'S','D')
						) as q
						from ".$this->tbdb." as A 
					";
				$where = " 1=1";


			} elseif ($session->get("idprofilo") == 10) {
				// webmaster view

				$t->campi="id_sito,de_nomesito,de_urlsito,fl_status,q,q1";
				$t->titoli="{Site ID},{Site name},{Site url},{Status},{Active ads},{Pending ads}";
				$t->query="SELECT A.id_sito,A.de_nomesito,A.de_urlsito,A.fl_status,
						(select count(1) from ".DB_PREFIX."7banner_posizioni inner join ".DB_PREFIX."7banner on cd_posizione=id_posizione where cd_sito=id_sito 
							and ".DB_PREFIX."7banner.fl_stato NOT IN ( 'S','D', 'K')
						) as q,
						(select count(1) from ".DB_PREFIX."7banner_posizioni inner join ".DB_PREFIX."7banner on cd_posizione=id_posizione where cd_sito=id_sito 
							and ".DB_PREFIX."7banner.fl_stato IN ( 'K')
						) as q1
						from ".$this->tbdb." as A 
						
					";
				$where = " cd_webmaster= '" . $session->get("idutente") . "'";

			} else {
				// administrator view

				$t->campi="id_sito,de_nomesito,de_urlsito,webmaster,fl_status,q,q1";
				$t->titoli="{Site ID},{Site name},{Site url},{Webmaster},{Status},{Active ads},{Pending ads}";
				$t->query="SELECT A.id_sito,A.de_nomesito,A.de_urlsito,CONCAT(U.nome,' ',U.cognome) as webmaster,A.fl_status, (select count(1) from ".DB_PREFIX."7banner_posizioni inner join ".DB_PREFIX."7banner on cd_posizione=id_posizione where cd_sito=id_sito 
							and ".DB_PREFIX."7banner.fl_stato NOT IN ( 'S','D','K')
						) as q,
						(select count(1) from ".DB_PREFIX."7banner_posizioni inner join ".DB_PREFIX."7banner on cd_posizione=id_posizione where cd_sito=id_sito 
							and ".DB_PREFIX."7banner.fl_stato IN ( 'K')
						) as q1
						from ".$this->tbdb." as A 
						left outer join ".DB_PREFIX."frw_utenti U ON cd_webmaster=U.id
					";
				$where = " 1=1 ";
			}

			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" A.fl_status='".$combotipo."'";
				}
			}

			if($keyword) {
				if($where!="") { $where.= " and "; }
				if ($session->get("idprofilo") == 5) {
					$where.="  (  (A.de_nomesito like '%{$keyword}%') OR (A.de_text like '%{$keyword}%') )";
				} elseif($session->get("idprofilo") == 10) {
					$where.="  ( (A.de_nomesito like '%{$keyword}%') OR (A.de_text like '%{$keyword}%') )";
				} else {
					$where.="  ( (A.de_nomesito like '%{$keyword}%') OR (webmaster like '%{$keyword}%') OR (A.de_text like '%{$keyword}%') )";
				}
			}
			if($where) {
				$t->query.=" where {$where}";
			}
			//$t->debug=true;

			$t->addScegliDaInsieme("fl_status",
				array(
					"1"=>"<span class='labelgreen'>{ON}</span>",
					"0"=>"<span class='labelred'>{OFF}</span>",
				)
			);
			$t->arFormattazioneTD=array(
				"q" => "numero",
				"q1" => "numero",
			);

			$t->addCampi('de_urlsito',"show_website_link");
			$t->addCampi('de_nomesito',"show_website_name");


			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

		} else {

			$html = "0";
		}
		return $html;
	}


	/*
		form to edit detail of website
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$conn;

		if ($session->get("WEBSITES") && $session->get("idprofilo")>=10) {
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
				//$dati["id_author"] = "";
				$dati["fl_status"] = 1;
				$dati["nu_share"] = 50;

				$action = "aggiungiStep2";

			}

			$html = loadTemplateAndParse("template/dettaglio.html");

			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if($session->get("idprofilo")==10) $bodyclass.='webmaster';


			$objform = new form();

			//------------------------------------------------
			//combo webmasters
			$sql = "select id,CONCAT(nome,' ',cognome) as nomewebmaster from ".DB_PREFIX."frw_utenti where cd_profilo=10 ".
				($session->get("idprofilo")==10 ? " and id ='".$session->get("idutente")."'" : "")
				."order by nomewebmaster";
			$cd_webmaster = new optionlist("cd_webmaster",($dati["cd_webmaster"]),array());
			$cd_webmaster->loadSqlOptions( $sql, "id", "nomewebmaster", ($session->get("idprofilo")==10 ? "" : "{choose}"));
			$cd_webmaster->obbligatorio= 1;
			$cd_webmaster->label="'{Webmaster}'";



			$nu_share = new numerointero("nu_share",$dati["nu_share"],3,5);
			$nu_share->obbligatorio=1;
			$nu_share->label="'{Revenue share %}'";
			$nu_share->attributes.=' style="text-align:right" class="small"';
			$objform->addControllo($nu_share);
			if($session->get("idprofilo")==10) {
				// nothing
				$nu_share->attributes.=' readonly="readonly"';
			} else {
				$objform->addControllo($nu_share, "nu_share.value < 0 || nu_share.value > 100", "{Revenue share % must be between 1 and 100}");
			}
			
			$de_text = new areatesto("de_text",(($dati["de_text"])),5,80);
			$de_text->obbligatorio=0;
			$de_text->label="'{Description}";
			$de_text->maxlimit = 350;
			$objform->addControllo($de_text);


			$objform->addControllo($cd_webmaster);
			//------------------------------------------------

			if($dati['fl_status']=='1') 
				$stati = array("1"=>"{ON}" ,"0"=>"{OFF}"); 
			else $stati= array("1"=>"ON" ,"0"=>"OFF");
			$fl_status = new optionlist("fl_status",$dati["fl_status"],$stati );
			$fl_status->obbligatorio=1;
			$fl_status->label="'{Status}'";
			$objform->addControllo($fl_status);

			$de_nomesito = new testo("de_nomesito",$dati["de_nomesito"],100,50);
			$de_nomesito->obbligatorio=1;
			$de_nomesito->label="'{Site name}'";
			$objform->addControllo($de_nomesito);

			$de_urlsito = new testo("de_urlsito",$dati["de_urlsito"],100,50);
			$de_urlsito->obbligatorio=1;
			$de_urlsito->label="'{Site url}'";
			$objform->addControllo($de_urlsito);

			$id_obj = new hidden("id",$dati["id_sito"]);
			$op = new hidden("op",$action);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##fl_status##", $fl_status->gettag(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##cd_webmaster##", $cd_webmaster->gettag(), $html);
			$html = str_replace("##de_urlsito##", $de_urlsito->gettag(), $html);
			$html = str_replace("##de_nomesito##", $de_nomesito->gettag(), $html);
			$html = str_replace("##de_text##", $de_text->gettag(), $html);
			$html = str_replace("##nu_share##", $nu_share->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##bodyclass##", $bodyclass, $html);


		} else {
			$html = "0";
		}
		return $html;
	}

	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX.$this->tbdb." where id_sito='{$id}'");
	}


	// in:
	// arDati--> array _POST from the form
	// files --> array _FILES
	// result:
	//	"" --> ok
	//  "0" --> no permission	
	function updateAndInsert($arDati,$files) {

		global $session,$conn;
		if ($session->get("WEBSITES")) {

			// check: advertiser can't edit websites
			if($session->get("idprofilo")==5) {
				return "0";
			}


			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_nomesito='##de_nomesito##',
					de_urlsito='##de_urlsito##',
					cd_webmaster='##cd_webmaster##', 
					fl_status='##fl_status##',
					de_text='##de_text##'
					".($session->get("idprofilo")>=20 ? ", nu_share='##nu_share##'" : "") ."
					where id_sito='##id##'";
				$sql= str_replace("##cd_webmaster##",$arDati["cd_webmaster"],$sql);
				$sql= str_replace("##de_nomesito##",$arDati["de_nomesito"],$sql);
				$sql= str_replace("##de_urlsito##",$arDati["de_urlsito"],$sql);
				$sql= str_replace("##fl_status##",$arDati["fl_status"],$sql);
				$sql= str_replace("##nu_share##",$arDati["nu_share"],$sql);
				$sql= str_replace("##de_text##",strip_tags($arDati["de_text"]),$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (cd_webmaster,de_nomesito,de_urlsito,fl_status,de_text,nu_share) values('##cd_webmaster##','##de_nomesito##','##de_urlsito##','##fl_status##','##de_text##','##nu_share##')";
				$sql= str_replace("##cd_webmaster##",$arDati["cd_webmaster"],$sql);
				$sql= str_replace("##de_nomesito##",$arDati["de_nomesito"],$sql);
				$sql= str_replace("##de_urlsito##",$arDati["de_urlsito"],$sql);
				$sql= str_replace("##nu_share##",$arDati["nu_share"],$sql);
				$sql= str_replace("##de_text##",strip_tags($arDati["de_text"]),$sql);
				$sql= str_replace("##fl_status##",$arDati["fl_status"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				$html= "ok|".$id;

			}



		} else {
			$html="0";		//no permission
		}
		return $html;
	}


	function getHtmlcombotipo($def="") {
		global $conn;
		//------------------------------------------------
		//combo filter
		$sql = "select fl_status as A,count(*) as c from ".DB_PREFIX.$this->tbdb." group by fl_status";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			if ($riga['A']=="") $riga['c']=0;
			$arFiltri[$riga['A']]= ($riga['A'] == 0 ? "OFF" : "ON")." (".$riga['c'].")";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}


	function deleteItem($id) {
		// in:
		// id --> id of table row to delete
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "-2" --> there are connected items
		global $session,$conn,$root;
		if ($session->get("WEBSITES") && $session->get("idprofilo")>=10) {

			$q = execute_scalar("select count(1) from ".DB_PREFIX."7banner_posizioni 
				where cd_sito='".$id."'");

			if( $q==0) {

				$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_sito='$id' ".
					($session->get("idprofilo")==10 ? " and cd_webmaster='".($session->get("idutente"))."' " : "");
				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
			
				$html = "";
			} else {
				$html = "-2";
			}

		} else {
			$html="0";		//no permission
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		// in:
		// _POST['gridcheck'] --> ids of table row to delete
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "-2" --> there are connected items

		global $session;
		if ($session->get("WEBSITES")) {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$risultato = $this->deleteItem($p[$i]);
				if($risultato!="") return $risultato;
			}
			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}


}

?>