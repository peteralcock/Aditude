<?php
/*
	class to handle variables in db
*/
class Vars {
	var $tbdb;	// table
	var $start;	// first record
	var $omode;	// asc|desc
	var $oby;	// ordered by field
	var $ps;	// pagesize: number of rows per page
	var $linkaggiungi;	//link utilizzato per "aggiungere"
	var $linkaggiungi_label;
	var $linkmodifica;	//link utilizzato per il comando "modifica"
	var $linkmodifica_label;
	var $linkview;	//link utilizzato per il comando "view"
	var $linkview_label;
	var $linkelimina;	//link utilizzato per il comando "elimina"
	var $linkelimina_label;
	var $gestore;
	function __construct($tbdb="frw_vars",$ps=20,$oby="id_var",$omode="desc",$start=0) {
		global $session,$root;

		// if (!table_exists("frw_vars")) $this->install();
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
		$this->linkaggiungi_label = "Aggiungi";
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_var##";
		$this->linkmodifica_label = "modifica";
		//		$this->linkview = "javascript:popofferta(##id_viaggio##);";
		//		$this->linkview_label = "<img src=\"images/layout_content.png\" align=\"absmiddle\" alt=\"visualizza la locandina\" border=\"0\">";
		$this->linkelimina = "javascript:confermaDelete('##id_var##');";
		$this->linkelimina_label = "elimina";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";
		$this->linkeliminamarcate_label = "Elimina record marcati";
		checkAbilitazione("FRWVARS","SETTA_SOLO_SE_ESISTE");

	}
	/*
		mostra l'elenco dei componenti.
		ritorna 0 se l'utente non è abilitato, altrimenti restituisce l'elenco in html.
	*/
	function elenco($combotiporeset="",$keyword="") {
		global $session;
		$html = "";
		if ($session->get("FRWVARS")) {
			if($combotiporeset=='reset') {
				//se ho cambiato con la combo del filtro
				//allora resetto la paginazione.
				$this->start = 0;
			}
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;
			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);
			//campi da visualizzare
			$t->campi="de_nome,de_value";
			//titoli dei campi da visualizzare
			$t->titoli="Variabile, Valore";
			//id per fare i link
			$t->chiave="id_var";
			//query per estrarre i dati
			//$t->debug = true;
			$t->query="SELECT id_var,de_nome,de_value from ".DB_PREFIX."frw_vars";
			$where = "";
			
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (de_nome like '%{$keyword}%' or de_value like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" where {$where}";
			}
			$t->addComando($this->linkmodifica,$this->linkmodifica_label,"Modifica questo record");
			$t->addComando($this->linkelimina,$this->linkelimina_label,"Elimina questo record");
			$texto = $t->show();
			if (trim($texto)=="") $texto="Nessun record trovato.";
			$html .= $texto."<br/>";
		} else {
			$html = "0";
		}
		return $html;
	}

	/*
		mostra il dettaglio.
		ritorna 0 se l'utente non è abilitato, altrimenti restituisce l'html.
	*/
	function getDettaglio($id="") {
		global $session,$root;
		if ($session->get("FRWVARS")) {
			if ($id!="") {
				/*
					modifica
				*/
				$dati = $this->getDati($id);
				if(empty($dati)) return "0";
				$action = "modificaStep2";
			} else {
				/*
					inserimento
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_vars") ;
				$action = "aggiungiStep2";
			}
			$html = loadTemplateAndParse("template/dettaglio.html");
			//costruzione form
			$objform = new form();
			//$objform->pathJsLib = $root."template/controlloform.js";
			$de_value = new areatesto("de_value",($dati["de_value"]),5,50);
			$de_value->obbligatorio=0;
			$de_value->label="'Valore'";
			$objform->addControllo($de_value);
			$de_nome = new testo("de_nome",($dati["de_nome"]),50,50);
			$de_nome->obbligatorio=1;
			$de_nome->label="'Nome variabile'";
			$objform->addControllo($de_nome);

			$id_var = new hidden("id",$dati["id_var"]);
			$op = new hidden("op",$action);
			$submit = new submit("invia","salva");
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_var->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			//$html = str_replace("##submit##", $submit->gettagimage($root."images/salva.gif"," Salva"), $html);
			$html = str_replace("##de_nome##", $de_nome->gettag(), $html);
			$html = str_replace("##de_value##", $de_value->gettag(), $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}
	function getDati($id) {
		return execute_row( "SELECT * from ".DB_PREFIX."frw_vars where id_var='{$id}'" );
	}

	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST del form
		// files --> array _FILES
		// risultato:
		//	"" --> ok
		//  "0" --> il tuo profilo non ti consente l'inserimento/modifica
		//  "2|messaggio" --> errore file
		global $session,$conn;
		if ($session->get("FRWVARS")) {
			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modifica
				*/
				$sql="UPDATE ".DB_PREFIX."frw_vars set
					de_nome='##de_nome##',
					de_value='##de_value##'
					where id_var='##id_var##'";
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##de_value##",$arDati["de_value"],$sql);
				$sql= str_replace("##id_var##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$html= "";
			} else {
				/*
					Inserimento
				*/
				$sql="INSERT into ".DB_PREFIX."frw_vars (de_value,de_nome) values('##de_value##','##de_nome##')";
				$sql= str_replace("##de_value##",$arDati["de_value"],$sql);
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$conn->query($sql);
				if($conn->errno==1062) {
					return "-1|Record già inserito";
				}
				//or 
				$html= "";
				$id = $conn->insert_id;
			}
			
		} else {
			$html="0";		//il tuo profilo non ti consente l'inserimento
		}
		return $html;
	}


	function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione
		global $session,$conn;
		if ($session->get("FRWVARS")) {
			$sql="DELETE FROM ".DB_PREFIX."frw_vars where id_var='$id'";
			//echo "$sql<br>";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}
	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// risultato:
		//	"" --> ok
		//  "0" -->il tuo profilo non ti consente la cancellazione
		global $session,$conn;
		if ($session->get("FRWVARS")) {
			$html="0";
			$idx ="";
			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				if ($idx) $idx.=", ";
				$idx .= $p[$i];
				$id = $p[$i];
			}
			if ($idx) {
				$sql = "delete from ".DB_PREFIX."frw_vars where id_var in ($idx)";
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");

			}
			$html = "";
		} else {
			$html="0";		//il tuo profilo non ti consente di cancellare
		}
		return $html;
	}

}
?>