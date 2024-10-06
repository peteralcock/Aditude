<?php
class LoginCustom {

	function __construct() {
		return true;
	}

	function isLogged() {
		global $session,$logger,$conn;
		if (!defined('WEBDOMAIN')) return false;

		if ( $session->get("username")!="") {
			// user already logged in session
			return true;

		} else {
			//
			// user not logged, if POST contains user and password try to login
			// you can adjust this code to fit your needs.
			// 
			// a user in a different application need to be logged in into AdAdmin:
			// - create a PHP file that print a form that send user and password to
			//   /amb/src/login.php file
			// - login.php will check for custom login, find the CUSTOM_LOGIN_CLASS
			//   and use this code to login.
			// - you have to create the user in frw_utenti table, to login a user you
			//   finally have to call the saveUserSession method with the selected user 
			//   row from frw_utenti
			

			//
			// add your login logic here
			// ----------------------------------------------------------------------

			$username = isset($_POST['user']) ? $_POST['user'] : "";
			$password = isset($_POST['pass']) ? $_POST['pass'] : "";
			
			// ...
			

			//
			// select the frw_utenti row corresponding to the logged in user
			// you should have a field in your database where you store the "id"
			// of frw_utenti table that match one user of yours to a user of AdAdmin ones.

			// get the "id"

			$id = 1; // add your code
			

			// ----------------------------------------------------------------------
			//
			// this method will login in the user
			if( $this->saveUserSession($id) ) {

				$logger->addlog( "{custom login, user session id #".$session->get("idutente")."}" );
				return true;
			} else {
				$logger->addlog( "{custom login failed for ".$username."}" );
				return false;
			}

		}
	}


	function saveUserSession($id) {
		global $session, $logger;

		$row = execute_row("SELECT * FROM ".DB_PREFIX."frw_utenti WHERE id=".(integer)$id." AND fl_attivo=1",array());
		if(isset($row['id'])) {

			//
			// force distribute user permissions on logged in user
			//
			require_once("componenti/gestioneutenti/_include/user.class.php");
			$u=new user();
			$u->id= $row['id'];
			$u->setProfilo($row['cd_profilo']);


			$session->register("idutente"	,	$row['id']);
			$session->register("username"	,	$row['username']);
			$session->register("password"	,	$row['password']);
			$session->register("nome"		,	$row['nome']);
			$session->register("idprofilo"	,	$row['cd_profilo']);
			$session->register("cognome"	,	$row['cognome']);
			$session->register("WEBURL"		,	WEBURL);

			return true;

		}

		return false;
	}

}
?>