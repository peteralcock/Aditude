<?php

$root="../../../";
include($root."src/_include/config.php");
include("_include/posizioni.class.php");

if(!isset($_GET["codeswitch"])) die;

if($_GET["codeswitch"]=="1" ) {
	// OLD CODE (document.write compatibility)
	?><script src="<?php echo WEBURL;?>/ser.php?f=<?php echo $_GET['id'];?>"></script><?php
}


if($_GET["codeswitch"]=="0") {

	if(isset($_GET["vignette"]) && $_GET["vignette"]=="1") {
		// VIGNETTE
		?><script>/* <?php echo $_GET['label'];?> */ var k=decodeURIComponent(document.cookie),ca=k.split(';'),psc="";for(var i=0;i<ca.length;i++){var c=ca[i];while(c.charAt(0)==' ') c=c.substring(1);if (c.indexOf("adcapban")==0)psc+=(psc==""?"":",")+c.replace("adcapban","").replace("=",",");}var s = document.createElement("script");s.src = "<?php echo WEBURL;?>/ser.php?m=v"+String.fromCharCode(38)+"tm=<?php echo $_GET['timer'];?>"+String.fromCharCode(38)+"tr=<?php echo urlencode($_GET['trigger']);?>"+String.fromCharCode(38)+"f=<?php echo $_GET['id'];?>"+String.fromCharCode(38)+"psc=" + psc;document.head.appendChild(s);</script><?php

	}  else {
		// STANDARD POSITION
		?><div id="AADIV<?php echo $_GET['id'];?>"></div><script>/* <?php echo $_GET['label'];?> */ var k=decodeURIComponent(document.cookie),ca=k.split(';'),psc="";for(var i=0;i<ca.length;i++){var c=ca[i];while(c.charAt(0)==' ') c=c.substring(1);if (c.indexOf("adcapban")==0)psc+=(psc==""?"":",")+c.replace("adcapban","").replace("=",",");}var s = document.createElement("script");s.src = "<?php echo WEBURL;?>/ser.php?t=AADIV<?php echo $_GET['id'];?>"+String.fromCharCode(38)+"f=<?php echo $_GET['id'];?>"+String.fromCharCode(38)+"psc=" + psc;document.head.appendChild(s);</script><?php
	}

}


if($_GET["codeswitch"]=="2") {
	
	?><?php echo WEBURL;?>/ser.php?flp=<?php echo $_GET['id'];?><?php

}

?>