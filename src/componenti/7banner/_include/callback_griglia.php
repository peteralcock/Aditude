<?php
/*
	functions for grid list
*/



//
// show banner duration
function  mostralimiti($p) {
	$p2 = explode("|",$p);
	$out = "";
	if($p2[0] > 0 && $p2[0]<20000) $out .= number_format($p2[0])." {days}<br>";
	if($p2[1] > 0) $out .= number_format($p2[1])." {views}<br>";
	if($p2[2] > 0) $out .= number_format($p2[2])." {clicks}<br>";
	return "<span class='piccolo'>".$out."</span>";
}

//
// position
function  posizione($p) {
	$p2 = explode("|^",$p);
	$out = "";
	return "<span class='piccolo'>[ ".$p2[3]." ] · " . $p2[0]."</span>";	
}


//
// banner image preview or icon
// banner title and link to edit
function imageAndTitle($p) {
	global $obj,$root,$session;
	$p2 = explode("|^",$p);
	$pics = loadgallery( $root."data/dbimg/media/" ,$p2[2].'_',"","array");
	$c = "img"; 
	
	if(isset($pics[0][0])) {
		if(stristr($pics[0][0],".zip")) {
			$n = "<span class='icon-folder'></span>"; 
		} else {
			$n = "<img src='".$pics[0][0]."' class='".$c."'/>"; 
		}
	} else {
		$n = "<span class='icon-code'></span>"; 
	}
	
	$link = "<a href='".$root."ser.php?test_id=".$p2[2]."' title=\"{Test banner output}\" target='_blank'>".$n."</a>";
	$linkedit = "".$p2[0]."";


	//
	// the user has the rights to edit the banner?
	// it depends on the banner status
	// if($session->get("idprofilo")>5 ) {
		$linkedit = "<a href=\"index.php?op=modifica&id=".$p2[2]."\" title=\"{Edit}\">".$p2[0]."</a>";
	// } else {
	// 	$hasOrder = execute_scalar("select count(1) from 7banner_ordini where cd_banner = '".$p2[2]."'",0);
	// 	if($hasOrder > 0 || $p2[1] == 'D') {
	// 		$linkedit = "<a href=\"index.php?op=modifica&id=".$p2[2]."\" title=\"{Edit}\">".$p2[0]."</a>";
	// 	} 
	// }

	// if ( ($session->get("idprofilo")==5 && PAYMENTS=="ON" && ($p2[1] == 'D' ||  $p2[1] == 'W' ||  $p2[1] == 'M') ) ||
	// 	( $session->get("idprofilo")>5 ) ) {
	// 		$linkedit = "<a href=\"index.php?op=modifica&id=".$p2[2]."\" title=\"{Edit}\">".$p2[0]."</a>";
	// }
	


	return "<div class=\"td\"><div class=\"pic\">".$link."</div><span class='nom'>".$linkedit."</span></div>";
}


//
// banner status
// $p => data from db
// $addAction => normally true, it is false from dashboard tables
function statofut($p, $addAction = true ) {
	$p2 = explode("|^",$p);
	$banner = new Banner();
	$stati = $banner->getStatusLabels("css");
	if(($p2[0] == "L" || $p2[0] == "A")  && $p2[1] > date("Y-m-d")) $p2[0] = "F";
	$out = $stati[$p2[0]];
	if($addAction && in_array($p2[0],array("A","P","L"))) {
		if ($p2[0]=='P') {
			$out = str_replace("</span>", "<a href=\"javascript:;\" onclick=\"setStato('go',".$p2[2].")\" class='go'>{Go}</a>" . "</span>",$out);
		} else {
			$out = str_replace("</span>", "<a href=\"javascript:;\" onclick=\"setStato('pause',".$p2[2].")\" class='pausa'>{Pause}</a>" . "</span>",$out);
		}
	}
	return $out;
}


function showClientName($p) {
	$p2 = explode("|^",$p);
	return $p2[0]."<div class='small'>".$p2[1]."</div>";
}

?>