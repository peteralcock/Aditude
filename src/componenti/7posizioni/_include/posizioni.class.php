<?php
/*

	class to handle banner positions (zones)

*/

class Posizioni {

	var $tbdb;	//table name

	var $start;	// start row
	var $omode;	// order mode asc|desc
	var $oby;	// order by field
	var $ps;	// page size

	var $linkaggiungi;	
	var $linkeliminamarcate;	

	var $linkmodifica;	
	var $linkmodifica_label;

	var $gestore;


	function __construct ($tbdb="7banner_posizioni",$ps=20,$oby="id_posizione",$omode="desc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_posizione##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("POSIZIONI","SETTA_SOLO_SE_ESISTE");

	}

	/*
		show positions grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("POSIZIONI")) {
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
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			// fields
			$t->campi="id_posizione,de_posizione,de_nomesito,activeNow,v,c,modello_vendita";

			// titles
			$t->titoli="{Position ID},{Position name},{Website},{Ads serving now},<acronym title='{Daily avarage views on 7 days}'>{Daily views}</acronym>,<acronym title='{Daily avarage clicks on 7 days}'>{Daily clicks}</acronym>,{Sales model}";

			// key
			$t->chiave="id_posizione";

			// SQL
			//$t->debug = true;

			$t->query="SELECT id_posizione,de_posizione,de_nomesito,
			(SELECT count(1) FROM ".DB_PREFIX."7banner WHERE cd_posizione=id_posizione and fl_stato IN ('A','L') ) as activeNow,
			ROUND( sum(".DB_PREFIX."7banner_stats.nu_pageviews) / 7) as v, ROUND(sum(".DB_PREFIX."7banner_stats.nu_click)/7) as c, UPPER(modello_vendita) as modello_vendita
			FROM ".DB_PREFIX."7banner_posizioni  left outer join ".DB_PREFIX."7banner on cd_posizione=id_posizione
				left outer join  `".DB_PREFIX."7banner_stats` on `".DB_PREFIX."7banner_stats`.cd_banner=id_banner and 7banner_stats.id_day >= '".date("Y-m-d",strtotime("-7 days"))."'
				left outer join ".DB_PREFIX."7banner_sites on id_sito=cd_sito
				#where#
			group by id_posizione" ;
			
			$where = "1 = 1";

			if($session->get("idprofilo")==10) {
				if($where!="") { $where.= " and "; }
				$where.=" cd_webmaster='".$session->get("idutente")."'";
			}

			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" modello_vendita='".$combotipo."'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (de_posizione like '%{$keyword}%')";
			}
			$t->query = str_replace("#where#", " where {$where}",$t->query);
			
            $t->addCampi('de_posizione',"show_position_name");
			
			$t->arFormattazioneTD=array(
				"v" => "numero",			// daily views, mean on last 7 days 
				"c" => "numero",			// daily clicks, mean on last 7 days 
				"activeNow" => "numero"
			);

			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

		} else {

			$html = "0";
		}
		return $html;
	}


	/*
		position detail form
	*/
	function getDettaglio($id="") {
		global $session,$root,$conn;

		if ($session->get("POSIZIONI")) {
			if ($id!="") {
				/*
					modify
				*/
				$dati = $this->getDati($id);

				$action = "modificaStep2";
				if($dati["de_trigger"]=="") $dati["de_trigger"] = "p a, nav a, h2 a";
				if($dati["nu_timer"]=="") $dati["nu_timer"] = "5";


			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$dati["de_trigger"] = "p a, nav a, h2 a";
				$dati["nu_timer"] = "5";
				$dati["prezzo_vendita"] = "0";


				$action = "aggiungiStep2";

			}

			$html = loadTemplateAndParse("template/dettaglio.html");

			// construction form
			$objform = new form();

			//------------------------------------------------
			//combo sites
			$sql = "select id_sito,de_nomesito from ".DB_PREFIX."7banner_sites ".
				($session->get("idprofilo")==10 ? " where cd_webmaster ='".$session->get("idutente")."'" : "")
				."order by de_nomesito";
			$rs = $conn->query($sql) or trigger_error($conn->error." ".$sql);

			if($session->get("idprofilo")==10) {
				// webmaster
				if($rs->num_rows != 1) $ar[""]="--{choose}--";
			} else {
				// admin
				$ar[""]="--{n.a.}--";
			}
			while($riga = $rs->fetch_array()) $ar[$riga['id_sito']]=$riga['de_nomesito'];
			$cd_sito = new optionlist("cd_sito",($dati["cd_sito"]),$ar);
			$cd_sito->obbligatorio= $session->get("idprofilo")==10 ?1 : 0;
			$cd_sito->label="'{Website}'";
			$objform->addControllo($cd_sito);
			//------------------------------------------------


			$de_posizione = new testo("de_posizione",$dati["de_posizione"],20,20);
			$de_posizione->obbligatorio=1;
			$de_posizione->label="'{Position name}'";
			$objform->addControllo($de_posizione);

			$nu_width = new intero("nu_width",$dati["nu_width"],5,5);
			$nu_width->obbligatorio=1;
			$nu_width->label="'{Width}'";
			$nu_width->attributes=" class=\"small\"";
			$objform->addControllo($nu_width);

			$nu_height = new intero("nu_height",$dati["nu_height"],5,5);
			$nu_height->obbligatorio=1;
			$nu_height->attributes=" class=\"small\"";
			$nu_height->label="'{Height}'";
			$objform->addControllo($nu_height);


			$modello_vendita = new optionlist("modello_vendita",$dati["modello_vendita"],
				array("cpm"=>"CPM" ,"cpc"=>"CPC","cpd"=>"CPD") );
			$modello_vendita->obbligatorio=0;
			$modello_vendita->label="'{Sales model}'";
			$objform->addControllo($modello_vendita);

			$vendita_online=new checkbox("vendita_online",$dati["vendita_online"],$dati["vendita_online"]==1);
			$vendita_online->obbligatorio=0;
			$vendita_online->label="'{Available for online sale}'";
			$objform->addControllo($vendita_online);

			$prezzo_vendita=new numerodecimale("prezzo_vendita",$dati["prezzo_vendita"],10,10,3);
			$prezzo_vendita->obbligatorio=1;
			$prezzo_vendita->label="'{Sell price}'";
			$objform->addControllo($prezzo_vendita);
			$objform->addControllo($prezzo_vendita, "vendita_online.checked && prezzo_vendita.value == 0", "{Price can't be 0}" );


			$fl_vignette=new checkbox("fl_vignette",$dati["fl_vignette"],$dati["fl_vignette"]==1);
			$fl_vignette->obbligatorio=0;
			$fl_vignette->label="'{Vignette}'";
			$objform->addControllo($fl_vignette);

			$de_trigger = new testo("de_trigger",$dati["de_trigger"],100,20);
			$de_trigger->obbligatorio=0;
			$de_trigger->label="'{Vignette trigger}'";
			$objform->addControllo($de_trigger);			
			
			$nu_timer = new intero("nu_timer",$dati["nu_timer"],5,5);
			$nu_timer->obbligatorio=1;
			$nu_timer->label="'{Timer}'";
			$nu_timer->attributes=" class=\"small\"";
			$objform->addControllo($nu_timer);

			//------------------------------------------------
			//combo fallback
			$sql = "select id_banner,CONCAT('[',id_banner,'] ',de_nome) as de_nome from ".DB_PREFIX."7banner where cd_posizione='".$id."' order by de_nome";
			$cd_fallback = new optionlist("cd_fallback",($dati["cd_fallback"]),array());
			$cd_fallback->loadSqlOptions( $sql, "id_banner", "de_nome", "{choose}");
			$cd_fallback->obbligatorio= 0;
			$cd_fallback->label="'{Banner}'";
			$objform->addControllo($cd_fallback);
			//------------------------------------------------

			
			$id_obj = new hidden("id",$dati["id_posizione"]);
			$op = new hidden("op",$action);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);

			$html = str_replace("##WWW##", WEBURL, $html);
			$html = str_replace("##MONEY##", MONEY, $html);
			$html = str_replace("##LABEL##", $dati["de_posizione"], $html);
			$html = str_replace("##IDPOS##", $id ? $id : "<b>{save to see}</b>", $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##nu_height##", $nu_height->gettag(), $html);
			$html = str_replace("##nu_width##", $nu_width->gettag(), $html);
			$html = str_replace("##cd_sito##", $cd_sito->gettag(), $html);
			$html = str_replace("##de_posizione##", $de_posizione->gettag(), $html);
			$html = str_replace("##cd_fallback##", $cd_fallback->gettag(), $html);

			$html = str_replace("##modello_vendita##", $modello_vendita->gettag(), $html);
			$html = str_replace("##vendita_online##", $vendita_online->gettag(), $html);
			$html = str_replace("##prezzo_vendita##", $prezzo_vendita->gettag(), $html);

			$html = str_replace("##fl_vignette##", $fl_vignette->gettag(), $html);
			$html = str_replace("##nu_timer##", $nu_timer->gettag(), $html);
			$html = str_replace("##de_trigger##", $de_trigger->gettag(), $html);

			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);


		} else {
			$html = "0";
		}
		return $html;
	}

	function getDati($id) {
		$sql = "SELECT * from ".$this->tbdb." where id_posizione='{$id}'";
		return execute_row($sql);
	}


	function updateAndInsert($arDati) {
		// in:
		// arDati--> array _POST from the form
		// result:
		//	"" --> ok
		//  "0" --> no permissions

		global $session,$conn;
		if ($session->get("POSIZIONI")) {

			if ($session->get("idprofilo")==10) {
				$iduser = execute_scalar("select cd_webmaster from ".DB_PREFIX."7banner_sites where id_sito='".$arDati["cd_sito"]."'");
				if($iduser != $session->get("idutente")) {
					return "0";
				}
			}

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/

				if(isset($arDati["vendita_online"]) && $arDati["prezzo_vendita"]==0) {
					die("Error price is zero");
				}

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_posizione='##de_posizione##',nu_width='##nu_width##',nu_height='##nu_height##',modello_vendita='##modello_vendita##',vendita_online=##vendita_online## , prezzo_vendita=##prezzo_vendita##, fl_vignette='##fl_vignette##',nu_timer='##nu_timer##',cd_sito='##cd_sito##',de_trigger='##de_trigger##',cd_fallback='##cd_fallback##'
					where id_posizione='##id##'";
				$sql= str_replace("##de_posizione##",$arDati["de_posizione"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$sql= str_replace("##nu_width##",$arDati["nu_width"],$sql);
				$sql= str_replace("##nu_height##",$arDati["nu_height"],$sql);
				$sql= str_replace("##vendita_online##",isset($arDati["vendita_online"])?1:0,$sql);
				$sql= str_replace("##modello_vendita##",$arDati["modello_vendita"],$sql);
				$sql= str_replace("##prezzo_vendita##",$arDati["prezzo_vendita"],$sql);
				$sql= str_replace("##nu_timer##",$arDati["nu_timer"],$sql);
				$sql= str_replace("##cd_sito##",(integer)$arDati["cd_sito"],$sql);
				$sql= str_replace("##fl_vignette##",isset($arDati["fl_vignette"])?1:0,$sql);
				$sql= str_replace("##de_trigger##",$arDati["de_trigger"],$sql);
				$sql= str_replace("##cd_fallback##",(integer)$arDati["cd_fallback"],$sql);
				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_posizione,nu_width,nu_height,modello_vendita,vendita_online,prezzo_vendita,fl_vignette,nu_timer,de_trigger,cd_sito,cd_fallback) values('##de_posizione##','##nu_width##','##nu_height##','##modello_vendita##',##vendita_online##,'##prezzo_vendita##',##fl_vignette##,'##nu_timer##','##de_trigger##','##cd_sito##','##cd_fallback##')";
				$sql= str_replace("##de_posizione##",$arDati["de_posizione"],$sql);
				$sql= str_replace("##nu_width##",$arDati["nu_width"],$sql);
				$sql= str_replace("##nu_height##",$arDati["nu_height"],$sql);
				$sql= str_replace("##vendita_online##",isset($arDati["vendita_online"])?1:0,$sql);
				$sql= str_replace("##modello_vendita##",$arDati["modello_vendita"],$sql);
				$sql= str_replace("##prezzo_vendita##",$arDati["prezzo_vendita"],$sql);
				$sql= str_replace("##nu_timer##",$arDati["nu_timer"],$sql);
				$sql= str_replace("##cd_sito##",(integer)$arDati["cd_sito"],$sql);
				$sql= str_replace("##fl_vignette##",isset($arDati["fl_vignette"])?1:0,$sql);
				$sql= str_replace("##de_trigger##",$arDati["de_trigger"],$sql);
				$sql= str_replace("##cd_fallback##",(integer)$arDati["cd_fallback"],$sql);
				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
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
		$sql = "select UPPER(modello_vendita) as A,count(*) as c from ".DB_PREFIX.$this->tbdb." group by UPPER(modello_vendita)";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			if ($riga['A']=="") $riga['c']=0;
			$arFiltri[$riga['A']]= $riga['A']." (".$riga['c'].")";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}


	function deleteItem($id) {
		// in:
		// id --> id record to be deleted
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "-2" --> connected objects

		global $session,$conn;
		if ($session->get("POSIZIONI")) {

			/*
				coerence check:
				can't delete if there are banners on this position
			*/

			$q = execute_scalar("select count(1) from ".DB_PREFIX."7banner where cd_posizione='".$id."'");
			if($q > 0) {
				return "-2";
			}


			$sql="DELETE FROM ".$this->tbdb." where id_posizione='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			if($conn->affected_rows>0) {

			}

			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST (contains array of checkboxed to delete items)
		// result:
		//	"" --> ok
		//  "0" -->no permssion
		//  "-2" -->connected objects

		global $session;
		if ($session->get("POSIZIONI")) {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$out = $this->deleteItem($p[$i]);
				if($out == "-2") return "-2";
			}
			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}


}

?>