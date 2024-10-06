<?php
/*

	class to handle users

*/

class GestioneUtenti
{

	var $tbdb;	// table

	var $start;	// first record
	var $omode;	// asc|desc
	var $oby;	// ordered by field
	var $ps;	// pagesize: number of rows per page

	var $linkaggiungi;	//link to add
	var $linkmodifica;	//link to edit
	var $linkeliminamarcate;	//link to delete
	var $linkmodifica_label;
	var $personifica;
    var $personifica_label;    
	var $MAX_USER_LEVEL;
	
    var $scegliDaInsiemeLabelProfili;

	var $gestore;

	var $selectedLetter;


	function __construct ($tbdb="frw_utenti",$ps=40,$oby="name",$omode="asc",$start=0,$selectedLetter="") {
		global $session,$root,$conn;
		$this->MAX_USER_LEVEL=999999;	//definizione utente superadmin
		$this->gestore = $_SERVER["PHP_SELF"];
		$this->tbdb = $tbdb;
		
		// setVariabile used GET > POST > SESSION > default value
		$this->start = setVariabile("gridStart",$start,$this->tbdb);
		$this->omode= setVariabile("gridOrderMode",$omode,$this->tbdb);
		$this->oby= setVariabile("gridOrderBy",$oby,$this->tbdb);
		$this->ps = setVariabile("gridPageSize",$ps,$this->tbdb);
		$this->scegliDaInsiemeLabelProfili = array();

		$this->selectedLetter=setVariabile("gridSelectedLetter",$selectedLetter,$this->selectedLetter);

		$this->linkaggiungi = "$this->gestore?op=aggiungi";

		$this->linkmodifica = "$this->gestore?op=modifica&id=##id##";
		$this->linkmodifica_label = "modifica";

		$this->personifica = "$this->gestore?op=personifica&id=##id##";
		$this->personifica_label = "personifica";

		$this->linkeliminamarcate = "javascript:confermaDeleteCheck(document.datagrid);";

		if ($session->get("GESTIONEUTENTI_READ") == "") {
			/*
				find permission GESTIONEUTENTI_READ 
			*/

			$sql = "SELECT ".DB_PREFIX."frw_funzionalita.label, ".DB_PREFIX."frw_funzionalita.nome
					FROM ".DB_PREFIX."frw_funzionalita
					JOIN ".DB_PREFIX."frw_componenti ON ".DB_PREFIX."frw_funzionalita.idcomponente = ".DB_PREFIX."frw_componenti.id
					JOIN ".DB_PREFIX."frw_ute_fun ON idfunzionalita = ".DB_PREFIX."frw_funzionalita.id
					WHERE ".DB_PREFIX."frw_componenti.nome =  'GESTIONEUTENTI' AND ".DB_PREFIX."frw_ute_fun.idutente =  '".$session->get("idutente")."';";

			$rs=$conn->query($sql) or die($conn->error."sql='$sql'<br>");
			$session->register("GESTIONEUTENTI_READ","false");
			$session->register("GESTIONEUTENTI_WRITE","false");

			while($row = $rs->fetch_array()){
				if ($row['nome']=='READ') {
					$session->register("GESTIONEUTENTI_READ","true");
				}
				if ($row['nome']=='WRITE') {
					$session->register("GESTIONEUTENTI_WRITE","true");
				}
			}
			$rs->free();
		}


	}

	function elencoUtenti($combotipo="",$combotiporeset="",$keyword="", $params = array()) {
		global $session;

		if (($session->get("GESTIONEUTENTI_WRITE")=="true")) {

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

			$t=new grid(DB_PREFIX.$this->tbdb,$this->start, $this->ps, $this->oby, $this->omode,0,"GESTIONEUTENTI_elenco",$this->selectedLetter);
			$t->checkboxFormAction=$this->gestore;
			$t->checkboxFormName="datagrid";
			$t->checkboxForm=true;
			$t->functionhtml = "";
			$t->mostraRecordTotali = true;

			$t->campi = "name,username,de_label,de_email,fl_attivo";
			$t->titoli="{Name},{Username},{Profile},{Email address},{Status}";
			$t->query="SELECT DISTINCT CONCAT(cognome,' ',nome) AS name,".DB_PREFIX."frw_utenti.id,".DB_PREFIX."frw_utenti.username,".DB_PREFIX."frw_profili.de_label,fl_attivo,password,de_email 
				FROM ".DB_PREFIX."frw_utenti 
				JOIN ".DB_PREFIX."frw_profili on ".DB_PREFIX."frw_utenti.cd_profilo=".DB_PREFIX."frw_profili.id_profilo 
				LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata on cd_user=id 
				";
			
			if( isset($params['fields']) ) $t->campi = $params['fields'];
			if( isset($params['labels']) ) $t->titoli = $params['labels'];
			if( isset($params['query']) ) $t->query = $params['query'];


			// superadmin
			if($session->get("idprofilo")=="999999") {
				$t->campi.=",password";
				$t->titoli.=",{Password}";
			} 
			

			$t->parametriDaPssare = "";
			if($combotipo) {
				$t->parametriDaPssare.="&combotipo=".urlencode($combotipo);
			}
			if($keyword) $t->parametriDaPssare.="&keyword=".urlencode($keyword);

			// key
			$t->chiave="id";

			$where = " cd_profilo<='".$session->get("idprofilo")."' ";
			if($combotipo==="0" || $combotipo) {
				if($combotipo=="-999") {

				} else {

					if($where!="") { $where.= " and "; }
					$where.=" ".DB_PREFIX."frw_utenti.fl_attivo='".$combotipo."'";


				}
			}

			if($keyword) {
				if($where!="") { $where.= " and "; }
				$where.="  ((nome like '%{$keyword}%') OR (cognome like '%{$keyword}%'))";
			}
			if($where) {
				$t->query.=" where {$where}";
			}

			if ($session->get("GESTIONEUTENTI_WRITE")=="true") {

				if( in_array( $session->get("idprofilo"), array(20,999999) )) {
					// add personify command
					$t->addComando($this->personifica,$this->personifica_label,"{Login as this user}");
				}

			}

			$t->addCampi("fl_attivo","toggleStato");
            $t->addCampi("name","show_user_fullname");

			if (count($this->scegliDaInsiemeLabelProfili)>0) $t->addScegliDaInsieme("de_label",$this->scegliDaInsiemeLabelProfili);

			$t->addCampi("password","decrypta");
			// $t->debug = true;
			$html = $t->show();


		} else {
			$html = "0";
		}

		return $html;
	}




	/*
		show user detail form, both insert and update
	*/
	function getDettaglioNew($id="",$params = array()) {
		global $session,$root,$conn;

		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {

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
				$dati1 = getEmptyNomiCelleAr(DB_PREFIX.$this->tbdb) ;
                $dati2 = getEmptyNomiCelleAr(DB_PREFIX."frw_extrauserdata"); ;
                $dati = $dati1 + $dati2;
				$action = "aggiungiStep2";

			}

			$templateName = "template/dettaglio_new.html";
			if(isset($params['template'])) $templateName = $params['template'];
			$html = loadTemplateAndParse($templateName);

			// form construction
			$objform = new form("createUserForm", "checkThisHoneypot");


			$username = new testo("username",$dati["username"],20,20);
			$username->obbligatorio=1;
			$username->label="'{Username}'";
			$objform->addControllo($username);

			$cr = new cryptor();
			$password = new password("password","",20,20);
			$password->obbligatorio= $id>0 ? 0 : 1;		// password mandatory in creation
			$password->label="'{Password}'";
			$objform->addControllo($password);

			$nome = new testo("nome",$dati["nome"],100,50);
			$nome->obbligatorio=1;
			$nome->label="'{Name}'";
			$objform->addControllo($nome);

			$cognome = new testo("cognome",$dati["cognome"],100,50);
			$cognome->obbligatorio=1;
			$cognome->label="'{Surname}'";
			$objform->addControllo($cognome);

            // force possible null values of fields
			$de_email = new email("de_email",htmlspecialchars("" . $dati["de_email"]),200,30);
			$de_email->obbligatorio=0;
			$de_email->label="'{Email address}'";
			$objform->addControllo($de_email);

			//------------------------------------------------
			//combo profiles
			$sql = "select * from ".DB_PREFIX."frw_profili where id_profilo<='".$session->get("idprofilo")."' order by id_profilo asc";
			$cd_profilo = new optionlist("cd_profilo",($dati["cd_profilo"]),array());
			$cd_profilo->loadSqlOptions( $sql, "id_profilo", "de_label", "{choose}");
			$cd_profilo->obbligatorio= 1;
			$cd_profilo->label="'{Profile}'";
			$objform->addControllo($cd_profilo);


			$fl_attivo=new checkbox("fl_attivo",1,$dati["fl_attivo"]==1);
			$fl_attivo->obbligatorio=0;
			$fl_attivo->label="'{Ative profile}'";
			$objform->addControllo($fl_attivo);

			// extra fields for inheritance behaviour
			if(isset($params['fieldsObjects'])) {
				foreach($params['fieldsObjects'] as $objField)
					$objform->addControllo($objField);
			}

			$id_obj = new hidden("id",$dati["id"]);
			$strong_password = new hidden("strong_password",STRONG_PASSWORD);
			$op = new hidden("op",$action);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_obj->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);
			$html = str_replace("##nome##", $nome->gettag(), $html);
			$html = str_replace("##password##", $password->gettag(), $html);
			$html = str_replace("##strong_password##", $strong_password->gettag(), $html);
			$html = str_replace("##cognome##", $cognome->gettag(), $html);
			$html = str_replace("##de_email##", $de_email->gettag(), $html);
			$html = str_replace("##username##", $username->gettag(), $html);
			$html = str_replace("##cd_profilo##", $cd_profilo->gettag(), $html);
			$html = str_replace("##fl_attivo##", $fl_attivo->gettag(), $html);

			// replace extra fields for inheritance behaviour
			if(isset($params['fieldsObjects'])) {
				foreach($params['fieldsObjects'] as $objField)
					$html = str_replace("##" . $objField->name . "##", $objField->gettag(), $html);
			}


			// replace extra extra strings for inheritance behaviour
			if(isset($params['stringsObjects'])) {
				foreach($params['stringsObjects'] as $k=>$v)
					$html = str_replace("##" . $k . "##", $v, $html);
			}
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("#aster#", $id > 0 ? "" : "*" , $html);
			$html = str_replace("#asterinstruction#", $id > 0 ? "{Leave blank if you don't want to change it}" : "" , $html);
			$html = str_replace("##dt_datacreazione##", "" . $dati['dt_datacreazione'], $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);

		} else {
			$html = "0";
		}
		return $html;
	}

	/* get data from user tables */
	function getDati($id) {
		$sql = "SELECT * from ".DB_PREFIX.$this->tbdb." left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id where id='{$id}'";
		return execute_row($sql);
	}

	function updateAndInsert($arDati) {
		// in:
		// arDati--> array _POST from the form
		// result:
		//	"" --> ok
		//  "0" --> no permissions

		global $session,$conn;
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {

			$u=new user();
			$u->MAX_USER_LEVEL=$this->MAX_USER_LEVEL;

			if ($arDati["id"]!="") {
				$id = $arDati["id"];
				/*
					Modify
				*/
				$checkmail = false;
				if ($arDati["de_email"]=="") $checkmail = true;
					else if(!$u->existUserWithEmail($arDati["de_email"],$arDati["id"])) $checkmail = true;

				if($checkmail) {
					if (!$u->existUserWithUsername($arDati["username"],$arDati["id"])){
						if ($arDati["password"]!="") {
							$sql="UPDATE ".DB_PREFIX."frw_utenti set username='##username##',password='##password##',nome='##nome##',cognome='##cognome##',fl_attivo='##fl_attivo##',cd_profilo='##cd_profilo##' where id='##id##'"; 
						} else {
							$sql="UPDATE ".DB_PREFIX."frw_utenti set username='##username##',nome='##nome##',cognome='##cognome##',fl_attivo='##fl_attivo##',cd_profilo='##cd_profilo##' where id='##id##'"; 
						}
						$cr  = new cryptor();
						$sql= str_replace("##username##",$arDati["username"],$sql);
						$sql= str_replace("##password##",$cr->crypta($arDati["password"]),$sql);
						$sql= str_replace("##nome##",$arDati["nome"],$sql);
						if (!isset($arDati["fl_attivo"])) $arDati["fl_attivo"]="0";
						$sql= str_replace("##fl_attivo##",$arDati["fl_attivo"],$sql);
						$sql= str_replace("##cd_profilo##",$arDati["cd_profilo"],$sql);
						$sql= str_replace("##cognome##",$arDati["cognome"],$sql);
						$sql= str_replace("##id##",$arDati["id"],$sql);

						$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");


						$u->setProfilo($arDati["cd_profilo"]);
						$html= "ok|".$id;

					} else {
						$html="-1|{The username you've choose is already used.}";	// user used
					}
				} else {
					$html="-1|{The email you've choose is already used.}";	// email used
				}

				
			} else {
				/*
					Insert
				*/

				$checkmail = false;
				if ($arDati["de_email"]=="") $checkmail = true;
					else if(!$u->existUserWithEmail($arDati["de_email"])) $checkmail = true;

				if($checkmail) {
					if (!$u->existUserWithUsername($arDati["username"])){

						$sql="INSERT INTO ".DB_PREFIX."frw_utenti (username,password,nome,cognome,fl_attivo,cd_profilo) values ('##username##','##password##','##nome##','##cognome##','##fl_attivo##','##cd_profilo##')";

						$cr = new cryptor();
						$sql= str_replace("##username##",$arDati["username"],$sql);
						$sql= str_replace("##password##",$cr->crypta($arDati["password"]),$sql);
						$sql= str_replace("##nome##",$arDati["nome"],$sql);
						if (!isset($arDati["fl_attivo"])) $arDati["fl_attivo"]="0";
						$sql= str_replace("##fl_attivo##",$arDati["fl_attivo"],$sql);
						$sql= str_replace("##cd_profilo##",$arDati["cd_profilo"],$sql);
						$sql= str_replace("##cognome##",$arDati["cognome"],$sql);
						$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
						$u->id= $conn->insert_id;
						$u->setProfilo($arDati["cd_profilo"]);

						$id = $u->id;
						$html= "ok|". $id;
					
					} else {
						$html="-1|{The username you've choose is already used.}";	//username already used
					}
				} else {
					$html="-1|{The email you've choose is already used.}";	//email already used
				}
			}


			if(stristr($html,"ok|")) {
					// if ok update frwextrauserdata table with extra fields

					$checkExtraUserData = execute_row("select * from ".DB_PREFIX."frw_extrauserdata where cd_user='".$id."'");
					if(
						!isset($checkExtraUserData["dt_datacreazione"])
						|| $checkExtraUserData["dt_datacreazione"] == ""
						|| $checkExtraUserData["dt_datacreazione"] == ZERODATE
					) $checkExtraUserData["dt_datacreazione"]=date("Y-m-d");
					if(isset($checkExtraUserData['de_email'])) {
						$sql="UPDATE ".DB_PREFIX."frw_extrauserdata set de_email='##de_email##',dt_datacreazione='##dt_datacreazione##' where cd_user='".$id."'"; 
						$sql= str_replace("##de_email##",$arDati["de_email"],$sql);
						$sql= str_replace("##dt_datacreazione##",$checkExtraUserData["dt_datacreazione"],$sql);
					} else {
						$sql="INSERT into ".DB_PREFIX."frw_extrauserdata (de_email,cd_user,dt_datacreazione,de_lang) values('##de_email##','".$id."','##dt_datacreazione##','".getDefaultLanguage()."')";
						$sql= str_replace("##de_email##",$arDati["de_email"],$sql);
						$sql= str_replace("##dt_datacreazione##",$checkExtraUserData["dt_datacreazione"],$sql);
					}
					$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");

			}

		} else {
			$html="0";		//no permission
		}
		return $html;
	}






	/* keep this code for sign up process */
	function insertNewUser($arDati) {
		// in:
		// arDati--> array POST from form
		// result:
		//	"" --> ok
		//	"1" --> username already used
		//  "0" --> no permission
		global $session,$conn;

        $u=new user();
		
		if((integer)$session->get("id") == 0) {

			//
			// this part is 
			// from sign in form
			//
			$form = new form("createUserForm", "checkThisHoneypot");
			if(!$form->checkHoney()) {
				// die with no info for spam
				die("Not available.");
			}

			// check email
			if(!is_email($arDati['de_email'])) return "2";
			// unique email
			if($u->existUserWithEmail($arDati['de_email'])) return "1";
			// user exists
			if($u->existUserWithUsername($arDati['username'])) return "5";

			// mandatory fields
			if( 
				// !isset($arDati['clientname']) || 
				!isset($arDati['username']) || 
				!isset($arDati['password']) || 
				!isset($arDati['nome']) || 
				!isset($arDati['cognome']) || 
				$arDati['cognome'] == "" ||
				$arDati['username'] == "" ||
				$arDati['nome'] == "" ||
				$arDati['password'] == "" // ||
				// $arDati['clientname'] == ""
				) return "3";

			$cr = new cryptor();
			$sql="INSERT INTO ".DB_PREFIX."frw_utenti (nome,cognome,password,username,cd_profilo,fl_attivo) VALUES ('##nome##','##cognome##',
				'##password##','##username##','##PROFILO##',0)";
			
			$arDati["cd_profilo"] = isset($arDati["cd_profilo"]) ? (integer)$arDati["cd_profilo"] : 5;
			if($arDati["cd_profilo"] != 5 && $arDati["cd_profilo"] != 10) $arDati["cd_profilo"] = 5;
			$sql= str_replace("##nome##",$arDati["nome"],$sql);
			$sql= str_replace("##cognome##",$arDati["cognome"],$sql);
			$sql= str_replace("##username##",$arDati["username"],$sql);
			$sql= str_replace("##PROFILO##",$arDati["cd_profilo"],$sql);
			$sql= str_replace("##password##",$cr->crypta($arDati["password"]),$sql);
			$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
			$html= "";

			$id= $conn->insert_id;
			if($id) {
				$code = md5("confirm" . $id);
				$sql = "INSERT INTO `".DB_PREFIX."frw_extrauserdata` (`cd_user`, `de_email`, `dt_datacreazione`, `de_temp`, `de_lang`) VALUES
				(".$id.", '".$arDati["de_email"]."', '".date("Y-m-d")."', '".$code."', '".getDefaultLanguage()."');";
				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
				// send email to confirm email address
				$subject = translateHtml("{Confirm email address}");

				$link  = WEBURL."/src/componenti/gestioneutenti/signin.php?op=code&id=".$code;
				$message = translateHtml("{Hi,<br>to confirm your email address click here: <br><a href='%s'>%s</a> <br>Thank you.}");
				$message = str_replace( "%s", $link, $message); 
				mail_utf8( $arDati["de_email"], "[".SERVER_NAME."] ".$subject, $message);


				if(NOTIFY_NEW_USERS_TO_ADMIN=="ON") {
					// notify the administrator for new users
					$sql = "select de_email from ".DB_PREFIX."frw_utenti inner join ".DB_PREFIX."frw_extrauserdata on cd_user=id where fl_attivo=1 and cd_profilo >=20";
					$subject = translateHtml("{New user}");
					$message = translateHtml("<p>{Hi,<br>new account created for %s.<br>Bye.}</p>") ;
					$message = str_replace( "%s", $arDati["de_email"], $message); 
					$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql);
					$ar = array();
					while($riga = $rs->fetch_array()) {
						if (is_email($riga['de_email'])) {
								mail_utf8(
									$riga['de_email'],
									"[".SERVER_NAME."] ". $subject,
									$message);
						}
					}
				}

				$html = "";
			} else {
				$html = "4";
			}




		} else {
			$html="0";		//no permission
		}
		return $html;
	}

	function confirmSignIn($code) {
		global $conn;
		$code = preg_replace("/[^a-z0-9]/i","",$code);
		$user = execute_row("select * from ".DB_PREFIX."frw_utenti inner join ".DB_PREFIX."frw_extrauserdata on cd_user=id where de_temp='".$code."' and fl_attivo=0");
		if(isset($user['id'])) {
			$conn->query("UPDATE ".DB_PREFIX."frw_utenti set fl_attivo=1 where id='".$user['id']."'");
			$conn->query("UPDATE ".DB_PREFIX."frw_extrauserdata set de_temp='' where cd_user='".$user['id']."'");
			return "";
		} else {
			return "1";
		}

	}





















	function updateUser($arDati) {
		// in:
		// arDati--> array POST from form
		// result:
		//	"" --> ok
		//	"1" --> username used
		//  "0" --> no permission

		global $session,$conn;
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
			$u=new user($arDati["id"],$this->MAX_USER_LEVEL);

			if (!$u->existUserWithUsername($arDati["lausername"],$arDati["id"])){
				if ($arDati["lapassword"]!="") {
					$sql="UPDATE ".DB_PREFIX."frw_utenti set username='##username##',password='##password##',nome='##nome##',cognome='##cognome##',fl_attivo='##fl_attivo##',cd_profilo='##cd_profilo##' where id='##id##'"; 
				} else {
					$sql="UPDATE ".DB_PREFIX."frw_utenti set username='##username##',nome='##nome##',cognome='##cognome##',fl_attivo='##fl_attivo##',cd_profilo='##cd_profilo##' where id='##id##'"; 
				}
				$cr  = new cryptor();
				$sql= str_replace("##username##",$arDati["lausername"],$sql);
				$sql= str_replace("##password##",$cr->crypta($arDati["lapassword"]),$sql);
				$sql= str_replace("##nome##",$arDati["nome"],$sql);
				if (!isset($arDati["fl_attivo"])) $arDati["fl_attivo"]="0";
				$sql= str_replace("##fl_attivo##",$arDati["fl_attivo"],$sql);
				$sql= str_replace("##cd_profilo##",$arDati["cd_profilo"],$sql);
				$sql= str_replace("##cognome##",$arDati["cognome"],$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);

				$conn->query($sql) or die($conn->error."sql='$sql'<br>");
				$html="";

				$u->setProfilo($arDati["cd_profilo"]);

			} else {
				$html="1";	//user used
			}
		} else {
			$html="0";		//no permission
		}
		return $html;
	}

	function deleteUser($id) {
		// in:
		// id --> id user to delete
		// result:
		//	"" --> ok
		//	"2" --> you can't delete user profile greater
		//	"1" --> can't delete yourself
		//  "0" --> no permission

		global $session,$conn;
		
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {
			if ($session->get("idutente")!=$id) {
				$u=new user($id,$this->MAX_USER_LEVEL);
				$dati = $u->getUserData();

				if (
					($session->get("idprofilo")==$this->MAX_USER_LEVEL) || 
					($dati["cd_profilo"] < $session->get("idprofilo")) || 
					($dati["cd_profilo"] == $session->get("idprofilo") && 20 == $session->get("idprofilo"))
					) {

					$sql="DELETE FROM ".DB_PREFIX."frw_utenti where id='$id'";
					$conn->query($sql) or die($conn->error."sql='$sql'<br>");

					$sql="DELETE FROM ".DB_PREFIX."frw_ute_fun where idutente='$id'";
					$conn->query($sql) or die($conn->error."sql='$sql'<br>");

					$sql="DELETE FROM ".DB_PREFIX."frw_extrauserdata where cd_user='$id'";
                    $conn->query($sql) or die($conn->error."sql='$sql'<br>");

					$html="";
				} else {
					$html="2";		//greater
				}
			} else {
				$html = "1";	//yourself
			}
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
		//  "0" --> can't

		global $session;
		if ($session->get("GESTIONEUTENTI_WRITE")=="true") {

			$html="0";

			$p=$dati['gridcheck'];
			for ($i=0;$i<count($p);$i++) {
				$html = $this->deleteUser($p[$i]);
				if($html!="") return $html;

			}
			$html = "";
		} else {
			$html="0";		//no permission
		}
		return $html;
	}


	function getHtmlcombotipo($def="1") {
		global $conn;
		//------------------------------------------------
		//combo filter
		$sql = "select fl_attivo, count(*) as c from ".DB_PREFIX."frw_utenti group by fl_attivo";
		$rs = $conn->query($sql) or trigger_error($conn->error);
		$arFiltri = array("-999"=>"All");
		while($riga = $rs->fetch_array()) {
			$arFiltri[$riga['fl_attivo']]= ($riga['fl_attivo'] == 1 ? "ON" : "OFF")." (".$riga['c'].")";
		}
		//------------------------------------------------
		$out = "";
		foreach ($arFiltri as $k => $v) { $out.="<option value='{$k}' ".(($k."x"==$def."x")?"selected":"").">{$v}</option>"; }
		return "<label><select onchange='aggiornaGriglia()' name='combotipo' id='combotipo' class='filter'>{$out}</select><input type='hidden' name='combotiporeset' id='combotiporeset'></label>";

	}

	function toggleStato($iduser,$op) {
		global $session,$conn;
		if ($session->get("GESTIONEUTENTI_WRITE") && $session->get("idutente")!=$iduser) {
			if($op>1 || $op<0) $op = 0;
			$sql = "update ".DB_PREFIX."frw_utenti set fl_attivo='{$op}' where id='{$iduser}'";
			$conn->query($sql) or (trigger_error($conn->error."<br>$sql='{$sql}'"));
			return $op;
		}
		return -1;
	}

}

?>