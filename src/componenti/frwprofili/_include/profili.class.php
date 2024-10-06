<?php

class Profili {

	var $tbdb;	

	var $start;	// posizione del primo record visualizzato
	var $omode;	// asc|desc
	var $oby;	// campo della tabella $tbdb utilizzato per ordinare 
	var $ps;	// numero di righe per pagina nell'elenco

	var $linkaggiungi;	//link utilizzato per "aggiungere"
	var $linkaggiungi_label;

	var $linkmodifica;	//link utilizzato per il comando "modifica"
	var $linkmodifica_label;
	var $linkeliminamarcate;	//link to delete

	var $gestore;


	function __construct ($tbdb="frw_profili",$ps=20,$oby="de_label",$omode="asc",$start=0) {
		global $session,$root,$conn;
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;
		//se ci sono impostazioni inviate in get o in post usa quelle
		//se non ci sono quelle usa quelle in session
		//se non ci sono neanche in session usa i valori passati.
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);

		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkaggiungi_label = "{Add profile}";

		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_profilo##";
		$this->linkmodifica_label = "modifica";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		
		checkAbilitazione("FRWPROFILI","FRWPROFILI");
		


	}

	/*
		mostra l'elenco dei componenti.
		ritorna 0 se l'utente non e' abilitato, altrimenti restituisce l'elenco in html.
	*/
	function elenco() {
		global $session;
		$html = "";
		if ($session->get("FRWPROFILI")) {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);

			//fields
			$t->campi="id_profilo,de_label,de_descrizione";

			//titles
			$t->titoli="ID,Label,Descrizione";

			//id 
			$t->chiave="id_profilo";

			// checkboxes
			$t->checkboxFormName="datagrid";
			$t->checkboxForm= true;

			//query per estrarre i dati
			$t->query="SELECT id_profilo, de_label,de_descrizione from ".DB_PREFIX."frw_profili";

			$t->addComando($this->linkmodifica,$this->linkmodifica_label);
			
			$html =""; 

			$html .= $t->show();

		} else {
			$html = "0";
		}
		return $html;
	}

	/*
		estrae una stringa con i record sepatati da una virgola.
		prende il primo item dei record estratti.
	*/
	function getElencoId($sql) {
		return concatenaId($sql);
	}


	function getListaCheckboxChiEdita($strValoriSelezionati="") {
		global $conn;
		$sql = "select id_profilo as idprofili,de_label from ".DB_PREFIX."frw_profili";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		$html="";
		while ($r=$rs->fetch_array()) {
			//echo $r["idprofili"]." in ".$strValoriSelezionati."<br>";
			$html.="<input type=\"checkbox\" name=\"idprofili[]\" value=\"{$r['idprofili']}\"";
			if (stristr($strValoriSelezionati, ",".$r["idprofili"].",")) $html.=" checked";
			$html.=">{$r['de_label']}";
			$html.="\r\n";
		}
		return $html;
	}


	/*
		mostra il dettaglio del componente.
		ritorna 0 se l'utente non e' abilitato, altrimenti restituisce l'html.
	*/
	function getDettaglio($id="") {
		global $session;

		if ($session->get("FRWPROFILI")) {


			$bodyclass = "admin";

			if ($id!="") {
				/*
					modify
				*/
				$dati = $this->getDati($id);
				if(empty($dati)) return "0";
				$action = "modificaStep2";



			} else {
				/*
					insert
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$dati["chiedita"] = "";
				$action = "aggiungiStep2";

			}

			$html = loadTemplateAndParse("template/dettaglio.html");
			// form construction
			$objform = new form();
			$op = new hidden("op",$action);
			$id_obj = new hidden("id",$dati["id_profilo"]);

			$chiedita = $this->getListaCheckboxChiEdita($dati["chiedita"]);

			$de_label = new testo("de_label",$dati["de_label"],20,20);
			$de_label->obbligatorio=1;
			$de_label->label="'{Profile name}'";
			$objform->addControllo($de_label);

			$id_profilo = new numerointero("id_profilo",$dati["id_profilo"],6,6);
			$id_profilo->obbligatorio=1;
			$id_profilo->label="'{ID}'";
			$objform->addControllo($id_profilo);

			$de_descrizione = new testo("de_descrizione",$dati["de_descrizione"],60,20);
			$de_descrizione->obbligatorio=0;
			$de_descrizione->label="'{Description}'";
			$objform->addControllo($de_descrizione);

			$html = str_replace("##elencoprofili##", nl2br($chiedita), $html);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_label##", $de_label->gettag(), $html);
			$html = str_replace("##de_descrizione##", $de_descrizione->gettag(), $html);
			$html = str_replace("##id_profilo##", $id_profilo->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##bodyclass##", $bodyclass, $html);



		} else {
			$html = returnmsg("Non sei autorizzato.");
		}
		return $html;
	}

	function getDati($id) {
		$sql = "SELECT * from ".DB_PREFIX."frw_profili where id_profilo='{$id}'";
		return execute_row($sql);
	}


	function checkExistIn($nometabella,$nomecampo,$valore,$giaesistente,$campoidunivoco) {
		global $conn;
		$sql = "select $nomecampo from $nometabella where $nomecampo='$valore'";
		if ($giaesistente!="") $sql.=" and $campoidunivoco<>'$giaesistente'";
		//echo $sql."<br>";
		$rs = $conn->query($sql) or die($conn->error."sql='$sql'<br>");
		if ($rs->num_rows>0) $risultato = true; else $risultato = false;
		$rs->free();
		return $risultato;
	}


	function updateAndInsert($arDati) {
		// in:
		// arDati--> array POST del form
		// risultato:
		//	"" --> ok
		//	"1" --> label gia' utilizzato da un altro profilo
		//	"3" --> id_profilo gia' utilizzato da un altro profilo
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica

		global $session,$conn;
		if ($session->get("FRWPROFILI")) {
			
			if ($arDati["id"]!="") {
				/*
					Modify record
				*/
				if (!$this->checkExistIn(DB_PREFIX."frw_profili","de_label",$arDati["de_label"],$arDati["id"],"id_profilo")){ 
					if (!$this->checkExistIn(DB_PREFIX."frw_profili","id_profilo",$arDati["id_profilo"],$arDati["id"],"id_profilo")){ 
						$sql="UPDATE ".DB_PREFIX."frw_profili set chiedita='##chiedita##',de_descrizione='##descrizione##',de_label='##label##', id_profilo='##id_profilo##' where id_profilo='##id##'";
						$sql= str_replace("##descrizione##",$arDati["de_descrizione"],$sql);
						$sql= str_replace("##label##",$arDati["de_label"],$sql);
						$sql= str_replace("##id##",$arDati["id"],$sql);
						$sql= str_replace("##id_profilo##",$arDati["id_profilo"],$sql);
						if (!isset($arDati["idprofili"])) $arDati["idprofili"] = array();
						$chiedita=",";
						for ($i=0;$i<count($arDati["idprofili"]);$i++) {
							$chiedita.=$arDati["idprofili"][$i].",";
						}
						if ($chiedita==",") $chiedita="";
						$sql= str_replace("##chiedita##",$chiedita,$sql);
						
						$conn->query($sql) or die($conn->error."sql='$sql'<br>");
						

						$html= "";

					} else {
						$html="-1|{ID already used}";	
					}

				} else {
					$html="-1|{Label already used}";	
				}
			} else {
				/*
					Inserimento
				*/
				if (!$this->checkExistIn(DB_PREFIX."frw_profili","de_label",$arDati["de_label"],$arDati["id"],"id_profilo")){ 
					if (!$this->checkExistIn(DB_PREFIX."frw_profili","id_profilo",$arDati["id_profilo"],$arDati["id"],"id_profilo")){ 
						$sql="INSERT into ".DB_PREFIX."frw_profili (id_profilo,de_descrizione,de_label,chiedita) values('##idprofilo##','##descrizione##','##label##','##chiedita##')";

						$sql= str_replace("##descrizione##",$arDati["de_descrizione"],$sql);
						$sql= str_replace("##idprofilo##",$arDati["id_profilo"],$sql);
						$sql= str_replace("##label##",$arDati["de_label"],$sql);

						$chiedita=",";
						if (!isset($arDati["idprofili"])) $arDati["idprofili"]=array();
						for ($i=0;$i<count($arDati["idprofili"]);$i++) {
							$chiedita.=$arDati["idprofili"][$i].",";
						}
						if ($chiedita==",") $chiedita="";
						$sql= str_replace("##chiedita##",$chiedita,$sql);

						$conn->query($sql) or die($conn->error."sql='$sql'<br>");

						$html= "";
					} else {
						$html="-1|{ID already used}";
					}
				} else {
					$html="-1|{Label already used}";	
				}

			}

		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}


	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "-2" --> connected items

		global $session;
		if ($session->get("FRWPROFILI")) {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$risultato = $this->deleteItem($p[$i]);
				if($risultato!="") return $risultato; // there are user on this profile
			}
			$html = "";
		} else {
			$html="0";		//no permissions
		}
		return $html;
	}



	function deleteItem($id) {
		global $session,$conn;
		if ($session->get("FRWPROFILI")) {
			$userCollegati = $this->getElencoId("select id from ".DB_PREFIX."frw_utenti where cd_profilo='$id'");
			if ($userCollegati=="") {
				$sql="DELETE FROM ".DB_PREFIX."frw_profili where id_profilo='$id'";
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$sql="DELETE from ".DB_PREFIX."frw_profili_funzionalita where cd_profilo='$id'";
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$html = "";
			} else {
				// there are user on this profile
				$html = "1";
			}
		} else {
			$html="0"; // no permissions
		}
		return $html;

	}


}

?>