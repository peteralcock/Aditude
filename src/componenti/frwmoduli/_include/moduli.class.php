<?php
/*
	class to handle modules for menu items
*/

class Moduli {

	var $tbdb;	

	var $start;	
	var $omode;	
	var $oby;	
	var $ps;	

	var $linkaggiungi;	
	
	var $linkmodifica;	
	
	var $linkelimina;
	var $linkeliminamarcate;

	var $gestore;

	function __construct ($tbdb="frw_moduli",$ps=20,$oby="posizione",$omode="asc",$start=0) {
		global $session,$root;

		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;

		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);

		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id##";
		
		$this->linkelimina = "javascript:confermaDelete('##id##');";
		
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		
		checkAbilitazione("FRWCOMPONENTI","SETTA_SOLO_SE_ESISTE");
		checkAbilitazione("FRWMODULI","SETTA_SOLO_SE_ESISTE");

	}

	/*
		list of the menu modules
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("FRWMODULI")) {
			if($combotiporeset=='reset') {
				$this->start = 0;
			}

			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=false;
			$t->mostraRecordTotali = true;
			$t->functionhtml="";

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			// fields
			$t->campi="id,label,posizione,visibile";

			// titles
			$t->titoli="#ID, {Menu label}, {Order}, {Status}";

			// key
			$t->chiave="id";

			// SQL
			//$t->debug = true;
			$t->query="SELECT id, label, posizione, visibile FROM ".DB_PREFIX."frw_moduli ";

			$where = "";
			if($combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" visibile='{$combotipo}'";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (nome like '%{$keyword}%' or label like '%{$keyword}%')";
			}
			
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->addScegliDaInsieme("visibile",
				array(
					"1"=>"<span class='labelgreen'>ON</span>",
					"0"=>"<span class='labelred'>OFF</span>"
				)
			);

			// $t->addComando($this->linkmodifica,"modifica","Edit");
			$t->addComando($this->linkelimina,"elimina","Delete");

			$t->addCampi("label","show_modulename");

			$texto = $t->show();
			if (trim($texto)=="") $texto="{No records found.}";
			$html .= $texto."<br/>";

		} else {
			$html = "0";
		}
		return $html;
	}


	/*
		details form for module
	*/
	function getDettaglio($id="") {
		global $session,$root;

		if ($session->get("FRWMODULI")) {
			if ($id!="") {
				/*
					modify
				*/

				//
            	// security check
				//
			    if((integer)$id<1000 && (integer)$session->get("idprofilo")<999999) return 0;

				$dati = $this->getDati($id);
				if(empty($dati)) return "0";
				$action = "modificaStep2";

				//
				// grid for menu items of this module
				//
				$t=new grid(DB_PREFIX."frw_com_mod",0, 999, "posizione", "asc");
				$t->functionhtml = "";
				$t->chiave = "id";
				$t->mostraRecordTotali = false;
				$t->flagOrdinatori = false;
				$t->campi="id,label,posizione,idcomponente";
				$t->titoli="ID,{Label},{Order},{Visibility}";
				$t->query="SELECT id,CONCAT('<a class=\"linkmenu ',nome,' ',COALESCE(urliconamenu,''),'\">',label,'</a>') as label,posizione,idcomponente,idmodulo FROM ".DB_PREFIX."frw_com_mod inner join ".DB_PREFIX."frw_componenti on id=idcomponente where idmodulo='{$id}'";
				$t->addComando("../frwcomponenti/index.php?op=modifica&id=##idcomponente##&cd_module=##idmodulo##","modifica");
				$t->addComando("javascript:confermaDeleteComponente('##idcomponente##','##idmodulo##');","elimina","Elimina questo record");
				$t->addCampi('idcomponente',"showVisibility");
				$tcollegati = $t->show();
				$taddcollegati = "<div class='panel2 internal'><div class='titlecontainer'>";
				if((integer)$session->get("idprofilo")==999999) 
					$taddcollegati .= "<a href='indexcomponenti.php?op=aggiungi&cd_item={$id}' class='aggiungi' title='{Add existing menu item}'>&sup2;</a>";
				$taddcollegati .= " <a href='../frwcomponenti/index.php?op=aggiungi&cd_module={$id}' class='aggiungi' title='{Add new}'></a>";
				$taddcollegati .= "</div></div>";

			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_moduli") ;
				$action = "aggiungiStep2";
				$tcollegati = "";
				$taddcollegati = "";

			}

			$html = loadTemplateAndParse("template/dettaglio.html");

			$objform = new form();
			$objform->pathJsLib = $root."src/template/controlloform.js";

			$nome = new testo("nome",$dati["nome"],30,30);
			$nome->obbligatorio=1;
			$nome->label="'Nome'";
			$objform->addControllo($nome);
			
			$label = new testo("label",$dati["label"],30,30);
			$label->obbligatorio=1;
			$label->label="'label'";
			$objform->addControllo($label);

			$visibile = new optionlist("visibile",$dati["visibile"],array("1"=>"ON" ,"0"=>"OFF") );
			$visibile->obbligatorio=0;
			$visibile->label="'Visibile'";
			$objform->addControllo($visibile);

			$arpos = array(); for($i=0;$i<99;$i++) $arpos[$i]=$i;
			$posizione = new optionlist("posizione",$dati["posizione"],$arpos );
			$posizione->obbligatorio=0;
			$posizione->label="'Posizione'";
			$objform->addControllo($posizione);

			$fl_translate=new checkbox("fl_translate",1,$dati["fl_translate"]==1);
			$fl_translate->obbligatorio=0;
			$fl_translate->label="'{Translate}'";
			$objform->addControllo($fl_translate);


			$objid = new hidden("id",$dati["id"]);
			$op = new hidden("op",$action);

			$submit = new submit("invia","salva");

			
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $objid->gettag(), $html);
			$html = str_replace("##fl_translate##", $fl_translate->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##visibile##", $visibile->gettag(), $html);
			$html = str_replace("##posizione##", $posizione->gettag(), $html);
			$html = str_replace("##label##", $label->gettag(), $html);
			$html = str_replace("##nome##", $nome->gettag(), $html);
			$html = str_replace("##TCOLLEGATI##", $tcollegati, $html);
			$html = str_replace("##TADDCOLLEGATI##", $taddcollegati, $html);

			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);


		} else {
			$html = "0";
		}

		return $html;
	}


	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."frw_moduli where id='{$id}'");
	}


	function updateAndInsert($arDati,$files) {

		global $session,$conn;
		if ($session->get("FRWMODULI")) {

			if (!isset($arDati["fl_translate"])) $arDati["fl_translate"] = "0";
	
			if ($arDati["id"]!="") {
				$id = $arDati["id"];

            	//security
			    if((integer)$id<1000 && (integer)$session->get("idprofilo")<999999) return 0;

				/*
					Modify
				*/

				$sql="UPDATE ".DB_PREFIX."frw_moduli set
					nome='##nome##',
					label='##label##',
					posizione='##posizione##',
					fl_translate='##fl_translate##',
					visibile='##visibile##'
					where id='##id##'";
				$sql= str_replace("##nome##",$arDati["nome"],$sql);
				$sql= str_replace("##fl_translate##",$arDati["fl_translate"],$sql);
				$sql= str_replace("##label##",$arDati["label"],$sql);
				$sql= str_replace("##posizione##",$arDati["posizione"],$sql);
				$sql= str_replace("##visibile##",$arDati["visibile"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");

				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/

				$sql="INSERT into ".DB_PREFIX."frw_moduli (nome,label,posizione,visibile,fl_translate) values('##nome##','##label##','##posizione##','##visibile##','##fl_translate##')";
				$sql= str_replace("##fl_translate##",$arDati["fl_translate"],$sql);
				$sql= str_replace("##nome##",$arDati["nome"],$sql);
				$sql= str_replace("##label##",$arDati["label"],$sql);
				$sql= str_replace("##posizione##",$arDati["posizione"],$sql);
				$sql= str_replace("##visibile##",$arDati["visibile"],$sql);
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$id = $conn->insert_id;
				$html= "ok|".$id;

			}

		} else {
			$html="0";		
		}
		return $html;
	}




	function deleteItem($id) {
		global $session,$conn;
		if ($session->get("FRWMODULI")) {

			$q = execute_scalar("SELECT COUNT(1) FROM ".DB_PREFIX."frw_com_mod WHERE idmodulo='{$id}'");
			if($q>0) return "-1|{This module contains items.}|jsback";

   			//security
			if((integer)$id<1000 && (integer)$session->get("idprofilo")<999999) return "-1|{You're not authorized.}|jsback";

			$sql="DELETE FROM ".DB_PREFIX."frw_moduli where id='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$sql="DELETE FROM ".DB_PREFIX."frw_com_mod where idmodulo='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$sql="DELETE from ".DB_PREFIX."frw_profili_funzionalita where cd_modulo='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$sql="DELETE from ".DB_PREFIX."frw_ute_fun where idmodulo='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$html = "|{Done.}|load index.php";

		} else {
			$html="-1|{You're not authorized.}|jsback";
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		global $session,$conn;
		if ($session->get("FRWMODULI")) {
			$html="";
			$idx ="";
			$p=$dati['gridcheck'];
            
            foreach($p as $id) {
                $out = $this->deleteItem($id);
                if(stristr($out,"-1|")) return $out;
            }
            return $out;

		} else {
			$html="-1|{You're not authorized.}|jsback";		
		}
		return $html;
	}


	function getHtmlcombotipo($def="") {
		global $conn;
		//------------------------------------------------
		//combo filter
		$sql = "select visibile,count(*) as c from ".DB_PREFIX.$this->tbdb." group by visibile";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			
				if ($riga['visibile']=="1") $arFiltri[$riga['visibile']] = "{ON} (".$riga['c'].")";
				if ($riga['visibile']=="0") $arFiltri[$riga['visibile']] = "{OFF} (".$riga['c'].")";
	
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}


	// function to distribute permissions or fix permissions
	function profila($idm,$check="YES") {
		global $session;
		if ($check!="NOSESSION" && $session->get("FRWCOMPONENTI")=="") return "0";

		$idcompo = $this->getElencoId("select idcomponente from ".DB_PREFIX."frw_com_mod where idmodulo = '$idm'");
		if ($idcompo=="") return "1";
		$arCompo = explode(",",$idcompo);
		$html="";
		for ($i=0;$i<count($arCompo);$i++) {
			$com = new componenti();
			$html.="componente: <b>{$arCompo[$i]}</b><br>".$com->profila($arCompo[$i],$check)."<br>";
			unset($com);
		}
		return $html;
	}

	// this method distribute permission from code
	// without having the real user connected
	function profila_service($idc) {
		$html = $this->profila($idc,"NOSESSION");
		return $html!="0" ? "ok" : "ko";
	}

	
	/*
		helper: from an sql get a list of items comma separted
	*/
	function getElencoId($sql) {
		return concatenaId($sql);
	}

}

?>