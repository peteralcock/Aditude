<?php



class logger {

	var $logfile;

	var $level;

	var $errorTemplate;

	var $counter = 0;

	var $errorMsgs = "";



	function __construct() {

		$this->errorTemplate="<div class='errore' id='##IDDUMP##' style='##POS##'><a class='closeme' href=\"javascript:show('##IDDUMP##')\">X</a> ##msg##</div>";

		set_error_handler(array(&$this,'handle_error'));

	}



	function setLogFile($logfile) {

		$this->logfile = $logfile;

	}



	function addlog($description="",$filename="") {

		global $root;

		if(!defined("LOGS_FILENAME")) return; // log file not defined, doesn't log anything

		if(!isset($this->logfile)) $this->logfile = $root.LOGS_FILENAME;

		if ($filename=="") $filename=$_SERVER["PHP_SELF"];

		$Message=date('d/m/y h:i:s')." ".$filename." ".$description." ".$_SERVER['REMOTE_ADDR']." Browser:".$_SERVER['HTTP_USER_AGENT'];

		$Message.="\n";

		if(!is_writable($this->logfile)) die("Please make the log folder \"data/logs\" writable.");

		error_log($Message, 3, $this->logfile);

	}



	function logsize() {

		global $root;

		if(!defined("LOGS_FILENAME")) return;

		if(!isset($this->logfile)) $this->logfile = $root.LOGS_FILENAME;

		if(!file_exists($this->logfile)) return "n.d.";

		return number_format(filesize($this->logfile)/1024,0,',','.') . " Kbyte";



	}

	function displayLog(){

		global $root;

		if(!defined("LOGS_FILENAME")) return;

		if(!isset($this->logfile)) $this->logfile = $root.LOGS_FILENAME;

		return nl2br(loadTemplate($this->logfile));

	}



	function deleteLog(){

		global $root;

		if(!defined("LOGS_FILENAME")) return;

		if(!isset($this->logfile)) $this->logfile = $root.LOGS_FILENAME;

		if (file_exists($this->logfile)) {

			unlink($this->logfile);

			echo $this->logfile." rimosso.";

		} else {

			echo $this->logfile." non trovato.";

		}

		return "";

	}



	function handle_error ($errno, $errstr, $errfile, $errline) {

		global $session,$root,$defaultReplace;



		if(stristr($this->errorMsgs,$errstr)) return;



		$this->errorMsgs.=", ".$errstr;

		

		/*

			if here withoug these variables, there is an error in login

		*/

		if(!defined('SEND_ERRORS_MAIL')) define("SEND_ERRORS_MAIL","");

		if(!defined('SHOW_ERRORS')) define("SHOW_ERRORS",true);

		if(!defined('STOP_ON_ERROR')) define("STOP_ON_ERROR",false);

		

	

		/*

			log errors

		*/

		$this->addlog("Error number: $errno, error is: $errstr - File: $errfile, Linea: $errline - Main file: ".$_SERVER['PHP_SELF']);



		$IDDUMP = "dump_".rand(1,111111);



		$support_link ="";
		if(hasModule("BANNER") && $errno!=256) {
			$support_link ="<a href='https://codecanyon.net/item/adadmin-easy-adv-server/12710605/support' target='_blank'><u>Need support?</u></a>";

		}



		$text = "<link href=\"".$root."src/template/stile.css\" type=\"text/css\" rel=\"stylesheet\">

			<script language=\"JavaScript\" src=\"".$root."src/template/comode.js?z\"></script>

			<b>$errstr</b><p>".(SEND_ERRORS_MAIL ? "I've sent a notice to the System Administrator." : "").
			" ".$support_link;
		if($errno!=256) {
			// hide details on wrong email errors
			$text.="<div>#<b>".$errno."</b>; Line:  <b>$errline</b>; $errfile;<br>Main file: ".$_SERVER['PHP_SELF']."<br><span>PHP ver. ".phpversion()." Framework ver. ".(isset($defaultReplace["##VER##"])?$defaultReplace["##VER##"]:"")."</span></div>";

		}
		$text=str_replace("##msg##",$text, $this->errorTemplate);

		$text=str_replace("##IDDUMP##",$IDDUMP, $text);

		$text=str_replace("##POS##","top:".($this->counter * 210)."px", $text);



		$this->counter++;



		if (SEND_ERRORS_MAIL) {

			//

			// if error mail sender active send email with errors

			//

			$headers = "MIME-Version: 1.0\r\n"

				."Content-type: text/html; charset=iso-8859-1\r\n"

				."From: errorhandler@{$_SERVER['SERVER_NAME']}\r\n"

				."X-Mailer: PHP/" . phpversion();

			$headers = str_replace("\r","",$headers);

			mail(SEND_ERRORS_MAIL, "Error on [".DOMINIODEFAULT."] user ".$session->get("username"), $text, $headers);

		}

		if (SHOW_ERRORS == true || !defined("SHOW_ERRORS")) echo $text;

		if (STOP_ON_ERROR == true || !defined("STOP_ON_ERROR")) die();

		/* Don't execute PHP internal error handler */

		return true;



	}



	function dump_array($s,$sep=",") {

		$o="";

		if (is_array($s)) 

			foreach($s as $key=>$value) {

				$o .= htmlspecialchars($key);

				$o .= " = ";

				if (is_array($value)) $o.="Array(".$this->dump_array($value).")"; else

					$o .= htmlspecialchars($value);

				$o .= $sep;

			}

		else return $s;

		return $o;

	}



	function dump_info(){

		$o = "";

		$o .= "<h2>Vars in _SESSION:</h2>";

		$o .= isset($_SESSION) ? $this->dump_array($_SESSION,"<br>") : "nulla.";

		$o .= "<h2>Vars in _GET:</h2>";

		$o .= $this->dump_array($_GET,"<br>");

		$o .= "<h2>Vars in _POST:</h2>";

		$o .= $this->dump_array($_POST,"<br>");

		$o .= "<h2>Vars in _SERVER:</h2>";

		$o .= $this->dump_array($_SERVER,"<br>");

		$o .= "<h2>Classes declared:</h2>";

		$o .= $this->dump_array(get_declared_classes(),"<br>");

		return $o;

	}

}

?>