<?php
/*
	this script serve ads
*/
    
$bots = array('googlebot','bingbot',"slurp","duckduckbot","baiduspider","yandexbot","sogou","exabot","konqueror","facebot","applebot","ia_archiver","adsbot","msnbot","megaindex","ahrefsbot");

//	prevents fake views by bot
$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
foreach($bots as $bot) if(strstr($agent,$bot)) die;

if(!stristr( ini_get('disable_functions'), "set_time_limit")) set_time_limit ( 30 );
$root="";

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
if(!isset($_GET['test_id'])) header('Content-Type: text/javascript; charset=UTF-8');
if (function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Rome');

include($root."pons-settings.php");
include($root."src/_include/comode.php");

// media folder
DEFINE("BANNERIMAGES",		$root."data/dbimg/media");

// empty banner code to eventually implement anti ad blocker and distinguish between empty banenr and removed banner
DEFINE("EMPTYBANNERCODE",	"<!-- empty -->");

if (!Connessione()) die(); else CollateConnessione();


/*

vignette mode banner info
=========================

	f = id position
	m = mode [ nothing is normal ; v = vignettecontainer ; vm = vignette ad ]

	vignette mode is a bit tricky because of cookie policies
	and going back between javascript and php. Vignette mode
	needs two calls, this is the first that output vignette 
	container. The second is called with m = vm;

*/
if(isset($_GET['f']) && isset($_GET['m']) && $_GET['m']=="v" ) {
	$on = execute_scalar("select count(1) from ".DB_PREFIX."7banner where cd_posizione=".(integer)$_GET['f']." and (fl_stato='L' OR fl_stato='A') AND (dt_giorno1<='".date("Y-m-d")."')");

	if($on > 0) {
		$trigger = isset($_GET['tr']) ? strip_tags($_GET['tr']) : "p a, nav a, h2 a";
		$timer = isset($_GET['tm']) ? (integer)$_GET['tm'] : "5";


		// Javascript that handles the second call that shows the banner.
		?>
		(()=>{
			var amb_goto = "";

			var controves = (l) => {
				var closemeX=document.getElementById('closemexVIGNA');
				var q = parseInt(closemeX.innerText,10);
				if(isNaN(q)) q = 1;
				if (q<=1) {
					closemeX.setAttribute("href",l);
					closemeX.innerHTML = "X";
				} else {
					q--;
					closemeX.innerHTML = q;
					setTimeout(() => {controves(l)},1000);
				}
			}

			var A = document.querySelectorAll("<?php echo $trigger;?>");

			for (let i = 0; i < A.length; i++) {
				// skip AdAdmin banners
				if( A[i].closest('div') && A[i].closest('div').hasAttribute("id") && A[i].closest('div').getAttribute("id").startsWith("AADIV") ) continue;

				A[i].addEventListener('click', (e) => {
					e.preventDefault();
					let obj = e.target || e.srcElement;
					let l=obj.getAttribute("href");

					if(l.substring(0,1)!="#") { 
						amb_goto = l;
						if(document.body.contains(document.getElementById('amb_vignettazza')) ) {
							document.getElementById('amb_vignettazza').remove();
						}
					 
						let div = document.createElement("div");
						div.style.display = 'none';
						div.id = 'amb_vignettazza';
						divContent = "<style>#overlaybannerVIGNA img {width:100%;height:auto}#overlaybannerVIGNA {position:fixed;top:0;left:0;z-index: 99999999999;width:100vw;height:100vh;background-color:rgba(0,0,0,.9)}#overlaybannerVIGNA div.picVIGNA {display:block;position:absolute;top: 50%;transform: translate(-50%, -50%);left: 50%;width: 80%;max-width:80%}#closemexVIGNA {font-family:sans-serif;border:1px solid #ffffff;position:absolute;top:15px;right:30px;text-decoration:none;font-size:15px;display:inline-block;width:30px;height:30px;line-height:30px;text-align:center;background-color:#000000;color:#ffffff;border-radius:50%}@media only screen and (min-width: 1024px) {#overlaybannerVIGNA div.picVIGNA{max-width:50%!important}}</style><span id='overlaybannerVIGNA'><div id='sTTVIGNA' class='picVIGNA'></div><a href='#' id='closemexVIGNA'><?php echo $timer;?></a></span>";
						div.innerHTML = divContent;
						
						document.body.append( div );
						controves(l);

						<?php
						echo printJavascript(array("cookie reader"));
						?>
						var s = document.createElement("script");
						s.src = "<?php echo WEBURL;?>/ser.php?t=sTTVIGNA"+String.fromCharCode(38)+"f=<?php echo (integer)$_GET['f'];?>"+String.fromCharCode(38)+"m=vm"+String.fromCharCode(38)+"psc=" + psc;
						document.head.appendChild(s);
						
					}

				});

			}

			

		})();
		<?php
	}
	die;
}





/*
	standard banner call with:
		f = position id
		t = div target
		psc = adadmin cookies

	(this code handles also the second call of vignette mode)

*/
if(isset($_GET['f']) && isset($_GET['t'])  ){
	$f = (integer)$_GET['f'];
	if (isset($_REQUEST['time'])) usleep($_REQUEST['time'] * 1000);
	$psc = ( isset ( $_GET['psc'] ) && preg_match("/^(([0-9]*),([0-9]*))*$/", $_GET['psc']) ) ? $_GET['psc'] : "";
	$psc = explode(",",$psc);

	$output = showBanner($f, "yes",null,$psc);
	$banner = $output[0];
	$pscUpdate = $output[1];
	$iframe_id = $output[2];

	//
	// empty vignette banner should not open
	// the layer, but should still handle cookie
	// for frequency cap limitation.
	if ( isset($_GET['m']) && $_GET['m']=="vm" ) {
		if(strlen($banner)!=0 && $banner!=EMPTYBANNERCODE) {
			echo "document.getElementById('amb_vignettazza').style.display='block';";
		} else {
			echo "if(amb_goto!='') document.location.href = amb_goto;";
		}
	}


	$s = str_replace("\n","",$banner);
	$s = str_replace("\r","",$s);

	/* output minify js script, the original source is in my dev repository in js-originale.php */
	echo printJavascript(array("set get cookie","resize iframe","set HTML","autorefresh"), $iframe_id );
	echo "amb_sH( document.getElementById('". $_GET['t']."'),'".str_replace("'","\'",$s)."', true);";
	if($pscUpdate<>'') echo "var amb_tc=amb_gC('$pscUpdate'); amb_sC('$pscUpdate', (amb_tc+1), 3);";
	die;
}




if(isset($_GET['f'])) { /* standard banner (old installations without target div, allows document.write) */

	$f = (integer)$_GET['f'];
	if (isset($_REQUEST['time'])) usleep($_REQUEST['time'] * 1000);
	$banner = showBanner($f);
	

	//echo $banner[0];
	//die;

	// If there is not target div, it's an old banner and need document.write
	
	$banner[0] = str_replace("+","%20",urlencode($banner[0]));
	echo "document.write ( unescape(\"".$banner[0]."\") );";
	die;
}

if(isset($_GET['id'])) { /* code for old installation with standard banner called by ID, it doesn't update rotation */

	echo "document.write('<div style=\"color:red;padding:50px;background:yellow;text-align:center;\">Not a valid call now (2).</div>');";
	die;
}

if(isset($_GET['flp'])) { /* link position, extract the click tag of the banner in the specified position and update rotation */

	$f = (integer)$_GET['flp'];
	if (isset($_REQUEST['time'])) usleep($_REQUEST['time'] * 1000);
	$banner = showBanner($f);
	if(isset($banner[3])) {
		$url = $banner[3];
		header("Location: ".$url);
	}
	die;
}



if(isset($_GET['test_id'])) { /* code to show the banner in backend, it doesn't update rotation */


	$id = (integer)$_GET['test_id'];
	$banner = showBanner(null,"no",$id);
	echo "<html lang=\"en-US\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Testing banner id #".$id."</title><meta name='robots' content='index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' /></head><body>";
	echo $banner[0];
	echo "</body></html>";
	die;
}


/**
 * function that outputs the code of the banner
 * 
 * @param string $image
 * @param int $xx
 * @param int $yy
 * @param string $alternative_pic
 * @param string $href
 * @param string $target
 * 
 * @return string
 */
function LinkBanner($image,$xx=300,$yy=250,$alternative_pic="",$href="",$target="_blank") { /*linka img. Usata nei banner*/
	$anchor="<a rel=\"nowfollow\" href=\"".$href."\"" . ($target ? " target=\"".$target."\" ":"").">";

	if(preg_match("/\.zip$/i",$image)) {
		//
		// HTML5 banner in a folder
		// output an iframe which call the index.html file inside the folder
		// it can be responsive iframe if xx=-1
		//
		if($xx==-1) $xx="100%"; // else $xx=$xx."px";
		if(!isset($_GET['t'])) {
			// fallback old installations without div target
			$link ="<iframe src=\"".$href."\" style=\"border:0;display:block;margin:0 auto;overflow:hidden\" scrolling=\"no\" width=\"".$xx."\" height=\"".$yy."\" allow=\"autoplay\" allowfullscreen include></iframe>";
		} else {
			$id_iframe = safeName($image);

			// new correct version with target div by id and resizeIframe
			$link ="<iframe id='".$id_iframe."' src=\"".$href."\" style=\"border:0;display:block;margin:0 auto;overflow:hidden\" scrolling=\"no\" width=\"".$xx."\" allow=\"autoplay\" allowfullscreen include></iframe>";
		}
	} else {

		if($xx==-1) $d="width=\"100%\""; else $d="width=\"".$xx."\" height=\"".$yy."\"";
		$link=$anchor."<img src=\"".$image."\" ".$d." /></a>";
	}
	return $link;
}

function get_psc($psc,$idbanner) {
	for($i=0;$i<count($psc);$i++) {
		if($i%2==0 && $psc[$i]==$idbanner) {
			//die($psc[$i+1]);
			return $psc[$i+1];
		}
	}
	return 0;
}

function GetBanner($posizione,$conta='yes',$id=0,$psc="") {
	$d = date( "Y-m-d");
	global $conn;
	$cookiname = "";
	
	if($posizione && !$id) {
		/*
			normal behaviour, banner called by position
		*/
		$sql="SELECT * FROM ".DB_PREFIX."7banner WHERE (fl_stato='L' OR fl_stato='A') AND (dt_giorno1<='{$d}') AND (cd_posizione='{$posizione}') ORDER BY id_banner DESC";
	} elseif( $id) {
		/*
			old beahvaiour, banner called by id
		*/
		$sql="SELECT * FROM ".DB_PREFIX."7banner WHERE  id_banner='".$id."'";
	}
	$result=$conn->query($sql) or die($conn->error."sql='$sql'<br>");
	if ($result->num_rows == 0) return array("","");
	//
	// extract the banner to show
	$l=0;
	$primo = null;
	$vecchio = null;
	$esce = null;
	while ($r=$result->fetch_array()) {


		if($r['nu_maxday']>0 && $r['dt_maxday_date']==date("Y-m-d") && $r['nu_maxday_count']>$r['nu_maxday']) {
			/*
				if daily views completed skip
			*/


		} else {

			/* if geoip configuration check with geo ip database */
			if($r['de_city']=="-" || $r['de_city']=="ALL") $r['de_city'] = "";
			if($r['de_country']=="-" || $r['de_country']=="ALL") $r['de_country'] = "";
			if($r['de_region']=="-" || $r['de_region']=="ALL") $r['de_region'] = "";
			if( $r['de_city']!="" || $r['de_country']!="" || $r['de_region']!="") {

				$row = getIP2LocationRow( getIP() );
				$keep = false;
				if(is_array($row)) {
					if ( $r['de_city'] !="" && $r['de_city']==$row['city_name'] && $r['de_region']==$row['region_name']) $keep = true; 
					if ( $r['de_city'] =="" && $r['de_region'] !="" && $r['de_region']==$row['region_name'] && $r['de_country']==$row['country_name']) $keep = true; 
					if ( $r['de_region'] =="" && $r['de_country'] !="" && $r['de_country']==$row['country_name']) $keep = true; 
				}
			} else $keep=true;


			/* if redux factgor apply it*/
			if($keep == true) {
				if($r['nu_redux']>0) {
					// 0 no reduction
					// 1 reduction by 25%
					// 2 reduction by  50%
					// 3 reduction by  75%
					if( rand(0,98) < $r['nu_redux'] ) {
						$keep = false;
					}
				}
			
			}

			/* device limitation  */
			if($keep == true) {
				if($r['nu_mobileflag']>0) {
					$mobile = is_mobile();
					if( ( $r['nu_mobileflag']==2 && !$mobile ) || ( $r['nu_mobileflag']==1 && $mobile ) ) {
						$keep = false;
					}
				}
			}

			/* os limitation  */
			if($keep == true) {
				if($r['se_os']) {
					$os = is_os( explode(",",$r['se_os']) );
					if( !$os ) {
						$keep = false;
					}
				}
			}

			/* frequency cap limitation */
			if($keep == true && $r['nu_cap']>0) {

					// it's a banner limited by frequency nu_cap times per user per day
					$contatore = get_psc( $psc, $r['id_banner']);


					if($r['nu_cap'] > (integer)$contatore) {
						$keep=true;
					} else {
						$keep=false;
					}

			}

			if($keep) {

				if (!$primo) $primo=$r;							// the first record extracted
				if ($vecchio && !$esce) $esce=$r;				// found the last one
																// set this one to be shown
				if ($r["fl_stato"]=='L') {$vecchio=$r; $l++;}	// the last viewed, count how many has L flag
				
			}

		}
	}
	$result->free();
	if (!$esce) $esce=$primo;		/* "esce" is the first after L, if it doesn't exists take "primo" */
	if (!$vecchio) $vecchio=$esce;  /* if there isn't the last viewed, then the last one it's "esce" */

	if(!$esce) return array("","");

	if($esce['nu_cap']>0) {
		// it's a banner limited by nu_cap times parameter
		$cookiname = "adcapban" . $esce['id_banner'];
	}

	// FIX L
	$fl='A';


	$gio=GetTimeStamp($vecchio["dt_giorno2"])-GetTimeStamp($d);
	if ($gio<0) $fl='S';

	if($vecchio['nu_maxtot']>0 && $vecchio['nu_maxtot']<=$vecchio['nu_pageviews']) $fl='S';
	if($vecchio['nu_maxclick']>0 && $vecchio['nu_maxclick']<=$vecchio['nu_clicks']) $fl='S';

	if(!$id) {
		/* flag rotation and count views */

		$sql1="UPDATE ".DB_PREFIX."7banner SET fl_stato='$fl' WHERE id_banner='".$vecchio["id_banner"]."'";
		$res1=$conn->query($sql1) or die($conn->error."sql1='$sql1'<br>");

		if ($fl=='S') {
			$sql11="UPDATE ".DB_PREFIX."7banner SET dt_giorno2=now() WHERE id_banner='".$vecchio["id_banner"]."'";
			$res11=$conn->query($sql11) or die($conn->error."sql11='$sql11'<br>");
		}
		if (($vecchio["id_banner"]!=$esce["id_banner"])or($l==0)) {
			if($esce["id_banner"]) {
				$fl='L';
				$sql2="UPDATE ".DB_PREFIX."7banner SET fl_stato='$fl' WHERE id_banner=".$esce["id_banner"];
				$conn->query($sql2) or die($conn->error."sql2='$sql2'<br>");
			}
		}

		if($conta=='yes') {
			if($esce["id_banner"]) {
				$sql2b="UPDATE ".DB_PREFIX."7banner SET ";
				if($esce['dt_maxday_date']!=date("Y-m-d")) $sql2b.="dt_maxday_date='".date("Y-m-d")."',nu_maxday_count=0,";
				$sql2b.="
					nu_pageviews = nu_pageviews + 1,
					nu_maxday_count = nu_maxday_count + 1
					WHERE id_banner=".$esce["id_banner"];

				$conn->query($sql2b) or die($conn->error."sql2b='$sql2b'<br>");
			}
		}

		$id = $esce["id_banner"];

	} else {
		/*
			if there is id, count directly and don't rotate
		*/
		if($conta=='yes') {
			$sql2c="UPDATE".DB_PREFIX."7banner SET ";
			if($esce['dt_maxday_date']!=date("Y-m-d")) $sql2c.="dt_maxday_date='".date("Y-m-d")."',nu_maxday_count=0,";
			$sql2c.="
				nu_pageviews = nu_pageviews + 1,
				nu_maxday_count = nu_maxday_count + 1
				WHERE id_banner='".$id."'";
			$conn->query($sql2c) or die($conn->error."sql2c='$sql2c'<br>");
		}
		$posizione = $esce['cd_posizione'];

	}


	// save stats data on 7banner_stats
    // ----------------------------------------------------------------------
    // get referrer to filter data in new dashboard a
    if(isset($_SERVER['HTTP_REFERER'])){
        $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    } else $host ='';
	$q = execute_scalar("SELECT id_day FROM ".DB_PREFIX."7banner_stats WHERE id_day='".$d."' AND cd_banner='".$id."' AND de_referrer='".addslashes($host)."' AND cd_posizione='".$posizione."' limit 0,1");
	if($q==$d) $conn->query("UPDATE ".DB_PREFIX."7banner_stats SET nu_pageviews=nu_pageviews+1 WHERE id_day='".$d."' 
        AND cd_banner='".$id."'
        AND cd_posizione='".$posizione."'
        AND de_referrer='".addslashes($host)."'");
	else $conn->query("INSERT IGNORE INTO ".DB_PREFIX."7banner_stats (id_day,nu_pageviews,nu_click,cd_banner,cd_posizione,de_referrer) VALUES ('$d',1,0,'".$id."','".$posizione."','".addslashes($host)."')");
    // ----------------------------------------------------------------------

	return array( $esce, $cookiname);
}

function safeName($n) {
	return preg_replace("/[^A-Za-z0-9_]/","_",$n);
}

function showBanner($posizione,$conta='yes',$id=0, $psc=array()) {
	// if $conta='yes' then increase views counter
	// else don't. It's used in old codes and can be used to see if there
	// is a banner available without showing it. 
	//
	// TO BE CHECKED: maybe it's used in overlay banners (05/05/2021)
	//

	if(!$id) {
		$arf=GetBanner($posizione,$conta, null, $psc);
		$f = $arf[0];
		$fcookie = $arf[1];

	} else {

		$arf=GetBanner(null,$conta,$id, $psc);
		$f = $arf[0];
		$fcookie = $arf[1];

	}

	// can be used to create a personalized AD BLOCK and distinguish between a really empty banenr position or a blocked one
	if (!is_array($f) || $f[0]=="") {
		// fallback
		// ---------------------
		// if there are no banners available for this position look for the banner sperified in 7psizioni.cd_fallback
		// and force a view with that code.
		$sql = "SELECT cd_fallback FROM ".DB_PREFIX."7banner_posizioni WHERE id_posizione='".$posizione."'";
		$id = execute_scalar($sql);
		if($id > 0) {
			$arf=GetBanner($posizione,"yes",$id, array());
			$f = $arf[0];
			$fcookie = $arf[1];
			if($f=="") return array(EMPTYBANNERCODE,"","");
		} else {
			return array(EMPTYBANNERCODE,"","");
		}
	}

	$dir = BANNERIMAGES."/";
	$pics = loadbannerfile($dir,$f['id_banner'].'_', array('gif','png','jpg','zip','jpeg'));

    // the iframe name if needed
    $HTML5iframename = "";

    $encoded_link = encrypt_bannerlink($f['id_banner']);

	if ($f['de_codicescript']) {
		//
		// if script make some replaces and output code
		//
		$s = str_replace("[TIMESTAMP]",date("YmdHis"),$f['de_codicescript']);
		$s = str_replace("[RANDOM]",rand(10000,99999).date("YmdHis"),$s);
		$s = str_replace("[ID]",$f['id_banner'],$s);
		$s = str_replace("[LINK]",trim($f['de_url']),$s);
		$s = str_replace("[TITOLO]",htmlspecialchars(trim($f['de_nome'])),$s);						 // BACK COMPATIBILY
		$s = str_replace("[TITLE]",htmlspecialchars(trim($f['de_nome'])),$s);
		$s = str_replace("[TARGET]",htmlspecialchars(trim($f['de_target'])),$s);
		$s = str_replace("[TRACKLINK]",htmlspecialchars( $encoded_link ),$s);  // BACK COMPATIBILY
		$s = str_replace("[CLICKTAG]",htmlspecialchars( $encoded_link ),$s);

		$bannerurl = $encoded_link;

		for($i=0;$i<count($pics);$i++) $s = str_replace("[IMG".$i."]",WEBURL . "/" . $pics[$i],$s);

		$n = "banner_".$f['id_banner'];

	} else {

		$n_alternate = "";
		$n = array_pop( $pics );
		$n = WEBURL . "/" .$n;

        if(preg_match("/\.zip$/i",$n)) {
            // ZIP HTML5 banner
            $bannerurl = WEBURL."/data/dbimg/media/".$f['id_banner']."/index.html";
            $HTML5iframename = safeName($n);
        } else {
            $bannerurl =  $encoded_link;
        }

		if ($n!="") {
			$s = LinkBanner(
				$n,
				$f['nu_width'],$f['nu_height'],
				$n_alternate,
				$bannerurl,
				$f['de_target']
			);
		} else {
			$s = "";
		}
	}
	return array($s, $fcookie, $HTML5iframename,$bannerurl);
}

function loadbannerfile($dir,$prenome,$arext) {
	$c = 0;
	$a=array();
	if (is_dir($dir) && $dh = opendir($dir)) {
		while (($file = readdir($dh)) !== false) {
			$ext = substr(strrchr($file, '.'), 1);
			if(in_array($ext,$arext)) {
				if(strpos(" ".$file,$prenome)==1) {
					$a[$c]['nome']=$dir.$file;
					$p = (integer)preg_replace("/[^0-9]/","",stristr($file,"_"));
					$a[$c]['posizione']=$p;
					$c++;
				}
			}
		}
		closedir($dh);
	}
	$a = array_key_multi_sort($a);
	$b = array();
	for($i=0;$i<count($a);$i++) $b[$i]=$a[$i]['nome'];
	return $b;
}



function printJavascript( $ar, $iframeid = "") {
	// the original source is on dev server in js-originale.php
	$out = "";
	foreach($ar as $a) {
		if($a=="autorefresh") {
			/* TBD
			$out.='if("function"!=typeof amb_psc) {function amb_psc(){var k=decodeURIComponent(document.cookie),ca=k.split(";"),psc="";for(var i=0;i<ca.length;i++){var c=ca[i];while(c.charAt(0)==" ") c=c.substring(1);if (c.indexOf("adcapban")==0)psc+=(psc==""?"":",")+c.replace("adcapban","").replace("=",",");}return psc;}};';
			$out.='setTimeout(function(){
				var psc = amb_psc();
				var s = document.createElement("script");s.src = "https://www.zepsec.com/amb/ser.php?t=AADIV32"+String.fromCharCode(38)+"f=32"+String.fromCharCode(38)+"psc=" + psc;document.head.appendChild(s);
			},5000);';
			*/
			
		}

		if ($a=="resize iframe" && $iframeid) {
			// adjusting height of banner from postMessage sent by the banner HTML5 in an iframe
			$out.='if("function"!=typeof amb_rI)function amb_rI(n){try{n.height=n.contentWindow.document.body.scrollHeight}catch(e){}};'; // OLD FUNCTION
			$out.='var amb_eM = window.addEventListener ? "addEventListener" : "attachEvent";'.
				'var amb_er = window[amb_eM];'.
				'var amb_msE = amb_eM == "attachEvent" ? "onmessage" : "message";';
			$out.='amb_er(amb_msE,function(e) {console.log("parent received message!:  ",e.data);document.getElementById("'.$iframeid.'").height = e.data + "px";},false);';
		}
		if ($a=="set get cookie") {
			$out.='if("function"!=typeof amb_sC){function amb_sC(e,t,n){var o=new Date;o.setTime(o.getTime()+24*n*60*60*1e3);var r="expires="+o.toUTCString();document.cookie=e+"="+t+";"+r+";path=/"}function amb_gC(e){for(var t=e+"=",n=decodeURIComponent(document.cookie).split(";"),o=0;o<n.length;o++){for(var r=n[o];" "==r.charAt(0);)r=r.substring(1);if(0==r.indexOf(t))return parseInt(r.substring(t.length,r.length),10)}return""}};';
		}
		if ($a=="set HTML") {
			$out.='if("function"!=typeof amb_sH)function amb_sH(e,n,t){t&&(e.innerHTML="");var i=document.createElement("div");if(i.innerHTML=n,0!==i.children.length)for(var r=0;r<i.children.length;r++){for(var a=i.children[r],l=document.createElement(a.nodeName),d=0;d<a.attributes.length;d++)l.setAttribute(a.attributes[d].nodeName,a.attributes[d].nodeValue);if(0==a.children.length)switch(a.nodeName){case"SCRIPT":a.text&&(l.text=a.text);break;default:a.innerHTML&&(l.innerHTML=a.innerHTML)}else amb_sH(l,a.innerHTML,!1);e.appendChild(l)}else e.innerHTML=n};';
		}
		if ($a=="cookie reader") {
			$out.='for(var k=decodeURIComponent(document.cookie),z=k.split(";"),psc="",i=0;i<z.length;i++){for(var c=z[i];" "==c.charAt(0);)c=c.substring(1);0==c.indexOf("adcapban")&&(psc+=(""==psc?"":",")+c.replace("adcapban","").replace("=",","))};';
		}
	}
	return $out;

}


?>