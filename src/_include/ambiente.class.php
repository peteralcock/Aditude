<?php

DEFINE("ERR_MSG","err");
DEFINE("OK_MSG","ok");


class Ambiente
{
	function __construct ()
	{

	}

	// list of elements to be replaced in the template
	private $replaceList = array();

	// template file name
	private $templateFile = "";

	/**
	 * set a variable to be replaced in the template
	 * 
	 * @param string $var
	 * @param string $value
	 * 
	 * @return void
	 */
	public function setKey($var, $value) {
		$this->replaceList[$var] = $value;
	}

	/**
	 * set the template file
	 * 
	 * @param string $filename
	 * 
	 * @return void
	 */
	public function setTemplate($filename) {
		$this->templateFile = $filename;
	}

	/**
	 * load and parse the template using both the global $defaultReplace (for backward compatibility)
	 * and the variables in $this->replaceList
	 * 
	 * @return string the parsed template
	 */
	public function loadAndParse() {

		global $defaultReplace;

		if(!file_exists($this->templateFile)) {
			trigger_error("File not found: ".$this->templateFile);
			return "";
		}

		$keys = array_merge( $this->replaceList, $defaultReplace) ;

		return loadTemplateAndParse($this->templateFile, $keys);

	}


	/**
	 * add the title string in the defaultReplace array
	 * 
	 * @param string $var
	 */
	function setPosizione($var)
	{
		global $defaultReplace;
		$defaultReplace['##TITLE##'] = $var;
	}

	/*

	2024-03-16
	@todo COMMENTED OUT for future removal
	
	function setNomeUtente()
	{
		trigger_error("RIMUOVERE chiamata a setNomeUtente()");
	}

	*/

	/**
	 * load the login page with a message
	 * 
	 * @param string $msg	the message
	 * 
	 * @return string 
	 */
	function loadLogin($msg){
		global $root;
		// handle the redirect to login
		return "<script>top.location.href=\"".$root."src/login.php?msg=".urlencode($msg)."\";</script>";
	}


	/*

	2024-03-16
	@todo COMMENTED OUT for future removal

	function goHome(){
		// handle the loading of the home page
		return "<script>document.location.href=\"".$root."src/index.php\";</script>";
	}

	*/

	/**
	 * load the message template and populate the variables to output the result of an operation
	 * 
	 * @param string $msg	the message
	 * @param string $op	"back"|"session"|"reload"|"jsback"|"load page"|"link page"
	 * @param string $class "err"|"ok"
	 */
	function loadMsg($msg,$op="",$class="err") {
		global $root,$session;

		$this->setTemplate( $root."data/".DOMINIODEFAULT."/layout-msg.php" );

		if ($op=="back" || $op=="session") {
			$msg .= $session->get("backbutton");
		} elseif ($op=="reload") {
			$msg.=" <span class='loading'><span class='icon-spin5 animate-spin'></span> {Loading...}</span>
				<script language='javascript'>setTimeout(\"document.location.href=document.location.href;\",1000)</script>";
		} elseif ($op=="jsback") {
			$msg.=" <br><br><a href='javascript:history.go(-1)' class='btn'>{Back}</a>";
		} elseif (preg_match("/^(load) /i",$op)) {
			$pageToLoadAr=explode(" ",$op);
			$msg.=" <span class='loading'><span class='icon-spin5 animate-spin'></span> {Loading...}</span>
				<script language='javascript'>setTimeout(\"document.location.href='{$pageToLoadAr[1]}';\",1000)</script>";
		} elseif (preg_match("/^(link) /i",$op)) {
			$pageToLoadAr=explode(" ",$op);
			$msg.=" <br><br><a href='{$pageToLoadAr[1]}' class='btn'><span class='icon-angle-right'></span> {Go on...}</a>";
		}
		$this->setKey("##msg##",$msg);
		$this->setKey("##class##",$class);
	}

}

