<?php
/*

	model to manage banners

*/

DEFINE("LASTDATE","2099-12-31");
DEFINE("MD5CKEY", "PO2OSOP129DKKXM20102O");

class Banner {

	// properties for uplading banners
	var $uploadDir;
	var $maxX;
	var $maxY;
	var $maxKB;
	var $max_files;

	var $tbdb;	// table

	var $start;	// first record
	var $omode;	// asc|desc
	var $oby;	// ordered by field
	var $ps;	// pagesize: number of rows per page

	var $linkaggiungi;	// add link
	var $linkmodifica;	// edit link
	var $linkstats;	// stats link
	var $linkeliminamarcate;
	var $linkduplica;

	var $gestore;

	var $dashObject;

	function __construct ($tbdb="7banner",$ps=20,$oby="dt_giorno1",$omode="desc",$start=0) {
		global $session,$root,$conn;
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
		$this->linkduplica = "$this->gestore?op=duplica&id=##id_banner##";
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_banner##";
		$this->linkstats = "$this->gestore?op=stats&id=##id_banner##";

		checkAbilitazione("BANNER","SETTA_SOLO_SE_ESISTE");
		checkAbilitazione("DASHBOARD","SETTA_SEMPRE");
	}


	/**
	 * return HTML labels for banner status. "css" return array with css classes in html span,
	 * blank return array with just labels
	 * 
	 * @param string $type css|text
	 */
	function getStatusLabels($type="") {
		$stati = array(
			"A"=>"<span class='label labelgreen'>{SERVING}</span>",
			"S"=>"<span class='label labelgreendark'>{ENDED}</span>",
			"L"=>"<span class='label labelgreen'>{SERVING}</span>",
			"P"=>"<span class='label labelgrey'>{PAUSED}</span>",
			"F"=>"<span class='label labelgreenlight'>{SCHEDULED}</span>",
			"K"=>"<span class='label labelyellow'>{PENDING}</span>",
			"D"=>"<span class='label labelred'>{NOT PAID}</span>",
			"W"=>"<span class='label labelyellow coinbase'>{WAITING PAYMENT}</span>",
			"M"=>"<span class='label labelyellow manual'>{WAITING PAYMENT}</span>"	
		);
		if($type=="css")  return $stati;
		foreach($stati as $k=>$v)  $stati[$k]=strip_tags($v);
		return $stati;
		
	}


	/**
	 * show the list of items, filtered by type and other filters. results depends on user profiles
	 * 
	 * @param string $combotipo
	 * @param string $comboclient
	 * @param string $combocampaign
	 * @param string $combotiporeset
	 * @param string $keyword
	 * 
	 * @return string HTML
	 */
	function elenco($combotipo="",$comboclient="",$combocampaign="",$combotiporeset="",$keyword="") {
		global $session;

		$html = "";

		if ($session->get("BANNER")) {
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
			$t->checkboxForm= true; 
			$t->functionhtml = "";	
			$t->ajaxMode = false;	
			$t->mostraRecordTotali = true;

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			// fields to show
			$t->campi="cliente,nome,dt_giorno1,dt_giorno2b,giorni,nu_pageviews,tasso,nu_clicks,de_posizioneb,stato";

			// title of fields in the head of grid
			$t->titoli="{Client},{Banner},{From},{To},{Duration},{Impressions},{Daily},{Clicks},{Position},{Status}";

			// key field id for links
			$t->chiave="id_banner";

			// advertiser profile sees only its banners
			$miocliente = "";
			if( $session->get("idprofilo") == 5) {
				$miocliente = concatenaId("select id_cliente from 7banner_clienti where cd_utente='".$session->get("idutente")."'");
				if ($miocliente) $miocliente = " AND id_cliente in (".$miocliente.")"; else $miocliente = " AND 1=0";
			}

			// build the big query sql
			// $t->debug = true;
			$t->query="SELECT id_banner, CONCAT(".DB_PREFIX."7banner_clienti.de_nome,'|^',de_titolo) as cliente, CONCAT(7banner.de_nome,'|^',fl_stato,'|^',CAST(id_banner AS CHAR CHARACTER SET utf8)) as nome,dt_giorno1,
				(CASE WHEN dt_giorno2='".LASTDATE."' THEN 
					null
					ELSE
					dt_giorno2
					END) as dt_giorno2b,
				CONCAT( CAST( DATEDIFF(dt_giorno2,dt_giorno1)+1 AS CHAR CHARACTER SET utf8 ), '|',nu_maxtot,'|',nu_maxclick) as giorni,
				nu_pageviews,
				(CASE WHEN dt_giorno1>=CURDATE() THEN '-'
					WHEN dt_giorno2>CURDATE() THEN 
					ROUND(nu_pageviews/DATEDIFF(CURDATE(),dt_giorno1) ) ELSE
					ROUND(nu_pageviews/DATEDIFF(dt_giorno2,dt_giorno1) ) 
					END ) as tasso,
				nu_clicks,
				CONCAT(de_posizione,'|^',modello_vendita,'|^', (CASE WHEN ISNULL(de_nomesito) THEN '' ELSE de_nomesito END),'|^',cd_posizione ) as de_posizioneb,
				CONCAT(".DB_PREFIX.$this->tbdb.".fl_stato,'|^',CAST(dt_giorno1 AS CHAR CHARACTER SET utf8),'|^',CAST(id_banner AS CHAR CHARACTER SET utf8)) as stato
				FROM ".DB_PREFIX.$this->tbdb." 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
				INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
				INNER JOIN ".DB_PREFIX."7banner_clienti ON ".DB_PREFIX."7banner_campagne.cd_cliente=id_cliente ".$miocliente." 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites ON ".DB_PREFIX."7banner_posizioni.cd_sito=id_sito
		

				";


			//
			// special filters for MiniAdministrators
			// show only its positions
			if ($session->get("idprofilo")==15) {
				$t->query.=" INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin as PP on PP.cd_position=cd_posizione AND PP.cd_user='".$session->get("idutente")."'";
			}


			$where = "";
			if($combotipo) {
				if($combotipo=="-999") {

				} else {
					$temp = explode("|",$combotipo);
					/*
						"A"=>   SERVING
						"S"=>   ENDED
						"L"=>	SERVING (LAST viewed)
						"P"=>   PAUSED
						"F"=>   SCHEDULED  (it's "S" with date in the future)
						"K"=>   PENDING  (to be approved)
						"D"=>   NOT PAID (to be paid)
						"W"=>   WAIT CONFIRMATION FROM BLOCKCHAIN
                        "M"=>   WAIT MANUAL CONMFIRMATION
					*/
					if($temp[1]=='A') { // combo A is different from status A
						$temp[2] = " in ('A','L','P','K','W','M') ";
					} elseif($temp[1]=='S') {
						$temp[2] = " in ( 'S','D')";
					} elseif($temp[1]=='T') { // Active
						$temp[2] = " in ('A','L','P','K','W','M') ";
					} elseif($temp[1]=='X') { // Inactive
						$temp[2] = " in ( 'S','D')";
					} 
					if($where!="") { $where.= " and "; }
					$where.=($temp[0]!="-999" ? "cd_posizione='{$temp[0]}' and " : "") ." fl_stato ".$temp[2]." ";
				}
			}
			if($comboclient && $comboclient!="-999") {
				if($where!="") { $where.= " and "; }
				$where.="cd_cliente='{$comboclient}'" ;
			}
			if($combocampaign && $combocampaign!="-999") {
				if($where!="") { $where.= " and "; }
				$where.= "cd_campagna='{$combocampaign}' " ;
			}
			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (".DB_PREFIX.$this->tbdb.".de_nome like '%{$keyword}%' or  ".DB_PREFIX."7banner_clienti.de_nome like '%{$keyword}%' or  de_url like '%{$keyword}%')";
			}
			

			if($where) {
				$t->query.=" where {$where}";
			}

			// custom callbacks
			$t->addCampi('nome',"imageAndTitle");
			$t->addCampi('cliente',"showClientName");
			$t->addCampi('stato',"statofut");
			$t->addCampi('giorni',"mostralimiti");
			$t->addCampi('de_posizioneb',"posizione");

			$t->arFormattazioneTD=array(
				"giorni" => "numero",
				"nu_pageviews" => "numero",
				"nu_clicks" => "numero",
				"tasso" => "numero",
				"CTR" => "numero",
			);

			$t->addComando($this->linkstats,"stats","{Stats}");
			if($session->get("idprofilo")>5) $t->addComando($this->linkduplica,"duplica","{Make a copy}");

			$t->addCampiDate("dt_giorno1",DATEFORMAT);
			$t->addCampiDate("dt_giorno2b",DATEFORMAT);
			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";
			$html .= $texto."<br/>";

		} else {
			$html = "0";
		}
		return $html;
	}











	/**
	 * check if the user can see the position
	 * 
	 * @param integer $id    id of position
	 * 
	 * @return boolean
	 */
	function check_position($id) {
		global $session;
		// miniadmin
		if($session->get("idprofilo")==15) {
			if(!$id) return true;
			$q = execute_scalar( "select count(1) from ".DB_PREFIX."7banner_pos_miniadmin 
				WHERE cd_position='$id' AND cd_user='".$session->get("idutente")."'");
			return $q>0;
		}
		return true;
	}


	/**
	 * given an id of a banner or an id of a campaign (with -999| at the begin) 
	 * returns true if the user is the owner of the banner
	 * 
	 * @param mixed $id  banner id or campaign id as -999|idcampagna
	 * 
	 * @return boolean
	 */
	function check_cliente($id) {
		global $session;

		// advertiser
		if($session->get("idprofilo")==5) {
			if(!$id) return true;
			if(stristr($id,"|")) {
				//
				// it's a campaign
				$temp = explode("|",$id);
				if($temp[0]=="-999") {
					$cd_campagna= $temp[1];
					$q = execute_scalar("
						select count(*) from ".DB_PREFIX."7banner_campagne 
							inner join ".DB_PREFIX."7banner_clienti ON ".DB_PREFIX."7banner_clienti.id_cliente=".DB_PREFIX."7banner_campagne.cd_cliente 
							where id_campagna='".$cd_campagna."'"
					);
					return $q>=1;
				}
				return false;
			}
			$q = execute_scalar( "select count(*) from ".DB_PREFIX."7banner inner join ".DB_PREFIX."7banner_campagne on cd_campagna=id_campagna
				inner join ".DB_PREFIX."7banner_clienti on ".DB_PREFIX."7banner_clienti.id_cliente=".DB_PREFIX."7banner_campagne.cd_cliente
				WHERE id_banner='$id' AND cd_utente='".$session->get("idutente")."'");
			return $q>=1;
		} else {
			
			// admin
			return true;

		}
	}

	

	/**
	 * fill in the form view
	 * 
	 * @param mixed $id (integer or empty string)
	 * @param string $op
	 * 
	 * @return string
	 * */
	function getDettaglio($id="",$op="modifica") {
		global $session,$root,$conn;

		if(PAYMENTS=="OFF" && $session->get("idprofilo")==5) return "0";

		$onlybasic = "0";
		if($session->get("idprofilo")==5) {
			// check extra permissions flags for advertiser
			// for example it's an advertiser limited to basic banners
			$u=new user();
			$onlybasic = $u->getManualPermission($session->get("idutente"), 'BANNER_LIMITTOBBASIC');
		}


		// css classes
		$bodyclass="";
		if($id=="") $bodyclass='create ';
		if($session->get("idprofilo")>=20) $bodyclass .='admin ';
		if($session->get("idprofilo")==5) $bodyclass.='advertiser';

		if ($session->get("BANNER") && $this->check_cliente($id) ) {
			if ($id!="") {
				/*
					edit or duplicate
				*/
				$dati = $this->getDati($id);

				// check ownership of position for miniadmin
				if(!$this->check_position($dati['cd_posizione'])) return "0";

				if($op=='duplica') {
					$dati['id_banner']='';
					$id = '';
					$dati["de_nome"] = '[{COPY}] '.$dati["de_nome"];
					$ts1 = strtotime($dati["dt_giorno1"]);
					$ts2 = strtotime($dati["dt_giorno2"]);
					$seconds_diff = $ts2 - $ts1;
					$dati["dt_giorno1"]= $dati["dt_giorno1"] > date("Y-m-d") ? $dati["dt_giorno1"] : date("Y-m-d");
					$dati["dt_giorno2"]= date("Y-m-d",(strtotime($dati["dt_giorno1"]) + $seconds_diff));
					$dati["nu_clickthrought"]= 0;
					$dati["nu_pageviews"]= 0;
					$dati["nu_clicks"]= 0;

					$action= "aggiungiStep2";
				} else {
					$action = "modificaStep2";
				}
			} else {
				/*
					insert new
				*/
				$dati = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
				$dati["nu_maxday"] = "0";
				$dati["nu_maxtot"] = "0";
				$dati["nu_maxclick"] = "0";
				$action = "aggiungiStep2";
                $dati["fl_stato"]="D";
			}

		
			$html = loadTemplateAndParse("template/dettaglio.html");

			// form building
			$objform = new form();


			// title of banenr
			$de_nome = new testo("de_nome",$dati["de_nome"],100,50);
			$de_nome->obbligatorio=1;
			$de_nome->label="'{Title}'";
			$objform->addControllo($de_nome);
		
			// max total impressions
			$nu_maxtot = new intero("nu_maxtot",$dati["nu_maxtot"],10,10);
			$nu_maxtot->obbligatorio=0;
			$nu_maxtot->label="'{Max total impressions}'";
			$objform->addControllo($nu_maxtot);

			// max numbers of days
			$nu_maxday = new intero("nu_maxday",$dati["nu_maxday"],10,10);
			$nu_maxday->obbligatorio=0;
			$nu_maxday->label="'{Max daily impressions}'";
			$objform->addControllo($nu_maxday);

			// max number of clicks
			$nu_maxclick = new intero("nu_maxclick",$dati["nu_maxclick"],10,10);
			$nu_maxclick->obbligatorio=0;
			$nu_maxclick->label="'{Max total click}'";
			$objform->addControllo($nu_maxclick);

			// alternative script code for banner
			$de_codicescript = new areatesto("de_codicescript",(($dati["de_codicescript"])),5,90);
			$de_codicescript->obbligatorio=0;
			$de_codicescript->label="'{Alternative script}'";
			$de_codicescript->attributes.=" class='code' rel='code' placeholder='{Put your script here...}'";
			$objform->addControllo($de_codicescript);

			$valore = ($dati["dt_giorno1"]=="") ? date("Y-m-d") : $dati["dt_giorno1"];
			$dt_giorno1 = new data("dt_giorno1",$valore,"aaaa-mm-gg",$objform->name);
			$dt_giorno1->obbligatorio=1;
			$dt_giorno1->label="'{Start date}'";
			$objform->addControllo($dt_giorno1);

			$valore = ($dati["dt_giorno2"]=="") ? date("Y-m-d") : $dati["dt_giorno2"];
			$dt_giorno2 = new data("dt_giorno2",$valore,"aaaa-mm-gg",$objform->name);
			$dt_giorno2->obbligatorio=1;
			$dt_giorno2->label="'{End date}'";
			$objform->addControllo($dt_giorno2);


			// possible status based on profile
			if(PAYMENTS=="ON") {
				if($session->get("idprofilo")>=15) {
					if($dati["fl_stato"]=="K") {
						$stati = array("K"=>"{Pending}", "A"=>"{Serving}");
					} elseif($dati["fl_stato"]=="D") {
						$stati = array("A"=>"{Serving}" ,"D"=>"{Not paid}");
					} elseif($dati["fl_stato"]=="M") {
						$stati = array("A"=>"{Serving}" ,"D"=>"{Not paid}");
					} else {
						$stati = array("A"=>"{Serving}" ,"S"=>"{Ended}" ,"P"=>"{Paused}","K"=>"{Pending}","D"=>"{Not paid}");
					}
				}
				if($session->get("idprofilo")==5) {
					if(!$id) {
						// advertiser creation of banner
						$stati = array("D"=>"{Not paid}");
					} elseif($id && $dati["fl_stato"]=="D") {
						$stati = array("D"=>"{Not paid}");
					} elseif($id && $dati["fl_stato"]=="K") {
						$stati = array("K"=>"{Pending}");
					} else {
						$stati = array("A"=>"{Serving}" ,"S"=>"{Ended}" ,"P"=>"{Paused}");
					}
				}


			} else {
				$stati = array("A"=>"{Serving}" ,"S"=>"{Ended}" ,"P"=>"{Paused}");

			}

			$fl_stato = new optionlist("fl_stato",(($dati["fl_stato"])), $stati );
			$fl_stato->obbligatorio=0;
			$fl_stato->label="'{Status}'";
			$objform->addControllo($fl_stato);


			$de_target = new optionlist("de_target",$dati["de_target"],
				array("_blank"=>"{New window}" ,"_self"=>"{Same window}") );
			$de_target->obbligatorio=0;
			$de_target->label="'{Target}'";
			$objform->addControllo($de_target);

			$de_url = new urllink("de_url",$dati["de_url"],255,60);
			$de_url->obbligatorio=0;
			$de_url->label="'{Link}'";
			$objform->addControllo($de_url);




			//------------------------------------------------
			//combo campaign
			$miocliente = $session->get("idprofilo")==5 ? " AND cd_cliente in (". execute_scalar("select id_cliente from ".DB_PREFIX."7banner_clienti where cd_utente='".$session->get("idutente")."' and id_cliente<>0","0").")" : "";
			$sql = "select id_campagna,CONCAT(de_titolo,' - ',de_nome) as nome_campagna,de_nome,de_titolo from ".DB_PREFIX."7banner_campagne 
				inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente ".(!$id ? " and fl_status=1 ": "")." 
				WHERE 1 ".$miocliente."
				order by de_nome,de_titolo";
			$cd_campagna = $this->getSelectOptions( $sql, "id_campagna", "nome_campagna", $dati["cd_campagna"], "cd_campagna", true, "{Campaign}","{choose}");
			$objform->addControllo($cd_campagna);
			//------------------------------------------------


			//------------------------------------------------
			//combo positions
			$miaposizione = ($session->get("idprofilo")==15 ? "INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin PP ON id_posizione=PP.cd_position AND PP.cd_user='".$session->get("idutente")."'" : "");
			$sql = "select id_posizione,CONCAT('[ ',id_posizione,' ] · ',IFNULL(de_nomesito,'{n.a.}'),' - ',de_posizione) as label from ".DB_PREFIX."7banner_posizioni 
				left outer join ".DB_PREFIX."7banner_sites on cd_sito=id_sito ".$miaposizione." ".
				"WHERE 1=1 ";
			if($session->get("idprofilo")==5) {
				$sql .= "AND vendita_online=1 ";
				$sql .= "AND ( id_sito IS NULL OR ".DB_PREFIX."7banner_sites.fl_status=1 OR id_posizione='".$dati["cd_posizione"]."') ";
			}
			$sql.="order by label";
			$cd_posizione = $this->getSelectOptions( $sql, "id_posizione", "label", $dati["cd_posizione"], "cd_posizione", true, "{Position}","{choose}");
			$objform->addControllo($cd_posizione);
			//------------------------------------------------




			$nu_price = new numerodecimale("nu_price",number_format((float)$dati["nu_price"],2,".",""),10,10,2);
			$nu_price->obbligatorio=0;
			$nu_price->label="'{Price}'";
			$nu_price->attributes.=' style="text-align:right" class="small"';
			$objform->addControllo($nu_price);
			if(PAYMENTS=="ON") {
				if($session->get("idprofilo")>=20) {
					// nothing
				} else {
					$objform->addControllo($nu_price, "nu_price.value < ". MIN_PRICE, "{Minimum transaction is} ".MIN_PRICE.MONEY);
				}
			}
            
            $arRedux = array(0=> "{No reduction}");
            for($i=1;$i<99;$i++) $arRedux[$i]="{$i}%";
            $nu_redux = new optionlist("nu_redux",$dati["nu_redux"], $arRedux);
			$nu_redux->obbligatorio=0;
			$nu_redux->label="'{Redux factor}'";
			$objform->addControllo($nu_redux);

			$nu_mobileflag = new optionlist("nu_mobileflag",($dati["nu_mobileflag"]), array(
					"0"=> "{Desktop + mobile}",
					"1"=> "{Only desktop}",
					"2"=> "{Only mobile}"
				));
			$nu_mobileflag->obbligatorio=0;
			$nu_mobileflag->label="'{Device type}'";
			$objform->addControllo($nu_mobileflag);
			$dati["se_os"] = (string)$dati["se_os"];
			$se_os = new checkboxlist("se_os",explode(",", $dati["se_os"]), set_and_enum_values("7banner","se_os"));
			$se_os->obbligatorio=0;
			$se_os->label="'{OS}'";
			$objform->addControllo($se_os);




			$nu_cap = new optionlist("nu_cap",($dati["nu_cap"]), array(
					"0"=> "{No limits}",
					"1"=> "{1 time per day per user}",
					"2"=> "{2 times per day per user}",
					"3"=> "3",
					"4"=> "4",
					"5"=> "5",
					"6"=> "6",
					"7"=> "7",
					"8"=> "8",
					"9"=> "9",
					"10"=> "10",
				));
			$nu_cap->obbligatorio=0;
			$nu_cap->label="'{Frequency cap}'";
			$objform->addControllo($nu_cap);


			//------------------------------------------------
			//combo country
			$sql = "select distinct country_name from ip2location_db3 where country_name<>'' order by country_name";
			$de_country = $this->getSelectOptions( $sql, "country_name", "country_name", $dati["de_country"], "de_country", false, "{Country}","{all}");

			

			$objform->addControllo($de_country);
			//------------------------------------------------

			//------------------------------------------------
			//combo region
			$sql = "select distinct region_name from ip2location_db3 where region_name<>'' and country_name='".addslashes($dati["de_country"])."' order by region_name";
			$de_region = $this->getSelectOptions( $sql, "region_name", "region_name", $dati["de_region"], "de_region", false, "{Region}","{all}");
			$objform->addControllo($de_region);
			//------------------------------------------------

			//------------------------------------------------
			//combo city
			$sql = "select distinct city_name from ip2location_db3 where city_name<>'' and region_name='".addslashes($dati["de_region"])."' order by city_name";
			$de_city = $this->getSelectOptions( $sql, "city_name", "city_name", $dati["de_city"], "de_city", false, "{City}","{all}");
			$objform->addControllo($de_city);
			//------------------------------------------------




			// $file = new fileupload2('file');
			$file = new fileupload2('file',$id,[
				"reopenButton"=> true,
				"uploadDir" => $this->uploadDir,
				"max_files" => $this->max_files,
				"maxKB" => $this->maxKB,
				"maxX" => $this->maxX,
				"maxY" => $this->maxY
			]);
			$file->obbligatorio=0;
			$file->label="'{Banner image file}'";
			$objform->addControllo($file);
			$file->value="";


			// $file->obbligatorio=0;
			// $file->label="'{Banner image file}'";
			// $objform->addControllo($file);
			// $file->value="";
			// $thumbs = loadgallery($this->uploadDir,$id."_","div1","html",true);


			$id_banner = new hidden("id",$dati["id_banner"]);
			$op = new hidden("op",$action);


			
			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##idid##", $id ? $id : "n.a.", $html);
			$html = str_replace("##id##", $id_banner->gettag(), $html);
			$html = str_replace("##nu_maxclick##", $nu_maxclick->gettag(), $html);
			$html = str_replace("##nu_price##", $nu_price->gettag(), $html);
			$html = str_replace("##cd_campagna##", $cd_campagna->gettag(), $html);
			$html = str_replace("##cd_posizione##", $cd_posizione->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##de_codicescript##", $de_codicescript->gettag(), $html);
			$html = str_replace("##fl_stato##", $fl_stato->gettag(), $html);
			$html = str_replace("##dt_giorno1##", $dt_giorno1->gettag(), $html);
			$html = str_replace("##dt_giorno2##", $dt_giorno2->gettag(), $html);
			$html = str_replace("##de_url##", $de_url->gettag(), $html);
			$html = str_replace("##nu_maxtot##", $nu_maxtot->gettag(), $html);
			$html = str_replace("##nu_maxday##", $nu_maxday->gettag(), $html);
			$html = str_replace("##nu_maxday_count##", number_format((float)$dati['nu_maxday_count'],0), $html);
			$html = str_replace("##dt_maxday_date##",TOdmy($dati["dt_maxday_date"]),$html);
			$html = str_replace("##nu_redux##", $nu_redux->gettag(),$html);
			$html = str_replace("##nu_cap##", $nu_cap->gettag(),$html);
			$html = str_replace("##nu_mobileflag##", $nu_mobileflag->gettag(),$html);
			$html = str_replace("##se_os##", $se_os->gettag(),$html);
			$html = str_replace("##bodyclass##", $bodyclass,$html);

			$html = str_replace("##PAYMODEL##", "",$html);
			$html = str_replace("##PAYMODELPRICE##", "",$html);
			$html = str_replace("##LASTDATE##", LASTDATE, $html);

			$html = str_replace("##de_country##", $de_country->gettag(), $html);
			$html = str_replace("##de_region##", $de_region->gettag(), $html);
			$html = str_replace("##de_city##", $de_city->gettag(), $html);

			$html = str_replace("##INFOCPM##", ($dati['fl_stato'] != "S" ? "<p>{Please wait the end of the campaign to see correct values for CPM, CPC and CPD.}</p>" : ""), $html);

			//
			// the advertiser can't edit a banner after payment or during pyament process.
			// if the banner is not 'D' adds the payment details and lets also the advertiser
			// to see them.
			if($dati['fl_stato'] != 'D') {

				$objOrder = execute_row("select * from ".DB_PREFIX."7banner_ordini where cd_banner = '".$id."'");
				if(isset($objOrder['cd_banner'])) {

					$type = "{MANUAL PAYMENT}";
					$instructions = $dati['fl_stato'] == 'M' ? nl2br(MANUAL_PAYMENTS_INFO) : '';
					if($objOrder['id_coinbase']!="") {
						$type="Coinbase: <a target='_blank' href='https://commerce.coinbase.com/charges/".$objOrder['id_coinbase']."'><u>".$objOrder['id_coinbase']."</u></a>";
						$instructions = $dati['fl_stato'] == 'W' ? "{Coinbase hasn't confirmed the transaction yet}" : '';
					}
					if($objOrder['id_paypal']!="") {
						$type="Paypal: <a target='_blank' href='https://www.paypal.com/activity/payment/".$objOrder['id_paypal']."'><u>".$objOrder['id_paypal']."</u></a>";
						$instructions = "";
					}
					$html = str_replace("##SHOWPAYMENT##", "yes", $html);
					
					$html = str_replace("##finalprice##", number_format((float)$objOrder["prezzo_finale"],2,".","")." ".MONEY, $html);

					$stato_pagamento = $objOrder["en_stato_pagamento"];
					if($stato_pagamento=='pagato') $stato_pagamento = "{COMPLETED}";
						else  $stato_pagamento = "{WAITING FOR PAYMENT}";
					$html = str_replace("##paymentstatus##", $stato_pagamento, $html);

					$stati = $this->getStatusLabels("css");
					$html = str_replace("##bannerstatus##", $stati[$dati["fl_stato"]], $html);

					$html = str_replace("##paymenttype##", $type, $html);

					$html = str_replace("##instructions##", $instructions, $html);
						

				} elseif( $session->get("idprofilo") == 5) {
						$html = str_replace("##SHOWPAYMENT##", "yes", $html);
						$html = str_replace("##paymenttype##", "{n.a.}", $html);
						$html = str_replace("##finalprice##", "{n.a.}", $html);
						$stati = $this->getStatusLabels("css");
						$html = str_replace("##bannerstatus##", $stati[$dati["fl_stato"]], $html);
						$html = str_replace("##instructions##", "", $html);
				}
			}

			
			//------------------------------------------------
			//json banner templates
			$sql = "select * from ".DB_PREFIX."7banner_templates order by de_titolo";
			$rs = $conn->query($sql) or die($conn->error.$sql."F");
			$templates = array();
			while($riga = $rs->fetch_array())
				$templates[$riga['id_template']] = array( $riga['de_titolo'], nl2br($riga['de_info']),$riga['de_code'] );

			//------------------------------------------------


			$html = str_replace("##TEMPLATES##", json_encode($templates), $html);

			$outOpts = "";
			foreach($templates as $k => $ar) {
				$outOpts.="<option value='". $k . "'>".$k."</option>";
			}
			$html = str_replace("##TEMPLATESOPTIONS##", $outOpts, $html);

			$html = str_replace("##de_target##", $de_target->gettag(), $html);
			$html = str_replace("##de_nome##", $de_nome->gettag(), $html);
			$html = str_replace("##file##", $file->gettag(), $html);
			// $html = str_replace("##max##", "Upload max ".$this->max_files." files", $html);
			$html = str_replace("##KB##", $this->maxKB ."Kb", $html);
			// $html = str_replace("##X##", $this->maxX, $html);
			// $html = str_replace("##Y##", $this->maxY, $html);
			$html = str_replace("##onlybasic##", $onlybasic, $html);
			// $html = str_replace("##thumbs##", $thumbs, $html);


			if($id) {
                $html = str_replace("##URLFORTRACKING##", encrypt_bannerlink($id), $html);

            } else $html = str_replace("##URLFORTRACKING##", "{The Clicktag url will be available after saving.}", $html);

			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##MONEY##", MONEY, $html);


		} else {
			$html = "0";
		}
		return $html;
	}


	
	

	
	
	function checkoutForm($id) {
		global $root, $session, $conn;

		if(PAYMENTS=="OFF" && $session->get("idprofilo")!=5) return "0";

		if(COINBASE_API_KEY=="" && PAYPAL_CLIENTID==""  && MANUAL_PAYMENTS=="OFF") {
			// Missing configuration for paypal or other methods.
			return "-1";
		}


		$html = "";

		if ($id > 0 && $session->get("BANNER") && $this->check_cliente($id) ) {

			$html = loadTemplateAndParse("template/checkout.html");

			// building checkout form
			$objform = new form();

			$codice_sconto = new testo("codice_sconto","",100,50);
			$codice_sconto->obbligatorio=0;
			$codice_sconto->label="'{Discount code}'";
			$objform->addControllo($codice_sconto);

			$id_banner = new hidden("id",$id);
			$op = new hidden("op","checkoutStep2");
			$html = str_replace("##id##", $id_banner->gettag(), $html);

			$obj = $this->getDati($id);
			if(!is_array($obj)) return "0";
			if($obj["fl_stato"]!="D") return "0";

			$posAr = execute_row("select * from ".DB_PREFIX."7banner_posizioni where id_posizione='".$obj['cd_posizione']."'");

			$html = str_replace("##posizione##", $posAr['de_posizione'], $html);
			$html = str_replace("##firstprice##", number_format($obj['nu_price'],2,".",""), $html);

			$formatted_price = number_format($posAr['prezzo_vendita'],2,".","");


			if($posAr['modello_vendita']=="cpm") {
				$html = str_replace("##detailes##", $obj['nu_maxtot']." {Impressions} (".$posAr['modello_vendita']." ".MONEY.$formatted_price.")", $html);
			}

			if($posAr['modello_vendita']=="cpc") {
				$html = str_replace("##detailes##", $obj['nu_maxclick']." {Clicks} (".$posAr['modello_vendita']." ".MONEY.$formatted_price.")", $html);
			}

			if($posAr['modello_vendita']=="cpd") {
				$html = str_replace("##detailes##", "{From} ".$obj['dt_giorno1']. " {To} ".$obj['dt_giorno2']." (".$posAr['modello_vendita']." ".MONEY.$formatted_price.")", $html);
			}

			$html = str_replace("##finalprice##", number_format($obj['nu_price'],2,".",""), $html);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##codice_sconto##", $codice_sconto->gettag(), $html);
			$html = str_replace("##id##", $id_banner->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			
			$html = str_replace("##COINBASE_API_KEY##", COINBASE_API_KEY, $html);
			// $html = str_replace("##COINBASE_REDIRURL##", WEBURL."/src/componenti/7banner/index.php?op=coonbaseredir&id=" . $id, $html );

			$html = str_replace("##PAYPAL_CLIENTID##", PAYPAL_CLIENTID, $html);
			$html = str_replace("##PAYPAL_REDIRURL##", WEBURL."/src/componenti/7banner/index.php?op=paypalredir&id=" . $id, $html );
			$html = str_replace("##PREZZOFINALE##", number_format($obj['nu_price'],2,".",""), $html );
			$html = str_replace("##CURRENCY##", MONEY_CODE , $html );
			$html = str_replace("##REFID##", md5("adscheck".$id), $html );

			$html = str_replace("##MANUAL_PAYMENTS##", MANUAL_PAYMENTS, $html);
            $html = str_replace("\"##MANUAL_PAYMENTS_INFO##\"", json_encode(MANUAL_PAYMENTS_INFO), $html);
            
			
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);
			$html = str_replace("##MONEY##", MONEY, $html);


			// -----------------------------------------------
			// creating order in database, waiting for payment
			$id_ordine = execute_scalar("select id_ordine from ".DB_PREFIX."7banner_ordini where cd_banner='".$id."'", 0);
			if($id_ordine == 0) {

				$sql = "INSERT INTO ".DB_PREFIX."7banner_ordini (
					cd_utente,cd_banner,prezzo,snapshot_id_posizione,snapshot_de_posizione,snapshot_nu_width,snapshot_nu_height,	snapshot_vendita_online,snapshot_modello_vendita,snapshot_prezzo_vendita,dt_inizio_banner,en_stato_pagamento,prezzo_finale,codicesconto
				) select 
					'".$session->get("idutente")."','".$id."','".number_format($obj['nu_price'],2,".","")."',id_posizione,de_posizione,	nu_width,	nu_height,vendita_online,modello_vendita,prezzo_vendita,'".$obj['dt_giorno1']."','attesa','".number_format($obj['nu_price'],2,".","")."','' from ".DB_PREFIX."7banner_posizioni where id_posizione='".$obj['cd_posizione']."'";
				$conn->query($sql) or trigger_error($conn->error. " ".$sql);

			}
			// -----------------------------------------------

		}

		return $html;

	}
	
	
	
	
	
	
	
	
	/**
	 * return the banner row from database
	 * 
	 * @param int $id
	 * 
	 * @return array
	 */
	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX.$this->tbdb." where id_banner='{$id}'");
	}


	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from input form
		// files --> array _FILES
		// result outpunt:
		//	"" --> ok
		//  "0" --> not authorized
		//  "2|messaggio" --> error file

		global $session,$conn;
		if ($session->get("BANNER") && $this->check_cliente($arDati["id"])) {

			// textarea with rel=code
			// docode special dangerous chars replaced
			$arDati["de_codicescript"] = str_replace("(MIN)","<",$arDati["de_codicescript"]);
			$arDati["de_codicescript"] = str_replace("(MAG)",">",$arDati["de_codicescript"]);
			$arDati["de_codicescript"] = str_replace("(--I--)","i",$arDati["de_codicescript"]);
			if(!isset($arDati["se_os"])) $arDati["se_os"] = array();


			if ($arDati["id"]!="") {
				$id = $arDati["id"];

				// banner is editable from advertiser only if not paid (D)
				if( $session->get("idprofilo") == 5 && $arDati['fl_stato'] != 'D') {
					return "0";
				}



				/*
					"A"=>   SERVING
					"S"=>   ENDED
					"L"=>	SERVING
					"P"=>   PAUSED
					"F"=>   SCHEDULED
					"K"=>   PENDING
					"D"=>   NOT PAID
                    "W"=>   WAITING BLOCKCHAIN
                    "M"=>   WAITING MANUAL
				*/
				$state0 = execute_scalar("select fl_stato from ".DB_PREFIX."7banner where id_banner='".$arDati["id"]."'");


				/*
					Modify
				*/
				$posAr = execute_row("select * from ".DB_PREFIX."7banner_posizioni where id_posizione='".$arDati["cd_posizione"]."'");

				$sql="UPDATE ".DB_PREFIX.$this->tbdb." set
					dt_giorno1='##dt_giorno1##',
					dt_giorno2='##dt_giorno2##',
					de_nome='##de_nome##',
					nu_maxday='##nu_maxday##',
					nu_maxtot='##nu_maxtot##',
					nu_maxclick='##nu_maxclick##',
					de_codicescript='##de_codicescript##',
					cd_posizione='##cd_posizione##',fl_stato='##fl_stato##',de_target='##de_target##',
					de_url='##de_url##',
					cd_campagna='##cd_campagna##',
					nu_width='##nu_width##',
					nu_height='##nu_height##',
					nu_price='##nu_price##',
					de_country='##de_country##',
					de_region='##de_region##',
					de_city='##de_city##',
					nu_redux='##nu_redux##',
					nu_cap='##nu_cap##',
					nu_mobileflag='##nu_mobileflag##',
					se_os='##se_os##'

				where id_banner='##id_banner##'";
				$sql= str_replace("##dt_giorno1##",$arDati["dt_giorno1"],$sql);
				$sql= str_replace("##dt_giorno2##",$arDati["dt_giorno2"],$sql);
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##nu_maxclick##",$arDati["nu_maxclick"],$sql);
				$sql= str_replace("##nu_maxtot##",$arDati["nu_maxtot"],$sql);
				$sql= str_replace("##nu_maxday##",$arDati["nu_maxday"],$sql);
				$sql= str_replace("##de_codicescript##",$arDati["de_codicescript"],$sql);
				$sql= str_replace("##fl_stato##",$arDati["fl_stato"],$sql);
				$sql= str_replace("##cd_posizione##",$arDati["cd_posizione"],$sql);
				$sql= str_replace("##de_url##",$arDati["de_url"],$sql);
				$sql= str_replace("##de_target##",$arDati["de_target"],$sql);
				$sql= str_replace("##cd_campagna##",$arDati["cd_campagna"],$sql);
				$sql= str_replace("##id_banner##",$arDati["id"],$sql);
				$sql= str_replace("##nu_width##",$posAr["nu_width"],$sql);
				$sql= str_replace("##nu_height##",$posAr["nu_height"],$sql);
				$sql= str_replace("##nu_price##",$arDati["nu_price"],$sql);
				$sql= str_replace("##de_country##",$arDati["de_country"],$sql);
				$sql= str_replace("##de_region##",$arDati["de_region"],$sql);
				$sql= str_replace("##de_city##",$arDati["de_city"],$sql);
				$sql= str_replace("##nu_redux##",$arDati["nu_redux"],$sql);
				$sql= str_replace("##nu_cap##",$arDati["nu_cap"],$sql);
				$sql= str_replace("##nu_mobileflag##",$arDati["nu_mobileflag"],$sql);
				$sql= str_replace("##se_os##",implode(",",$arDati["se_os"]),$sql);
				
				$conn->query($sql) or die(trigger_error($conn->error." SQL:".$sql));


				if( $state0 == "K"  && ($arDati['fl_stato']=="L" or $arDati['fl_stato']=="A")) {
					/* send mail to advertiser */
					$this->notifyAdvertiserBannerOnline( $arDati["id"] );

					/* save payment in payments table */
					$this->updateAdminPayments( $arDati['id'] , "approved");

				}
                
				if( $state0 == "M"  && $arDati['fl_stato']=="A") {
					/* send mail to advertiser */
					// $this->notifyAdvertiserBannerOnline( $arDati["id"] );

					/* save payment in payments table */
					// $this->updateAdminPayments( $arDati['id'] , "approved");

                    $this->complete_purchase($arDati['id'],"","","");

				}



				$html= "ok|".$id;
			} else {
				/*
					Insert
				*/
				$posAr = execute_row("select * from ".DB_PREFIX."7banner_posizioni where id_posizione='".$arDati["cd_posizione"]."'");

				// -----------------------------------------------------------------
				// 22/04/2020 patch for Too many files, 1
				// allow usage of 0000-00-00 dates on mysqlserver > 5.6
				//$ver = execute_row("SHOW VARIABLES LIKE 'version'");
				//if($ver['Value'] >= '5.7') { $conn->query("SET sql_mode = '';"); }
				// -----------------------------------------------------------------
				

				$sql="INSERT into ".DB_PREFIX.$this->tbdb." (dt_giorno1,dt_giorno2,de_nome,de_codicescript,de_url,cd_posizione,fl_stato,de_target,nu_maxday,nu_maxclick,nu_maxtot,cd_campagna,nu_width,nu_height,nu_price,de_country,de_region,de_city,nu_redux,nu_cap,nu_mobileflag,se_os) values('##dt_giorno1##','##dt_giorno2##','##de_nome##','##de_codicescript##','##de_url##','##cd_posizione##','##fl_stato##','##de_target##','##nu_maxday##','##nu_maxclick##','##nu_maxtot##','##cd_campagna##','##nu_width##','##nu_height##','##nu_price##','##de_country##','##de_region##','##de_city##','##nu_redux##','##nu_cap##','##nu_mobileflag##','##se_os##')";
				$sql= str_replace("##dt_giorno1##",$arDati["dt_giorno1"],$sql);
				$sql= str_replace("##dt_giorno2##",$arDati["dt_giorno2"],$sql);
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##nu_maxclick##",(integer)$arDati["nu_maxclick"],$sql);
				$sql= str_replace("##nu_maxtot##",(integer)$arDati["nu_maxtot"],$sql);
				$sql= str_replace("##nu_maxday##",(integer)$arDati["nu_maxday"],$sql);
				$sql= str_replace("##de_codicescript##",$arDati["de_codicescript"],$sql);
				$sql= str_replace("##fl_stato##",$arDati["fl_stato"],$sql);
				$sql= str_replace("##cd_posizione##",$arDati["cd_posizione"],$sql);
				$sql= str_replace("##de_url##",$arDati["de_url"],$sql);
				$sql= str_replace("##de_target##",$arDati["de_target"],$sql);
				$sql= str_replace("##cd_campagna##",$arDati["cd_campagna"],$sql);
				$sql= str_replace("##nu_width##",$posAr["nu_width"],$sql);
				$sql= str_replace("##nu_height##",$posAr["nu_height"],$sql);
				$sql= str_replace("##nu_price##",$arDati["nu_price"],$sql);
				$sql= str_replace("##de_country##",$arDati["de_country"],$sql);
				$sql= str_replace("##de_region##",$arDati["de_region"],$sql);
				$sql= str_replace("##de_city##",$arDati["de_city"],$sql);
				$sql= str_replace("##nu_redux##",$arDati["nu_redux"],$sql);
				$sql= str_replace("##nu_cap##",$arDati["nu_cap"],$sql);
				$sql= str_replace("##nu_mobileflag##",$arDati["nu_mobileflag"],$sql);
				$sql= str_replace("##se_os##",implode(",",$arDati["se_os"]),$sql);

				$conn->query($sql) or die($conn->error. " ".$sql);
				$id = $conn->insert_id;
				$html= "ok|".$id;

			}

			



			/* upload FILE */
			if(stristr($html,"ok|") && $files['file']['type']!="") {
				$htmltemp = uploadFile(
					$files,
					'file',
					$this->uploadDir.$id."_",
					array('gif','jpg','png','zip','jpeg','webp'),
					$this->maxX,
					$this->maxY,
					$this->maxKB,
					$this->max_files
				);

				if($htmltemp=="") {
					// ok, check for zip
					$thumbs = loadgallery($this->uploadDir,$id."_","","array");
					
					if(isset($thumbs[0][3]) && $thumbs[0][3] == "zip") {
						$dest =$this->uploadDir.$id."/";
						
						// https://github.com/alexcorvi/php-zip modified at line 1851
						$zip = new Zip();

						// security check for zip file content
						// check for malicious files
						$ar = $zip->fileList($thumbs[0][0]);
						foreach($ar as $f) {
							if(preg_match("/\.php$/i",$f)) {
								if (file_exists($thumbs[0][0])) unlink($thumbs[0][0]);
								if (file_exists($thumbs[0][0].'.info')) unlink($thumbs[0][0].'.info');
								return "-1|File ZIP blocked, it contains PHP code.";
							}
						}

						// ok, unzip
						if(!file_exists($dest)) mkdir($dest,0755);
						$result = $zip->unzip_file($thumbs[0][0] );
						if($result === true) {
							$result = $zip->unzip_to($dest);
						}
						if($result !== true) {
							return "-1|" . $result;
						}
						
					}


				} else $html = $htmltemp;
			}


		} else {
			$html="0";		// insert not allowed
		}
		return $html;
	}




	function deleteItem($id) {
		// in:
		// id --> id of record to be deleted
		// result:
		//	"" --> ok
		//  "0" --> no permission

		$id = preg_replace("/[^0-9]/","",$id);

		global $session,$conn;

		if ($session->get("BANNER") && $this->check_cliente($id)) {

			if($session->get("idprofilo")==5) {
				// delete only NOT PAID
				$stato = execute_scalar("select fl_stato from ".DB_PREFIX.$this->tbdb." where id_banner='".$id."'");
				if($stato != "D") return "-1";
			}

			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb." where id_banner='$id'";
			$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");

			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb."_ordini where cd_banner='$id'";
			$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");

			$sql="DELETE FROM ".DB_PREFIX.$this->tbdb."_stats where cd_banner='$id'";
			$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");

			// connected banner files deletion
			for ($i=0;$i<$this->max_files;$i++) deldbimg($this->uploadDir.$id."_".$i);
			rrmdir($this->uploadDir.$id);
			$html = "";
		} else {
			$html="0";		// no permission
		}
		return $html;

	}

	function eliminaSelezionati($dati) {
		// in:
		// dati --> $_POST
		// output:
		//	"" --> ok
		//  "0" --> no permission

		global $session;
		if ($session->get("BANNER")) {
			if(isset($dati['gridcheck'])) {
				$p=$dati['gridcheck'];
				for ($i=0;$i<count($p);$i++) {
					$res = $this->deleteItem( $p[$i] );
					if($res != "") return $res;
				}
			}
			$html = "";
		} else {
			$html="0";		// no permission
		}
		return $html;
	}





	/**
	 * extract the HTML select to filter banners in the banners list. the options changes based on the user profile
	 * 
	 * @param string $def
	 * @return string HTML
	 */
	function getHtmlcombotipo($def="") {
		global $conn,$session;

		if( $session->get("idprofilo") == 5) {
			// ADVERTISER sees only his banners
			$sql = "select cd_posizione, de_posizione,
				( CASE fl_stato WHEN 'A' THEN 'A' 
				WHEN 'L' THEN 'A'
				WHEN 'P' THEN 'A'
				WHEN 'K' THEN 'A'
				WHEN 'W' THEN 'A'
                WHEN 'M' THEN 'A'
				WHEN 'D' THEN 'S'
				WHEN 'S' THEN 'S'
                END ) AS stato,
				count(*) as c
				from ".DB_PREFIX.$this->tbdb."
				LEFT OUTER JOIN ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
					inner join ".DB_PREFIX."7banner_campagne on id_campagna=cd_campagna 
					and cd_cliente in (select id_cliente from ".DB_PREFIX."7banner_clienti where cd_utente='".$session->get("idutente")."') 
					group by cd_posizione, stato order by stato,de_posizione";
			$rs = $conn->query($sql) or trigger_error($conn->error);
		} elseif( $session->get("idprofilo") == 15) {
			// MINI ADMIN sees only his positions
			$sql = "SELECT cd_posizione, de_posizione,
				( CASE fl_stato WHEN 'A' THEN 'A' 
					WHEN 'L' THEN 'A'
					WHEN 'P' THEN 'A'
					WHEN 'K' THEN 'A'
					WHEN 'W' THEN 'A'
					WHEN 'M' THEN 'A'
					WHEN 'D' THEN 'S'
					WHEN 'S' THEN 'S'
                END ) AS stato,
				COUNT(*) AS c
				FROM ".DB_PREFIX.$this->tbdb." AS B 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_posizioni ON B.cd_posizione=id_posizione
				INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin PP ON B.cd_posizione=PP.cd_position AND PP.cd_user='".$session->get("idutente")."'
				
				GROUP BY cd_posizione,  de_posizione, stato ORDER BY stato,de_posizione";
			$rs = $conn->query($sql) or trigger_error($conn->error);
		} else {
			// ADMINISTATOR
			$sql = "SELECT cd_posizione, de_posizione,
				( CASE fl_stato WHEN 'A' THEN 'A' 
				WHEN 'L' THEN 'A'
				WHEN 'P' THEN 'A'
				WHEN 'K' THEN 'A'
				WHEN 'W' THEN 'A'
                WHEN 'M' THEN 'A'
				WHEN 'D' THEN 'S'
				WHEN 'S' THEN 'S'
                END ) AS stato,
				COUNT(*) as c
				FROM ".DB_PREFIX.$this->tbdb." LEFT OUTER JOIN ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
				GROUP BY cd_posizione,  de_posizione,stato ORDER BY stato,de_posizione";
		}
		$rs = $conn->query($sql) or trigger_error($conn->error);
		$arFiltri = array("-999"=>"--{all}--","-999|T"=>"------- {Active} -------");
		$old = "A";
		while($riga = $rs->fetch_array()) {

			
			
			if ($riga['cd_posizione']=="") $riga['c']=0;
			if($riga['stato']=="S" && $old!="S") {
				$arFiltri["-999|X"]= "------- {Inactive} -------"; $old = $riga['stato']; 
			}
			if( $session->get("idprofilo") == 5) {

				$arFiltri[$riga['cd_posizione']."|".$riga['stato']]= ($riga['cd_posizione'] ? $riga['de_posizione']."":"no position") ;

			} else {

				$arFiltri[$riga['cd_posizione']."|".$riga['stato']]= ($riga['cd_posizione'] ? "[ ".$riga['cd_posizione']." ] · ".$riga['de_posizione']." (".$riga['c'].")":"no position") ;

			}
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'>";
	}



	/**
	 * returns the HTML combo for clients. the options changes based on the user profile
	 * 
	 * @param string $def  (the selected element)
	 * @return string HTML  (the HTML for <select> tag)
	 */
	function getHtmlcomboclient($def="") {
		global $conn,$session;
		//------------------------------------------------
		//combo filter clients
		if( $session->get("idprofilo") == 5) {
			// advertiser sees only his client name
			$sql = "select distinct id_cliente, ".DB_PREFIX."7banner_clienti.de_nome
				from ".DB_PREFIX.$this->tbdb." INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
					inner ".DB_PREFIX."join 7banner_clienti on id_cliente=cd_cliente where cd_utente='".$session->get("idutente")."' 
					order by ".DB_PREFIX."7banner_clienti.de_nome";
			$rs = $conn->query($sql) or trigger_error($conn->error);


		} elseif($session->get("idprofilo") == 15) {
			// miniadmin sees only his positions
			$sql = "select distinct id_cliente, ".DB_PREFIX."7banner_clienti.de_nome
				from ".DB_PREFIX.$this->tbdb." B INNER JOIN ".DB_PREFIX."7banner_campagne ON B.cd_campagna=id_campagna
				inner join ".DB_PREFIX."7banner_clienti on id_cliente=cd_cliente
				INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin PP ON B.cd_posizione=PP.cd_position AND PP.cd_user='".$session->get("idutente")."'
				order by ".DB_PREFIX."7banner_clienti.de_nome";

		} else {
			// adminsitrator
			$sql = "select distinct id_cliente, ".DB_PREFIX."7banner_clienti.de_nome
				from ".DB_PREFIX.$this->tbdb." INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
					inner join ".DB_PREFIX."7banner_clienti on id_cliente=cd_cliente
					order by ".DB_PREFIX."7banner_clienti.de_nome";
		}
		$rs = $conn->query($sql) or trigger_error($conn->error);
		$arFiltri = array("-999"=>"All","-999"=>"--{all}--");
		$old = "A";
		while($riga = $rs->fetch_array()) {

			$arFiltri[$riga['id_cliente']]= $riga['de_nome'];
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='comboclient' id='comboclient' class='filter'>{$out}</select>";
	}


	/**
	 * returns the HTML combo for campaigns. the options changes based on the user profile
	 * 
	 * @param string $def  (the selected element)
	 * @return string HTML  (the HTML for <select> tag)
	 */
	function getHtmlcombocampaign($def="") {
		global $conn,$session;
		//------------------------------------------------
		//combo campaigns filter


		if( $session->get("idprofilo") == 5) {
			// advertiser profile sees only its campaigns

			$sql = "select distinct id_campagna, ".DB_PREFIX."7banner_campagne.de_titolo
				from ".DB_PREFIX.$this->tbdb." INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
					inner join ".DB_PREFIX."7banner_clienti TBC on ".DB_PREFIX."7banner_campagne.cd_cliente=TBC.id_cliente where cd_utente='".$session->get("idutente")."' 
					order by ".DB_PREFIX."7banner_campagne.de_titolo";
			$rs = $conn->query($sql) or trigger_error($conn->error);


		}  elseif($session->get("idprofilo") == 15) {
			// miniadmin sees only his positions
			$sql = "select distinct id_campagna, ".DB_PREFIX."7banner_campagne.de_titolo
				from ".DB_PREFIX.$this->tbdb." B INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
				INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin PP ON B.cd_posizione=PP.cd_position AND PP.cd_user='".$session->get("idutente")."'
					order by ".DB_PREFIX."7banner_campagne.de_titolo";

		} else {
			// administrator
			$sql = "select distinct id_campagna, ".DB_PREFIX."7banner_campagne.de_titolo
				from ".DB_PREFIX.$this->tbdb." INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna=id_campagna
					order by ".DB_PREFIX."7banner_campagne.de_titolo";
		}
		$rs = $conn->query($sql) or trigger_error($conn->error);
		$arFiltri = array("-999"=>"--{all}--");
		$old = "A";
		while($riga = $rs->fetch_array()) {

			$arFiltri[$riga['id_campagna']]= $riga['de_titolo'];
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange='aggiornaGriglia()' name='combocampaign' id='combocampaign' class='filter'>{$out}</select>";
	}


	//
	// used by change of status 
	// via ajax (ajax.jobs.php).
	function setStato($id,$stat) {
		global $conn;
		$conn->query("update ".$this->tbdb." set fl_stato='".$stat."' where id_banner='".$id."' and fl_stato IN ('A','L','P')" );
	}


	// used for ip Location
	// via ajax (ajax.jobs.php).
	// -------------------------------------
	function getListRegion($country) {
		global $session,$conn;
		$out = "";
		if ($session->get("BANNER")) {
			$sql = "select distinct region_name from ip2location_db3 where region_name<>'' and country_name='".$country."' order by region_name";
			$rs = $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
			$out = "<option value=''>--{all}--</option>";
			while($riga = $rs->fetch_array()) {
				$out.= "<option value=\"".addslashes($riga['region_name'])."\">".$riga['region_name']."</option>";
			}
		}
		return $out;
	}
	function getListCity($country,$region) {
		global $session,$conn;
		$out = "";
		if ($session->get("BANNER") ) {
			$sql = "select distinct city_name from ip2location_db3 where city_name<>'' and country_name='".$country."' and region_name='".$region."' order by city_name";
			$rs = $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
			$out = "<option value=''>--{all}--</option>";
			while($riga = $rs->fetch_array()) {
				$out.= "<option value=\"".addslashes($riga['city_name'])."\">".$riga['city_name']."</option>";
			}
		}
		return $out;
	}
	// -------------------------------------



	// info on  selected banner position
	// via ajax (ajax.jobs.php).
	function getPosInfo($id) {
		global $session,$conn;
		$out = "";
		if ($session->get("BANNER") ) {
			$objPos = execute_row("select * from ".DB_PREFIX."7banner_posizioni where id_posizione='".$id."'", false);
			if(!is_array($objPos)) {
				$objPos = array();
				$objPos['modello_vendita']="";
				$objPos['prezzo_vendita']="";
				$objPos['vendita_online']="";
				$objPos['nu_height']="";
				$objPos['nu_width']="";
			}
			$daily = execute_row("SELECT ROUND( sum(".DB_PREFIX."7banner_stats.nu_pageviews) / 7) as v, ROUND(sum(".DB_PREFIX."7banner_stats.nu_click)/7) as c
			FROM ".DB_PREFIX."7banner_posizioni  left outer join ".DB_PREFIX."7banner on cd_posizione=id_posizione
				left outer join  `".DB_PREFIX."7banner_stats` on `".DB_PREFIX."7banner_stats`.cd_banner=id_banner and ".DB_PREFIX."7banner_stats.id_day >= '".date("Y-m-d",strtotime("-7 days"))."'
				where id_posizione='".$id."' group by id_posizione", false);

			if(!is_array($daily) ) { $daily = array(); $daily['v']=""; $daily['c']="";  }
			
			if($objPos['prezzo_vendita']) {
                $decimals = 2;
                if($objPos['prezzo_vendita'] < 0.01) $decimals = 3;
				$objPos['prezzo_vendita'] = number_format( $objPos['prezzo_vendita'],$decimals,".","");
			}
			$out = $objPos['modello_vendita']."|".$objPos['prezzo_vendita']."|".$objPos['vendita_online']."|".$daily['v']."|".$daily['c']."|".$objPos['nu_width']."|".$objPos['nu_height'];
		}
		return $out;
		
	}


	/* complete the purchase process after payment (coinbase or paypal or manual) */

	function complete_purchase($id_banner,$id_paypal,$paypal_client_mail,$id_coinbase) {
		global $conn;

		$dt_giorno1= execute_scalar("select dt_giorno1 from ".DB_PREFIX."7banner where id_banner='".$id_banner."'");

		$conn->query("update ".DB_PREFIX."7banner set fl_stato='K' where id_banner='".$id_banner."'");
		$sql = "update ".DB_PREFIX."7banner_ordini set en_stato_pagamento='pagato',
			dt_inizio_banner='".$dt_giorno1."',
			data_pagamento='".date("Y-m-d H:i:s")."',
			paypal_user='".$paypal_client_mail."',
			id_paypal='".$id_paypal."',
			id_coinbase='".$id_coinbase."'
		where cd_banner='".$id_banner."'";
		$conn->query($sql) or trigger_error($conn->error." ".$sql);


		$u=new user();
		$id_utente = execute_scalar("SELECT cd_utente FROM `".DB_PREFIX."7banner_clienti` inner join ".DB_PREFIX."7banner_campagne on ".DB_PREFIX."7banner_campagne.cd_cliente=id_cliente inner join ".DB_PREFIX."7banner on cd_campagna=id_campagna where id_banner=".$id_banner." limit 0,1");

		$autoapprove = $u->getManualPermission($id_utente, 'BANNER_AUTOAPPROVEPENDING');

		/* send mail to administrator */
		$this->notifyAdministratorItemSold( $id_banner, $autoapprove=="1" ? true : false );

		/* save payment in payments table */
		$this->updateAdminPayments( $id_banner, "pending");

		/* save payment auto-approved in paynments table */
		if($autoapprove=="1") {
			// AUTO APPROVE
			$conn->query("update ".DB_PREFIX."7banner set fl_stato='A' where id_banner='".$id_banner."'");
			$this->updateAdminPayments( $id_banner, "approved");
		}

		
		/* change output msg if autoapprove*/
		$outmsg = "{Payment completed, now your ad is pending for review.}";
		if($autoapprove) $outmsg = "{Your banner is online!}";


		return $outmsg;

	}



	//
	// check server side that transction specified
	// is correct also with paypal, it's a curl
	// call to Paypal APIs
	function paypal_checkTransaction($dati) {
		global $session,$conn;

		$out = "";
		if ($session->get("BANNER") ) {

			// get access token
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, PAYPAL_SERVER."/v1/oauth2/token");
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENTID.":".PAYPAL_SECRET);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
			$result = curl_exec($ch);
			if(empty($result)) return "-1|{Paypal error: No response.}";
			else {
				$json = json_decode($result);
				/*
				stdClass Object
				(
					[scope] => https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/vault/payment-tokens/read https://uri.paypal.com/services/disputes/read-buyer https://uri.paypal.com/services/payments/realtimepayment https://uri.paypal.com/services/disputes/update-seller https://uri.paypal.com/services/payments/payment/authcapture openid https://uri.paypal.com/services/disputes/read-seller Braintree:Vault https://uri.paypal.com/services/payments/refund https://api.paypal.com/v1/vault/credit-card https://api.paypal.com/v1/payments/.* https://uri.paypal.com/payments/payouts https://uri.paypal.com/services/vault/payment-tokens/readwrite https://api.paypal.com/v1/vault/credit-card/.* https://uri.paypal.com/services/subscriptions https://uri.paypal.com/services/applications/webhooks
					[access_token] => A21AALqf4EDlAmejG8TPO6qWK3AvrwfD3QgDajOyJDaifm5aMyi7hY-kvQTg_8S67Kknoq5tPRVVTznkm6Ht7AibzDokNGiCQ
					[token_type] => Bearer
					[app_id] => APP-80W284485P519543T
					[expires_in] => 31829
					[nonce] => 2020-12-29T11:04:20ZGbpTJMUl8lr8OZkUjBFugKS2O6AaHDKr78C8rKfxaRI
				)
				*/
				if(!isset($json->access_token)) return "-1|{Paypal access failure.}";
			}
			curl_close($ch);


			//
			// search for transaction
			$url = PAYPAL_SERVER."/v2/checkout/orders/".$dati['transid'];
			$accessToken= $json->access_token;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $accessToken,
				'Accept: application/json',
				'Content-Type: application/json'
			));
			$response = curl_exec($curl);

			$conn->query("update ".DB_PREFIX."7banner_ordini set temp='".addslashes($response)."' where cd_banner='".$dati['id']."'");

			$pdati = json_decode($response);

			if(isset($pdati->purchase_units) && isset($pdati->purchase_units[0]) && isset($pdati->purchase_units[0]->reference_id) &&
				md5("adscheck".$dati['id']) == $pdati->purchase_units[0]->reference_id
			) {

				$payer = "";
				if(isset($pdati->payer->email_address)) $payer = $pdati->payer->email_address;

				$transaction_id = $pdati->purchase_units[0]->payments->captures[0]->id;

				$outmsg = $this->complete_purchase($dati['id'],$transaction_id,$payer,"");

				return "1|".$outmsg;
			} else {
				return "-1|{Transaction not valid.}";
			}
			
		} 

		return "0";

	}

	

	




	/* call coinbase api to retrive the charge to complete payments on blockchains */
	function coinbase_getCharge($id_banner) {

		global $conn,$session;

		$id_banner= (integer)$id_banner;

		$ok_redir = WEBURL."/src/componenti/7banner/index.php?op=coinbaseredir&id=" . $id_banner."&ok=" . md5( "OK" . $id_banner . MD5CKEY);

		$ko_redir = WEBURL."/src/componenti/7banner/index.php?op=coinbaseredir&id=" . $id_banner."&ok=" . md5( "KO" . $id_banner . MD5CKEY);

		$curl = curl_init();

		$obj = execute_row("select * from ".DB_PREFIX."7banner_ordini inner join ".DB_PREFIX."7banner on cd_banner=id_banner where cd_banner = '".$id_banner."'");
		$amount = number_format($obj['nu_price'],2,".","");
		$currency = MONEY_CODE;
		$customer_name = "User #". $session->get("idutente");
		$customer_id = $session->get("idutente");

		/* CREATE COINBASE CHARGE */
		curl_setopt_array($curl, [
		  CURLOPT_URL => "https://api.commerce.coinbase.com/charges",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\"local_price\":{\"amount\":".$amount.",\"currency\":\"".$currency."\"},\"metadata\":{\"customer_id\":\"".$customer_id."\",\"customer_name\":\"".$customer_name."\"},\"name\":\"".htmlspecialchars(SERVER_NAME)."\",\"description\":\"Banner purchase id ".$id_banner."\",\"pricing_type\":\"fixed_price\",\"redirect_url\":\"".$ok_redir."\",\"cancel_url\":\"".$ko_redir."\"}",
		  CURLOPT_HTTPHEADER => [
			"Accept: application/json",
			"Content-Type: application/json",
			"X-CC-Api-Key: ".COINBASE_API_KEY,
			"X-CC-Version: 2018-03-22"
		  ],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			/*
			 curl has an error
			 */
		  echo "cURL Error #:" . $err;
		  die;
		} else {
		  $coinbase_info = json_decode($response);
		  // wait for payment
		  $conn->query("UPDATE ".DB_PREFIX."7banner SET fl_stato='W' where id_banner='".$id_banner."'");
		  $conn->query("UPDATE ".DB_PREFIX."7banner_ordini SET id_coinbase='".$coinbase_info->data->code."' where cd_banner='".$id_banner."'");
		  echo $coinbase_info->data->hosted_url;
		  die;
		  //echo "<a href='" . $a->data->hosted_url."'>" . $a->data->hosted_url."</a> CODE E': ". $a->data->code;
		}

	}


	/* manual payment */
	function manual_pay($id_banner) {

		global $conn,$session;

		$id_banner= (integer)$id_banner;

        // wait for payment
        $conn->query("UPDATE ".DB_PREFIX."7banner SET fl_stato='M' where id_banner='".$id_banner."'");

        echo WEBURL."/src/componenti/7banner/index.php";

        // A MAIL TO ADMINS SHOULD BE SENT HERE
        
        die;

	}



	function coinbase_checkTransaction($dati, $public = false) {
		// when $public is true is called by cron
		// with a not logged user, so session is not available
		//
		global $session,$conn;

		$out = "";

		$id_banner = (integer)$dati['id'];



		if ($session->get("BANNER") || $public) {

			if(isset($dati["ok"]) && $dati["ok"]==md5( "OK" . $id_banner . MD5CKEY)) {

				
				// WAIT BLOCKCHAIN CONFIRMATION
				$conn->query("update ".DB_PREFIX."7banner set fl_stato='W' where id_banner='".$id_banner."'");
				
				$outmsg = "{Thank you, we are waiting for confirmation by Coinbase}";
				return "1|".$outmsg;
			}


			if(isset($dati["ok"]) && $dati["ok"] == md5( "KO" . $id_banner . MD5CKEY) ) {
				
				// delete charge from db
				// and put back in NOT PAID status
				$conn->query("UPDATE ".DB_PREFIX."7banner_ordini SET id_coinbase='', fl_stato='D' WHERE cd_banner='".$id_banner."'");

				$outmsg = "{Sorry, you have cancelled the transaction}";
				return "1|".$outmsg;
			}

			$order = execute_row("select * from ".DB_PREFIX."7banner_ordini where cd_banner='".$id_banner."'");
			if($order['id_coinbase']!="") {

				// if there is coinbase charge code

				$curl = curl_init();

				curl_setopt_array($curl, [
				  CURLOPT_URL => "https://api.commerce.coinbase.com/charges/" . $order['id_coinbase'],
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "GET",
				  CURLOPT_HTTPHEADER => [
					"Accept: application/json",
					"X-CC-Version: 2018-03-22"
				  ],
				]);

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
				  return "-1|".$err;

				} else {

					$coinbase_info = json_decode($response);

					$last = new stdClass();

					if(isset($coinbase_info->data->timeline) && is_array($coinbase_info->data->timeline)) {
						$lastArr = $coinbase_info->data->timeline; 
						for($i=0;$i<count($lastArr);$i++) {
							if($lastArr[$i]->status == "COMPLETED") {
								// se trovo un COMPLETED esco
								$last = $lastArr[$i];
								break;
							}
							$last = $lastArr[$i];
						}
					} else {
						$last->status = "NOT FOUND";
					}

					if ($last->status == "COMPLETED") {

						$outmsg = $this->complete_purchase($id_banner,"","",$order['id_coinbase']);

						return "1|".$outmsg;
					}

					if ($last->status == "PENDING" || $last->status == "NEW") {

						$outmsg = "{Coinbase hasn't confirmed the transaction yet}";

						return "2|".$outmsg;
					}

					if ($last->status == "EXPIRED" || $last->status == "CANCELED") {

						$conn->query("UPDATE ".DB_PREFIX."7banner_ordini SET id_coinbase = '' WHERE cd_banner='".$id_banner."' and en_stato_pagamento='attesa'");
						$conn->query("UPDATE ".DB_PREFIX."7banner SET fl_stato = 'D' WHERE id_banner='".$id_banner."' and fl_stato='W'");

						$outmsg = "{Transaction expired}";

						return "3|".$outmsg;
					}

					// -------------------------------
					// other status, not handled
					// 
					$outmsg = "{Sorry, transaction status not handled} ". $last->status;
					return "4|".$outmsg;
				}
			}
		}

		return "0";
	}





	// helper (later recreated in form class)
	// load sql options in select html form object
	function getSelectOptions( $sql, $idfield, $labelfield, $defaultValue, $formfieldname, $mandatoryflag, $formfieldlabel,$emptylabel) {
		global $conn;
		$ar = array();
		if ($emptylabel!="") $ar[""]="--".$emptylabel."--";
		$fieldObj = new optionlist($formfieldname, $defaultValue ,$ar);
		$fieldObj->loadSqlOptions( $sql, $idfield, $labelfield, $emptylabel);
		$fieldObj->obbligatorio= $mandatoryflag ? 1 : 0;
		$fieldObj->label="'" . $formfieldlabel ."'";
		return $fieldObj;
	}
/*
	function loadSqlOptions( $sql, $idfield, $labelfield, $emptylabel) {
		global $conn;
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql);
		$ar = array();
		if ($emptylabel!="") $ar[""]="--".$emptylabel."--";
		while($riga = $rs->fetch_array()) $ar[$riga[$idfield]]=$riga[$labelfield];
		$this->arrayvalori = $ar;
	}
*/

	/* send an email to the advertiser because his banner is now online */
	function notifyAdvertiserBannerOnline($id_banner){
		$message = nl2br( translateHtml( "<p>{Hi,<br>your banner n.% has been approved and it is now serving.<br>Thank you.}</p>" ) );
		$message = str_replace("%", $id_banner, $message);
		$subject = "[".SERVER_NAME."] ". translateHtml("{Your banner is online!}");
		$to = execute_scalar("select de_email from ".DB_PREFIX."7banner 
			inner join ".DB_PREFIX."7banner_campagne on cd_campagna=id_campagna
			inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente 
			inner join ".DB_PREFIX."frw_extrauserdata on cd_utente=cd_user
			where id_banner='".$id_banner."'");
		if (is_email($to)) {
				mail_utf8(
					$to,
					$subject,
					$message);
		}
	}



	/* send an email to every administrator because there is a banner that need approvation */
	/* OR IS Autoapproved */
	function notifyAdministratorItemSold( $id_banner, $autoapprove = false ) {
		$obj = execute_row("select * from ".DB_PREFIX."7banner where id_banner='".$id_banner."'");
		if(!$autoapprove) $message = nl2br( translateHtml( "<p>{Hi,<br>the banner n.% needs your review to go online.<br>Thank you.}</p>" ) );
			else $message = nl2br( translateHtml( "<p>{Hi,<br>the banner n.% is online.<br>Thank you.}</p>" ) );	// autoapprove
		$message = str_replace("%", $id_banner, $message);

		if(!$autoapprove) $subject = "[".SERVER_NAME."] ". translateHtml("{A new banner need your review!}");
			else $subject = "[".SERVER_NAME."] ". translateHtml("{A new banner is online!}");	// autoapprove


		global $conn;
		$sql = "select de_email from ".DB_PREFIX."frw_utenti inner join ".DB_PREFIX."frw_extrauserdata on cd_user=id where fl_attivo=1 and cd_profilo >=20";
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql);
		$ar = array();
		while($riga = $rs->fetch_array()) {
			if (is_email($riga['de_email'])) {
					mail_utf8(
						$riga['de_email'],
						$subject,
						$message);
			}
		}
	}




	/* save the payment in the payments table, so webmaster can see its revenue and adminstrator can track money in and out */
	function updateAdminPayments( $id_banner, $action ) {
		global $conn;
	
		if($action == "pending") { $stato = "'K'"; }
		if($action == "approved") { $stato = "'A','L'"; }

		$dati = execute_row("
		    select cd_webmaster,prezzo_finale,nu_share from ".DB_PREFIX."7banner 
			inner join ".DB_PREFIX."7banner_ordini on cd_banner=id_banner
			inner join ".DB_PREFIX."7banner_posizioni on cd_posizione=id_posizione
			inner join ".DB_PREFIX."7banner_sites on cd_sito=id_sito
                        inner join ".DB_PREFIX."frw_utenti on cd_webmaster=frw_utenti.id                        
			where id_banner='".$id_banner."' and ".DB_PREFIX."frw_utenti.fl_attivo = 1 and cd_profilo=10 and ".DB_PREFIX."7banner.fl_stato in (".$stato.")",0);
		if(isset($dati['cd_webmaster']) && $dati['cd_webmaster']> 0) {
			
			$lastRow = execute_row("select * from ".DB_PREFIX."7banner_payments where cd_webmaster = '".$dati["cd_webmaster"]."' and fl_stato=0 order by dt_quando desc limit 0,1", false);
			if(!is_array($lastRow)){
				$logline = "{#YmdHis payment record created. Amount is #M.}\n";
				$logline = translateHtml($logline);
				$logline = str_replace("#YmdHis", date("Y-m-d H:i:s"), $logline);
				$logline = str_replace("#M", "0 ".MONEY, $logline);
				$sql = "insert into ".DB_PREFIX."7banner_payments (cd_webmaster,nu_import_webmaster,nu_import_admin,dt_quando,fl_stato,de_log) values (".
					"'".$dati['cd_webmaster']."',".
					"'0',".
					"'0',".
					"'".date("Y-m-d")."',".
					"'0','".addslashes($logline)."')";
				$conn->query($sql) or trigger_error("SQL ERROR: ".$sql);
				$id = $conn->insert_id;
			} else {
				$id = $lastRow['id_payment'];
			}

			if($action=="pending") {


				$webmaster_revenue = $dati["nu_share"] * $dati['prezzo_finale'] / 100;
				$logline = "{#YmdHis Banner ##ID sold for #T. Possible webmaster revenue of #M (#S%).}\n";
				$logline = translateHtml($logline);
				$logline = str_replace("#YmdHis", date("Y-m-d H:i:s"), $logline);
				$logline = str_replace("#M", number_format($webmaster_revenue,2,".","") . " ".MONEY, $logline);
				$logline = str_replace("#S", $dati["nu_share"], $logline);
				$logline = str_replace("#ID", $id_banner, $logline);
				$logline = str_replace("#T", $dati['prezzo_finale'] . " ".MONEY, $logline);
				$sql = "UPDATE ".DB_PREFIX."7banner_payments 
					SET de_log = CONCAT(de_log,'".addslashes($logline)."'),
					dt_quando='".date("Y-m-d")."' where id_payment='".$id."'";
				$conn->query($sql) or trigger_error("SQL ERROR: ".$sql);
	
			} 

			
			if($action=="approved") {

				$webmaster_revenue = $dati["nu_share"] * $dati['prezzo_finale'] / 100;
				$admin_revenue = $dati['prezzo_finale'] - $webmaster_revenue;
				$logline = "{#YmdHis Banner ##ID approved. Added #M (#S% of #T) to webmaster revenue.}\n";
				$logline = translateHtml($logline);
				$logline = str_replace("#YmdHis", date("Y-m-d H:i:s"), $logline);
				$logline = str_replace("#M", number_format($webmaster_revenue,2,".","") . " ".MONEY, $logline);
				$logline = str_replace("#S", $dati["nu_share"], $logline);
				$logline = str_replace("#ID", $id_banner, $logline);
				$logline = str_replace("#T", $dati['prezzo_finale'] . " ".MONEY, $logline);
				$sql = "UPDATE ".DB_PREFIX."7banner_payments 
					SET 
					nu_import_webmaster= nu_import_webmaster + ".$webmaster_revenue." , 
					nu_import_admin= nu_import_admin + ".$admin_revenue." , 
					de_log = CONCAT(de_log,'".addslashes($logline)."'),
					dt_quando='".date("Y-m-d")."' where id_payment='".$id."'";
				$conn->query($sql) or trigger_error("SQL ERROR: ".$sql);
	
			} 



		}

	}


}

?>