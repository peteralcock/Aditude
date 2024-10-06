<?php
/*
	class to handle clients
*/

class Clienti extends CrudBase{

	var $linkaggiungi;
	var $linkmodifica;
	var $linkmodifica_label;
	var $linkeliminamarcate;

	function __construct ($tbdb="7banner_clienti",$ps=20,$oby="id_cliente",$omode="desc",$start=0) {
		global $session;

		parent::__construct($tbdb,$ps,$oby,$omode,$start);

		// link above in the panel
		$this->linkaggiungi = "$this->gestore?op=aggiungi";
		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_cliente##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("CLIENTI","SETTA_SOLO_SE_ESISTE");

	}

	/*
		clients grid
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("CLIENTI")) {
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

			// fields to show
			$t->campi="id_cliente,de_nome,b,p,q,ecpm";

			// titles to show
			$t->titoli="{Client ID},{Client name},{Total ads},{Total revenue},{Total views},{eCPM}";

			// key field id for links
			$t->chiave="id_cliente";

			// query sql
			// $t->debug = true;
			$t->query="SELECT A.id_cliente,A.de_nome,count(id_banner)as b,  SUM(nu_price) as p, 
				@tot := (select sum(nu_pageviews) from ".DB_PREFIX."7banner_stats S where S.cd_banner in 
					(select ads.id_banner from ".DB_PREFIX."7banner ads inner join ".DB_PREFIX."7banner_campagne C where C.id_campagna=ads.cd_campagna and C.cd_cliente=A.id_cliente)) as q, (SUM(nu_price) *1000 / (SELECT @tot) ) as ecpm
				from ".DB_PREFIX.$this->tbdb." as A
				left outer join ".DB_PREFIX."7banner_campagne C on id_cliente=C.cd_cliente
				left outer join ".DB_PREFIX."7banner B on B.cd_campagna=id_campagna
				";

			$where = " 1=1 ";
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					if($combotipo=="-998") {
						$where.=" A.cd_utente>0 ";	
					} 
					if($combotipo=="-997") {
						$where.=" A.cd_utente=0 ";	
					} 
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (A.de_nome like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->query.= "group by id_cliente,A.de_nome";

			$t->addCampi("p","show_client_value");
			$t->addCampi('ecpm',"show_ecpm");
			$t->addCampi('de_nome',"link",array("url"=>$this->linkmodifica));

			$t->arFormattazioneTD=array(
				"b" => "numero",
				"p" => "numero",
				"q" => "numero",
				"ecpm" => "numero",
			);
			
			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

			//
			// template filling
			$this->ambiente->setTemplate("template/elenco.html");
			$this->ambiente->setKey("##corpo##", $html );
			$this->ambiente->setKey("##keyword##", $keyword);
			$this->ambiente->setKey("##bottoni1##","<a href=\"$this->linkaggiungi\" title=\"{Add new item}\" class='aggiungi'></a>");
			$this->ambiente->setKey("##bottoni2##","<a href=\"$this->linkeliminamarcate\" title=\"{Delete selected items}\" class='elimina'></a>");
			$this->ambiente->setKey("##combotipo##", $this->getHtmlcombotipo($combotipo));


		} else {

			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}	

	}




	/*
		show client detail from
	*/
	function getDettaglio($id="",$duplica='no') {
		global $session,$root,$conn;

		if ($session->get("CLIENTI")) {
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

			// form construction
			$objform = new form();

			$de_nome = new testo("de_nome",$dati["de_nome"],50,50);
			$de_nome->obbligatorio=1;
			$de_nome->label="'Name'";
			$objform->addControllo($de_nome);
	
			$rs = $conn->query($sql ="select * from ".DB_PREFIX."frw_utenti where cd_profilo=5 and fl_attivo=1 ". (!empty($arrIN) ? "and id not in (".implode(",",array_keys($arrIN)).")" : "") );
			$arr = array();
			while($row = $rs->fetch_array()) $arr[$row['id']] = $row['nome']." ".$row['cognome']." (".$row['username'].")";



			//------------------------------------------------
			//combo users
			$sql = "select id,nome,cognome from ".DB_PREFIX."frw_utenti 
				where id not in (select cd_utente from ".DB_PREFIX."7banner_clienti where id_cliente <> '".$dati['id_cliente']."') and cd_profilo = 5
				order by nome, cognome ";
			$rs = $conn->query($sql) or die($conn->error.$sql);
			$ar = array();
			$ar[""]="--choose--";
			while($riga = $rs->fetch_array()) $ar[$riga['id']]=$riga['nome']." ".$riga['cognome'];
			$cd_utente = new optionlist("cd_utente",($dati["cd_utente"]),$ar);
			$cd_utente->obbligatorio=0;
			$cd_utente->label="'{User}'";
			$objform->addControllo($cd_utente);
			//------------------------------------------------

			$de_address = new areatesto("de_address",(($dati["de_address"])),5,80);
			$de_address->obbligatorio=0;
			$de_address->label="'{Address}";
			$de_address->maxlimit=350;
			$objform->addControllo($de_address);

			$id_obj = new hidden("id",$dati["id_cliente"]);
			$op = new hidden("op",$action);

			//
			// template filling
			$this->ambiente->setTemplate("template/dettaglio.html");
			$this->ambiente->setKey("##STARTFORM##", $objform->startform());
			$this->ambiente->setKey("##id##", $id_obj->gettag());
			$this->ambiente->setKey("##op##", $op->gettag());
			$this->ambiente->setKey("##de_address##", $de_address->gettag());
			$this->ambiente->setKey("##de_nome##", $de_nome->gettag());
			$this->ambiente->setKey("##cd_utente##", $cd_utente->gettag());
			$this->ambiente->setKey("##gestore##", $this->gestore);
			$this->ambiente->setKey("##ENDFORM##", $objform->endform());


		} else {
			
			//
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);

		}
	}

	function getDati($id) {
		$sql = "SELECT * from ".DB_PREFIX.$this->tbdb." where id_cliente='{$id}'";
		return execute_row($sql);
	}


	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// files --> array _FILES

		global $session,$conn;
		if ($session->get("CLIENTI")) {

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/
				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					de_nome='##de_nome##',
					de_address='##de_address##',
					cd_utente='##cd_utente##'
					where id_cliente='##id##'";
				$sql= str_replace("##de_address##",$arDati["de_address"],$sql);
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##cd_utente##",(integer)$arDati["cd_utente"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				// $html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (de_nome,de_address,cd_utente) values('##de_nome##','##de_address##','##cd_utente##')";
				$sql= str_replace("##de_address##",$arDati["de_address"],$sql);
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##cd_utente##",(integer)$arDati["cd_utente"],$sql);
				$conn->query($sql) or die($conn->error.$sql);
				$id = $conn->insert_id;
				// $html= "ok|".$id;
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
		// return $html;
	}


	function getHtmlcombotipo($def="") {
		//------------------------------------------------
		//combo filter
		$arFiltri = array("-999"=>"{All}","-998"=>"{With user}","-997"=>"{Without user}");
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}


	function deleteItem($id) {
		// in:
		// id --> id tipo da cancellare
		// result:
		//	"" --> ok
		//  "0" -->no permission
		// -2 connected items error

		global $session,$conn,$root;
		if ($session->get("CLIENTI")) {


			$q = execute_scalar("select count(1) from ".DB_PREFIX."7banner 
				inner join ".DB_PREFIX."7banner_campagne on cd_campagna=id_campagna
				inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente
				where id_cliente='".$id."'");
			if($q > 0) {
				return "-2";
			}

			$sql="DELETE FROM ".DB_PREFIX."7banner_campagne where cd_cliente='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");

			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_cliente='$id'";
			$conn->query($sql) or die($conn->error."sql='$sql'<br>");


			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;

	}
	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// result:
		//	"" --> ok
		//  "0" -->no permission
		//  "-2" -->connected items error

		global $session;
		if ($session->get("CLIENTI")) {

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
				$this->ambiente->loadMsg("{You can't delete a client with banners.}","jsback", ERR_MSG);
			} else 
				$this->ambiente->loadMsg("{Deleted.}","load ".$_SERVER['SCRIPT_NAME'], OK_MSG);
			
		} else {
			// error template
			$this->ambiente->loadMsg("{You're not authorized.}","jsback", ERR_MSG);
		}
		
	}

}