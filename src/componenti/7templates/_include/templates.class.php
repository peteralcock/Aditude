<?php
/*
	class to handle banner templates
*/

class Templates {

	var $tbdb;	//table

	var $start;	// start row
	var $omode;	// order mode asc|desc
	var $oby;	// table field order by
	var $ps;	// page size

	var $linkaggiungi;	// link to add

	var $linkmodifica;	// link to edit
	var $linkmodifica_label;

	var $linkeliminamarcate;	// link to delete

	var $gestore;


	function __construct ($tbdb="7banner_templates",$ps=20,$oby="id_template",$omode="desc",$start=0) {
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
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_template##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("TEMPLATES","SETTA_SOLO_SE_ESISTE");

	}

	/*
		show templates in grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("TEMPLATES")) {
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
			$t->campi="id_template,de_titolo";

			// titles
			$t->titoli="{Template ID},{Template name}";

			// key
			$t->chiave="id_template";

			// SQL
			//$t->debug = true;

			$t->query="SELECT A.id_template,A.de_titolo from ".DB_PREFIX.$this->tbdb." as A
				";

			$where = " 1=1 ";
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {

				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (A.de_titolo like '%{$keyword}%' or A.de_info like '%{$keyword}%' or A.de_code like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->addCampi('de_titolo',"show_template_title");

			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

		} else {

			$html = "0";
		}
		return $html;
	}


	/*
		detail form for template
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$root,$conn;

		if ($session->get("TEMPLATES")) {
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

				$action = "aggiungiStep2";

			}

			$html = loadTemplateAndParse("template/dettaglio.html");

			// form construction
			$objform = new form();

			$de_titolo = new testo("de_titolo",$dati["de_titolo"],50,50);
			$de_titolo->obbligatorio=1;
			$de_titolo->label="'{Template name}'";
			$objform->addControllo($de_titolo);

			$de_info = new areatesto("de_info",($dati["de_info"]),5,50);
			$de_info->obbligatorio=0;
			$de_info->label="'{Info}'";
			$de_info->attributes=" class='code'";
			$objform->addControllo($de_info);

			$de_code = new areatesto("de_code",($dati["de_code"]),5,50);
			$de_code->obbligatorio=0;
			$de_code->attributes=" class='code'";
			$de_code->label="'{Code}'";
			$objform->addControllo($de_code);


			$id_obj = new hidden("id",$dati["id_template"]);
			$op = new hidden("op",$action);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_titolo##", $de_titolo->gettag(), $html);
			$html = str_replace("##de_info##", $de_info->gettag(), $html);
			$html = str_replace("##de_code##", $de_code->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}

	function getDati($id) {
		$sql = "SELECT * from ".$this->tbdb." where id_template='{$id}'";
		return execute_row($sql);
	}


	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// result:
		//	"" --> ok
		//  "0" --> no permissions

		global $session,$conn;
		if ($session->get("TEMPLATES")) {

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_titolo='##de_titolo##',
					de_info='##de_info##',
					de_code='##de_code##'
					where id_template='##id##'";

				$sql= str_replace("##de_titolo##",$arDati["de_titolo"],$sql);
				$sql= str_replace("##de_info##",$arDati["de_info"],$sql);
				$sql= str_replace("##de_code##",$arDati["de_code"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_titolo,de_info,de_code) values('##de_titolo##','##de_info##','##de_code##')";

				$sql= str_replace("##de_titolo##",$arDati["de_titolo"],$sql);
				$sql= str_replace("##de_info##",$arDati["de_info"],$sql);
				$sql= str_replace("##de_code##",$arDati["de_code"],$sql);
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
		//------------------------------------------------
		//combo filter
		$arFiltri = array("-999"=>"All");
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

		global $session,$conn,$root;
		if ($session->get("TEMPLATES")) {

			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_template='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");

			$html = "";
		} else {
			$html="0";
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		// in:
		// id --> id record to be deleted
		// result:
		//	"" --> ok
		//  "0" --> no permission

		global $session;
		if ($session->get("TEMPLATES")) {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) $this->deleteItem($p[$i]);
			$html = "";
		} else {
			$html="0";	
		}
		return $html;
	}


}

?>