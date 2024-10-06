<?php
/*
    model for dashboard and charts
*/

class Dashboard {
    var $bannerObject;
    var $tbdb;
    var $gestore;
    function __construct ( $obj ) {
        $this->bannerObject = $obj;
        
        $this->tbdb = $this->bannerObject->tbdb;
        $this->gestore = $this->bannerObject->gestore;
    }

    /**
     * csv data extraction
     * 
     * @param mixed $id   banner id or campaign if -999|idcampagna
     * @param string $combobanner
     * @param string $combobannerreset
     * @param string $startdateData
     * @param string $enddateData
     * 
     * @return string csv data
     */
	function getCsvData($id,$combobanner,$combobannerreset,$startdateData="",$enddateData="") {
		global $session;
		$def = 30;
		$csv = "";
		
		if ($session->get("BANNER") && $this->bannerObject->check_cliente($id ? $id : $combobanner)) {
			$v = $id ? $id : $combobanner;
			if(!stristr($v,"-999|")) $sql = "select MIN(id_day) from ".DB_PREFIX."7banner_stats where cd_banner=".(integer)$v;
			else $sql = "select MIN(id_day) from ".DB_PREFIX."7banner_stats inner join ".DB_PREFIX."7banner on cd_banner=id_banner where cd_campagna=".((integer)(str_replace("-999|","",$combobanner)));
			$d = execute_scalar($sql);
			if(!stristr($v,"-999|")) $sql = "select MAX(id_day) from ".DB_PREFIX."7banner_stats where cd_banner=".(integer)$v;
			else $sql = "select MAX(id_day) from ".DB_PREFIX."7banner_stats inner join ".DB_PREFIX."7banner on cd_banner=id_banner where cd_campagna=".((integer)(str_replace("-999|","",$combobanner)));
			$d1 = execute_scalar($sql);
 			if($startdateData=="" || $combobannerreset=="combobannerreset") {
				$startdateData = DateAdd(-1,$d,"Y-m-d");
				$enddateData = DateAdd(+0,$d1,"Y-m-d");
			}
			if($startdateData > date("Y-m-d")) $startdateData= date("Y-m-d");
			$period = $this->dateDifference($startdateData,$enddateData) + 1;

			if(stristr($combobanner,"|")) {
				$temp = explode("|",$combobanner);
				if($temp[0]=="-999") {
					$dati['cd_campagna'] = $temp[1];
					$dati['de_nome'] = "All";
					$ids = concatenaId("select id_banner from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
				}
			} else {
				$dati = $this->bannerObject->getDati($id ? $id : $combobanner);
				$dati["de_posizione"] = execute_scalar("select de_posizione from ".DB_PREFIX."7banner_posizioni where id_posizione='".$dati['cd_posizione']."'");
				$ids = $dati['id_banner'];
			}
			
			// days
			$giorni = $this->giorni($period,$startdateData);
			// values
			$sql = "select id_day,sum(nu_pageviews) as nu_pageviews,sum(nu_click) as nu_click from ".DB_PREFIX.$this->tbdb."_stats where cd_banner in(".($ids ? $ids : "-1").") and id_day>='".$startdateData."' group by id_day order by id_day asc limit 0,".$period;
			$serie = $this->serie($sql ,$period,$startdateData);
			preg_match_all("/\[([^\]]*)\]/",$serie,$obj);
			// create csv data
			$lista = "";
			if(isset($obj[1]) && isset($obj[1][0]) && $obj[1][0]!="") {
				$pv = explode(",",$obj[1][0]); // pageviews
				$cl = explode(",",$obj[1][1]); // clicks
				$gg = explode(",",$giorni);    // days
				$q=0;
				foreach($pv as $v){
					if($q==0) {
						$lista.= translateHtml( "{Day};{Views};{Clicks}\n" );
					}
					$gg[$q] = str_replace("'","",$gg[$q]);
					$lista .= $gg[$q].";".$v.";".$cl[$q]."\n";
					$q++;
				}
				$csv = $lista;
			}
		} else {
			$csv = "0";
		}
		return $csv;
	}

	//
	//	given a period and comboreferrer and using session user
	//	determine the ids that the user can see and the queries to extract
	//	data for series (chart)
	function dash_getIdsAndQueries($startdate, $enddate, $comboreferrer) {
		global $session;
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		if($session->get("idprofilo")==5) {
			/* ADVERTISER VIEW */
			$ids = concatenaId("SELECT id_banner FROM ".DB_PREFIX."7banner
				INNER JOIN ".DB_PREFIX."7banner_campagne ON cd_campagna = id_campagna
				INNER JOIN ".DB_PREFIX."7banner_clienti ON ".DB_PREFIX."7banner_campagne.cd_cliente = ".DB_PREFIX."7banner_clienti.id_cliente AND cd_utente=".$session->get("idutente")	
			);
			if($ids == "") {
				$ids = "-1";
			}
			$sql = "SELECT id_day,SUM(nu_pageviews) AS nu_pageviews,SUM(nu_click) AS nu_click FROM ".DB_PREFIX.$this->tbdb."_stats WHERE cd_banner IN(".($ids ? $ids : "-1").") 
			AND id_day>='".$startdate."' 
			AND id_day<='".$enddate."' ".
			($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
			" GROUP BY id_day ORDER BY id_day ASC";
		} elseif ($session->get("idprofilo")==10) {
			/* WEBMASTER VIEW */
			$ids = concatenaId("SELECT id_banner FROM ".DB_PREFIX."7banner 
				INNER JOIN ".DB_PREFIX."7banner_posizioni ON cd_posizione = id_posizione
				INNER JOIN ".DB_PREFIX."7banner_sites ON cd_sito = id_sito 
				WHERE cd_webmaster=".$session->get("idutente"));
			if($ids == "") {
				$ids = "-1";
			}
			$sql = "SELECT id_day,SUM(nu_pageviews) AS nu_pageviews,SUM(nu_click) AS nu_click FROM ".DB_PREFIX.$this->tbdb."_stats WHERE cd_banner IN(".($ids ? $ids : "-1").") 
			AND id_day>='".$startdate."'
			AND id_day<='".$enddate."' ".
			($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
			" GROUP BY id_day ORDER BY id_day ASC";
		} else {
			/* ADMINISTRATOR VIEW */
			$ids = '';
			$sql = "SELECT id_day,SUM(nu_pageviews) AS nu_pageviews,SUM(nu_click) AS nu_click FROM ".DB_PREFIX.$this->tbdb."_stats WHERE 
			id_day>='".$startdate."' 
			AND id_day<='".$enddate."'".
			($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
			" GROUP BY id_day ORDER BY id_day ASC";
		}
		return array("sql" => $sql, "ids" => $ids);
	}
	//
	// given a period get the query for the money serie report
	// it uses ids and session user to determine the correct query
	function dash_getMoneyQuery( $startdate, $enddate, $ids,$comborefferer ) {
		global $session;
		$id_sito=0;
		if( $comborefferer !="") {
			$id_sito = execute_scalar("select id_sito from ".DB_PREFIX."7banner_sites where de_urlsito='".addslashes($comborefferer)."'",0);
		}
		if($session->get("idprofilo")==5) {
			/* ADVERTISER view */
			$sql = "SELECT 
						CASE 
							WHEN cd_banner IS NULL
							THEN dt_giorno1
							ELSE DATE(data_pagamento)
						END
						 AS id_day,SUM(CASE 
							WHEN cd_banner IS NULL
							THEN nu_price
							ELSE prezzo_finale
						END) AS nu_money
					FROM ".DB_PREFIX."7banner 
					LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner=id_banner
					INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione=id_posizione
					LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito=id_sito
				WHERE id_banner IN (".($ids ? $ids : "-1").") 
					AND (
							(
								cd_banner IS NOT NULL AND
								DATE(data_pagamento)>='".$startdate."' 
								AND DATE(data_pagamento)<='".$enddate."'
							)
								OR
							(
								cd_banner IS NULL AND
								dt_giorno1>='".$startdate."' 
								AND dt_giorno1<='".$enddate."'
							)
					) 
					AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )
					".($id_sito > 0 ? "AND cd_sito='".$id_sito."'":"")."
					GROUP BY id_day ORDER BY id_day ASC";
		} elseif ($session->get("idprofilo")==10) {
			/* WEBMASTER VIEW */
			
			 $sql = "SELECT
 						CASE 
							WHEN cd_banner IS NULL
							THEN dt_giorno1
							ELSE DATE(data_pagamento)
						END AS id_day,SUM(
				CASE 
					WHEN cd_banner IS NULL
					THEN nu_price*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
					ELSE prezzo_finale*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
				END
				) AS nu_money FROM ".DB_PREFIX."7banner
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito
			 WHERE id_banner IN(".($ids ? $ids : "-1").") 
					AND (
						(
						cd_banner IS NOT NULL AND
						DATE(data_pagamento)>='".$startdate."' 
						AND DATE(data_pagamento)<='".$enddate."'
						)
						OR
						(
						cd_banner IS NULL AND
						dt_giorno1>='".$startdate."' 
						AND dt_giorno1<='".$enddate."'
						)
					) 
			 AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )
			 ".($id_sito > 0 ? "AND cd_sito='".$id_sito."'":"")."
			 GROUP BY id_day ORDER BY id_day ASC";
			 
		} else {
			/* ADMINISTRATOR VIEW */
			$sql = "SELECT 
			 			CASE 
							WHEN cd_banner IS NULL
							THEN dt_giorno1
							ELSE DATE(data_pagamento)
						END
			 AS id_day,SUM(
				CASE 
				WHEN cd_banner IS NULL
				THEN nu_price*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				ELSE prezzo_finale*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				END
				) AS nu_money FROM ".DB_PREFIX."7banner 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito
			WHERE 
			1
			AND (
						(
						cd_banner IS NOT NULL AND
						DATE(data_pagamento)>='".$startdate."' 
						AND DATE(data_pagamento)<='".$enddate."'
						)
						OR
						(
						cd_banner IS NULL AND
						dt_giorno1>='".$startdate."' 
						AND dt_giorno1<='".$enddate."'
						)
					) 
			AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )
			".($id_sito > 0 ? "AND cd_sito='".$id_sito."'":"")."
			GROUP BY id_day ORDER BY id_day ASC";
		}
		return $sql;
	}
	//
	// given a period and an interval and using ids and the session user
	// retrievs the money and the variation for the counter boxes
	function dash_getMoney( $enddate, $interval=1, $ids="" ) {
		global $session;
		if($session->get("idprofilo")==5) {
			/* ADVERTISER view */
			$value = execute_scalar("SELECT SUM(CASE 
					WHEN cd_banner IS NULL
					THEN nu_price
					ELSE prezzo_finale
				END) AS tot FROM ".DB_PREFIX."7banner LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini on cd_banner=id_banner WHERE 
				"  .($interval==1
					? 
						" ((cd_banner IS NULL AND dt_giorno1='".$enddate."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".$enddate."')) " 
					: 
						" ((cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval ,$enddate,'Y-m-d')."')) 
						
					")."
				AND id_banner IN(".($ids ? $ids : "-1").")
				AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");
			$value_previous = execute_scalar("SELECT SUM(CASE 
					WHEN cd_banner IS NULL
					THEN nu_price
					ELSE prezzo_finale
				END) AS tot FROM ".DB_PREFIX."7banner LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini on cd_banner=id_banner WHERE  
				".($interval==1 
					? 
						" 
						((cd_banner IS NULL AND dt_giorno1='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".DateAdd( -$interval ,$enddate,'Y-m-d')."'))
						" 
					: 
						"((cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
							AND  DATE(data_pagamento)<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ) OR 
						(cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
							AND  dt_giorno1<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ))
						"
				)." 
				AND id_banner IN(".($ids ? $ids : "-1").")
				AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");
		} elseif ($session->get("idprofilo")==10) {
			/* WEBMASTER VIEW 
			*/
			$value = execute_scalar("SELECT SUM(
					CASE 
						WHEN cd_banner IS NULL
						THEN nu_price*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
						ELSE prezzo_finale*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
					END
				) AS tot FROM ".DB_PREFIX."7banner 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito
			WHERE 
				".
				($interval==1
				? 
					" ((cd_banner IS NULL AND dt_giorno1='".$enddate."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".$enddate."')) " 
				: 
					" ((cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval ,$enddate,'Y-m-d')."')) 
					
				")."
				AND id_banner IN(".($ids ? $ids : "-1").")
				AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");
			$value_previous = execute_scalar("SELECT SUM(CASE 
						WHEN cd_banner IS NULL
						THEN nu_price*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
						ELSE prezzo_finale*(CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END)/100
					END) AS tot FROM ".DB_PREFIX."7banner
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito
			WHERE 
				".
				($interval==1 
				? 
					" 
					((cd_banner IS NULL AND dt_giorno1='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".DateAdd( -$interval ,$enddate,'Y-m-d')."'))
					" 
				: 
					"((cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
						AND  DATE(data_pagamento)<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ) OR 
					(cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
						AND  dt_giorno1<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ))
					"
			)." 			
				AND id_banner IN(".($ids ? $ids : "-1").")
				AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");			

		} else {
			/* ADMINISTRATOR VIEW 
			*/
			$value = execute_scalar($sql = "SELECT SUM(
				CASE 
				WHEN cd_banner IS NULL
				THEN nu_price*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				ELSE prezzo_finale*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				END) AS tot FROM ".DB_PREFIX."7banner 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito			
			WHERE 
				".				
				($interval==1 
				? 
					" 
					((cd_banner IS NULL AND dt_giorno1='".$enddate."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".$enddate."'))
					" 
				: 
					"((cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR 
					(cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval ,$enddate,'Y-m-d')."'))
					"
			)."
			AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");
			// echo "\n\n----- $interval \n\n".$sql;
			$value_previous = execute_scalar("SELECT SUM(CASE 
				WHEN cd_banner IS NULL
				THEN nu_price*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				ELSE prezzo_finale*(100 - (CASE WHEN nu_share IS NULL THEN 0 ELSE nu_share END))/100
				END) AS tot FROM ".DB_PREFIX."7banner 
				LEFT OUTER JOIN ".DB_PREFIX."7banner_ordini ON cd_banner = id_banner
				INNER JOIN ".DB_PREFIX."7banner_posizioni on cd_posizione = id_posizione
				LEFT OUTER JOIN ".DB_PREFIX."7banner_sites on cd_sito = id_sito
			WHERE 
				".
				
				($interval==1 
				? 
					" 
					((cd_banner IS NULL AND dt_giorno1='".DateAdd( -$interval ,$enddate,'Y-m-d')."') OR (cd_banner IS NOT NULL AND DATE(data_pagamento)='".DateAdd( -$interval ,$enddate,'Y-m-d')."'))
					" 
				: 
					"((cd_banner IS NOT NULL AND DATE(data_pagamento)>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
						AND  DATE(data_pagamento)<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ) OR 
					(cd_banner IS NULL AND dt_giorno1>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' 
						AND  dt_giorno1<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ))
					"
				)."
				AND (en_stato_pagamento='pagato' OR (en_stato_pagamento IS NULL AND fl_stato NOT IN ('K','D','W') ) )"
			,"0");	
		}
		return array("value" =>$value, "previous"=>$value_previous);
	}

	//
	// given a period and an interval and using ids and comboreferrer
	// retrieves the numb for the counter boxes, it works with field
	// nu_pageviews and nu_click, but also prezzo_finale and nu_share for the money
	function dash_getNumber( $field, $enddate, $interval=1, $ids = "", $comboreferrer="" ) {
		$value = 0;
		$variation = 0;
		$value_previous = 0;
		//
		// views or clicks
		if ($field == "nu_pageviews" || $field == "nu_click") {
			// $comboreferrer == "" ===> all records
			// $comboreferrer == "-1" ===> only empty referres
			$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
			$sqlValue = "SELECT SUM(".$field.") 
				FROM ".DB_PREFIX."7banner_stats 
				WHERE ".($interval==1 ? " id_day='".$enddate."' " : " id_day>='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ").
				($ids ? " AND cd_banner IN(".$ids.")" : "" ) .
				($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "");
			$sqlPrevValue ="SELECT SUM(".$field.")
				FROM ".DB_PREFIX."7banner_stats 
				WHERE
					".($interval==1 ? " id_day='".DateAdd( -$interval ,$enddate,'Y-m-d')."' " : " id_day>='".DateAdd( -$interval*2 ,$enddate,'Y-m-d')."' AND  id_day<='".DateAdd( -$interval ,$enddate,'Y-m-d')."' ").
				($ids ? "AND cd_banner IN(".$ids.")" : "" ) .
				($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "");
			$value = execute_scalar($sqlValue,"0");
			$value_previous =  execute_scalar($sqlPrevValue,"0");
		}
		//
		// money
		if ($field == "prezzo_finale") {
			$valueAr = $this->dash_getMoney( $enddate, $interval, $ids );
			$value = $valueAr["value"];
			$value_previous = $valueAr["previous"];
		}
		//
		// prepare output
		if ($value_previous==0) {
			$variation = $value == 0 ? 0 : 100;
		} else {
			$variation = number_format( ( $value - $value_previous ) / $value_previous * 100,0, ".",",");
		}
		$variation = ($variation >=0 ? "+".$variation : $variation) . "%";
		$value = number_format($value, $field == "prezzo_finale" ? 2 : 0 ,".",",");
		return array("value"=>$value,"variation"=>$variation);	
	}

	//
	// given a period and and ids and comboreferrer
	// get all the numbers for boxes
	function dash_getCounters($enddate, $ids, $comboreferrer) {
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		// DAILY VIEWS
		$ar = $this->dash_getNumber("nu_pageviews", $enddate, 1, $ids, $comboreferrer);
		$DV = $ar["value"];	$DV_V = $ar["variation"];
		// DAILY CLICKS
		$ar = $this->dash_getNumber("nu_click", $enddate, 1, $ids, $comboreferrer);
		$DC = $ar["value"];	$DC_V = $ar["variation"];
		// DAILY MONEY
		$ar = $this->dash_getNumber("prezzo_finale", $enddate, 1, $ids, $comboreferrer);
		$DM = $ar["value"];	$DM_V = $ar["variation"];
		
		// WEEK VIEWS
		$ar = $this->dash_getNumber("nu_pageviews", $enddate, 7, $ids, $comboreferrer);
		$WV = $ar["value"];	$WV_V = $ar["variation"];
		// WEEK CLICKS
		$ar = $this->dash_getNumber("nu_click", $enddate, 7, $ids, $comboreferrer);
		$WC = $ar["value"];	$WC_V = $ar["variation"];
		// WEEKLY MONEY
		$ar = $this->dash_getNumber("prezzo_finale", $enddate, 7, $ids, $comboreferrer);
		$WM = $ar["value"];	$WM_V = $ar["variation"];
		// MONTH VIEWS
		$ar = $this->dash_getNumber("nu_pageviews", $enddate, 30, $ids, $comboreferrer);
		$MV = $ar["value"];	$MV_V = $ar["variation"];
				
		// MONTH CLICKS
		$ar = $this->dash_getNumber("nu_click", $enddate, 30, $ids, $comboreferrer);
		$MC = $ar["value"];	$MC_V = $ar["variation"];
		// MONTH MONEY
		$ar = $this->dash_getNumber("prezzo_finale", $enddate, 30, $ids, $comboreferrer);
		$MM = $ar["value"];	$MM_V = $ar["variation"];
		return array("DV" => $DV, "DV_V"=>$DV_V, "DC" => $DC, "DC_V" => $DC_V, "DM" => $DM, "DM_V" => $DM_V, "WV" => $WV, "WV_V" => $WV_V, "WC" => $WC, "WC_V" => $WC_V, "WM" => $WM, "WM_V" => $WM_V, "MV" => $MV,	"MV_V" => $MV_V, "MC" => $MC, "MC_V" => $MC_V, "MM" => $MM, "MM_V" => $MM_V);
	}
	/**
	 * get stats for main server
	 * 
	 * @return string "number of banners|number of views"
	 */
    function getStatsForMainServer() {
        $ba = execute_scalar("SELECT count(1) FROM `".DB_PREFIX."7banner` WHERE fl_stato IN ('A','L')");
        $mv = execute_scalar("SELECT SUM(nu_pageviews) FROM `".DB_PREFIX."7banner_stats` WHERE id_day > '".date("Y-m-d",strtotime("-30 days"))."'");
        return $mv."|".$ba;
    }
	/**
	 * returns the difference between two dates with the specified format
	 * 
	 * @param string $date_1
	 * @param string $date_2
	 * @param string $differenceFormat
	 * 
	 * @return string
	 */
	function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
	{
		if(function_exists("date_diff")) {
			$datetime1 = date_create($date_1);
			$datetime2 = date_create($date_2);
			$interval = date_diff($datetime1, $datetime2);
			return $interval->format($differenceFormat);
		} else {
			return date_diff2($date_1, $date_2);
		}
	}
	// 
	// stats chart for a banner or for a campaign
	function getCharts($id,$combobanner,$combobannerreset,$startdateData="",$enddateData="") {
		global $session;
		$def = 30;
		
		if ($session->get("BANNER") && $this->bannerObject->check_cliente($id ? $id : $combobanner)) {
			$html = loadTemplateAndParse ("template/charts.html");
			$v = $id ? $id : $combobanner;
			// get the first day of the campaign
			if(!stristr($v,"-999|")) $sql = "select MIN(id_day) from ".DB_PREFIX."7banner_stats where cd_banner=".(integer)$v;
			else $sql = "select MIN(id_day) from ".DB_PREFIX."7banner_stats inner join ".DB_PREFIX."7banner on cd_banner=id_banner where cd_campagna=".((integer)(str_replace("-999|","",$combobanner)));
			$d = execute_scalar($sql);
			// get the last day of the campaign
			if(!stristr($v,"-999|")) $sql = "select MAX(id_day) from ".DB_PREFIX."7banner_stats where cd_banner=".(integer)$v;
			else $sql = "select MAX(id_day) from ".DB_PREFIX."7banner_stats inner join ".DB_PREFIX."7banner on cd_banner=id_banner where cd_campagna=".((integer)(str_replace("-999|","",$combobanner)));
			$d1 = execute_scalar($sql);
 			if($startdateData=="" || $combobannerreset=="combobannerreset") {
				$startdateData = DateAdd(-1,$d,"Y-m-d");
				$enddateData = DateAdd(+0,$d1,"Y-m-d");
				/* if the whole period is too long get the last 120 days */
				$period = $this->dateDifference($startdateData,$enddateData) + 1;
				if($period > 120) {
					$enddateData = DateAdd(+0,$d1,"Y-m-d");
					$startdateData = DateAdd(-120,$d1,"Y-m-d");
				}
			}
			if($startdateData > date("Y-m-d")) $startdateData= date("Y-m-d");
			$period = $this->dateDifference($startdateData,$enddateData) + 1;

			// its a campaign
			if(stristr($combobanner,"|")) {
				$temp = explode("|",$combobanner);
				if($temp[0]=="-999") {
					$dati['cd_campagna'] = $temp[1];
					$dati['de_nome'] = "All";
					$ids = concatenaId("select id_banner from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['nu_pageviews'] = execute_scalar("select SUM(nu_pageviews) from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['nu_clicks'] = execute_scalar("select SUM(nu_clicks) from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['nu_price'] = execute_scalar("select SUM(nu_price) from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['dt_giorno1'] = execute_scalar("select MIN(dt_giorno1) from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['dt_giorno2'] = execute_scalar("select MAX(dt_giorno2) from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."'");
					$dati['fl_stato'] = execute_scalar("select fl_stato from ".DB_PREFIX."7banner where cd_campagna='".$dati['cd_campagna']."' and fl_stato!='S'",'S');
				}
			} else {

				// it's a banner
				$dati = $this->bannerObject->getDati($id ? $id : $combobanner);
				$dati["de_posizione"] = execute_scalar("select de_posizione from ".DB_PREFIX."7banner_posizioni where id_posizione='".$dati['cd_posizione']."'");
				$html = str_replace("##LABEL##", $dati["de_posizione"], $html);
				$html = str_replace("##IDPOS##", $dati['cd_posizione'] , $html);
				$ids = $dati['id_banner'];
			}
			
			$cli = execute_row("select ".DB_PREFIX."7banner_clienti.de_nome,".DB_PREFIX."7banner_campagne.de_titolo from ".DB_PREFIX."7banner_campagne inner join ".DB_PREFIX."7banner_clienti on cd_cliente=id_cliente and id_campagna='".$dati['cd_campagna']."'");
			$giorni = $this->giorni($period,$startdateData);
			$html = str_replace("##GIORNI##", $giorni, $html);
			$sql = "select id_day,sum(nu_pageviews) as nu_pageviews,sum(nu_click) as nu_click from ".DB_PREFIX.$this->tbdb."_stats where cd_banner in(".($ids ? $ids : "-1").") and  id_day>='".$startdateData."' group by id_day order by id_day asc limit 0,".$period;
			$serie = $this->serie($sql ,$period,$startdateData);
			$lista = $this->getSerieDataTableHTML($startdateData, $enddateData, $giorni, $serie);
			$html = str_replace("##STATSLIST##",$lista,$html);
			$html = str_replace("##CSV##","<a href='csv.php?id=".$id."&combobanner=".$combobanner."&combobannerreset=".$combobannerreset."&enddate=".$enddateData."&startdate=".$startdateData."' class='csv btn' title='{Download csv data}'> CSV</a>",$html);
			$html = str_replace("##SERIE##", $serie, $html);

			$html = str_replace("##CTR##", $dati['nu_pageviews'] > 0 ? number_format($dati['nu_clicks'] / $dati['nu_pageviews'] * 100,4)."%" : "0.00",$html); 
			$html = str_replace("##V##", number_format((float)$dati["nu_pageviews"],0,'.',','), $html);
			$html = str_replace("##C##", number_format((float)$dati["nu_clicks"],0,'.',','), $html);
			if($dati["nu_clicks"]==0) {
				$html = str_replace("##CPC##", "n.a.", $html);
			} else {
				$html = str_replace("##CPC##", $dati["nu_clicks"] > 0 ? number_format((float)$dati['nu_price']/$dati["nu_clicks"],2,'.',',') : "n.a.", $html);
			}
			$html = str_replace("##CPM##", $dati["nu_pageviews"] > 0 ? number_format((float)$dati["nu_price"]*1000/$dati["nu_pageviews"],2,'.',',') : "n.a.", $html);
			$ts1 = strtotime($dati["dt_giorno1"]);
			$ts2 = strtotime($dati["dt_giorno2"]);
			$seconds_diff = $ts2 - $ts1;
			$days = floor( $seconds_diff / ( 24 * 3600 ) )  + 1;
			$html = str_replace("##P##", number_format((float)$dati['nu_price'],0,'.',','), $html);
			$html = str_replace("##CPD##", number_format((float)$dati["nu_price"]/$days,2,'.',','), $html);
		
			if($dati['fl_stato'] != "S") {
				$html = str_replace("##AVVISO##", "{Please wait the end of the campaign to see correct values for CPM, CPC and CPD.}", $html);
				$html = str_replace("##WARNING##", "<em>{In progress}</em>", $html);
			} else {
				$html = str_replace("##AVVISO##", "", $html);
				$html = str_replace("##WARNING##", "", $html);
			}
			
			$html = str_replace("##MONETA##", MONEY, $html);
			$html = str_replace("##combobanner##", $this->getHtmlcomboBanner($id ? $id : $combobanner, $dati['cd_campagna']), $html);
			$html = str_replace("##CLIENTE##", addslashes($cli['de_nome'] . " / ". $cli['de_titolo']), $html);
			$html = str_replace("##NOME##", addslashes($dati['de_nome']) , $html);
			
			$html = $this->getFormDateHTML($html, $startdateData, $enddateData, "","stats");

		} else {
			$html = "0";
		}
		return $html;
	}

	function getDashboardNew($startdate="",$enddate="", $comboreferrer="") {
		global $session,$conn;
		
		
		if ($session->get("DASHBOARD")) {
			// TO DO (ATTEMPT TO FILL BLANK REFERRERS)
			// (non necessario per la consegna)
			// ===============================================================================
			// to handle old data and new data from clients that don't send HTTP_REFERRER
			// run a query that searches for empty de_referrer in 7banner_stats
			// on those records try to fill de_referrer with the corresponding 7banner_sites.de_urlsito
			// if the position has cd_site=0 than leave the referr empty
            //
            // ===> popolare cd_posizione e de_referrer su 7banner_stats
            //
			// ===============================================================================
			// TO DO (necessario)
			// aggiungere bottone scarica csv (non per la consegna?)
			// days
            $filtered_by_date = true;
 			if($startdate=="") {
				// not filtered by date, get the last 30 days
				$enddate = date("Y-m-d");
				$startdate = DateAdd(-31,$enddate,"Y-m-d");
				$filtered_by_date = false;
			}
			if($startdate > $enddate) {
				// swaps the dates
				$temp = $startdate;
				$startdate = $enddate;
				$enddate = $temp;
			}
			$daysFromStartdate = $this->dateDifference($startdate,$enddate) + 1;
            
			// get ids and queries for Views and Clicks
			$ar = $this->dash_getIdsAndQueries($startdate, $enddate, $comboreferrer);
			$ids = $ar["ids"];
			$sql = $ar["sql"];
			// profile based css classes
			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if($session->get("idprofilo")==10) $bodyclass.='webmaster ';
			if($session->get("idprofilo")==5) $bodyclass.='advertiser ';
			// COUNTERS
			$counters = $this->dash_getCounters(date("Y-m-d"), $ids, $comboreferrer);
			$html = loadTemplateAndParse ("template/dashboardnew.html");
			$period = $this->dateDifference($startdate,$enddate) + 1;
			// get query for Money Serie
			$sql_money = $this->dash_getMoneyQuery($startdate,$enddate,$ids, $comboreferrer);
			
			// views and click Y series 
			$serie = $this->serie($sql ,$daysFromStartdate,$startdate, $sql_money);
			// get days for X ax
			$giorni = $this->giorni($period,$startdate);
			
			$html = str_replace("##bodyclass##", $bodyclass, $html);
			$html = str_replace("##GIORNI##", $giorni, $html);
			$html = str_replace("##SERIE##", $serie["serie"], $html);
			// $ecpm = $serie["tot_money"] / $serie["tot_views"] * 1000;
			$ecpm = $serie["tot_views"] > 0 ? $serie["tot_money"] / $serie["tot_views"] * 1000 : 0;   
			
			$html = str_replace("##ECPM##", number_format($ecpm, 2, '.', ''), $html);		// ecpm
			$html = str_replace("#TM#", $serie["tot_money"], $html);		
			$html = str_replace("#TV#", $serie["tot_views"], $html);		
			
            // stats to main server
            $html = str_replace("##STATS##", $this->getStatsForMainServer(), $html);
            // global stats
			$html = str_replace("##DV##", $counters["DV"], $html);		// daily views
			$html = str_replace("##DV_V##", $counters["DV_V"], $html);	// daily views variation
			$html = str_replace("##WV##", $counters["WV"], $html);		// weekly views
			$html = str_replace("##WV_V##", $counters["WV_V"], $html);	// weekly views variation
			$html = str_replace("##MV##", $counters["MV"], $html);		// monthly views
			$html = str_replace("##MV_V##", $counters["MV_V"], $html);	// monthly views variation
			$html = str_replace("##DC##", $counters["DC"], $html);		// daily clicks
			$html = str_replace("##DC_V##", $counters["DC_V"], $html);	// daily clicks variation
			$html = str_replace("##WC##", $counters["WC"], $html);		// weekly clicks
			$html = str_replace("##WC_V##", $counters["WC_V"], $html);	// weekly clicks variation
			$html = str_replace("##MC##", $counters["MC"], $html);		// monthly clicks
			$html = str_replace("##MC_V##", $counters["MC_V"], $html);	// monthly clicks variation
			$html = str_replace("##DM##", $counters["DM"], $html);		// daily money
			$html = str_replace("##DM_V##", $counters["DM_V"], $html);	// daily money variation
			$html = str_replace("##WM##", $counters["WM"], $html);		// weekly money
			$html = str_replace("##WM_V##", $counters["WM_V"], $html);	// weekly money variation
			$html = str_replace("##MM##", $counters["MM"], $html);		// monthly money
			$html = str_replace("##MM_V##", $counters["MM_V"], $html);	// monthly money variation
			$html = str_replace("##MONEY##", MONEY, $html);	// MONEY symbol
			// if($filtered_by_date) {
			// 	$html = str_replace("##switch##", 'style="display:none"', $html);
			// } else {
				$html = str_replace("##switch##", '', $html);
			// }
			$listPV = $this->dash_getFilteredTableStatsHTML($startdate, $enddate, $ids, $comboreferrer);
            $html = str_replace("##STATSLIST##",$listPV,$html);
            $listAds = $this->dash_getFilteredAdsHTML($startdate, $enddate, $ids, $comboreferrer,10);
            $html = str_replace("##ADS_LIST##",$listAds,$html);
            $positionsFormats = $this->dash_getFilteredPositionsHTML($startdate, $enddate, $ids, $comboreferrer,5);
            $html = str_replace("##POSITIONS_LIST##",$positionsFormats,$html);
            $sitesFormats = $this->dash_getFilteredSitesHTML($startdate, $enddate, $ids, $comboreferrer,10);
            $html = str_replace("##SITES_LIST##",$sitesFormats,$html);
            
			
			
			$html = str_replace("##CHECK_VERSION##",CHECK_VERSION,$html);
			if($session->get("idprofilo")==10) {
				$revenue = execute_scalar("select nu_import_webmaster from ".DB_PREFIX."7banner_payments where cd_webmaster='".$session->get("idutente")."' and fl_stato=0",0);
				$html = str_replace("##REVENUE##", number_format($revenue,2,".",",")." ".MONEY, $html);
			}
			$html = str_replace("##TITOLO##", " ", $html);
			$html = $this->getFormDateHTML($html, $startdate, $enddate, $comboreferrer, "dashboard", $ids);

		} else {
			$html = "0";
		}
		return $html;
	}

	// function that retrieves the form at the begininning of the dashboard
	// with dates and websites. Used also for banner/campaign stats
	function getFormDateHTML($html, $startdateData, $enddateData, $comboreferrer="",$op="dashboard", $ids='') {
		$objform = new form();
		$objform->method="GET";
		$startdate = new data("startdate",$startdateData,"aaaa-mm-gg", $objform->name);
		$startdate->obbligatorio=1;
		$startdate->label="'{Start date}'";
		$objform->addControllo($startdate);
		$enddate = new data("enddate",$enddateData,"aaaa-mm-gg",$objform->name);
		$enddate->obbligatorio=1;
		$enddate->label="'{End date}'";
		$objform->addControllo($enddate);
		//------------------------------------------------
		//combo referrer ('-1' means empty referrer, because 'empty' alreay means 'choose')
 		$sql = "SELECT (CASE WHEN de_referrer='' THEN '-1'
		ELSE de_referrer
		END ) as id_referrer,
		(CASE WHEN de_referrer='' THEN 'n.a.'
			ELSE de_referrer
			END ) as de_referrer
			FROM ".DB_PREFIX."7banner_stats 
			WHERE ".($ids=="" ? " 1 " : " cd_banner IN (".$ids.")")."
			ORDER BY de_referrer";
		// $select_referrer = $this->getSelectOptions( $sql, "id_referrer", "de_referrer", $comboreferrer, "combosite", 
		// false, "{Website}","{all}");
		// $select_referrer->attributes.=" class='filter'";
        $select_referrer = new optionlist("combosite",$comboreferrer,array());
        $select_referrer->loadSqlOptions( $sql, "id_referrer", "de_referrer", "{all}");
        $select_referrer->obbligatorio= 0;
        $select_referrer->label="'{Website}'";
        $select_referrer->attributes.=" class='filter'";
        $objform->addControllo($select_referrer);
		//------------------------------------------------				
		$op = new hidden("op", $op );
		$submit = new submit("submitta","view");
		$html = str_replace("##STARTFORM##", $objform->startform(), $html);
		$html = str_replace("##STARTDATEDATA##", $startdateData , $html);
		$html = str_replace("##enddate##", $enddate->gettag() , $html);
		$html = str_replace("##startdate##", $startdate->gettag() , $html);
		$html = str_replace("##combosites##", $select_referrer->gettag() , $html);
		$html = str_replace("##op##", $op->gettag(), $html);
		$html = str_replace("##SUBMIT##", "<a href='#' onclick='checkForm()' class='btn' title='{Filter data using dates}'>{Apply}</a>", $html);
		$html = str_replace("##gestore##", $this->gestore, $html);
		$html = str_replace("##ENDFORM##", $objform->endform(), $html);
		return $html;
	
	}

    /**
	 * extract the HTML select to filter banners, used in getCharts
	 * 
	 * @param integer $id_banner
	 * @param integer $id_campagna
	 * 
	 * @return string HTML
	 * */
	function getHtmlcomboBanner($id_banner, $id_campagna) {
		global $conn,$session;
		//------------------------------------------------
		// query, handles different profiles
		$miaposizione = $session->get("idprofilo")==15 ? "INNER JOIN ".DB_PREFIX."7banner_pos_miniadmin PP ON id_posizione=PP.cd_position AND PP.cd_user='".$session->get("idutente")."' " : "";
		$sql = "SELECT id_banner, de_nome, de_posizione, cd_posizione
			FROM ".DB_PREFIX.$this->tbdb." 
			INNER JOIN ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
			".$miaposizione." 
			".($id_campagna ? " WHERE cd_campagna = '$id_campagna' " : "" ) .
			"ORDER BY de_posizione";
		$rs = $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
		$arFiltri = array("-999|$id_campagna"=>"--{all}--");
		while($riga = $rs->fetch_array()) {
			if ($riga['cd_posizione']=="") $riga['c']=0;
			$arFiltri[$riga['id_banner']]= $riga['de_nome']." (".$riga['de_posizione'].")" ;
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$id_banner."x")?"selected":"").">{$v}</option>"; }
		return "<select onchange=\"$('#combobannerreset').val('combobannerreset');$('form[name=dati]').submit();\" name='combobanner' id='combobanner' class='filter'>{$out}</select><input type='hidden' name='combobannerreset' id='combobannerreset'>";
	}

	/**
     * days string for stats chart
     * 
     * @param integer $giornidastardate
     * @param string $startdate
     * 
     * @return string comma separated dates string
     * */
	function giorni($giornidastardate,$startdate) {
		$s = "";
		for($i=0;$i<$giornidastardate;$i++) $s.=($s ? "," : "") . "'".DateAdd($i,$startdate, phpFormat(DATEFORMAT))."'";
		return $s;
	}
	
    /**
     * get json data for series in stats chart. Fill empty dates withs 0 value.
     * If sql_money is passed add also data for money
     * 
     * @param string $sql
     * @param integer $giornidastardate
     * @param string $startdate
     * @param string $sql_money
     * 
     * @return mixed
     */
	function serie($sql,$giornidastardate,$startdate, $sql_money = "") {
		$serie = "";
		global $conn;
		$rs2=$conn->query($sql) or trigger_error($conn->error." SQL: ".$sql." ");
		$moneyAr = array();$TM=0;
		if($sql_money!=""){
			$rs3=$conn->query($sql_money) or trigger_error($conn->error." SQL: ".$sql_money." ");
			while($r3=$rs3->fetch_array()) {$moneyAr[$r3["id_day"]] = $r3["nu_money"]; $TM+=$r3["nu_money"];}
		}
		$v=array(); // view
		$c=array(); // click
		$m=array(); // money
		$TV=0;$TC=0;
		while($r2=$rs2->fetch_array()) {
			for($i=0;$i<$giornidastardate;$i++) {
				$data = DateAdd($i,$startdate,"Y-m-d");
				if($data==$r2['id_day']) {
					$v[$data]=$r2['nu_pageviews']; $TV+=$r2['nu_pageviews'];
					$c[$data]=$r2['nu_click']; $TC+=$r2['nu_click'];
				} else {
					if(!isset($v[$data])) $v[$data]="0";
					if(!isset($c[$data])) $c[$data]="0";
				}
				if($sql_money!="") {
					if(isset($moneyAr[$data])) {
							$m[$data]=numberf($moneyAr[$data],2);
					} 
						else  $m[$data]=0;
				}
			}
		}
		$serie.= ($serie ? ", " : "") . "{ name :'{Views}', type: 'spline', yAxis: 1, data: [";
		for($i=0;$i<$giornidastardate;$i++) { $data = DateAdd($i,$startdate,"Y-m-d"); if(isset($v[$data])) $serie.= ($i==0 ? "": ",") . $v[$data]; }
		$serie.="] }";
		$serie.= ($serie ? ", " : "") . "{ name :'{Clicks}', type: 'spline', yAxis: 1, data: [";
		for($i=0;$i<$giornidastardate;$i++) { $data = DateAdd($i,$startdate,"Y-m-d"); if(isset($c[$data])) $serie.= ($i==0 ? "": ",") . $c[$data]; }
		$serie.="] }";
		if($sql_money!="") {
			$serie.= ($serie ? ", " : "") . "{ name :'{Value}', type: 'column', data: [";
			for($i=0;$i<$giornidastardate;$i++) { $data = DateAdd($i,$startdate,"Y-m-d"); if(isset($m[$data])) $serie.= ($i==0 ? "": ",") . $m[$data]; }
			$serie.="] }";
	
		}
		if ($sql_money!="") {
			return array("serie"=>$serie, "tot_money"=>$TM, "tot_views"=>$TV, "tot_clicks"=>$TC);
		}
		return $serie;
	}

    //
    // retrieves the HTML ads table for the dashboard
	function dash_getFilteredAdsHTML($startdate, $enddate, $ids, $comboreferrer,$LIMIT=5) {
		global $conn, $session;
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		if($session->get("idprofilo")==5 || $session->get("idprofilo")==10) {
			/* ADVERTISER VIEW */
            $sql = "SELECT cd_banner, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c, CONCAT(".DB_PREFIX."7banner.fl_stato,'|^',CAST(dt_giorno1 AS CHAR CHARACTER SET utf8),'|^',CAST(id_banner AS CHAR CHARACTER SET utf8)) as stato,de_nome,de_posizione,de_codicescript,".DB_PREFIX."7banner.nu_width,".DB_PREFIX."7banner.nu_height FROM `".DB_PREFIX."7banner_stats` 
                INNER join ".DB_PREFIX."7banner ON cd_banner=id_banner
                INNER join ".DB_PREFIX."7banner_posizioni ON ".DB_PREFIX."7banner.cd_posizione=id_posizione
                WHERE cd_banner IN(".($ids ? $ids : "-1").") ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY cd_banner ORDER BY pv DESC LIMIT 0,".$LIMIT;
		}  else {
			/* ADMINISTRATOR VIEW */
            $sql = "SELECT cd_banner, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c, CONCAT(".DB_PREFIX."7banner.fl_stato,'|^',CAST(dt_giorno1 AS CHAR CHARACTER SET utf8),'|^',CAST(id_banner AS CHAR CHARACTER SET utf8)) as stato,de_nome,de_posizione,de_codicescript,".DB_PREFIX."7banner.nu_width,".DB_PREFIX."7banner.nu_height FROM `".DB_PREFIX."7banner_stats` 
                INNER join ".DB_PREFIX."7banner ON cd_banner=id_banner
                INNER join ".DB_PREFIX."7banner_posizioni ON ".DB_PREFIX."7banner.cd_posizione=id_posizione
                WHERE 1=1 ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY cd_banner ORDER BY pv DESC LIMIT 0,".$LIMIT;
 
		}
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql." ");
		$rows = array();
        $title = translateHtml($comboreferrer=="" ? "{Top banners}" : "{Top banners for %s}");
        $title = str_replace("%s",$comboreferrer=="-1" ? "{n.a.}" : $comboreferrer,$title);
        $header = "<h2>". $title ."</h2>";
		$header .= "<table class='griglia w100'>";
		$header .= "<tr><th></th><th>{Ad name}</th><th style='width:80px'>{Status}</th><th>{Views}</th><th>{Clicks}</th></tr>";
       
        $html = "";
        $p=0;
		while($row = $rs->fetch_array()) {
            $p++;
            $html .="<tr>";
            $html .= "<td>".$p.".</td>";
            $html .= "<td>".$row["de_nome"]."</td>";
            $html .= "<td style='font-weigth:bold;font-size:.8em'>".statofut($row["stato"],false)."</td>";
            $html .= "<td class='right'>".$row["pv"]."</td>";
            $html .= "<td class='right'>".$row["c"]."</td>";									
            $html .="</tr>";            
        }
		$footer = "</table>";
		return $header . $html . $footer;
			
	}

    //
    // retrieves the HTML sites table for the dashboard
	function dash_getFilteredSitesHTML($startdate, $enddate, $ids, $comboreferrer,$LIMIT=5) {
		global $conn, $session;
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		if($session->get("idprofilo")==5 || $session->get("idprofilo")==10) {
			/* ADVERTISER VIEW */
            /* WEBMASTER VIEW */
            $sql = "SELECT de_referrer, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c FROM `".DB_PREFIX."7banner_stats` 
                WHERE cd_banner IN(".($ids ? $ids : "-1").") ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY de_referrer ORDER BY pv DESC LIMIT 0,".$LIMIT;
		} else {
			/* ADMINISTRATOR VIEW */
            $sql = "SELECT de_referrer, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c FROM `".DB_PREFIX."7banner_stats` 
                WHERE 1 ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY de_referrer ORDER BY pv DESC LIMIT 0,".$LIMIT;
 
		}
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql." ");
		$rows = array();
        $title = translateHtml($comboreferrer=="" ? "{Top sites}" : "{Site info for %s}");
        $title = str_replace("%s",$comboreferrer=="-1" ? "{n.a.}" : $comboreferrer,$title);
        $header = "<h2>". $title ."</h2>";
		$header .= "<table class='griglia w100'>";
		$header .= "<tr><th></th><th>{Website}</th><th>{Views}</th><th>{Clicks}</th></tr>";
       
        $html = "";
        $p=0;
		while($row = $rs->fetch_array()) {
            $p++;
            $html .="<tr>";
            $html .= "<td>".$p.".</td>";
            $html .= "<td>".($row["de_referrer"]=='' ? "{n.a.}" : $row['de_referrer'])."</td>";
            // $html .= "<td>".$row["de_posizione"]."</td>";
            $html .= "<td class='right'>".$row["pv"]."</td>";
            $html .= "<td class='right'>".$row["c"]."</td>";									
            $html .="</tr>";            
        }
		$footer = "</table>";
		return $header . $html . $footer;
			
	}
    //
    // retrieves the HTML Positions table for the dashboard
	function dash_getFilteredPositionsHTML($startdate, $enddate, $ids, $comboreferrer, $LIMIT = 5) {
		global $conn, $session;
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		if($session->get("idprofilo")==5 || $session->get("idprofilo")==10) {
			/* ADVERTISER VIEW */
            /* WEBMASTER VIEW */
            $sql = "SELECT cd_posizione,de_posizione, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c FROM `".DB_PREFIX."7banner_stats` 
                left outer join ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
                WHERE cd_banner IN(".($ids ? $ids : "-1").") ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY cd_posizione ORDER BY pv DESC LIMIT 0,".$LIMIT;
		} else {
			/* ADMINISTRATOR VIEW */
            $sql = "SELECT cd_posizione,de_posizione, SUM(".DB_PREFIX."7banner_stats.nu_pageviews) AS pv, SUM(".DB_PREFIX."7banner_stats.nu_click)   
                AS c FROM `".DB_PREFIX."7banner_stats` 
                left outer join ".DB_PREFIX."7banner_posizioni ON cd_posizione=id_posizione
                WHERE 1 ".
                    "AND id_day>='".$startdate."' ".
                    "AND id_day<='".$enddate."' ".
                    ($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
                "GROUP BY cd_posizione ORDER BY pv DESC LIMIT 0,".$LIMIT;
    	}
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql." ");
		$rows = array();
        $title = translateHtml($comboreferrer=="" ? "{Top positions}" : "{Top positions for %s}");
        $title = str_replace("%s",$comboreferrer=="-1" ? "{n.a.}" : $comboreferrer,$title);
        $header = "<h2>". $title ."</h2>";
		$header .= "<table class='griglia w100'>";
		$header .= "<tr><th></th><th>{Position}</th><th>{Views}</th><th>{Clicks}</th></tr>";
       
        $html = "";
        $p=0;
		while($row = $rs->fetch_array()) {
            $p++;
            $html .="<tr>";
            $html .= "<td>".$p.".</td>";
            $html .= "<td>".($row["de_posizione"]=='' ? "{n.a.}" : $row['de_posizione'])."</td>";
            // $html .= "<td>".$row["de_posizione"]."</td>";
            $html .= "<td class='right'>".$row["pv"]."</td>";
            $html .= "<td class='right'>".$row["c"]."</td>";									
            $html .="</tr>";            
        }
		$footer = "</table>";
		return $header . $html . $footer;
	}
    //
    // retrieves the HTML stats table for the dashboard
	function dash_getFilteredTableStatsHTML($startdate, $enddate, $ids, $comboreferrer) {
		global $conn, $session;
		// $comboreferrer == "" ===> all records
		// $comboreferrer == "-1" ===> only empty referres
		$realComboReferr = $comboreferrer=="-1"?"":$comboreferrer;
		if($session->get("idprofilo")==5 || $session->get("idprofilo")==10) {
			/* ADVERTISER VIEW */
            /* WEBMASTER VIEW */
			$sql = "SELECT id_day,de_referrer,SUM(nu_pageviews) as nu_pageviews,SUM(nu_click) as nu_click FROM ".DB_PREFIX."7banner_stats WHERE cd_banner IN(".($ids ? $ids : "-1").") ".
				"AND id_day>='".$startdate."' ".
				"AND id_day<='".$enddate."' ".
				($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
			" GROUP BY id_day,de_referrer ORDER BY id_day ASC";
		}  else {
			/* ADMINISTRATOR VIEW */
			$sql = "SELECT id_day,de_referrer,SUM(nu_pageviews) as nu_pageviews,SUM(nu_click) as nu_click FROM ".DB_PREFIX."7banner_stats WHERE ".
			"id_day>='".$startdate."' ".
			"AND id_day<='".$enddate."' ".
			($comboreferrer!="" ? " AND de_referrer='".addslashes($realComboReferr)."'" : "").
			" GROUP BY id_day,de_referrer  ORDER BY id_day ASC";	
		}
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql." ");
		$rows = array();
		while($row = $rs->fetch_array()) $rows[] = $row;
		$header = "<h2>{Data for selected period and website}</h2>";
		$header .= "<table class='griglia w100'>";
		$header .= "<tr><th class='left'>{Day}</th><th class='left'>{Website}</th><th class='right'>{Views}</th><th class='right'>{Clicks}</th></tr>";
		$html = "";
		$q=0;
		$tc = 0; // total clicks
		$tv = 0; // total views
		
		$daysFromStartdate = $this->dateDifference($startdate,$enddate) + 1;
		for($k=$daysFromStartdate-1;$k>=0;$k--) {
			$date = DateAdd($k,$startdate, "Y-m-d");
			
			$found = false;
			for($i=0;$i<count($rows);$i++){
				if($date == $rows[$i]["id_day"]) {
					$found = true;
					$html .="<tr>";
					$html .= "<td>".$rows[$i]["id_day"]."</td>";
					$html .= "<td>".($rows[$i]["de_referrer"]=='' ? "{n.a.}" : $rows[$i]["de_referrer"])."</td>";
					$html .= "<td class='right'>".$rows[$i]["nu_pageviews"]."</td>";
					$html .= "<td class='right'>".$rows[$i]["nu_click"]."</td>";									
					$html .="</tr>";
					$tc+= $rows[$i]["nu_click"];
					$tv+= $rows[$i]["nu_pageviews"];
				}
			}
			if (!$found) {
				$html .= "<tr>";
				$html .= "<td>".$date."</td>";
				$html .= "<td>-</td>";
				$html .= "<td class='right'>0</td>";
				$html .= "<td class='right'>0</td>";
				$html .= "</tr>";				
			}
		}
	
		$footer =  "<tr><th></th><th class='right'>{Totals}</th><th class='right'>".$tv."</th><th class='right'>".$tc."</th></tr>";
		$footer .= "</table>";
		return $header . $html . $footer;
			
	}
	//
	// return the HTML table of the serie extracted by $sql
	// $startdateData = first date
	// $enddateData = last date
	// $giorni = csv list of dates
	// $serie = csv list of values
	function getSerieDataTableHTML($startdateData, $enddateData, $giorni, $serie) {
		preg_match_all("/\[([^\]]*)\]/",$serie,$obj);
		$lista = "";
		if(isset($obj[1]) && isset($obj[1][0]) && $obj[1][0]!="") {
			$pv = explode(",",$obj[1][0]); // pageviews
			$cl = explode(",",$obj[1][1]); // clicks
			$gg = explode(",",$giorni);    // days
			$header = "<h2>From: ".$startdateData." To: ".$enddateData."</h2>";
			$header .= "<table class='griglia w100'>";
			$q=0;
			$tc = 0; // total clicks
			$tv = 0; // total views
			foreach($pv as $v){
				if($q==0) {
					$header .= "<tr><th class='left'>{Day}</th><th class='right'>{Views}</th><th class='right'>{Clicks}</th></tr>";
				}
				$gg[$q] = str_replace("'","",$gg[$q]);
				$riga = "<tr>";
				$riga .= "<td class='left'>".$gg[$q]."</td>";
				$riga .= "<td class='right'>$v</td>";
				$riga .= "<td class='right'>".$cl[$q]."</td>";
				$riga .= "</tr>";
				$lista = $riga . $lista;
				$tc = $tc+$cl[$q];
				$tv = $tv+$v;
				$q++;
			}
			
			$foot =  "<tr><th class='right'>{Totals}</th><th class='right'>".$tv."</th><th class='right'>".$tc."</th></tr>";
			$foot .= "</table>";
			$lista = $header . $lista . $foot;
		}
		return $lista;
	}

}
