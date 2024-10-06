<?php
/*
	class to handle my personal data
*/

class Mioprofilo {

	var $gestore;

	function __construct () {
		global $session,$root;
		$this->gestore = $_SERVER["PHP_SELF"];

		checkAbilitazione("MIOPROFILO");

	}


	/*
		show myself form detail
	*/
	function getDettaglio($params = array()) {

		global $session,$root;

		if ($session->get("MIOPROFILO")) {


			/*
				modifiy
			*/
			$dati = $this->getDati( $session->get("idutente") );
			if(empty($dati)) return "0";

			$extra = $this->getDatiExtra( $session->get("idutente") );

			$action = "modificaStep2";

			$bodyclass="";
			if($session->get("idprofilo")>=20) $bodyclass .='admin ';
			if(hasModule("BANNER")) {
				if($session->get("idprofilo")==5) $bodyclass .='advertiser';
				if($session->get("idprofilo")==10) $bodyclass .='webmaster';
			}

			
			$templateName = "template/dettaglio.html";
			if(isset($params['template'])) $templateName = $params['template'];
			$html = loadTemplateAndParse($templateName);


			$objform = new form();

			$nome = new testo("nome",($dati["nome"]),100,30);
			$nome->obbligatorio=1;
			$nome->label="'{Name}'";
			$objform->addControllo($nome);

			$cognome = new testo("cognome",($dati["cognome"]),100,30);
			$cognome->obbligatorio=1;
			$cognome->label="'{Surname}'";
			$objform->addControllo($cognome);

			/*
				look for available languages in folder
			*/
			$opz = array();
			if ($dh = opendir($root."data/lang")) {
				while (($file = readdir($dh)) !== false) {
					if(preg_match("/\.lang\.txt$/", $file)) {
						$o = substr($file, 0,strpos($file,".lang"));
						if(!strstr($o,'.')) 
							$opz[$o] = strtoupper($o);
						else {
							$o = explode('.',$o)[1];
							$opz[$o] = strtoupper($o);
						}
					}
				}
				closedir($dh);
			}
			$de_lang = new optionlist("de_lang", $extra["de_lang"], $opz);
			$de_lang->obbligatorio=0;
			$de_lang->label="'{Language}'";
			$objform->addControllo($de_lang);

			$opzComp = array();
			global $login;
			$opzComp = $login->getMenu($session->get("idutente"), "DATA");
			$opzDefault = array(""=>"---{choose}---"); $module = "";

			foreach($opzComp as $component) {
				if($component["type"] == "module") {
					$module = $component["label"];
				} else 
					$opzDefault[$component["id"]] = $module . " > " . $component["label"];
			}
			
			$cd_default_component = new optionlist("cd_default_component", $extra["cd_default_component"], $opzDefault);
			$cd_default_component->obbligatorio=0;
			$de_lang->label="'{Language}'";
			$objform->addControllo($de_lang);

			$cr = new cryptor();
			$password = new password("password",($cr->decrypta($dati["password"])),20,20);
			$password->obbligatorio=1;
			$password->label="'{Password}'";
			$objform->addControllo($password);

			$de_email = new testo("de_email",htmlspecialchars(isset($extra["de_email"]) ? $extra["de_email"] :"" ),100,30);
			$de_email->obbligatorio=1;
			$de_email->label="'{Email address}'";
			$objform->addControllo($de_email);
		
			// extra fields for inheritance behaviour
			if(isset($params['fieldsObjects'])) {
				foreach($params['fieldsObjects'] as $objField)
					$objform->addControllo($objField);
			}

			$id_field = new hidden("id",$dati["id"]);
			$strong_password = new hidden("strong_password",STRONG_PASSWORD);
			$op = new hidden("op",$action);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id_field->gettag(), $html);
			$html = str_replace("##strong_password##", $strong_password->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);

			$html = str_replace("##username##", $dati['username'], $html);
			$html = str_replace("##password##", $password->gettag(), $html);
			$html = str_replace("##nome##", $nome->gettag(), $html);
			$html = str_replace("##cognome##", $cognome->gettag(), $html);
			$html = str_replace("##de_email##", $de_email->gettag(), $html);

			$html = str_replace("##de_lang##", $de_lang->gettag(), $html);
			$html = str_replace("##cd_default_component##", $cd_default_component->gettag(), $html);
			
			$html = str_replace("##bodyclass##", $bodyclass,$html);


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
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);


		} else {
			$html = "0";
		}

		return $html;
	}


	/*
		show sign in form
	*/
	function getDettaglioSignIn($params=array()) {
		global $session,$root;
			/*
				modify
			*/
			$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_utenti") ;
		
			$extra = getEmptyNomiCelleAr(DB_PREFIX."frw_extrauserdata") ;

			$action = "modificaStep2";

			$templateName = "../gestioneutenti/template/signin.html";
			if(isset($params['template'])) $templateName = $params['template'];
			$html = loadTemplateAndParse($templateName);

			$objform = new form("createUserForm", "checkThisHoneypot");

			$username = new testo("username",($dati["username"]),100,50);
			$username->obbligatorio=1;
			$username->label="'{Username}'";
			$objform->addControllo($username);


			$nome = new testo("nome",($dati["nome"]),100,50);
			$nome->obbligatorio=1;
			$nome->label="'{Name}'";
			$objform->addControllo($nome);

			$cognome = new testo("cognome",($dati["cognome"]),100,50);
			$cognome->obbligatorio=1;
			$cognome->label="'{Surname}'";
			$objform->addControllo($cognome);


			$cr = new cryptor();
			$password = new password("password","",20,20);
			$password->obbligatorio=1;
			$password->label="'{Password}'";
			$objform->addControllo($password);

			$de_email = new email("de_email",htmlspecialchars(isset($extra["de_email"]) ? $extra["de_email"] :"" ),200,50);
			$de_email->obbligatorio=1;
			$de_email->label="'{Email address}'";
			$objform->addControllo($de_email);

			// extra fields for inheritance behaviour
			if(isset($params['fieldsObjects'])) {
				foreach($params['fieldsObjects'] as $objField)
					$objform->addControllo($objField);
			}

			$id = new hidden("id",$dati["id"]);
			$op = new hidden("op",$action);
			$strong_password = new hidden("strong_password",STRONG_PASSWORD);

			$html = str_replace("##STARTFORM##", $objform->startform(), $html);
			$html = str_replace("##id##", $id->gettag(), $html);
			$html = str_replace("##op##", $op->gettag(), $html);

			// replace extra fields for inheritance behaviour
			if(isset($params['fieldsObjects'])) {
				foreach($params['fieldsObjects'] as $objField)
					$html = str_replace("##" . $objField->name . "##", $objField->gettag(), $html);
			}

			$html = str_replace("##username##", $username->gettag(), $html);
			$html = str_replace("##password##", $password->gettag(), $html);
			$html = str_replace("##nome##", $nome->gettag(), $html);
			$html = str_replace("##strong_password##", $strong_password->gettag(), $html);
			$html = str_replace("##cognome##", $cognome->gettag(), $html);
			$html = str_replace("##de_email##", $de_email->gettag(), $html);
			
			$html = str_replace("##gestore##", $this->gestore, $html);
			$html = str_replace("##ENDFORM##", $objform->endform(), $html);


		return $html;
	}



	function getDati($id) {
		return execute_row("SELECT * from ".DB_PREFIX."frw_utenti where id='{$id}'");
	}
	function getDatiExtra($id) {
		$sql = "SELECT * from ".DB_PREFIX."frw_extrauserdata where cd_user='{$id}'";
		$dati = execute_row($sql);
		if(!isset($dati['cd_user'])) {
			$dati = getEmptyNomiCelleAr(DB_PREFIX."frw_extrauserdata") ;
			$dati['cd_user'] = 0;
		}
		return $dati;
	}

	function update($arDati,$files) {
		// in:
		// arDati--> array _POST from the form
		// files --> array _FILES
		// result:
		//	"" --> ok
		//  "0" --> no permission
		//  "2" --> mail not valid
		//  "1" --> mail exists

		global $session,$conn;
		$result = "0";
		if ($session->get("MIOPROFILO")) {


			if ($arDati["id"]!="") {
				$id = $session->get("idutente");

				if(!is_email($arDati['de_email'])) return "2";

				$u=new user();
				if($u->existUserWithEmail($arDati['de_email'], $id )) return "1";

				/*
					Modify
				*/
				$cr = new cryptor();

				$sql="UPDATE ".DB_PREFIX."frw_utenti set nome='##nome##',cognome='##cognome##',
					password='##password##'
					where id='##id##'";
				$sql= str_replace("##nome##",$arDati["nome"],$sql);
				$sql= str_replace("##cognome##",$arDati["cognome"],$sql);
				$sql= str_replace("##password##",$cr->crypta($arDati["password"]),$sql);
				$sql= str_replace("##id##",$arDati["id"],$sql);


				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
				$result= ""; // ok

				$dt_datacreazione = execute_scalar("select dt_datacreazione from ".DB_PREFIX."frw_extrauserdata where cd_user={$arDati['id']}");
				if($dt_datacreazione=="") $dt_datacreazione = TOymd();

                $q = execute_scalar("select count(1) from ".DB_PREFIX."frw_extrauserdata where cd_user={$arDati['id']}",0);
                if($q==0) {
                    $sql="INSERT INTO ".DB_PREFIX."frw_extrauserdata (cd_user,dt_datacreazione,de_lang,cd_default_component) values(##id_user##,'##dt_datacreazione##','##de_lang##','##cd_default_component##')";
                    $sql= str_replace("##dt_datacreazione##",$dt_datacreazione,$sql);
                    $sql= str_replace("##id_user##",$arDati["id"],$sql);
                    $sql= str_replace("##de_lang##",$arDati["de_lang"],$sql);
					$sql= str_replace("##cd_default_component##",(integer)$arDati["cd_default_component"],$sql);
                    $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");        
                } else {

					$sql="UPDATE ".DB_PREFIX."frw_extrauserdata SET de_email='##de_email##',
						dt_datacreazione='##dt_datacreazione##',cd_default_component='##cd_default_component##',
						de_lang='##de_lang##' WHERE cd_user='##id_user##'";
					$sql= str_replace("##de_email##",$arDati["de_email"],$sql);
					$sql= str_replace("##dt_datacreazione##",$dt_datacreazione,$sql);
					$sql= str_replace("##id_user##",$arDati["id"],$sql);
					$sql= str_replace("##de_lang##",$arDati["de_lang"],$sql);
					$sql= str_replace("##cd_default_component##",(integer)$arDati["cd_default_component"],$sql);
				}
				
				$session->register("language",$arDati["de_lang"]);

				$conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
				

				/*
				if($session->get("idprofilo")==5) {

                    if(hasModule("BANNER")) {
                        if ($arDati["id_cliente"]!="") {
                            //Modify some data on client table
                            
                            $sql="UPDATE ".DB_PREFIX."7banner_clienti set de_nome='##de_nome##',de_address='##de_address##' where id_cliente='##id_cliente##'";
                            $sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
                            $sql= str_replace("##de_address##",$arDati["de_address"],$sql);
                            $sql= str_replace("##id_cliente##",$arDati["id_cliente"],$sql);

                            $conn->query($sql) or die($conn->error."sql='$sql'<br>");

                        } else {
                            //create record on client table if not exists
                            
                            $sql="INSERT into ".DB_PREFIX."7banner_clienti (de_nome,de_address,cd_utente) values('##de_nome##','##de_address##','##cd_utente##')";
                            $sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
                            $sql= str_replace("##de_address##",$arDati["de_address"],$sql);
                            $sql= str_replace("##cd_utente##",$session->get("idutente"),$sql);

                            $conn->query($sql) or die($conn->error."sql='$sql'<br>");


                        }
                    }
				}
				*/


			} 

		} 
		return $result;
	}


}

?>