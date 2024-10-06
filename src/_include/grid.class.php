<?php

DEFINE("NULLSIMBOL","-");

class Grid
{
	var $tablename;			//name of the table shown
	var $campi;				//list of fields comma separated, es: de_nome,de_cognome
	var $titoli;			//list of labels extracted, comma separated, es.: Nome,Cognome
	var $chiave;			//key field used in command links
	var $query;				//sql select
	var $debug;
	var $comandi;
	var $gestore;
	var $scegliDaInsieme;	//array with possible fields 
	var $campiMeta;			//array with fields that has meta
	var $evidenzia;
	var $tdcssevidenzia;
	var $flagOrdinatori;	//if false no ordering command links
	var $istanceName;
	var $parametriDaPssare;

	var $letterSelectField;	//letter index fields A B C D ...
	var $gridSelectedLetter;
	
	var $checkboxForm;		//true to add a checkbox with key id of each line
	var $checkboxFormName;
	var $checkboxFormTitolo;	//if there is title column for checkbox
	var $checkboxFormChekcboxAttributes;	//more infor on checkbox
	var $checkboxFormAction;
	var $mostraRecordTotali;
	var $ABCDmenu;
	var $paginatori;	//if false doesn't use pagination
	var $alternaColoriRighe;	//se true alterna i colori delle righe
	var $arFormattazioneCondizionale;	//if used handles conditional formats
										//with styles based on value, es:
										//	$t->arFormattazioneCondizionale=array(
										//		"de_stato" => array("Aperto","rigarossa")
										//	);
	var $arFormattazioneTD;	//if setted checks for formats in td with css
							//$t->arFormattazioneTD=array("giorni"=>"numero", "nomecampo"=>"nomeclassecss");

	var $functionhtml;	// allowed values: htmlspecialchars, htmlentities, myhtmlspecialchars
						// encode tds
						//
	var $mouseeffect;	// if true styles onmouseover/onmouseout
    var $ajaxMode;

	function __construct($table,$start=0,$pagesize=40,$orderby="id",$ordermode="asc",$selectedId="0",$istanceName="",$selectedLetter="")
	{
		global $session;
		$this->flagOrdinatori="on"; // if "on" show ordering links
		$this->tablename=$table;
		$this->istanceName=$istanceName;
		$this->alternaColoriRighe=true;
		$this->functionhtml="htmlspecialchars";
		$this->mouseeffect=true;
		$this->debug=false;
        $this->ajaxMode= true;

		if ($istanceName=="") {
			$this->istanceName=$this->tablename;
			$istanceName = $this->tablename;
		}
		$this->parametriDaPssare="";
		$this->letterSelectField="";

		$this->ABCDmenu="false";
		$this->gridSelectedLetter=$selectedLetter;

		$session->register($istanceName."gridStart",$start);
		$session->register($istanceName."gridPageSize",$pagesize);
		$session->register($istanceName."gridOrderBy",$orderby);
		$session->register($istanceName."gridOrderMode",$ordermode);
		$session->register($istanceName."gridSelectedId",$selectedId);
		$session->register($istanceName."gridSelectedLetter",$selectedLetter);

		$this->tdcssevidenzia="evidenziacelle";
		$this->campi="";
		$this->titoli="";
		$this->chiave="";
		$this->query="";
		$this->comandi=array();
		$this->scegliDaInsieme=array();
		$this->campiMeta=array();
		$this->gestore=$_SERVER["PHP_SELF"];
		$this->evidenzia=$selectedId;
		$this->mostraRecordTotali=false;	//if "true" shows total records

		$this->checkboxForm=false;
		$this->checkboxFormTitolo = "";
		$this->checkboxFormAction="";
		$this->checkboxFormName="";
		$this->checkboxFormChekcboxAttributes="";
	}

	/**
	 * The function adds a command to the array of commands used in the grid to make an action on a single record
	 * 

	 * @param string $link The "link" parameter is a string that represents the URL or destination of the command.

	 * @param string $label The "label" parameter is a string that represents the label or name of the command. It

	 * is used to identify the command in the user interface or any other context where the command is
	 * displayed.

	 * @param string $titolo The parameter "titolo" is an optional parameter that represents the title of the

	 * command. It is used in the "addComando" function to set the value of the "title" key in the command
	 * array. If a value is provided for "titolo", it will be assigned to the

	 * @param string $attribute

	 * 

	 * @return void

	 */
	function addComando($link,$label,$titolo="",$attribute='href') {
		$i = count($this->comandi);
		$a = array();
		$a["link"]=$link;
		$a["label"]=$label;
		$a["title"]=$titolo;
		$a['attribute']=$attribute;
		$this->comandi = $this->comandi + array($i => $a);
	}

	/**
	 * The function "addScegliDaInsieme" adds an array of choices to the existing "scegliDaInsieme" array,
	 * using a specified field as the key. The array is used to map values of a field to an html rapresentation
	 * of that value, or value 1/0 to ON/OFF labels.
	 * 

	 * @param string $campoMatch The parameter "campoMatch" is a field that is used to match the values in the

	 * array "arrayscelte". It is used as a key in the associative array "scegliDaInsieme".

	 * @param array $arrayscelte The parameter "arrayscelte" is an array that contains the choices/options that

	 * can be selected for a given field.
	 */
	function addScegliDaInsieme($campoMatch,$arrayscelte) {
		$this->scegliDaInsieme = $this->scegliDaInsieme + array($campoMatch => $arrayscelte);
	}

	/**
	 * The function "addCampi" adds a field and its corresponding format to an array. This is useful to 
	 * render dates/numbers/etc in the grid
	 * 

	 * @param string $campoMatch The parameter "campoMatch" is a string that represents the name or identifier of a

	 * field or variable. It is used to match the field with its corresponding format.

	 * @param string $formato The "formato" parameter is used to specify the format of the data that will be stored

	 * in the "campoMatch" field. It can have one of the following values:
	 */
	function addCampi($campoMatch,$formato, $params = array()) {
		// allowed formats:
		//    dd/mm/yyyy , dd/mm/yyyy hh:ii , email, url
		$this->campiMeta = $this->campiMeta + array($campoMatch => array("formato"=>$formato,"params"=>$params));
	}

	/**
	 * The function "addCampiDate" adds a new field and its corresponding date format to the "campiMeta"
	 * array. Similar to the previous but explicitly for dates.
	 * 

	 * @param string $campoMatch The parameter `` is a string that represents the name of a field or

	 * column in a database table. It is used as a key in an associative array to store the format of the
	 * date for that particular field.

	 * @param string $formato The "formato" parameter is a string that specifies the desired format for the date.

	 * It can have two possible values:
	 */
	function addCampiDate($campoMatch,$formato="dd/mm/yyyy") {
		// allowed formats:
		//    dd/mm/yyyy
		//    dd/mm/yyyy hh:ii
		$this->campiMeta = $this->campiMeta + array($campoMatch => array("formato"=>$formato,"params"=>array()));
	}


	/**
	 * The function "getABCDmenu" generates a menu of letters from A to Z, with an option to select all
	 * letters, and returns the HTML code for the menu.
	 * 
	 * @param string sel The parameter "sel" is used to specify the currently selected letter in the menu. It is a
	 * single character string representing a letter from A to Z.
	 * 
	 * @return string an HTML string that represents a menu of letters. The menu includes links for each letter from A
	 * to Z, with one letter being selected (highlighted) based on the value of the  parameter. The
	 * selected letter is displayed with a different CSS class compared to the other letters. Additionally,
	 * there is a link for selecting all letters. The returned string is wrapped in a div element
	 */
	function getABCDmenu($sel="") {
		$s="";
		for ($i=65;$i<=90;$i++) {
			if ($sel==chr($i)) {
				$s.="<a class=\"grid_lettera_selezionata\" href=\"{$this->gestore}?gridSelectedLetter=".chr($i)."&gridStart=0{$this->parametriDaPssare}\">".chr($i)."</a> ";
			} else {
				$s.="<a class=\"grid_lettera_normale\" href=\"{$this->gestore}?gridSelectedLetter=".chr($i)."&gridStart=0{$this->parametriDaPssare}\">".chr($i)."</a> ";
			}
		}
		if ($sel=="") {
			$s.="<a class=\"grid_lettera_selezionata\" href=\"{$this->gestore}?gridSelectedLetter=&gridStart=0{$this->parametriDaPssare}\">{all}</a> ";
		} else {
			$s.="<a class=\"grid_lettera_normale\" href=\"{$this->gestore}?gridSelectedLetter=&gridStart=0{$this->parametriDaPssare}\">{all}</a> ";
		}
		return "<div class='grid_lettere_contenitore'>$s</div>";
	}
	
	/**
	 * The function "show" shows the grid
	 *
	 * @return string HTML string with the grid
	 */
	function show() {
		global $session,$root,$conn;
		//rimappo i valori perchè "true" come stringa non mi piace.
		if($this->checkboxForm=="true") { $this->checkboxForm=true;} else {$this->checkboxForm=false;}
		if($this->ABCDmenu=="true") { $this->ABCDmenu=true;} else {$this->ABCDmenu=false;}

		if (($this->ABCDmenu==true)&&($this->letterSelectField!="")&&($session->get($this->istanceName."gridSelectedLetter")!="")) {
			//	se ho selezionato una lettera e c'è configurato un campo
			//	per selezionare l'elenco per lettera e se e' specificata
			//	una lettera
			if (stristr($this->query," where ")) {
				// se c'e' un where aggiungo la condizione
				// sulla lettera
				$this->query=preg_replace("/ where /i",' where '.$this->letterSelectField.' like \''.$session->get($this->istanceName."gridSelectedLetter").'%\' and ',$this->query);
			} else {
				//	non ci sono condizioni, aggiungo quella
				//	sulla lettera
				$this->query.=' where '.$this->letterSelectField.' like \''.$session->get($this->istanceName."gridSelectedLetter").'%\'';
			}

			$this->getABCDmenu($session->get($this->istanceName."gridSelectedLetter"));
		}
		$sql=$this->query;
		if(!stristr($this->query," ORDER BY ")) {
			$sql.= " ORDER BY ".$session->get($this->istanceName."gridOrderBy")." ";
			$sql.= $session->get($this->istanceName."gridOrderMode");
		}
		$sql.= " LIMIT ".(integer)$session->get($this->istanceName."gridStart").",";
		$sql.=(integer)$session->get($this->istanceName."gridPageSize");
		$x=strpos($sql,"SQL_CALC_FOUND_ROWS");
		if (strpos($sql,"SQL_CALC_FOUND_ROWS")==false) $sql = preg_replace("/^select /i","select SQL_CALC_FOUND_ROWS ",$sql);
		$t="<div class='gridWrapper ".($this->ajaxMode?"ajaxmode":"")."' id='gridWrapper_{$this->tablename}'><div>";
		if ($this->debug) {
			$t.="<div class='helpbox'><b>QUERY:</b><br/> $sql<br/>";
			$rs = $conn->query($sql) or $t .= "<span style='background-color:red;color:#fff;'>errore: ".$conn->error."</span>";
			$t.="</div><br style='clear:both'/>";
		} else {
			$rs = $conn->query($sql) or trigger_error("Query: {$sql}; errore: ".$conn->error);
		}
		$sql = "Select FOUND_ROWS()"; $temp = $conn->query($sql); 
		$temprow = $temp->fetch_array();
		$max = $temprow[0];
		$indietro=0;
		if ($session->get($this->istanceName."gridStart") > $max) {
			$session->register($this->istanceName."gridStart",0);
			return "<script type='text/javascript'> document.location = \"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridStart=".$indietro."$this->parametriDaPssare\";</script>";
		}

		$ab = 0;	//per dare l'id alla riga;
		$flagEmpty = false;
		if ($rs->num_rows>0) {

			$titoli = explode(",",$this->titoli);
			$titolicampi = explode (",",$this->campi);

			if(count($titolicampi)<count($titoli)) {
				return "<div class='helpbox'>Attenzione la griglia non &egrave; configurata correttamente, stai estraendo meno campi di quelli che vuoi visualizzare.</div>";
			}


			$maxPagine = ceil($max / $session->get($this->istanceName."gridPageSize"));
			$paginaattuale= ceil($session->get($this->istanceName."gridStart") /  $session->get($this->istanceName."gridPageSize")) + 1;
			if ($paginaattuale > $maxPagine) $paginaattuale = $maxPagine;

			//--------------------------------------
			//paginatore
			$p = "";

			if ($max > $session->get($this->istanceName."gridPageSize") || $this->mostraRecordTotali) {
				$indietro=$session->get($this->istanceName."gridStart") - $session->get($this->istanceName."gridPageSize");
				$avanti = $session->get($this->istanceName."gridStart") + $session->get($this->istanceName."gridPageSize");
				if ($indietro >= 0) {

					// if($indietro!=0) $p.="<a title=\"{Go to first page}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridStart="."0"."$this->parametriDaPssare\" title=\"{Go to first page}\" class='ajax'><span class='icon-angle-double-left'></span></a>&nbsp;&nbsp;";

					//pagina indietro
					$p.="<a title='{Go to previous page}' href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridStart=".$indietro."$this->parametriDaPssare\" title=\"{Go to previous page}\" class='ajax'><span class='icon-angle-left'></span></a>&nbsp;&nbsp;";

				}
				$p.="  ";

				if ($avanti < $max) {
					//pagina avanti
					$p.="&nbsp;&nbsp;<a title=\"{Go to next page}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridStart=".$avanti."$this->parametriDaPssare\" title=\"{Go to next page}\" class='ajax'><span class='icon-angle-right'></span></a>&nbsp;&nbsp;";


					// if($maxPagine> 2) $p.="<a title=\"{Go to last page}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridStart=".($max-$session->get($this->istanceName."gridPageSize"))."$this->parametriDaPssare\" title=\"{Go to last page}\" class='ajax'><span class='icon-angle-double-right'></span></a>";

				}
				//$p.=" {Page} <B>".number_format($paginaattuale,0,'.','.')."</B> {of} <B>".number_format($maxPagine,0,'.','.')."</B> ";
				
				$first = (1 + (integer)$session->get($this->istanceName."gridStart"));
				$second = (integer)$session->get($this->istanceName."gridStart") + (integer)$session->get($this->istanceName."gridPageSize");
				if($second > $max) $second = $max;

				//if ($this->mostraRecordTotali) $p .="<span id='infogrid'>".number_format($max,0,'.','.')." {records}</span>";
				if ($this->mostraRecordTotali) $p ="<span class='infogrid'>".$p. " ". $first."-".
					$second	. " {of} ".number_format($max,0,'.','.')."</span>";
			}


			//--------------------------------------



			if ($this->checkboxForm==true)
				$custr="<a href='#' class='checkall' title=\"{Check all / Uncheck}\" onclick=\"return checkAll(this)\"><span class='icon-check-empty'></span></a>";
			//
			// intestazione colonne
			//

			if ($this->checkboxForm==true) {
				//
				// if has checkbox add <form...
				//
				$t.="<form name=\"$this->checkboxFormName\" method=\"post\" action=\"$this->checkboxFormAction\">\n";
				$t.="<input type=\"hidden\" name=\"op\" value=\"checkboxes\">\n";
				$t.="<input type=\"hidden\" name=\"id\" value=\"\">\n";
			}
			$t.="<div class='grigliacontainer' id='container_{$this->tablename}'>";
            if($p) $t.="<div class='first'>{$p}</div>";
            $t.="<table class='griglia' id='tab_{$this->tablename}'>";
			$t.="<thead>";


			//header--------------------------------------
			$th = "";
			$th.="<tr class='initial' id='header_{$this->tablename}'>";
			if($this->checkboxForm) {
				$th.="\t<th>{$custr}{$this->checkboxFormTitolo}</th>";
			}
			for ($i=0; $i<count($titoli); $i++ ){

				$allinea="";
				if(isset($this->arFormattazioneTD)) foreach($this->arFormattazioneTD as $k=>$v) {
					if($v == "numero" && $k== $titolicampi[$i]) {
						$allinea='numero'; break;
					}
				}

				$th.="\t<th class='col".$i." ".$allinea."' >";

				if($this->functionhtml == "htmlspecialchars") {
					$label =htmlspecialchars($titoli[$i]);
				} elseif($this->functionhtml == "htmlentities") {
					$label =htmlentities($titoli[$i]);
				} elseif($this->functionhtml == "myhtmlspecialchars") {
					$label =myhtmlspecialchars($titoli[$i]);
				} else $label = $titoli[$i] ;

				
				if ($this->flagOrdinatori=="on") {

					if(
						($titolicampi[$i] == $session->get($this->istanceName."gridOrderBy"))
						&&
						($session->get($this->istanceName."gridOrderMode" )== "asc")
						) {
							$link = "<a title=\"{Sort in descending order}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridOrderBy=$titolicampi[$i]&gridOrderMode=desc$this->parametriDaPssare\" class='ajax'>";
							$img = "<span class='icon-up-open-mini'></span>";
					} elseif (($titolicampi[$i] == $session->get($this->istanceName."gridOrderBy"))
						&&
						($session->get($this->istanceName."gridOrderMode" )== "desc")
						) {
							$link = "<a title=\"{Sort in ascending order}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridOrderBy=$titolicampi[$i]&gridOrderMode=asc$this->parametriDaPssare\" class='ajax'>";
							$img = "<span class='icon-down-open-mini'></span>";
					} else {
						$link = "<a title=\"{Sort in ascending order}\" href=\"$this->gestore?gridSelectedLetter=$this->gridSelectedLetter&gridOrderBy=$titolicampi[$i]&gridOrderMode=asc$this->parametriDaPssare\" class='ajax'>";
						$img = "";
					}


					$th.=$link.$label.$img;
					$th.="</a>";

				} else {
					$th .= $label;
				}
				$th.="</th>\n";
			}

			if(count($this->comandi)>0) $th.="<th colspan='".count($this->comandi)."' style='text-align:right'>";
			
			//
			$th.="</th>";
			$th.="</tr>\n";
			$t.=$th."</thead>";
			//--------------------------------------

			$t.="<tbody>";

			//
			// shows rows
			//
			while ($r=$rs->fetch_array()) {
				$t_TR="";
				$id="";
				$t_FOR="";
				
				$arCampi = explode(",",$this->campi);
				for ($i = 0; $i<count($arCampi); $i++) {
					$arCampi[$i] = trim ($arCampi[$i]);
					if($this->chiave) $id = $r[$this->chiave]; else $id ="";
					$classetr = "";
					$classetd = "";

					if (isset($this->arFormattazioneTD) ) {
						foreach ($this->arFormattazioneTD as $nomecampo => $val) {
							if($nomecampo == $arCampi[$i] ) {
								$classetd = $val;
							}
						}
					}



					if (($this->checkboxForm==true)&&($i==0)) {
						$t_FOR.="\t<td class='chex' id='cell_{$id}_check'>";
						$t_FOR.="<input type=\"checkbox\" {$this->checkboxFormChekcboxAttributes} name=\"gridcheck[]\" value=\"$id\"></td>";
					}

				
					if (isset($this->arFormattazioneCondizionale)) {
						// if defined use this values to handles css styles
						foreach ($this->arFormattazioneCondizionale as $nomecampo => $arValori) {
							if($nomecampo == $arCampi[$i] ) {
								for($fc=0;$fc<count($arValori);$fc++) {
									if($arValori[$fc]==$r[$arCampi[$i]]) {
										$classetr = $arValori[$fc + 1];
									}
									$fc++;
								}
							}
						}
					}




					if (($id == $this->evidenzia)&&($this->evidenzia!="0")) $classetr = $this->tdcssevidenzia;
					$t_FOR.="\t<td class='$classetd col".$i."' id='cell_{$id}_{$i}'>";


					if (array_key_exists ($arCampi[$i],$this->scegliDaInsieme)) {
						// show alternatives label for a field
						//
						if (isset($this->scegliDaInsieme[$arCampi[$i]][$r[$arCampi[$i]]]))
							$t_FOR.= str_replace("##$this->chiave##",$id,$this->scegliDaInsieme[$arCampi[$i]][$r[$arCampi[$i]]]) ;
						else $t_FOR.=$r[$arCampi[$i]];
					} else if (array_key_exists ($arCampi[$i],$this->campiMeta)) {
						//	format date fields and other fields with "meta" infos
						//
						switch ($this->campiMeta[$arCampi[$i]]["formato"]) {
							case 'url':
								if(trim($r[$arCampi[$i]])) {
									$t_FOR.="<a href='".(preg_match("/^http:/i",$r[$arCampi[$i]])?"http://":"").$r[$arCampi[$i]]."'>".$r[$arCampi[$i]]." <img src='".$root."src/images/browse.gif'/></a>";
								} else {
									$t_FOR.=$r[$arCampi[$i]];
								}
								break;
							case 'email':
								if(trim($r[$arCampi[$i]])) {
									$t_FOR.="<a href=\"mailto:".$r[$arCampi[$i]]."\" class='emaildata'>".$r[$arCampi[$i]]."</a>";
								} else {
									$t_FOR.=$r[$arCampi[$i]];
								}
								break;
                            case 'link':
                                $url = $this->campiMeta[$arCampi[$i]]["params"]["url"];
                                $url = str_replace("##".$this->chiave."##",$r[$this->chiave],$url);
                                $t_FOR.="<a href=\"".$url."\"><u>".$r[$arCampi[$i]]."</u></a>";
                                break;
							case 'dd/mm/yyyy':
								$t_FOR.=($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) ? TOdmy($r[$arCampi[$i]],"/") : $r[$arCampi[$i]];
								break;
							case 'dd/mm/yyyy hh:ii':
								if ($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) {
									$tempAr = explode(" ",$r[$arCampi[$i]]);
									$t_FOR.=TOdmy($tempAr[0],"/")." ".$tempAr[1];
								} else {
									$t_FOR.=$r[$arCampi[$i]];
								}
								break;
							case 'yyyy/mm/dd':
								$t_FOR.=($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) ? date("Y/m/d",strtotime($r[$arCampi[$i]])) : $r[$arCampi[$i]];
								break;
							case 'yyyy/mm/dd hh:ii':
								if ($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) {
									$t_FOR.=($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) ? date("Y/m/d H:i",strtotime($r[$arCampi[$i]])) : $r[$arCampi[$i]];
								} else {
									$t_FOR.=$r[$arCampi[$i]];
								}
								break;
							case 'mm/dd/yyyy':
								$t_FOR.=($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) ? date("m/d/Y",strtotime($r[$arCampi[$i]])) : $r[$arCampi[$i]];
								break;
							case 'mm/dd/yyyy hh:ii':
								$t_FOR.=($r[$arCampi[$i]] && !stristr($r[$arCampi[$i]],ZERODATE)) ? date("m/d/Y H:i",strtotime($r[$arCampi[$i]])) : $r[$arCampi[$i]];
								break;
							case 'timestamp':
								$r[$arCampi[$i]] = date("Y-m-d H:i:s",(float) $r[$arCampi[$i]]);
								$t_FOR.=$r[$arCampi[$i]];
								break;

							default:
								// external function
								if($r[$arCampi[$i]] === null) $r[$arCampi[$i]] = "";

								$param1 = str_replace("\"","&quot;", $r[$arCampi[$i]]);
								$param2 = str_replace("\"","&quot;", $id);
								$param1 = str_replace('$','\$', $param1);
								if(isset($this->campiMeta[$arCampi[$i]]["params"])) {
									// TO DO
									// support for a third parameter (feature not so nice completed)
									//
									$param3 = json_encode($this->campiMeta[$arCampi[$i]]["params"]);
									$param3 = str_replace("\"","&quot;", $param3); // bad
									$strCmd = $this->campiMeta[$arCampi[$i]]["formato"].'("'.$param1.'","'.$param2.'","'.$param3.'");';
								} else{
									$strCmd = $this->campiMeta[$arCampi[$i]]["formato"].'("'.$param1.'","'.$param2.'");';
								}
								eval ( '$t_FOR.='.$strCmd.";" );
								break;

						}

					} else {
						//
						// else show value from db
						//
						if(!isset($r[$arCampi[$i]])) { 
							if($this->debug) {
								$r[$arCampi[$i]] = "<span class='nf'><b>{$arCampi[$i]}</b> not found!</span>";
							} else {
								$r[$arCampi[$i]] = "<span class='nf2'>".NULLSIMBOL."</span>";
							}
						}
						if($this->functionhtml == "htmlspecialchars") {
							$t_FOR.=htmlspecialchars($r[$arCampi[$i]]);
						} elseif($this->functionhtml == "htmlentities") {
							$t_FOR.=htmlentities($r[$arCampi[$i]]);
						} elseif($this->functionhtml == "myhtmlspecialchars") {
							$t_FOR.=myhtmlspecialchars($r[$arCampi[$i]]);
						} else $t_FOR.= $r[$arCampi[$i]] ;
					}
					$t_FOR.="</td>\n";

				}

				$ab++;

				$t_TR="<tr id=\"tr{$ab}\" {$classetr}>";

				$t.=$t_TR.$t_FOR;
				for ($i=0; $i<count($this->comandi); $i++ ){
					$t.="\t<td>";
					$comando = $this->comandi[$i]["link"];
					for ($j=0; $j<mysqli_num_fields($rs); $j++) {
						$field = $rs->fetch_field_direct($j);
						$comando=str_replace("##".$field->name."##",(isset($r[$j]) ? $r[$j] : "") ,$comando);
					}
					// if command contains "##idrigatabella##" replace it with id of tr table row
					$comando=str_replace("##idrigatabella##","tr{$ab}",$comando);
					$t.="<a ".
						"title=\"".$this->comandi[$i]["title"]."\" ".
						"class=\"".$this->comandi[$i]["label"]."\" ".
						$this->comandi[$i]["attribute"]."=\"".str_replace("##$this->chiave##",$id,$comando)."\"".
						">";

					$t.="</a>";
					$t.="</td>\n";
				}

				$t.="</tr>\n";
			}
			$t.="</tbody>";
			$t.="</table></div>\n";
			if ($this->checkboxForm==true) {
				//
				// if has checkbox it's a form, close it
				//
				$t.="</form>\n";
			}
		} else {
			$t.="";
			$flagEmpty = true;
		}
		if ($this->ABCDmenu==true) {
			$t=$this->getABCDmenu($session->get($this->istanceName."gridSelectedLetter")). $t;
		}
        $t.='</div></div>';

		if($flagEmpty == true && !$this->ABCDmenu && !$this->debug) {
			return "";
		}
		return $t;
	}

}
