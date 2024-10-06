<?php
/*

	class for items in menu (components)

*/

class Componenti {

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


	function __construct ($tbdb="frw_componenti",$ps=20,$oby="nome",$omode="asc",$start=0) {
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
		
		checkAbilitazione("FRWCOMPONENTI","SETTA_SOLO_SE_ESISTE");



	}

	/*
		grid of components
	*/
	function elenco($combotipo="",$combotiporeset="",$keyword="") {
		global $session;
		$html = "";
		if ($session->get("FRWCOMPONENTI")!="") {
			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode);
			$t->mostraRecordTotali = true;
			$t->functionhtml="";

			$t->campi="id,label,idc,urlcomponente";
			$t->titoli="#ID,{Preview},{Modules},{Url}";
			$t->chiave="id";
			$t->query="SELECT id, id as idc, CONCAT('<a class=\"linkmenu ',nome,'\">',label,'</a>') as label, urlcomponente from ".DB_PREFIX."frw_componenti";

			$where = "";
			if($combotipo) {
				if($combotipo=="-999") {

				} else {
					if($where!="") { $where.= " and "; }
					$where.=" id in (SELECT idcomponente FROM `".DB_PREFIX."frw_com_mod` WHERE idmodulo='{$combotipo}')";
				}
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (nome like '%{$keyword}%' or label like '%{$keyword}%')";
			}
			
			if($where) {
				$t->query.=" where {$where}";
			}

			$t->addComando($this->linkmodifica,"modifica","{Edit}");
			$t->addComando($this->linkelimina,"elimina","{Delete}");

            $t->addCampi('idc',"showModules");
			

			if($session->get("idprofilo")==999999) {
				$t->addComando("javascript:abilitaComponente('##id##');","assegna");
			}

			$html = $t->show();

		} else {
			$html = "0";
		}
		return $html;
	}

	// this method distributes permissions from code
	// without having the real user connected
	function profila_service($idc) {
		$html = $this->profila($idc,"NOSESSION");
		return $html!="0" ? "ok" : "ko";
	}

	// function to distribute permissions or fix permissions
	function profila($idc, $check="YES") {
		global $session,$conn;
		if ($check!="NOSESSION" && $session->get("FRWCOMPONENTI")=="") return "0";

		$html = "";

		$html.="Pulizia tabelle... ";
		$sql = "delete FROM `".DB_PREFIX."frw_com_mod` WHERE idmodulo not in (".$this->getElencoId("select id from ".DB_PREFIX."frw_moduli").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$sql = "DELETE FROM `".DB_PREFIX."frw_com_mod` WHERE idcomponente not in (".$this->getElencoId("Select id from ".DB_PREFIX."frw_componenti").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$sql = "delete FROM `".DB_PREFIX."frw_funzionalita` WHERE idcomponente not in (".$this->getElencoId("select id from ".DB_PREFIX."frw_componenti").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$sql = "delete FROM `".DB_PREFIX."frw_profili_funzionalita` where cd_funzionalita not in (".$this->getElencoId("select id from ".DB_PREFIX."frw_funzionalita").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$sql = "DELETE FROM `".DB_PREFIX."frw_ute_fun` WHERE idfunzionalita not in (".$this->getElencoId("select id from ".DB_PREFIX."frw_funzionalita").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$sql = "delete FROM `".DB_PREFIX."frw_ute_fun` WHERE idutente not in (".$this->getElencoId("select id from ".DB_PREFIX."frw_utenti").")";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		$html.="ok<br><br>";


		$idfunzionalita = $this->getElencoId("select id from ".DB_PREFIX."frw_funzionalita where idcomponente={$idc}");
		if ($idfunzionalita==""){
			return "1";
			//il componente non ha funzionalità...
		}
		$html.="funzionalit&agrave; trovate: $idfunzionalita<br>";

		$idmoduli = $this->getElencoId("select distinct idmodulo from ".DB_PREFIX."frw_com_mod where idcomponente={$idc}");
		if ($idmoduli==""){
			return "2";
			//il componente non e' installato in nessun modulo...
		}
		$html.="moduli trovati: $idmoduli<br>";

		$sql = "delete from ".DB_PREFIX."frw_ute_fun where idfunzionalita in ($idfunzionalita)";
		//echo "pulizia: $sql<hr>";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));

		$idprofili = $this->getElencoId("select distinct cd_profilo from ".DB_PREFIX."frw_profili_funzionalita where cd_funzionalita in ($idfunzionalita)");
		if ($idprofili==""){
			/*
				qua bisogna cancellare tutti gli ute_fun che hanno queste funzionalita
			*/
			return "3";
			//il componente non � associato a nessun profilo...
		}
		$html.="profili trovati: $idprofili<br>";

		$arFunz = explode(",",$idfunzionalita);
		$arModu = explode(",",$idmoduli);
		$arProf = explode(",",$idprofili);
		$qi = 0;
		$qs = 0;
		$qd = 0;

		// profile checks
		$sql = "select id,cd_profilo from ".DB_PREFIX."frw_utenti where cd_profilo NOT IN ($idprofili)";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
		while ($r = $rs->fetch_array()) {
			for ($j=0;$j<count($arModu);$j++) {
				for ($k=0;$k<count($arFunz);$k++) {
					$sql = "delete from ".DB_PREFIX."frw_ute_fun where idutente={$r['id']} and idfunzionalita={$arFunz[$k]} and idmodulo={$arModu[$j]}";
					$conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
					$qd++;
				}
			}
		}
		$rs->free();

		// functionalities check
		for ($i=0;$i<count($arProf);$i++) {
			$sql = "select id,cd_profilo from ".DB_PREFIX."frw_utenti where cd_profilo in ($idprofili)";
			$rs = $conn->query($sql) or (trigger_error($conn->error."<br>sql=\"{$sql}\""));
			$qs++;
			while ($r = $rs->fetch_array()) {
				for ($j=0;$j<count($arModu);$j++) {
					for ($k=0;$k<count($arFunz);$k++) {
						if ($r['cd_profilo']==$arProf[$i]) {
							$qs++;
							if ($this->checkExistProfiloConFunzionalita($arProf[$i],$arFunz[$k])) {
								$sql = "insert into ".DB_PREFIX."frw_ute_fun (idutente,idfunzionalita,idmodulo) values ( {$r['id']},{$arFunz[$k]},{$arModu[$j]})";
								$conn->query($sql);
								$errno = $conn->errno;
								if ($errno!=1062 && $errno>0) trigger_error($conn->error."<br>sql=\"{$sql}\"");
								$qi++;
							}
						}
					}
				}
			}
			$rs->free();
		}
		$html.="query select eseguite: $qs<br>";
		$html.="query insert eseguite: $qi<br>";
		$html.="query delete eseguite: $qd<br>";
		return $html;
	}

	/*
		helper: from an sql get a list of items comma separted
	*/
	function getElencoId($sql) {
		return concatenaId($sql);
	}

	function tendinaPosizione($selez,$index) {
		$o="";
		for ($i=0;$i<100;$i++){
			$o.="<option value='$i' ";
			if ($i==$selez) $o.="selected";
			$o.=">$i</option>";
		}
		$o = "<select name='tp_$index' onChange=\"document.dati.elements['idmoduli[]'][$index].value = document.dati.elements['idmoduli[]'][$index].value.substr(0,document.dati.elements['idmoduli[]'][$index].value.indexOf(',')) + ','+this.value;\">$o</select>";
		return $o;
	}

	function getListaCheckboxModuli($idcomp,$strValoriSelezionati="") {
		/*
			list of modules to assign component to modules
		*/
		global $conn;
		$sql = "select id as idmoduli,nome,label from ".DB_PREFIX."frw_moduli";
		if ($sql=="") return "";
		$rs = $conn->query ($sql);
		$html="<div class='list'><label></label><span>{Order}</span></div>";
		$c = 0;
		while ($r=$rs->fetch_array()) {
			$posiz="0";
			$posizSql = "select posizione from ".DB_PREFIX."frw_com_mod where idcomponente='$idcomp' and idmodulo='{$r['idmoduli']}'";
			$posizRs = $conn->query($posizSql);
			if ($posizRs->num_rows>0) {
				$posizR = $posizRs->fetch_array();
				$posiz= $posizR["posizione"];
			}
			$posizRs->free();
			$html.="<div class='list'><label for='chk{$c}'><input type=\"checkbox\" id='chk{$c}' name=\"idmoduli[]\" value=\"{$r['idmoduli']},$posiz\"";
			if (stristr(",".$strValoriSelezionati.",",",".$r["idmoduli"].",")) $html.=" checked";

			$html.="> {$r['label']}</label> ".$this->tendinaPosizione($posiz,$c);
			$html.="</div>";
			$c++;
		}
		return $html;
	}

	function getListaCheckboxProfili($strValoriSelezionati="", $funzionalita_id="") {
		global $conn;
		$sql = "select id_profilo as idprofili,de_label from ".DB_PREFIX."frw_profili";
		if ($sql=="") return "";
		$rs = $conn->query ($sql) or trigger_error($conn->error."<br>sql='$sql'");
		$html="";
		$c=0;
		while ($r=$rs->fetch_array()) {
			$html.="<label for='chkp{$c}' class='check level".$r["idprofili"]."'><input id='chkp{$c}' type=\"checkbox\" name=\"idprofili[]\" value=\"{$r['idprofili']}\"";
			if (stristr(",".$strValoriSelezionati.",",",".$r["idprofili"].",")) $html.=" checked";
			$html.="> {$r['de_label']}";
			$html.="</label>";
			$c++;
		}
		if($funzionalita_id!="") {
			$html.="<input type='hidden' name='funzionalita_id' value='$funzionalita_id'>";
		}

		return $html;
	}

	/*
		Menu item (AKA component) details form
	*/
	function getDettaglio($id="") {
		global $session, $root;

		if ($session->get("FRWCOMPONENTI")!="") {

			if ($id!="") {
				// security
				if((integer)$id<1000 && (integer)$session->get("idprofilo")<999999) return 0;

				/*
					modify
				*/
				$dati = $this->getDatiComponente($id);
				if($dati===false) return 0;
				$action = "modificaStep2";

			} else {

				$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_componenti") ;
				$action = "aggiungiStep2";
			}


			$html = loadTemplateAndParse("template/dettaglio.html");

			// form building
			$objform = new form();

			$objid = new hidden("id",$dati["id"]);
			$op = new hidden("op",$action);

			$submit = new submit("invia","salva");

			$nome = new testo("nome",$dati["nome"],30,30);
			$nome->obbligatorio=1;
			$nome->label="'{Name}'";
			$objform->addControllo($nome);
			
			$label = new testo("labelfield",$dati["label"],30,30);
			$label->obbligatorio=1;
			$label->label="'{Label}'";
			$objform->addControllo($label);

			$url = new testo("urlcomponente",$dati["urlcomponente"],100,30);
			$url->obbligatorio=1;
			$url->label="'{Url}'";
			$objform->addControllo($url);
		
			// combo icons
			$file = file_get_contents($root.'src/icons/fontello/css/fontello.css');
			$pattern = '/\.icon-(.*):before/';
			preg_match_all($pattern, $file, $matches);
			$iconNames = $matches[1];
			$aricons = array();
			foreach ($iconNames as $name) $aricons["icon-".$name]="icon-".$name;
			$iconlist = new optionlist("urliconamenu",$dati["urliconamenu"],$aricons );
			$iconlist->extraHtml = "&nbsp;<span id='iconshow' class='".$dati["urliconamenu"]."'></span>";
			$iconlist->obbligatorio=0;
			$iconlist->label="'{Icon}'";
			$objform->addControllo($iconlist);
			// ---------


			// combo target
			$artarget = array(""=>"_self","_blank"=>"_blank");
			$targetlist = new optionlist("target",$dati["target"],$artarget );
			$targetlist->obbligatorio=0;
			$targetlist->label="'{Target window}'";
			$objform->addControllo($targetlist);
			// ---------


            $fl_translate=new checkbox("fl_translate",1,$dati["fl_translate"]==1);
			$fl_translate->obbligatorio=0;
			$fl_translate->label="'{Translate}'";
			$objform->addControllo($fl_translate);


			if ($id!="") {
				/*
					modify
				*/
				$modulichecckati = $this->getElencoId("select idmodulo from ".DB_PREFIX."frw_com_mod where idcomponente={$id}");
				$moduli = $this->getListaCheckboxModuli( $dati["id"],$modulichecckati);
				$html = str_replace("##elencomoduli##", nl2br($moduli), $html);
				$sql = "select id, nome, label from ".DB_PREFIX."frw_funzionalita where idcomponente='{$id}'";
				
				// modules grid with this component
				$t=new grid(DB_PREFIX."frw_funzionalita",0, 40, "nome", "asc");
				//campi da visualizzare
				$t->campi="id,nome,label";
				$t->flagOrdinatori=false;
				//titoli dei campi da visualizzare
				$t->titoli="ID,{Slug},{Value}";
				//id per fare i link
				$t->chiave="id";
				//query per estrarre i dati
				$t->query="SELECT id, nome, label from ".DB_PREFIX."frw_funzionalita where idcomponente='{$id}'";
				$t->addComando("{$this->gestore}?op=modificaf&id=##id##","modifica");
				$t->addComando("javascript:eliminaf(##id##)","elimina");

				// list of functionalities for multiple connection (superadmin)
				$funzionalita = "<div class='panel2 internal'><div class='titlecontainer'>";
				if((integer)$session->get("idprofilo")==999999) 
					$funzionalita .= "<a href='{$this->gestore}?op=aggiungif&id={$id}' class='aggiungi' title='{Add functionality}'></a>";
				$funzionalita .= "</div></div>" . $t->show();


				// mono-functionality for admin user
				$idfunz = execute_scalar("select id from ".DB_PREFIX."frw_funzionalita where idcomponente={$id}",0);
				$profilichecckati = $this->getElencoId("select cd_profilo from ".DB_PREFIX."frw_profili_funzionalita where cd_funzionalita={$idfunz}");
				$profili = $this->getListaCheckboxProfili($profilichecckati,$idfunz);
					
	
			} else {
				/*
					Insert
				*/
				$modulichecckati = isset($_GET['cd_module']) ? (integer)$_GET['cd_module'] : "";
				$moduli = $this->getListaCheckboxModuli( "", $modulichecckati);
				$html = str_replace("##elencomoduli##", nl2br($moduli), $html);
				$funzionalita = "<i>{After insert...}</i>";
				
				// mono-functionality for admin user
				$idfunz = "";
				$profilichecckati = "999999";
				$profili = $this->getListaCheckboxProfili($profilichecckati,$idfunz);
				

			}


            $module = "";
            if (isset($_GET['cd_module'])) {
                $module = (integer)$_GET['cd_module'];
            }
            $objmoduleid = new hidden("cd_module",$module);


			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			
			$html = str_replace("##labelid##", $id ? $id : "{n.a.}", $html);

			$html = str_replace("##id##", $objid->gettag(), $html);
			$html = str_replace("##cd_module##", $objmoduleid->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##nome##", $nome->gettag(), $html);
			$html = str_replace("##label##", $label->gettag(), $html);
            $html = str_replace("##fl_translate##", $fl_translate->gettag(), $html);
			$html = str_replace("##url##", $url->gettag(), $html);
			$html = str_replace("##iconlist##", $iconlist->gettag(), $html);
			$html = str_replace("##targetlist##", $targetlist->gettag(), $html);
			$html = str_replace("##elencoprofili##", nl2br($profili), $html);
			$html = str_replace("##elencofunzionalita##", $funzionalita, $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = returnmsg("Non sei autorizzato.");
		}
		return $html;
	}

	function getDatiFunzionalita($id) {
		$sql = "SELECT * from ".DB_PREFIX."frw_funzionalita where id='{$id}'";
		return execute_row($sql);
	}

	function getDatiComponente($id) {
		$sql = "SELECT * from ".DB_PREFIX."frw_componenti where id='{$id}'";
		return execute_row($sql);
	}

	/*
		functionalities detail form
	*/
	function getDettaglioF($id="",$idcomponente="") {
		global $session;

		if ($session->get("FRWCOMPONENTI")!="" && $session->get("idprofilo")==999999) {
			if ($id!="") {
				/*
					modify
				*/
				$html = loadTemplateAndParse("template/dettagliof.html");
				$dati = $this->getDatiFunzionalita($id);
				if(empty($dati)) return "0";
				$datiC = $this->getDatiComponente($dati["idcomponente"]);
				if(empty($dati)) return "0";
				$html = str_replace("##componente##", "{$datiC['nome']} (id n.{$datiC['id']})", $html);
				$html = str_replace("##idcomponente##", "{$datiC['id']}", $html);
				$html = str_replace("##descrizione##", $dati["descrizione"], $html);
				$html = str_replace("##id##", $dati["id"], $html);
				$html = str_replace("##labelid##", $dati["id"], $html);
				$html = str_replace("##action##", "modificafStep2", $html);
				$html = str_replace("##nome##", htmlspecialchars($dati["nome"]), $html);
				$html = str_replace("##label##", htmlspecialchars($dati["label"]), $html);
				$html = str_replace("##gestore##", $this->gestore, $html);

				$modulichecckati = $this->getElencoId("select idmodulo from ".DB_PREFIX."frw_com_mod where idcomponente={$datiC['id']}");
				$html = str_replace("##modulichecckati##", $modulichecckati, $html);

				$profilichecckati = $this->getElencoId("select cd_profilo from ".DB_PREFIX."frw_profili_funzionalita where cd_funzionalita={$id}");

				$profili = $this->getListaCheckboxProfili($profilichecckati);

				$html = str_replace("##elencoprofili##", nl2br($profili), $html);

			} else {
				/*
					Insert
				*/


				$html = loadTemplateAndParse("template/dettagliof.html");
				$datiC = $this->getDatiComponente($idcomponente);
				if($datiC===false) return 0;
				$html = str_replace("##componente##", "{$datiC['nome']} (id n.{$datiC['id']})<br>{$datiC['label']}", $html);
				$html = str_replace("##idcomponente##", "{$datiC['id']}", $html);
				$modulichecckati = $this->getElencoId("select idmodulo from ".DB_PREFIX."frw_com_mod where idcomponente={$datiC['id']}");
				$html = str_replace("##modulichecckati##", $modulichecckati, $html);
                $html = str_replace("##descrizione##", "", $html);
				$html = str_replace("##id##", "", $html);
				$html = str_replace("##labelid##", "<i>non ancora assegnato</i>", $html);
				$html = str_replace("##action##", "aggiungifStep2", $html);
				$html = str_replace("##nome##", "", $html);
				$html = str_replace("##label##", "", $html);
				$html = str_replace("##gestore##", $this->gestore, $html);
				$profili = $this->getListaCheckboxProfili( "");
				$html = str_replace("##elencoprofili##", nl2br($profili), $html);
			}

		} else {
			$html = returnmsg("{You're not authorized.}");
		}
		return $html;
	}




	function checkExistIn($nometabella,$nomecampo,$valore,$giaesistente,$campoidunivoco) {
		global $conn;
		$sql = "select $nomecampo from $nometabella where $nomecampo='$valore' and $campoidunivoco<>'$giaesistente'";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
		if ($rs->num_rows>0) $risultato = true; else $risultato=false;
		$rs->free();
		return $risultato;
	}

	function checkExistProfiloConFunzionalita($p,$f) {
		global $conn;
		$sql = "select cd_profilo from ".DB_PREFIX."frw_profili_funzionalita where cd_profilo='$p' and cd_funzionalita='$f'";
		$rs = $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
		if ($rs->num_rows>0) $risultato = true; else $risultato=false;
		$rs->free();
		return $risultato;
	}

	function addComMod($idcomponente,$arModuli) {
		/*
			add connection between module and components
		*/
		global $conn;
		$sql ="delete from ".DB_PREFIX."frw_com_mod where idcomponente='{$idcomponente}'";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
		//echo "{$sql}<br>";
		for ($i=0;$i<count($arModuli);$i++) {
			$idmodulo = substr($arModuli[$i],0,strpos($arModuli[$i],","));
			$posizione =substr($arModuli[$i],strlen($idmodulo)+1);
			$sql ="insert into ".DB_PREFIX."frw_com_mod (idcomponente,idmodulo,posizione) values ($idcomponente,$idmodulo,$posizione)";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
			//echo "{$sql}<br>";

		}
	}

	function addFunPro($idfunzionalita,$arProfili,$modulichecckati) {
		/*
			add connection between functionalities and profiles
		*/
		global $conn;
		$sql ="delete from ".DB_PREFIX."frw_profili_funzionalita where cd_funzionalita='{$idfunzionalita}'";
		$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
		$idmodulo = explode(",",$modulichecckati);
		if (count($idmodulo)==0) return "1";
		for ($j=0;$j<count($idmodulo);$j++) {
            if($idmodulo[$j] > 0) {
                for ($i=0;$i<count($arProfili);$i++) {
                    $sql ="insert into ".DB_PREFIX."frw_profili_funzionalita (cd_profilo,cd_modulo,cd_funzionalita) values ({$arProfili[$i]},{$idmodulo[$j]},$idfunzionalita)";
                    $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'; mc=".$modulichecckati));
                }
            }
		}
		return "";
	}


	function updateAndInsert($arDati) {

		global $session,$conn;
		if ($session->get("FRWCOMPONENTI")!="") {

            if (!isset($arDati["fl_translate"])) $arDati["fl_translate"] = "0";

			if ($arDati["id"]!="") {

				// security
				if((integer)$arDati["id"]<1000 && (integer)$session->get("idprofilo")<999999) return 0;

				/*
					Modify
				*/
				
				if (!$this->checkExistIn(DB_PREFIX."frw_componenti","nome",$arDati["nome"],$arDati["id"],"id")){
					$sql="UPDATE ".DB_PREFIX."frw_componenti set nome='##nome##',label='##label##',urlcomponente='##urlcomponente##',
                    fl_translate='##fl_translate##',
                    urliconamenu='##urliconamenu##',target='##target##' where id='##id##'";
					$sql= str_replace("##nome##",$arDati["nome"],$sql);
					$sql= str_replace("##label##",$arDati["labelfield"],$sql);
					$sql= str_replace("##fl_translate##",$arDati["fl_translate"],$sql);
					$sql= str_replace("##urlcomponente##",$arDati["urlcomponente"],$sql);
					$sql= str_replace("##urliconamenu##",$arDati["urliconamenu"],$sql);
					$sql= str_replace("##target##",$arDati["target"],$sql);
					$sql= str_replace("##id##",$arDati["id"],$sql);

					$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

					if (!isset($arDati["idmoduli"])) $arDati["idmoduli"] = array();
					$this->addComMod(
						$arDati["id"],
						$arDati["idmoduli"]
					);

					// save single function binding
					if(isset($arDati["funzionalita_id"])) {
						if (!isset($arDati["idprofili"])) $arDati["idprofili"] = array();
						$mCheckati = "";
						foreach($arDati["idmoduli"] as $m) $mCheckati.=explode(",",$m)[0];
						$this->addFunPro(
							$arDati["funzionalita_id"] ,
							$arDati["idprofili"],
							$mCheckati
						);
                         $this->profila_service($arDati["id"]);
    
					}

					$html= "";



				} else {
					$html="1";	//already used
				}
			} else {
				/*
					Insert
				*/
				if (!$this->checkExistIn(DB_PREFIX."frw_componenti","nome",$arDati["nome"],$arDati["id"],"id")){
					$sql="INSERT into ".DB_PREFIX."frw_componenti (nome,label,urlcomponente,target,fl_translate) values('##nome##','##label##','##urlcomponente##','##target##','##fl_translate##')";
					$sql= str_replace("##nome##",$arDati["nome"],$sql);
					$sql= str_replace("##label##",$arDati["labelfield"],$sql);
					$sql= str_replace("##urlcomponente##",$arDati["urlcomponente"],$sql);
                    $sql= str_replace("##target##",$arDati["target"],$sql);
					$sql= str_replace("##fl_translate##",$arDati["fl_translate"],$sql);
					

					$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
					$id_com = $conn->insert_id;

					if (!isset($arDati["idmoduli"])) $arDati["idmoduli"] = array();
					$this->addComMod(
						$id_com ,
						$arDati["idmoduli"]
					);


					if(getVarSetting("CREA_FUNZIONI_AUTOMATICAMENTE")=="1") {
						$this->updateAndInsertF(
							array ( "id" => "",
								"op" => "aggiungifStep2",
								"nome" => $arDati["nome"],
								"label" => $arDati["nome"],
								"descrizione" => $arDati["nome"],
								"idcomponente" => $id_com,
								"modulichecckati" => $this->getElencoId("select idmodulo from ".DB_PREFIX."frw_com_mod where idcomponente={$id_com}"),
								"idprofili" => $arDati["idprofili"]
							)
						);
					}

					$html= "";

				} else {
					$html="1";	//already used
				}

			}

            if($html=="") {
                //
                // if it comes from module editor
                // goes back to module editor
                $idmodule_redirect = $arDati['cd_module'];
                if($idmodule_redirect) {
                   $html = "5|".$idmodule_redirect;
       
                }
            }

		} else {
			$html="0";		// no permission
		}
		return $html;
	}


	/* the same for functionalities */
	function updateAndInsertF($arDati) {

		global $session,$root,$conn;
		if ($session->get("FRWCOMPONENTI")!="") {
			if ($arDati["modulichecckati"]<>"") {
				if ($arDati["id"]!="") {
					/*
						Modify
					*/
					$sql="UPDATE ".DB_PREFIX."frw_funzionalita set idcomponente='##idcomponente##',label='##label##',descrizione='##descrizione##',nome='##nome##' where id='##id##'";
					$sql= str_replace("##nome##",$arDati["nome"],$sql);
					$sql= str_replace("##label##",$arDati["label"],$sql);
					$sql= str_replace("##descrizione##",$arDati["descrizione"],$sql);
					$sql= str_replace("##idcomponente##",$arDati["idcomponente"],$sql);
					$sql= str_replace("##id##",$arDati["id"],$sql);

					$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

					if (!isset($arDati["idprofili"])) $arDati["idprofili"] = array();


					$html= "";
				} else {
					/*
						Insert
					*/
					$sql="INSERT into ".DB_PREFIX."frw_funzionalita (nome,label,descrizione,idcomponente) values('##nome##','##label##','##descrizione##','##idcomponente##')";
					$sql= str_replace("##nome##",$arDati["nome"],$sql);
					$sql= str_replace("##label##",$arDati["label"],$sql);
					$sql= str_replace("##descrizione##",$arDati["descrizione"],$sql);
					$sql= str_replace("##idcomponente##",$arDati["idcomponente"],$sql);

					$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

					if (!isset($arDati["idprofili"])) $arDati["idprofili"] = array();
                    $arDati["id"] = $conn->insert_id;

				}

                // print_r($arDati);
                // die;

                $this->addFunPro(
                    $arDati["id"] ,
                    $arDati["idprofili"],
                    $arDati["modulichecckati"]
                );
                 $this->profila_service($arDati["idcomponente"]);
                 $html="";

			} else {
				return "1"; // the component isn't connected to a module 
			}
		} else {
			$html="0";		// no permission
		}
		return $html;
	}

	function deleteF($elencoIdF) {
		/*
			delete functionalities, also string array comma separated
		*/
		global $session,$root,$conn;
		if ($session->get("FRWCOMPONENTI")!="") {
			$sql="DELETE FROM ".DB_PREFIX."frw_funzionalita where id in ($elencoIdF)";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));


			$sql="DELETE FROM ".DB_PREFIX."frw_profili_funzionalita where cd_funzionalita in ($elencoIdF)";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

			$sql="DELETE FROM ".DB_PREFIX."frw_ute_fun where idfunzionalita in ($elencoIdF)";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));

			// $session->register("backbutton","<br><a href=\"javascript:history.back()\"><img src=\"{$root}images/back.gif\" border=\"0\"> torna</a>");

			return "";
		}
		return "0";
	}

	function deleteC($id) {
		/*
			delete component
		*/

		global $session,$conn;
		if ($session->get("FRWCOMPONENTI")!="") {

			
			//security
			if((integer)$id<1000 && (integer)$session->get("idprofilo")<999999) return 0;


			$sql="DELETE FROM ".DB_PREFIX."frw_componenti where id='$id'";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
			$sql="DELETE FROM ".DB_PREFIX."frw_com_mod where idcomponente='$id'";
			$conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
			$sql="SELECT id from ".DB_PREFIX."frw_funzionalita where idcomponente='$id'";
			$rs= $conn->query($sql) or (trigger_error($conn->error."<br>sql='{$sql}'"));
			$elencoIdF="";
			while($r=$rs->fetch_array()) {
				if ($elencoIdF!="")$elencoIdF.=",";
				$elencoIdF.=$r["id"];
			}
			$rs->free();
			if ($elencoIdF!="") {
				$this->deleteF($elencoIdF);
			}
			$html = "";
		} else {
			$html="0";	
		}
		return $html;

	}



	function getHtmlcombotipo($def="") {
		global $conn;
		//------------------------------------------------
		//combo filter
		$sql = "SELECT CONCAT( ".DB_PREFIX."frw_moduli.nome, ' - ', ".DB_PREFIX."frw_moduli.label)  as nome,idmodulo,count(1) as q FROM `".DB_PREFIX."frw_com_mod` inner join ".DB_PREFIX."frw_moduli on idmodulo=frw_moduli.id group by idmodulo";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		if($rs->num_rows > 1 || $rs->num_rows == 0) $arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			
				$arFiltri[$riga['idmodulo']] = $riga['nome']. " (".$riga['q'].")";

		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}

}

?>