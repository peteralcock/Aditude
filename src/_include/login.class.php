<?php
class Login {

	var $template;
	var $usernamevar;
	var $passwordvar;
	var $setmodulovar;
	var $actionurl;
	//var $externalUserLogin;		//external function for login see documentation on dev server
	//var $externalUserLogout;		//external funciton for logout


	function __construct() {
		$this->usernamevar="utente";
		$this->passwordvar="password";
		$this->actionurl=$_SERVER['PHP_SELF'];
		$this->template = "";
		//$this->externalUserLogin="";
		//$this->externalUserLogout="";
		return true;
	}

	/**
	 * get login form
	 * 
	 * @param string $msg
	 * 
	 * @return string
	 */
	function getLoginForm($msg="") {
		global $session,$conn,$root;
		$html = loadTemplateAndParse(
			$root."data/".DOMINIODEFAULT."/layout-login-form.php"
		);

		$html = str_replace("##msg##", $msg, $html);
		$html = str_replace("##usernamevar##", $this->usernamevar, $html);
		$html = str_replace("##passwordvar##", $this->passwordvar, $html);
		$html = str_replace("##actionurl##", $this->actionurl, $html);
		//$strLOGO = getLogo();

		$html = str_replace("##LOGO##", WEBURL . "/data/".DOMINIODEFAULT."/thumb.jpg", $html);


		if(!defined("SERVER_EMAIL_ADDRESS") || (SERVER_EMAIL_ADDRESS=="")) {
			$html = str_replace("##hiderecover##", "style='display:none'", $html);
		} else {
			$html = str_replace("##hiderecover##", "", $html);
		}

		if(!defined("PAYMENTS") || (PAYMENTS=="OFF")) {
			$html = str_replace("##hidesignin##", "style='display:none'", $html);
		} else {
			$html = str_replace("##hidesignin##", "", $html);
		}

		$html = translateHtml($html);
		
		return $html;
	}




	/**
	 * reset form for user recover password procedure
	 * 
	 * @param string $msg
	 * @param string $email
	 * @param string $pass1
	 * @param string $pass2
	 * @param string $code
	 * 
	 * @return string
	 */
	function getResetForm($msg="",$email="",$pass1="",$pass2="",$code="") {
		global $session,$conn,$root;

		$code = preg_replace("/[^0-9a-z]/i","",$code);

		//
		// reset password field adding if needed
		$q = execute_scalar( "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA='".DEFDBNAME."' AND TABLE_NAME = '".DB_PREFIX."frw_extrauserdata' AND COLUMN_NAME = 'de_temp'");
		if($q==0) {
			$sql = "ALTER TABLE ".DB_PREFIX."frw_extrauserdata ADD de_temp VARCHAR(200) NOT NULL";
			$conn->query($sql) or die(trigger_error("Error while upgrading your DB for Password Reset function. ".$conn->error." sql='$sql'<br>"));
		}


		$html = loadTemplateAndParse(
			$root."data/".DOMINIODEFAULT."/layout-resetpassword.php"
		);
		
		
		//
		// default intro message for user
	
		if(!defined("SERVER_EMAIL_ADDRESS") || (SERVER_EMAIL_ADDRESS=="")) {
			
			// not configured
			$ciao = "<b>WARNING</b>:<br>
			Can your server send emails? Be sure of it.<br>
			Go to Config &gt; Settings and edit your SERVER_EMAIL_ADDRESS and SMTP fields.<br>";
			
			//
			// hide the form
			$html = str_replace("##hideall##", "style='display:none'", $html);

		} else {


			if($code=="" && $pass1=="" && $pass2=="" && $email=="") {
				/* STEP 0 */
				$ciao = "{Insert your email to reset your password}";
				$html = str_replace("##show##", "", $html); // show email field
				$html = str_replace("##hide##", "style='display:none'", $html); // hide password fields
				$html = str_replace("##backlink##", WEBURL, $html); 
				$html = str_replace("##code##", "", $html);
				$html = str_replace("##email##", "", $html);
				$html = str_replace("##hideall##", "", $html);

			}

			if($email!="" && $pass1=="" && $pass2=="" && $code=="")  {
				/* STEP 1, there is email and not password, check for user to send recovery reset password mail message */
				if(is_email($email)) {
					$user = execute_row("SELECT * FROM ".DB_PREFIX."frw_extrauserdata INNER JOIN ".DB_PREFIX."frw_utenti ON cd_user=id WHERE de_email='".$email."'");
					if(isset($user['nome'])) {

						$ciao = "{Check your email for reset link}";

						$html = str_replace("##hideall##", "style='display:none'", $html);
						$html = str_replace("##backlink##", WEBURL, $html); 
						$html = str_replace("##code##", "", $html);
						$html = str_replace("##email##", "", $html);

						$tempCode = md5($email."--check-");
						$conn->query("UPDATE ".DB_PREFIX."frw_extrauserdata SET de_temp='".$tempCode."' WHERE cd_user='".$user['cd_user']."'");

						// compose reset password message
						$link  = WEBURL."/src/resetpassword.php?code=".$tempCode;
						$subject = translateHtml("{Reset password}");
						$message = translateHtml("{Hi, <br>your username is <b>%u</b> and to reset your password click here: <br><a href='%s'>%s</a> <br>Thank you.}");
						$message = str_replace( "%u",$user['username'], $message);
						$message = str_replace( "%s",$link, $message);
						mail_utf8($email, "[".SERVER_NAME."] ".$subject, $message);
	
					} else {
						$ciao = "{Sorry, email not found}";
						$html = str_replace("##email##", "", $html);
						$html = str_replace("##code##", "", $html);
						$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
						$html = str_replace("##show##", "", $html);	// show email
						$html = str_replace("##backlink##", WEBURL, $html); 

					}

				} else {
					$ciao = "{Sorry, email not found}";
					$html = str_replace("##email##", "", $html);
					$html = str_replace("##code##", "", $html);
					$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
					$html = str_replace("##show##", "", $html);	// show email
					$html = str_replace("##backlink##", WEBURL, $html); 
					

				}
				$email = "";
			}


			if($code!="" && $pass1=="" && $pass2=="" && $email=="") {

				/* STEP 2, there is verify code from email */
				$user = execute_row("SELECT * FROM ".DB_PREFIX."frw_extrauserdata INNER JOIN ".DB_PREFIX."frw_utenti ON cd_user=id WHERE de_temp='".$code."'");
				
				if(isset($user['de_email'])) {

					// user not confirmed but reset process reaches its email
					// so email is correct.
					$conn->query("UPDATE ".DB_PREFIX."frw_utenti set fl_attivo=1 where id='".$user['id']."'");
				
					
					// the user has an email and the form is visible
					$ciao = "{Choose your new password}";
					$html = str_replace("##hideall##", "", $html);		// shwo password fields
					$html = str_replace("##show##", "style='display:none'", $html); // hide email field
					$html = str_replace("##backlink##", WEBURL, $html); 

					$html = str_replace("##code##", $code, $html);
					$html = str_replace("##email##", "", $html);
					$html = str_replace("##hide##", "", $html);

					
				} else {
					$ciao = "{User not found}";
					$html = str_replace("##hideall##", "style='display:none'", $html);
					$html = str_replace("##backlink##", WEBURL, $html); 
					$html = str_replace("##email##", "", $html);
					$html = str_replace("##code##", "", $html);
					$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
					$html = str_replace("##show##", "", $html);	// show email
				}


			}



			if($code!="" && $pass1!="" && $pass2!="" && $email=="") {
				// STEP 3 save passwords
				
				if ($pass1 == $pass2) {
					$user = execute_row("SELECT * from ".DB_PREFIX."frw_extrauserdata INNER JOIN ".DB_PREFIX."frw_utenti ON cd_user=id WHERE de_temp='".$code."'");
					if(isset($user['de_email'])) {
						$cr = new cryptor();
						$conn->query("UPDATE ".DB_PREFIX."frw_extrauserdata SET de_temp='' WHERE cd_user='".$user['cd_user']."'");
						$conn->query("UPDATE ".DB_PREFIX."frw_utenti SET password='".$cr->crypta($pass1)."' WHERE id='".$user['cd_user']."'");
						$html = str_replace("##hideall##", "style='display:none'", $html);
						$ciao = "{Go back and use your new password}";
						$html = str_replace("##backlink##", WEBURL, $html); 

						$html = str_replace("##email##", "", $html);
						$html = str_replace("##code##", "", $html);
						$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
						$html = str_replace("##hideall##", "style='display:none'", $html);
						$html = str_replace("##show##", "", $html);	// show email


					} else {
						$ciao = "{User not found}";
						$html = str_replace("##hideall##", "style='display:none'", $html);
						$html = str_replace("##backlink##", WEBURL, $html); 
						$html = str_replace("##email##", "", $html);
						$html = str_replace("##code##", "", $html);
						$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
						$html = str_replace("##show##", "", $html);	// show email


					}

				} else {

					$ciao = "{Password mismatch}";
					$html = str_replace("##hideall##", "style='display:none'", $html);
					$html = str_replace("##backlink##", "javascript:window.history.back();", $html); 
					$html = str_replace("##email##", "", $html);
					$html = str_replace("##code##", "", $html);
					$html = str_replace("##hide##", "style='display:none'", $html);  // hide passwords
					$html = str_replace("##show##", "", $html);	// show email

				
				}
			}

			$html = str_replace("##actionurl##", $this->actionurl, $html);
			$html = str_replace("#ciao#", $ciao, $html);

			return translateHtml($html);

		}
	}

	/**
	 * check if user is logged in, search for username and password in _POST or cookie with id
	 * 
	 * @return boolean
	 */
	function logged() {
		global $session;

		if ( $session->get("username")!="") {
			// user already logged in session
			return true;

		} else {
			// user not logged, if POST contains user and password try to login
			if (isset($_POST[$this->usernamevar])) {
				return @$this->checkUser($_POST[$this->usernamevar],$_POST[$this->passwordvar]);
			} else {
				if($this->loginFromCookie()) return true;
					else return false;
			}
		}
	}

	/**
	 * login from cookie, called in config.php
	 * 
	 * @return boolean
	 */
	function loginFromCookie() {
		global $conn,$logger;
		if (isset($_COOKIE["comein"]) && $_COOKIE["comein"]!="") {
			// fare query con id
			// per recuperare login
			// da sess scaduta
			$ar = explode("-",$_COOKIE["comein"]);
			if(isset($ar[1]) && md5(ENCRYPTIONKEY.$ar[0] == $ar[1])) {
				// echo "ok";

				$sql = "SELECT id, username,
				password,
				nome, cognome, cd_profilo, de_lang
				FROM ".DB_PREFIX."frw_utenti 
				LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata ON cd_user=id
				WHERE ".DB_PREFIX."frw_utenti.id='".(integer)$ar[0]."' AND ".DB_PREFIX."frw_utenti.fl_attivo='1'";
				$rs = $conn->query($sql) or trigger_error($conn->error);

				if ($rs->num_rows == 1) {
					$row = $rs->fetch_array();					
					$this->setSessionVariables($row);
					$logger->addlog( "{user logged from cooke userid #".$ar[0]."}" );
					return true;
				}
			} 
			
			return false;

		}
	}

	/**
	 * save data from $row (db) to session variables
	 * 
	 * @param array $row
	 * @return void
	 */
	function setSessionVariables($row) {
		global $session;
		$session->register("idutente",$row['id']);
		$session->register("username",$row['username']);
		$session->register("password",$row['password']);
		$session->register("nome",$row['nome']);
		$session->register("idprofilo",$row['cd_profilo']);
		$session->register("cognome",$row['cognome']);
		$session->register("WEBURL",WEBURL);
		$session->register("language",$row['de_lang']);
		// set a cookie to keep user logged
		setcookie("comein",$row["id"]."-".md5(ENCRYPTIONKEY.$row['id']),time()+2000000,"/" );
	}


	/**
	 * check if user is logged
	 * 
	 * @param string $username
	 * @param string $password
	 * 
	 * @return boolean
	 */
	function checkUser($username,$password) {
		global $session,$logger,$conn;
		if (!defined('WEBDOMAIN')) return false;

		//
		// if custom login is defined
		// include new custom login class
		// and check login using "logged" method
		// you have to implement a class "LoginCustom" with "logged" method
		// that return true or false.
		// if false custom login method the process
		// goes on to default AdAdmin login method.
		if(defined('CUSTOM_LOGIN_CLASS') && CUSTOM_LOGIN_CLASS!= "") {
			$relPath = str_replace(PONSDIR."/src/","",CUSTOM_LOGIN_CLASS);
			if(file_exists($relPath)) {
				require_once( $relPath );
				$customLogin = new LoginCustom();
				if( $customLogin->isLogged() ) return true;
			}
		}

		//
		// default login
		// --------------------------------------------------------
		$cr = new cryptor();
		$sql = "SELECT id, username,
				password,
				nome, cognome, cd_profilo, de_lang
				FROM ".DB_PREFIX."frw_utenti 
				LEFT OUTER JOIN ".DB_PREFIX."frw_extrauserdata ON cd_user=id
				WHERE username='$username' AND password='".$cr->crypta($password)."' AND ".DB_PREFIX."frw_utenti.fl_attivo='1'";
		$rs = $conn->query($sql) or trigger_error($conn->error);

		if ($rs->num_rows == 1) {
			$row = $rs->fetch_array();

			// force distribute permissions on logged in user
			require_once("componenti/gestioneutenti/_include/user.class.php");
			$u=new user();
			$u->id= $row['id'];
			$u->setProfilo($row['cd_profilo']);

			$this->setSessionVariables($row);

			$logger->addlog( "{user session userid #".$session->get("idutente")."}" );
			
			
			return true;
		} else {
			$logger->addlog( "{login failed for ".$username."}" );
			return false;
		}
		

	}

	/**
	 * get the HTML of the menu for a user
	 * 
	 * @param integer $idutente
	 * @return mixed (array or HTML)
	 */
	function getMenu($idutente, $output = "HTML") {
		global $session,$conn,$root;

		if (!Connessione()) trigger_error($conn->error); else CollateConnessione();

		$modulo = $session->get("moduloattiva");

		$MODULIOK = "";

		$sql = "SELECT distinct urlcomponente, urliconamenu, ".DB_PREFIX."frw_componenti.id, ".DB_PREFIX."frw_componenti.nome, ".DB_PREFIX."frw_componenti.label AS labelc, ".DB_PREFIX."frw_moduli.label AS labelv , ".DB_PREFIX."frw_moduli.label as nmodu, ".DB_PREFIX."frw_moduli.id as idmodu, ".DB_PREFIX."frw_moduli.nome as nomemodu, ".DB_PREFIX."frw_componenti.descrizione, 
		".DB_PREFIX."frw_moduli.posizione,".DB_PREFIX."frw_com_mod.posizione,".DB_PREFIX."frw_componenti.target,".DB_PREFIX."frw_componenti.fl_translate,".DB_PREFIX."frw_moduli.fl_translate as fl_transaltemodu
		from ".DB_PREFIX."frw_ute_fun
		join ".DB_PREFIX."frw_funzionalita on idfunzionalita=".DB_PREFIX."frw_funzionalita.id
		join ".DB_PREFIX."frw_componenti on ".DB_PREFIX."frw_funzionalita.idcomponente=".DB_PREFIX."frw_componenti.id
		join ".DB_PREFIX."frw_com_mod on ".DB_PREFIX."frw_com_mod.idcomponente=".DB_PREFIX."frw_componenti.id
		join ".DB_PREFIX."frw_moduli on ".DB_PREFIX."frw_moduli.id=".DB_PREFIX."frw_com_mod.idmodulo
		WHERE idutente = '$idutente' and ".DB_PREFIX."frw_moduli.visibile=1 
        order by ".DB_PREFIX."frw_moduli.posizione,idmodu,".DB_PREFIX."frw_com_mod.posizione";

		$rs = $conn->query($sql) or trigger_error($conn->error);
		$html="";
		$nomemodulo = "";
		$dataOutput = array();
		while($row = $rs->fetch_array()){
			if ($nomemodulo!=$row['nmodu']) {
				if ($html!="") $html.="</div></div>";
				if($row['fl_transaltemodu']==1) {
					$label = "{".$row['nmodu']."}";
				} else {
					$label = $row['nmodu'];
				}
				$html.="<a class=\"linkmenu0 item-".$row['id']."\" data-rel='modulo{$row['idmodu']}' href=\"javascript:show('modulo{$row['idmodu']}')\">".htmlspecialchars($label)."</a><div id='modulo{$row['idmodu']}' class='".($modulo!=$row['idmodu']?"sottomenu":"sottomenu chiuso")."'><div>";
				$nomemodulo = $row['nmodu'];
				$dataOutput[] = array( "type"=>"module","id"=>$row['idmodu'],"label"=>$row['nmodu'], "link"=>"");
			}

           
            if($row['id']<1000) {
				if(strpos($row['urlcomponente'],"http")===0) {
					$target = "target='_blank'";
					$href = $row['urlcomponente'];
				} else {
					$target = "target='_self'";
					$href = preg_match("/^http/",$row['urlcomponente']) ? $row['urlcomponente'] : WEBURL."/src/".$row['urlcomponente'];
				}
            } else {
				$target = "target='".$row['target']."'";
				$href = preg_match("/^http/",$row['urlcomponente']) ? $row['urlcomponente'] : WEBURL."/src/".$row['urlcomponente'];
            }

			if($row['fl_translate']==1) {
				$label = "{".$row['labelc']."}";
			} else {
				$label = $row['labelc'];
			}

			if($row["urliconamenu"] == null) $row["urliconamenu"] = "";
			if($row['id'] > 1000 && (!strstr($row["urliconamenu"],'/'))) $icona = $row["urliconamenu"];
				else $icona = $row['nome'];

			if($row['id'] <= 1000 && ($row["urliconamenu"]=="icon-menu")) $icona = $row['nome'];

			$html.="\n<a class=\"linkmenu ".$icona." item-".$row['id']."\" href=\"".$href."\" {$target}>";
			$html.=" ".$label."</a>";

			$dataOutput[] = array( "type"=>"component","id"=>$row['id'],"label"=>$label, "link"=>$href);
		}
		$html.="</div></div>";

		if($output == "HTML")
			return translateHtml($html);
		else 
			return $dataOutput;

	}

	/**
	 * logout menu item
	 * 
	 * @return string
	 */
	function footMenu() {
		return translateHtml(
			"<a class=\"linkmenu esci\" href=\"".WEBURL."/src/logout.php\">{Logout}</a>"
		);
	}

	/**
	 * head menu item with favicon from theme folder
	 * 
	 * @return string
	 */
	function headMenu() {
		return translateHtml("<a class=\"linkmenu topMenu\" href=\"#\" style=\"background-image:url(" . WEBURL.'/data/'.DOMINIODEFAULT.'/favicon.png' .");\">".SERVER_NAME."</a>");
	}
}
?>