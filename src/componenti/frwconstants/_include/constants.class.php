<?php
/*
	class to handle constants in database
*/
class Constants {
	var $tbdb;	//table
	var $start;	// start row
	var $omode;	// order mode asc|desc
	var $oby;	// table field order by
	var $ps;	// page size
	var $linkmodifica;	// link to edit
	var $linkmodifica_label;
	var $gestore;

	function __construct($tbdb="frw_vars",$ps=20,$oby="de_nome",$omode="desc",$start=0) {
		global $session,$root;

		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;

		// setVariabile used GET > POST > SESSION > default value
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);

		// link in table grid
		$this->linkmodifica = "$this->gestore?op=modifica&id=##id_var##";
		$this->linkmodifica_label = "modifica";

		checkAbilitazione("CONSTANTSSETTINGS","SETTA_SOLO_SE_ESISTE");

	}

	/*
		show constants in grid
	*/
	function elenco($combotiporeset="",$keyword="", $type="main") {
		global $session;

		$html = "";

		if ($session->get("CONSTANTSSETTINGS")) {

			if($combotiporeset=='reset') {
				// if changed with filter select
				// reset pagination
				$this->start = 0;
			}

			$t=new grid($this->tbdb.$type,$this->start, $this->ps, $this->oby, $this->omode);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=false;
			$t->functionhtml = "";
            $t->flagOrdinatori = false;
			$t->mostraRecordTotali = false;

			$t->parametriDaPssare = "";
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			// fields
			$t->campi="de_nome,de_value";

			// titles
			$t->titoli="{Setting name}, {Setting value}";

			// key
			$t->chiave="id_var";

			// SQL
			//$t->debug = true;
			if(WEBURL=="https://www.zepsec.com/ambdemo") {
				$t->query="SELECT id_var,REPLACE(de_nome, 'CONST_', '') as de_nome,
				( CASE 
					WHEN de_nome='CONST_PAYPAL_CLIENTID' THEN 'jsd8wkd929xOcXAyjwBJGvhsUjsXKjusikd4uD-oR83kdnjsiOPTH-IR1V5mUjJjsduwnzowd1bgtBonA'
					WHEN de_nome='CONST_SMTP_PASSWORD' THEN '782723xjsjkd83'
					WHEN de_nome='CONST_PAYPAL_SECRET' THEN 'EGSmBEjsGidk30cswksXmk-qbIUu72ldofjcnlkduwm9bkcJEisTNJ280wIBh1h6v4FasVMhw82kch2o'
					WHEN de_nome='CONST_GEOIP_TOKEN' THEN 'jduej3849cA7dNROoLjshfuir83724wA16xR1F91VqCnnI64oBb061jduencjeo2'
					WHEN de_nome='CONST_COINBASE_API_KEY' THEN 'sidiidis-8818-zj191-sasas-sosod929329ss'
				ELSE REPLACE(de_value,'\n','<br>') END ) as de_value
				from ".DB_PREFIX."frw_vars";
			} else {
				$t->query="SELECT id_var,REPLACE(de_nome, 'CONST_', '') as de_nome,REPLACE(de_value,'\n','<br>') as de_value from ".DB_PREFIX."frw_vars";
			}
			$where = " de_nome like 'CONST_%'";
			$fields ="";
			if($type=="main") {
				$where.=" AND de_nome IN ('CONST_DATEFORMAT','CONST_MONEY','CONST_MAXSIZE_UPLOAD','CONST_SERVER_NAME','CONST_FAVICON','CONST_CHECK_VERSION','CONST_NOTIFY_NEW_USERS_TO_ADMIN','CONST_STRONG_PASSWORD','CONST_OPENAI_API_KEY') ";
			}

			if($type=="email_settings") {
				$where.=" AND de_nome IN ('CONST_SMTP_USERNAME','CONST_SMTP_SERVER','CONST_SMTP_PORT','CONST_SMTP_PASSWORD','CONST_SMTP_ENCRYPTION','CONST_SMTP_AUTH','CONST_SERVER_EMAIL_ADDRESS') ";
			}

			if($type=="payments_settings") {
				$fields = "'CONST_PAYMENTS','CONST_MONEY_CODE','CONST_MIN_PRICE','CONST_PAYPAL_SERVER','CONST_PAYPAL_SECRET','CONST_PAYPAL_CLIENTID','CONST_COINBASE_API_KEY','CONST_MANUAL_PAYMENTS','CONST_MANUAL_PAYMENTS_INFO'";
				$where.=" AND de_nome IN ({$fields}) ";
			}

			if($type=="geoip_settings") {
				$where.=" AND de_nome IN ('CONST_GEOIP_LIMIT_COUNTRY','CONST_GEOIP_TOKEN','CONST_GEOIP_CSV') ";
			}

			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  (de_nome like '%{$keyword}%' or de_value like '%{$keyword}%')";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			if ($fields!="") $t->query.= " ORDER BY FIELD(de_nome,{$fields})";

			$t->addComando($this->linkmodifica,$this->linkmodifica_label,"{Edit}");
			// $t->debug = true;

			$texto = $t->show();

			if (trim($texto)=="") $texto="{No records found.}";

			$html .= $texto."<br/>";

		} else {
			$html = "0";
		}
		return $html;
	}


	/*
		detail to edit a single constant
	*/
	function getDettaglio($id="") {

		global $session,$root;

		if ($session->get("CONSTANTSSETTINGS")) {
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
				$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_vars") ;
				$action = "aggiungiStep2";
			}

			$html = loadTemplateAndParse("template/dettaglio.html");

			// form construction
			$objform = new form();

			// default behaviour */
			$de_value = new testo("de_value", $dati["de_value"],150,50 );
			$de_value->obbligatorio=1;
			$de_value->label="'{Setting value}'";
			
			if(($dati["de_nome"] == "CONST_PAYPAL_CLIENTID" ||
				$dati["de_nome"] == "CONST_PAYPAL_SECRET" ||
				$dati["de_nome"] == "CONST_SMTP_PASSWORD" ||
				$dati["de_nome"] == "CONST_GEOIP_TOKEN" ||
				$dati["de_nome"] == "CONST_COINBASE_API_KEY"
				) && ( WEBURL=="https://www.zepsec.com/ambdemo" ))  {
					return "0";
			}

			if(($dati["de_nome"] == "CONST_PAYPAL_CLIENTID" ||
				$dati["de_nome"] == "CONST_PAYPAL_SECRET" ||
				$dati["de_nome"] == "CONST_GEOIP_TOKEN" ||
				$dati["de_nome"] == "CONST_SMTP_SERVER" ||
				$dati["de_nome"] == "CONST_SMTP_USERNAME" ||
				$dati["de_nome"] == "CONST_SMTP_PORT" ||
				$dati["de_nome"] == "CONST_SMTP_PASSWORD" ||
				$dati["de_nome"] == "CONST_COINBASE_API_KEY"  
				))  {
					$de_value->obbligatorio=0;
			}
			if($dati["de_nome"] == "CONST_PAYPAL_CLIENTID"
				)  {
					$de_value->extraHtml="<p class='description'>{Leave empty if you don't want Paypal payments}</p>";
			}
			if($dati["de_nome"] == "CONST_COINBASE_API_KEY"
				)  {
					$de_value->extraHtml="<p class='description'>{Leave empty if you don't want Coinbase payments}</p>";
			}
				
			if($dati["de_nome"] == "CONST_DATEFORMAT") {
				$de_value = new optionlist("de_value", DATEFORMAT, array(
					"dd/mm/yyyy"=>"DD/MM/YYYY",
					"mm/dd/yyyy"=>"MM/DD/YYYY",
					"yyyy/mm/dd"=>"YYYY/MM/DD"				
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Date format}'";
			}

			if($dati["de_nome"] == "CONST_NUMBERFORMAT") {
				$de_value = new optionlist("de_value", NUMBERFORMAT, array("1000.00"=>"1000.00","1000,00"=>"1000,00") );
				$de_value->obbligatorio=1;
				$de_value->label="'{Number format}'";
			}

			if($dati["de_nome"] == "CONST_GEOIP_CSV") {
				$de_value = new optionlist("de_value", GEOIP_CSV, array(
					"DB1LITE"=>"DB1LITE: Country",
					"DB3LITE"=>"DB3LITE: Country, region, city"));
				$de_value->obbligatorio=1;
				$de_value->label="'{Geoip database file type}'";
			}

			if($dati["de_nome"] == "CONST_MONEY") {
				$de_value = new testo("de_value", $dati["de_value"],5,5 );
				$de_value->obbligatorio=1;
				$de_value->label="'{Currency}'";
				$de_value->extraHtml="<p class='description'>{For example: $}</p>";
			}

			if($dati["de_nome"] == "CONST_MONEY_CODE") {
				$de_value = new testo("de_value", $dati["de_value"],5,5 );
				$de_value->obbligatorio=1;
				$de_value->label="'{Currency money code}'";
				$de_value->extraHtml="<p class='description'>{For example: USD}</p>";
			}

			if($dati["de_nome"] == "CONST_PAYMENTS") {
				$de_value = new optionlist("de_value", PAYMENTS, array(
					"ON"=>"{ON}",
					"OFF"=>"{OFF}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Payments}'";
			}

			if($dati["de_nome"] == "CONST_STRONG_PASSWORD") {
				$de_value = new optionlist("de_value", STRONG_PASSWORD, array(
					"ON"=>"{ON}",
					"OFF"=>"{OFF}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Strong passwords}'";
				$de_value->extraHtml="<p class='description'>{Turn off if you don't want to force the users to use strong passwords}</p>";
			}

			if($dati["de_nome"] == "CONST_OPENAI_API_KEY") {
				$de_value = new testo("de_value", OPENAI_API_KEY,128,64 );
				$de_value->obbligatorio=1;
				$de_value->label="'{OpenAI API key}'";
				$de_value->extraHtml="<p class='description'>{Insert your OpenAI API key to active chat bot Timy, leave empty if you don't want to use it}</p>";
			}
			

			if($dati["de_nome"] == "CONST_MANUAL_PAYMENTS") {
				$de_value = new optionlist("de_value", MANUAL_PAYMENTS, array(
					"ON"=>"{ON}",
					"OFF"=>"{OFF}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Manual payments}'";
				$de_value->extraHtml="<p class='description'>{Turn off if you don't want Manual payments}</p>";
			}
			

			if($dati["de_nome"] == "CONST_MANUAL_PAYMENTS_INFO") {
				$de_value = new areatesto("de_value", $dati["de_value"],5,80 );
				$de_value->obbligatorio=0;
				$de_value->label="'{Manual payments}'";
				$de_value->extraHtml="<p class='description'>{Insert manual payment instructions for advertiser}</p>";
			}			

			if($dati["de_nome"] == "CONST_CHECK_VERSION") {
				$de_value = new optionlist("de_value", CHECK_VERSION, array(
					"ON"=>"{ON}",
					"OFF"=>"{OFF}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Check version}'";
				$de_value->extraHtml="<p class='description'>{Check for new version on development server}</p>";
			}

			if($dati["de_nome"] == "CONST_PAYPAL_SERVER") {
				$de_value = new optionlist("de_value", PAYMENTS, array(
					"https://api.sandbox.paypal.com"=>"api.sandbox.paypal.com {Sandbox}",
					"https://api-m.paypal.com"=>"api-m.paypal.com {Live}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Payments}'";
			}

			if($dati["de_nome"] == "CONST_SMTP_AUTH") {
				$de_value = new optionlist("de_value", SMTP_AUTH, array(
					"1"=>"1 - {Yes}",
					"0"=>"0 - {No}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Auth}'";
			}

			if($dati["de_nome"] == "CONST_SMTP_ENCRYPTION") {
				$de_value = new optionlist("de_value", SMTP_ENCRYPTION, array(
					"SSL"=>"SSL",
					"TLS"=>"TLS"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Encryption}'";
			}

			if($dati["de_nome"] == "CONST_GEOIP_LIMIT_COUNTRY") {
				$de_value = new testo("de_value", $dati["de_value"],2,2 );
				$de_value->obbligatorio=0;
				$de_value->label="'{Setting value}'";
				$de_value->extraHtml="<p class='description'>{Insert the two letters country code}</p>";
			}

			if($dati["de_nome"] == "CONST_MAXSIZE_UPLOAD") {
				$de_value = new numerointero("de_value", $dati["de_value"],5,5 );
				$de_value->obbligatorio=1;
				$de_value->label="'{Maximum upload file size}'";
				$de_value->extraHtml=" Kb<p class='description'>".$de_value->label."</p>";
			}

			if($dati["de_nome"] == "CONST_NOTIFY_NEW_USERS_TO_ADMIN") {
				$de_value = new optionlist("de_value", NOTIFY_NEW_USERS_TO_ADMIN, array(
					"ON"=>"{ON}",
					"OFF"=>"{OFF}"
				) );
				$de_value->obbligatorio=1;
				$de_value->label="'{Notify new user registrations to admin}'";
				$de_value->extraHtml="<p class='description'>".$de_value->label."</p>";
			}

			$objform->addControllo($de_value);
			$de_nome = new hidden("de_nome",$dati["de_nome"]);

			$id_var = new hidden("id",$dati["id_var"]);
			$op = new hidden("op",$action);

			// $submit = new submit("invia","salva");

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_var->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##const##", str_replace("CONST_","",$dati["de_nome"]), $html);
			$html = str_replace("##de_nome##", $de_nome->gettag(), $html);
			$html = str_replace("##de_value##", $de_value->gettag() , $html);
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}



	function getDati($id) {
		return execute_row( "SELECT * from ".DB_PREFIX."frw_vars where id_var='{$id}' AND de_nome like 'CONST_%'" );
	}

	function updateAndInsert($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// result:
		//	"" --> ok
		//  "0" --> no permissions

		global $session,$conn;
		if ($session->get("CONSTANTSSETTINGS")) {
			// $session->register("CONST_LOGO","");

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/

				$sql="UPDATE ".DB_PREFIX."frw_vars set
					de_nome='##de_nome##',
					de_value='##de_value##'
					where id_var='##id_var##' AND de_nome like 'CONST_%'";
				$sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
				$sql= str_replace("##de_value##",$arDati["de_value"],$sql);
				$sql= str_replace("##id_var##",$arDati["id"],$sql);
				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$html= "";

			} else {
				/*
					Insert
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
			$html="0";	
		}
		return $html;
	}



	// update geo ip database
	function insertsql($id,$filename) {
		global $conn, $logger;
		//$logger->addlog( "insertsql( {$id}, {$filename}) " );

		$BLOCCO = 50000;
		$row = 0;
		$done = false;
		if (stristr($filename,"IPV6")) {
			$table = "ip2location_db3_ipv6";
		} else {
			$table = "ip2location_db3";
		}
		$newlines =0;
		if($id==0) $conn->query("truncate table `".$table."`");
		if (($handle = fopen($filename, "r")) !== FALSE) {
			//$logger->addlog( "leggo righe da {$filename} per metterle su {$table} ||| row = {$row}" );
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE    &&    $row < $BLOCCO + $id ) {
				set_time_limit(60);
				if($row >= $id && $row <$id + $BLOCCO) {

					$import = true;
					if( GEOIP_LIMIT_COUNTRY <>"" && isset($data[4]) ) {
						if( $data[2]==GEOIP_LIMIT_COUNTRY ) {
							$import = true;
						} else {
							$import = false;
						}
					}

					if($import) {
						$sql = "insert ignore into ".$table." (ip_from,ip_to,country_code,country_name,region_name,city_name) values (
							'".addslashes($data[0])."',
							'".addslashes($data[1])."',
							'".addslashes($data[2])."',
							'".addslashes($data[3])."',
							'".(isset($data[4]) ? addslashes($data[4]) : "")."',
							'".(isset($data[5]) ? addslashes($data[5]) : "")."'
							)";
						$conn->query($sql) or die($conn->error . " SQL:" .$sql);
					}
					$newlines++;
				}
				$row++;
			}
			$row--;
			//echo "rowfine = " . $row."; ";

			fclose($handle);
			if ($newlines == 0 && $row > 0) {
				//$logger->addlog( "fine");
				return "fine";
			}
			if($row > 0) {
				//$logger->addlog( "row > 0, row è {$row}");
				return $row + 1;
			}
		}
		//$logger->addlog( "Non doveva finire qui");
		return "ERROR";
	}


	function aggiornadb($id) {
		global $session,$conn,$root,$logger;
		//$logger->addlog("aggiornadb( {$id} )");
		if ($session->get("CONSTANTSSETTINGS")) {
			$recover = false;
			$STEP = getVarSetting("GEO_IP_STEP");
			if ($STEP!="" && $id=="0|ipv4") {
				// recover update interrupted
				$id = $STEP;
				$recover = true;
			}

			if(GEOIP_TOKEN=="") return "KO|Missing IP2Location token";

			$ar = explode("|",$id);
			if(!isset($ar[1])) die("ERROR, missing ar 1");
			$iptype = $ar[1];
			$id = $ar[0];

			if(GEOIP_CSV == "DB1LITE") {
				if($iptype == "ipv4") $filename = "IP2LOCATION-LITE-DB1.CSV";
					else $filename = "IP2LOCATION-LITE-DB1.IPV6.CSV";
			} else {
				if($iptype == "ipv4") $filename = "IP2LOCATION-LITE-DB3.CSV";
					else $filename = "IP2LOCATION-LITE-DB3.IPV6.CSV";
			}

			$filename = $root."data/geoip/".$filename;
			//$logger->addlog("il file è {$filename} ( type: {$iptype} )");
			if($id==0 && $iptype=="ipv4") {
				if( !file_exists($filename) ) {
					$OKv4= $this->downloadZIPandExtract ( 'https://www.ip2location.com/download/?token='.GEOIP_TOKEN.'&file='.GEOIP_CSV );
				} else {
					$OKv4= "ok";
				}
				if($OKv4!="ok") return "KO|Download failed v4: ".$OKv4;
			}

			if($id==0 && $iptype=="ipv6") {
				if( !file_exists($filename) ) {
					$OKv6= $this->downloadZIPandExtract ( 'https://www.ip2location.com/download/?token='.GEOIP_TOKEN.'&file='.GEOIP_CSV."IPV6" );
				} else {
					$OKv6= "ok";
				}
				if($OKv6!="ok") return "KO|Download failed v6: ".$OKv6;
			}

			if (file_exists($filename)) {
				$newid = $this->insertsql($id,$filename);
				//$logger->addlog("risultato insertsql = {$newid}");
				if($newid=="fine" && $iptype=="ipv4") {
					$conn->query("delete from ".DB_PREFIX."frw_vars where de_nome='GEO_IP_UPDATE'");
					$conn->query("insert into ".DB_PREFIX."frw_vars (de_nome,de_value) values ('GEO_IP_UPDATE','".date("Y-m-d H:i:s")."')");
					unlink($filename);
					$conn->query("UPDATE ".DB_PREFIX."frw_vars set de_value='' where de_nome ='GEO_IP_STEP'");
				}

				if($newid=="fine" && $iptype=="ipv6") {
					$conn->query("delete from ".DB_PREFIX."frw_vars where de_nome='GEO_IP_UPDATE'");
					$conn->query("insert into ".DB_PREFIX."frw_vars (de_nome,de_value) values ('GEO_IP_UPDATE','".date("Y-m-d H:i:s")."')");
					unlink($filename);
					$conn->query("UPDATE ".DB_PREFIX."frw_vars set de_value='' where de_nome ='GEO_IP_STEP'");
				}
				if($newid=="ERROR") return "KO|ERROR insersql ".$newid;
					elseif($newid!="fine") $conn->query("UPDATE ".DB_PREFIX."frw_vars set de_value='".$newid."|".$iptype."' where de_nome ='GEO_IP_STEP'");
				return $newid."|".$iptype;
			} else {
				if($recover)
					$conn->query("UPDATE ".DB_PREFIX."frw_vars set de_value='' where de_nome ='GEO_IP_STEP'");
				return "KO|File not found, retry to restart (".$filename.")";
			}
		}
	}

	function downloadZIPandExtract($file) {
		global $root,$logger;
		$dir = $root."data/geoip/";
		$newfile = $dir."temp.zip";
		$desttemp = $dir."temp";
		//echo ("downloadZIPandExtract( {$file} )\n");

		if(!file_exists($newfile)) {
			// download zip file
			//echo("non esiste file locale scarico zip in {$newfile}\n");
			$res = copy($file, $newfile);
			//die("KO|res=" . ($res?"1":"0"));
		} else {
			//echo("il file temp.zip c'è già\n");
			$res = true;		
			//die("KO|gia");
		}

		//die("KO|pppp");

		if ($res) {
			// zip exists
			//echo("c è cartella {$desttemp} ?\n");
			
			if(!file_exists($desttemp)) {
				// unzip it
				//echo ("no la creo.\n");
				mkdir($desttemp,0755);
			}

			$zip = new Zip();
			//echo("scompatto {$newfile} in {$desttemp}\n");
			$zip->unzip_file( $newfile , $desttemp);

			// move CSV file
			$files = scandir($desttemp);
			//echo("cerco file in desttemp = {$desttemp}\n");
			$found = 0;
			foreach ($files as $key => $value)
			{
			  if (!in_array($value,array(".","..")))
			  {
				 if (!is_dir($desttemp. "/".$value) && preg_match("/\.csv$/i",$value))
				 {
					//echo ("sposto con rename ( {$desttemp}/{$value} , {$dir}{$value}\n");
					rename ( $desttemp. "/".$value , $dir.$value ); // move file csv
					$found++;
				 } else 
				  {
					if (!is_dir($desttemp. "/".$value)) unlink ($desttemp."/". $value); // delete files
				  }
			 }
			}
			//die;

			unlink($newfile); // del temp.zip
			rmdir($desttemp); // remove dir;

			if($found) {
				return "ok";
			} else {
				return "No csv file in zip, or low memory size limit.";
			}

		} else {
			return "ko";
		}
	}
}
?>