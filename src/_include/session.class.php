<?php
/////////////////////////////////////////////////////
// this class was build
// to handle sessions with cookie or not
// if you choose "zipcook" value the cookies
// are crypted and packed to hide data to malicious
// users.
//
// $use_cookie parameter values:
// no --------> use session
// yes -------> use cookie, one cookie for each variable
// zipone  -------> use cookie, one cookie crypted clear for each variable
// zipcook ---> encrypted cookies
//
// by Giulio Pons, http://www.zepsec.com
//
/////////////////////////////////////////////////////

class Session
{
	private $use_cookie;
	private $preStr;
	private $maxCookie;
	private $cookieLenght;
	private $stringone;
	private $duratacookie;
	private $secret;

	public function __construct ($cook = "no") {

        $cookietime = 3600*24*30; //cookie life time seconds

		$this->use_cookie = $cook;	//choose mode
		$this->preStr= "_KK_";		//prefix for cookies
		$this->maxCookie=20;		//since cookie lenght is limited, I've limited the number of cookies
		$this->cookieLenght=3096;	//max cookie length (it depends on browser)
		$this->duratacookie= $cookietime ;//cookie life time
		$this->secret="secret";		//secret keyword to crypt/decrypt, change this to customize encryption
		if ($this->use_cookie=="yes" || $this->use_cookie=="zipone") {
			// nothing
		} elseif ($this->use_cookie=="zipcook") {
			$this->stringone = $this->prelevaStringaTotale();
		} else {
			try{session_cache_expire($cookietime / 60); } catch (Exception $e) {}
			try{ini_set("session.gc_maxlifetime", $cookietime );} catch (Exception $e) {}
			try{ini_set("session.cookie_lifetime", $cookietime );} catch (Exception $e) {}
			try{ini_set("session.cache_expire", $cookietime );} catch (Exception $e) {}
			try{ini_set("url_rewriter.tags","");} catch (Exception $e) {}
			try{ini_set("session.use_trans_sid", false);} catch (Exception $e) {}
			if($cook=='no') {
				try{session_start();} catch (Exception $e) {}
			}

		}

		// security check for more installation on same domain
		if($this->get("WEBURL")!= WEBURL) {
			$this->finish();
		}

	}

	/* ------------------------------------------- */
	/* pack variables for parse_str                */
	/* ------------------------------------------- */
	private function build_str($ar) {
		$qs = array();
		foreach ($ar as $k => $v) { $qs[] = $k.'='.$v; }
		return join('&', $qs);
	}

	/* ------------------------------------------- */
	/* get the list of variables from the crypted  */
	/* cookies                                     */
	/* ------------------------------------------- */
	private function prelevaStringaTotale() {
		$cookiesSet = array_keys($_COOKIE);
		$out = "";
		for ($x=0;$x<count($cookiesSet);$x++) {
			if (strpos(" ".$cookiesSet[$x],$this->preStr)==1)
				$out.=$_COOKIE[$cookiesSet[$x]];
		}
		return $this->decrypta($out);
	}

	public function debug() {
		// for debug
		return $this->prelevaStringaTotale();
	}

	/* ------------------------------------------- */
	/* determine available cookies                 */
	/* ------------------------------------------- */
	private function calcolaCookieLiberi() {
		$cookiesSet = array_keys($_COOKIE);
		$c=0;
		for ($x=0;$x<count($cookiesSet);$x++) {
			if (strpos(" ".$cookiesSet[$x],$this->preStr)==1)
				$c+=1;
		}
		return $this->maxCookie - count($cookiesSet) + $c;
	}

	/* ------------------------------------------- */
	/* split the string in blocks to store cookies */
	/* ------------------------------------------- */
	private function my_str_split($s,$len) {
		$output = array();
		if (strlen($s)<=$len) {
			$output[0] = $s;
			return $output;
		}
		$i = 0;
		while (strlen($s)>0) {
			$s = substr($s,0,$len);
			$output[$i]=$s;
			$s = substr($s,$len);
			$i++;
		}
		return $output;
	}

	/* ------------------------------------------- */
	/* save vars in cookies or session             */
	/* ------------------------------------------- */
	public function register($var,$value) {
		// back compatibility
		$this->set($var,$value);
	}
	public function set($var,$value) {
		if ($this->use_cookie=="yes") {
			// use clear cookies
			setcookie($var,$value,time()+$this->duratacookie,"/", $_SERVER['HTTP_HOST'] );
		} elseif ($this->use_cookie=="zipone") {
			// use crypted cookies
			setcookie($var,$this->crypta($value),time()+$this->duratacookie,"/", $_SERVER['HTTP_HOST'] );
		} elseif  ($this->use_cookie=="zipcook") {
			// use cookies crypted and zipped
			if ($this->stringone!="") parse_str($this->stringone, $vars);  else $vars=array();
			$vars[$var] = $value;	//add variable
			$str = $this->crypta($this->build_str($vars));
			$arr = $this->my_str_split($str,$this->cookieLenght);
			$cLiberi = $this->calcolaCookieLiberi();
			if (count($arr) < $cLiberi) {
				// there is enough space to add a variable
				$this->stringone = $this->build_str($vars);
				for ($i=0;$i<count($arr);$i++) {
					setcookie($this->preStr.$i,$arr[$i],time()+$this->duratacookie,"/", $_SERVER['HTTP_HOST'] );
				}
			} else {
				//cookie overflow
				return "Error cookie overflow";
			}
		} else {
			// use simple session array
			$_SESSION[$var]=$value;
		}
	}

	/* ------------------------------------------- */
	/* get variables back from cookies crypted or  */
	/* not, or directly from session               */
	/* ------------------------------------------- */
	public function get($var) {
		if ($this->use_cookie=="yes") {
			// simple clear cookie
			return isset($_COOKIE[$var]) ? $_COOKIE[$var] : "";
		} elseif ($this->use_cookie=="zipone") {
			// cookie crypted
			return isset($_COOKIE[$var]) ? $this->decrypta($_COOKIE[$var]) : "";
		} elseif ($this->use_cookie=="zipcook") {
			// many vars in a cookie (cookie zipped)
			if ($this->stringone!="") parse_str($this->stringone, $vars); else return "";
			return isset($vars[$var]) ? $vars[$var] : "";
		} else {
			// use session array
			return isset($_SESSION[$var]) ? $_SESSION[$var] : "";
		}
	}

	/* ------------------------------------------- */
	/* empty session or cookies                    */
	/* ------------------------------------------- */
	public function finish() {
		if ($this->use_cookie=="yes" || $this->use_cookie=="zipone") {
			$cookiesSet = array_keys($_COOKIE);
			for ($x=0;$x<count($cookiesSet);$x++) {
				setcookie($cookiesSet[$x],"",time()-3600*24,"/", $_SERVER['HTTP_HOST'] );	//faccio scadere il cookie
			}

		} elseif ($this->use_cookie=="zipcook") {
			$cookiesSet = array_keys($_COOKIE);
			for ($x=0;$x<count($cookiesSet);$x++) {
				if (strpos(" ".$cookiesSet[$x],$this->preStr)==1)
					setcookie($cookiesSet[$x],"",time()-3600*24,"/",$_SERVER['HTTP_HOST']);
				$this->stringone="";
			}
		} else {
			$_SESSION = array();
		}
	}

	/* ------------------------------------------- */
	/* helper methods                              */
	/* ------------------------------------------- */
	/* crypt */
	private function crypta($t){
		if ($t=="") return $t;
		$r = md5(10); $c=0; $v="";
		for ($i=0;$i<strlen($t);$i++){
			if ($c==strlen($r)) $c=0;
			$v.= substr($r,$c,1) . (substr($t,$i,1) ^ substr($r,$c,1));
			$c++;
		}
		return (base64_encode($this->ed($v)));
	}
	/* decrypt */
	private function decrypta($t) {
		if ($t=="") return $t;
		$t = $this->ed(base64_decode(($t)));
		$v = "";
		for ($i=0;$i<strlen($t);$i++){
			$md5 = substr($t,$i,1);
			$i++;
			$v.= (substr($t,$i,1) ^ $md5);
		}
		return $v;
	}

	/* used to crypt/decrypt */
	private function ed($t) {
		$r = md5($this->secret); $c=0; $v="";
		for ($i=0;$i<strlen($t);$i++) {
			if ($c==strlen($r)) $c=0;
			$v.= substr($t,$i,1) ^ substr($r,$c,1);
			$c++;
		}
		return $v;
	}

}

?>