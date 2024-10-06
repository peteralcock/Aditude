<?php

//
// require classes to send mail with SMTP
require 'componenti/PHPMailer/src/Exception.php';
require 'componenti/PHPMailer/src/PHPMailer.php';
require 'componenti/PHPMailer/src/SMTP.php';

$root="../";

$public=true;

include($root."src/_include/config.php");

$msg = setVariabile("msg","");
$email = setVariabile("email","");
$pass1 = setVariabile("pass1","");
$pass2 = setVariabile("pass2","");
$code = setVariabile("code","");
$html = "";

//
// try to make login with data in post
if (!$login->logged()) {

	// if fails output login form
	$html = $login->getResetForm($msg,$email,$pass1,$pass2,$code);

} else {
	header("Location: index.php");
	die;
}

echo $html;

?>