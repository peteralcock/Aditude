<?php

class pezzoDelForm {
	var $name = "";
	var $value = "";
	var $attributes = "";
	var $obbligatorio = 0;
	var $label = "***";		//used in error msg
	var $onFocusString = "";
	var $onBlurString = "";
    var $tipo = "";
    var $showValue = false;
    var $extraHtml="";

	function __construct() {
		
	}

	function gettag() {
		$fuori="<input type=\"hidden\" name=\"{$this->name}\" id=\"{$this->name}\" value=\"{$this->value}\" {$this->attributes}>".($this->showValue?$this->value:"").$this->extraHtml;
		return $fuori;
	}

	function editaEvidenziato(){

		$this->attributes.=" onfocus=\"{$this->onFocusString}\" onblur=\"{$this->onBlurString}\" ";
	}

}













































































class hidden extends pezzoDelForm {

	function __construct ($name='', $value=0) {
		parent::__construct();
		$this->name=$name;
		$this->value=$value;
	}

}

class intero extends pezzoDelForm {

	function __construct ($name='', $value=0, $maxlength=10, $size=10 ) {
		parent::__construct();
		$this->name=$name;
		$this->attributes.=" maxlength=\"{$maxlength}\" size=\"{$size}\"";
		$this->value=$value;
		$this->label=$this->name;
			}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();

		$fuori="<input type=\"text\" name=\"{$this->name}\" id=\"{$this->name}\" value=\"{$this->value}\" {$this->attributes}>".$this->extraHtml;
		return $fuori;
	}





}

class testo extends pezzoDelForm {

    var $maxlength;
    var $size;
    var $maxlimit = 0; //0 = no limits. XX = max XX chars
    var $attributes= "";
    var $label;

	function __construct ($name='', $value="", $maxlength=null, $size=null ) {
		parent::__construct();
		$this->name=$name;
				$this->value=$value;
		$this->label=$this->name;
		$this->maxlength = $maxlength;
		$this->size = $size;
			}

	// create tags to generate HTML
	function gettag () {
		if($this->maxlength > $this->maxlimit && $this->maxlimit>0 ) $this->maxlength = $this->maxlimit;
		$this->editaEvidenziato();
		$r = ""; $s = "";
		if($this->maxlength) $r.= "maxlength=\"{$this->maxlength}\"";
		if($this->size) $r.= " size=\"{$this->size}\"";
		if($this->maxlimit>0) {$r.=" onkeyup=\"contacaratteri('{$this->name}',{$this->maxlimit})\""; $s="<span id='counter{$this->name}' class='contatore'></span>"; }
		$fuori="<div class='testocontainer'><input type=\"text\" id=\"{$this->name}\" name=\"{$this->name}\" value=\"".str_replace("\"","&quot;",$this->value)."\" {$this->attributes}{$r}>{$s}".$this->extraHtml."</div>";
		return $fuori;
	}

}



class password extends pezzoDelForm {

	function __construct ($name='', $value="", $maxlength=10, $size=10 ) {
		parent::__construct();
		$this->name=$name;
		$this->attributes.=" maxlength=\"{$maxlength}\" size=\"{$size}\"";
		$this->value=$value;
		$this->label=$this->name;
	}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();

		$fuori="<input type=\"password\" name=\"{$this->name}\" value=\"{$this->value}\" {$this->attributes}>";
		return $fuori;
	}

}




class numerointero extends intero {

}

class numerodecimale extends pezzoDelForm {

	var $decimali;

	function __construct ($name='', $value="", $maxlength=10, $size=10, $decimali=2 ) {
		parent::__construct();
		$this->name=$name;
		$this->attributes.=" maxlength=\"{$maxlength}\" size=\"{$size}\"";
		$this->value=$value;
		$this->decimali=$decimali;
		$this->label=$this->name;
	}

	// create tags to generate HTML
	function gettag () {
		$this->onFocusString .= "this.value=parseFloatString(this.value,{$this->decimali});";
		$this->onBlurString .= "this.value=parseFloatString(this.value,{$this->decimali});";
		$this->editaEvidenziato();

		$fuori="<input type=\"text\" id='{$this->name}' name=\"{$this->name}\" value=\"{$this->value}\" {$this->attributes}>";
		return $fuori;
	}

}


class email extends testo {

	function __construct ($name='', $value="", $maxlength=10, $size=10) {
		parent::__construct($name,$value, $maxlength, $size);
	}

}

class urllink extends testo {

	
	function __construct ($name='', $value="", $maxlength=10, $size=10) {
		parent::__construct($name,$value, $maxlength, $size);
			}

	function gettag() {
		$this->editaEvidenziato();

		$fuori="<input type=\"text\" name=\"{$this->name}\" maxlength=\"{$this->maxlength}\" size=\"{$this->size}\" id=\"{$this->name}\" onkeyup=\"
			if (document.getElementById('{$this->name}').value!='') document.getElementById('img{$this->name}').style.display=''; else document.getElementById('img{$this->name}').style.display='none';\"  value=\"{$this->value}\" {$this->attributes}>";
		$fuori.=" <a onclick=\"
			if (document.getElementById('{$this->name}').value!='')
				window.open(document.getElementById('{$this->name}').value);

			void(0);\" title=\"apri link\" href='javascript:void(0);'><span id='img{$this->name}' class='icon-link' ".($this->value=="" ? "style='display:none'":"")."></span></a>";
		return $fuori;
	}

}


class areatesto extends pezzoDelForm {

    var $rows;
    var $columns;
    var $maxlimit = 0; //0 = no limits. XX = max XX chars

	function __construct ($name='', $value="", $rows=null, $columns=null ) {
		parent::__construct();
		$this->name=$name;
				$this->rows=$rows;
		$this->columns=$columns;
		$this->label=$this->name;
		$this->value=$value;
			}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$r = ""; $s = "";
		if($this->rows) $r.= "rows=\"{$this->rows}\"";
		if($this->columns) $r.= " cols=\"{$this->columns}\"";
		if($this->maxlimit>0) {$r.=" onkeyup=\"contacaratteri('{$this->name}',{$this->maxlimit})\""; $s="<span id='counter{$this->name}' class='contatore'></span>"; }
		$fuori="<div class='areatestocontainer'><textarea id=\"{$this->name}\" name=\"{$this->name}\" {$this->attributes} {$r}\">{$this->value}</textarea>{$s}</div>". $this->extraHtml;
		return $fuori;
	}

}


class richtext extends pezzoDelForm {

	function __construct ($name='', $value="", $width="", $height="", $toolbar="") {
		parent::__construct();
		$this->name=$name;
		$this->width=$width;
		$this->height=$height;
		$this->toolbar=$toolbar;
		$this->label=$this->name;
		// this is a patch for controls that break tinymce editor
		$value = str_replace(chr(226).chr(128).chr(168)," ",$value);
		$this->value=$value;
	}

	// create tags to generate HTML
	function gettag () {
		global $root;
		$this->value=(preg_replace("/[\n\r]/","",$this->value));

		if(!$this->toolbar || $this->toolbar=="BasicExt") 
			$tools = "plugins: 'link image code responsivefilemanager textcolor paste',
			toolbar: 'undo redo | bold italic forecolor backcolor | style-p style-h2 style-h3 | link image responsivefilemanager | code |  alignleft aligncenter alignright alignjustify',";
		else $tools = $this->toolbar;

		$fuori="<script>tinymce.init({
			width:{$this->width},
			height:{$this->height},
			menubar : false,
			selector:'textarea.class".$this->name."',
			".$tools."
			external_filemanager_path:'".$root."src/filemanager/',
			filemanager_title:\"Files\" ,
			/*extended_valid_elements: 'span',*/
			external_plugins: { 'filemanager' : '".$root."src/filemanager/plugin.min.js' }
			
		});</script><textarea class='class{$this->name}' id='#{$this->name}' name='{$this->name}'>{$this->value}</textarea>";



		return $fuori;
	}

}

class fileupload extends pezzoDelForm {

	var $showlink=true;	//if true shows link

	function __construct ($name='', $size=30, $value="") {
		parent::__construct();
		$this->name=$name;
				$this->label=$this->name;
		$this->value=$value;
			}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$fuori="<input type='file' id=\"{$this->name}\" name=\"{$this->name}\" {$this->attributes}><input type='hidden' id='{$this->name}_val' name='{$this->name}_val' value='{$this->value}'>";
		if($this->showlink && $this->value!='') {
			$fuori.=" <span id='span_hide_{$this->name}'>(<a href='{$this->value}?x=".rand(1,1000)."'>apri</a>";
			$fuori.=" | <a href=\"javascript:elimina('{$this->value}','{$this->name}')\">elimina</a>)</span>";
		}
		return $fuori;
	}
}


class fileupload2 extends pezzoDelForm {

	var $showLink=false;			//if true shows link
	var $reopenButton=false;
	var $showFilename=true;
	var $showThumbs=true;

	var $uploadDir = "";
	var $maxX= 200;
	var $maxY= 200;
	var $maxKB = 10;
	var $max_files= 1;

	var $valueID = "";

	function __construct ($name='', $val="", $params = array()) {
		parent::__construct();
		$this->name = $name;
		$this->label = $this->name;
		$this->valueID = $val;

		
		
		if(isset($params['showLink']) && $params['showLink']===true) $this->showLink=true;
		if(isset($params['reopenButton']) && $params['reopenButton']===true) $this->reopenButton=true;
		if(isset($params['showFilename']) && $params['showFilename']===false) $this->showFilename=false;
		if(isset($params['showThumbs']) && $params['showThumbs']===false) $this->showThumbs=false;
		if(isset($params['uploadDir']) && $params['uploadDir']!="") $this->uploadDir = $params['uploadDir'];
		if(isset($params['maxY']) && $params['maxY']>0) $this->maxY = (integer)$params['maxY'];
		if(isset($params['maxX']) && $params['maxX']>0) $this->maxX = (integer)$params['maxX'];
		if(isset($params['max_files']) && $params['max_files']>0) $this->max_files = (integer)$params['max_files'];
		
	}

	// create tags to generate HTML
	function gettag () {
		
		$max_files = translateHtml("{Upload max %s files;}");
		$max_files = sprintf($max_files, $this->max_files);
		
		$max_KB = translateHtml("{File size max is %sKb;}");
		$max_KB = sprintf($max_KB, $this->maxKB);

		$pixel = translateHtml("{Image size is %sx%s pixels;}");
		$pixel = sprintf($pixel, $this->maxX, $this->maxY);

		$info = trim($max_files." ".$max_KB." ".$pixel);

				
		if($this->uploadDir>"") $thumbs = loadgallery($this->uploadDir,$this->valueID."_","div1","html",true);
			else $thumbs = "";

		$out = "<div class=\"upload_wrapper\">
					<div class=\"file_container\">
						<input type='file' id=\"{$this->name}\" name=\"{$this->name}\" {$this->attributes}>
						<input type='hidden' id='{$this->name}_val' name='{$this->name}_val' value='{$this->valueID}'>
						<span>{Select file}</span>
					</div>
					<span class='file_name'></span>
					<div class=\"file_info\">".$info."</div>
					".
					($this->reopenButton?"<a href=\"javascript:beforeSaveAndLoad()\" class=\"reopen\">{Save and reopen}</a>":"").
					($this->showThumbs?"<div class='thumbscontainer'>".$thumbs."</div>":"")."
				</div>
			";
			
		if($this->showLink && $this->valueID!='') {

			if($this->uploadDir>"") $ar = loadgallery($this->uploadDir,$this->valueID."_","div1","array",true);
			else $ar = array();
			if(isset($ar[0][0])) {
				$out.=" <span id='span_hide_{$this->name}'>(<a href='{$ar[0][0]}?x=".rand(1,1000)."'>apri</a>";
				$out.=" | <a href=\"javascript:elimina('{$ar[0][0]}','{$this->name}')\">elimina</a>)</span>";
			}

		}
		return $out;
	}
}



class data extends pezzoDelForm {
	var $gg;
	var $mm;
	var $aaaa;
	var $formato;
	var $formname;	//link calendar


	function __construct ($name='', $value="", $formatoIN="gg-mm-aaaa",$formname = "") {
		//"formato" contains the input format
		parent::__construct();
		$value = substr($value,0,10);
		$this->name=$name;
		$this->formato = $formatoIN;
		$this->formname = $formname;
		$token = "-";
		if (!stristr($formatoIN,"-")) $token="/";
		$arData = explode($token,$value);
		if ($formatoIN=="gg{$token}mm{$token}aaaa") {
			$g = $arData[0];	$m = $arData[1];	$a = $arData[2];
		} else if ($formatoIN=="mm{$token}gg{$token}aaaa") {
			$g = $arData[1];	$m = $arData[0];	$a = $arData[2];
		} else if ($formatoIN=="aaaa{$token}mm{$token}gg") {
			$g = $arData[2];	$m = $arData[1];	$a = $arData[0];
		}
		if (strlen($g)<2)$g="0".$g;
		if (strlen($m)<2)$m="0".$m;
		$this->gg = new intero($name."_gg",$g,2,2);
		$this->mm = new intero($name."_mm",$m,2,2);
		$this->aaaa = new intero($name."_aaaa",$a,4,4);

		$this->label=$this->name;
		$this->value=$value;

		//		onblur and onchange compose hidden field
		//		------------------------------------------------------------
		$this->gg->onBlurString .= "Ricomponi{$this->name}()";
		$this->mm->onBlurString .= "Ricomponi{$this->name}()";
		$this->aaaa->onBlurString .= "Ricomponi{$this->name}()";

		$this->gg->attributes.=" onchange=\"Ricomponi{$this->name}()\" class='small'";
		$this->mm->attributes.=" onchange=\"Ricomponi{$this->name}()\" class='small'";
		$this->aaaa->attributes.=" onchange=\"Ricomponi{$this->name}()\" class='small'";

	}

	// create tags to generate HTML
	function gettag () {

		$this->editaEvidenziato();
		$g=$this->gg; 
		$m=$this->mm;
		$a=$this->aaaa;
		if(DATEFORMAT=="dd/mm/yyyy") {
			$fuori=$g->gettag()."/".$m->gettag()."/".$a->gettag();
		} elseif(DATEFORMAT=="mm/dd/yyyy") {
			$fuori=$m->gettag()."/".$g->gettag()."/".$a->gettag();
		} else {
			$fuori=$a->gettag()."/".$m->gettag()."/".$g->gettag();
		}


		$veradata = new hidden($this->name,$this->value);
		$fuori.=$veradata->gettag();
		if ($this->formname) $fuori.=" ".$this->getCalendarLink($this->formname);

		$fuori.="<script>
		function Ricomponi{$this->name}() {
			var g = document.{$this->formname}.{$this->gg->name};
			var m = document.{$this->formname}.{$this->mm->name};
			var a = document.{$this->formname}.{$this->aaaa->name};

			document.{$this->formname}.{$this->name}.value = a.value+'-'+m.value+'-'+g.value;

		}
		</script>
		";

		return $fuori;
	}

	function getCalendarLink($formaname) {
		$g=$this->gg;
		$m=$this->mm;
		$a=$this->aaaa;

		return "
		<script>
			$(function() {
				$( \"#{$this->name}\" ).datepicker({
				  showOn: \"button\",
				  buttonText: \"\",
				  dateFormat: \"yy-mm-dd\",
				  firstDay: 1,
				  defaultDate: \"".$a->value."-".$m->value."-".$g->value."\"
				});
				$( \"#{$this->name}\").on(\"change\",function(){
					c = $( \"#{$this->name}\").val().split( '-' );
					document.{$formaname}.{$g->name}.value=c[2];
					document.{$formaname}.{$m->name}.value=c[1];
					document.{$formaname}.{$a->name}.value=c[0];
				});
			  });
		</script>
		

		";

	}

}

class dataOra extends pezzoDelForm {
	var $gg;
	var $mm;
	var $aaaa;
	var $h;
	var $m;
	var $formato;
	var $formname;	//link calendar


	function __construct ($name='', $value="", $formatoIN="gg-mm-aaaa",$formname = "") {
		//"formato" is input format
		//hour input string is hh:mm (24 hours)
		parent::__construct();
		$this->name=$name;
	    $this->formato = $formatoIN;
		$this->formname = $formname;
		
		if ($value && $value!=""){
			$value = substr($value,0,16); //"31-12-2007 19:03"
			
			$token = "-";
			if (!stristr($formatoIN,"-")) $token="/";
			$arDataOra = explode(" ",$value);
			$arOra = explode(":",$arDataOra[1]);
			$arData = explode($token,$arDataOra[0]);
			if ($formatoIN=="gg{$token}mm{$token}aaaa") {
				$g = $arData[0];	$m = $arData[1];	$a = $arData[2];
			} else if ($formatoIN=="mm{$token}gg{$token}aaaa") {
				$g = $arData[1];	$m = $arData[0];	$a = $arData[2];
			} else if ($formatoIN=="aaaa{$token}mm{$token}gg") {
				$g = $arData[2];	$m = $arData[1];	$a = $arData[0];
			}
			if (strlen($g)<2)$g="0".$g;
			if (strlen($m)<2)$m="0".$m;
			$this->gg = new intero($name."_gg",$g,2,2);
			$this->mm = new intero($name."_mm",$m,2,2);
			$this->aaaa = new intero($name."_aaaa",$a,4,4);

			$h2 = $arOra[0];
			$m2 = $arOra[1];
			if (strlen($h2)<2)$h2="0".$h2;
			if (strlen($m2)<2)$m2="0".$m2;
			$this->h = new intero($name."_h",$h2,2,2);
			$this->m = new intero($name."_m",$m2,2,2);
		}else {
			$this->gg = new intero($name."_gg","",2,2);
			$this->mm = new intero($name."_mm","",2,2);
			$this->aaaa = new intero($name."_aaaa","",4,4);
			$this->h = new intero($name."_h","",2,2);
			$this->m = new intero($name."_m","",2,2);
		}	
		$this->gg->attributes.=' class="small"';
		$this->mm->attributes.=' class="small"';
		$this->aaaa->attributes.=' class="small"';
		$this->h->attributes.=' class="small"';
		$this->m->attributes.=' class="small"';

		$this->label=$this->name;
		$this->value=$value;

		//		onblur and onchangericompone compose hidden field
		//		------------------------------------------------------------
		$this->gg->onBlurString .= "Ricomponi{$this->name}()";
		$this->mm->onBlurString .= "Ricomponi{$this->name}()";
		$this->aaaa->onBlurString .= "Ricomponi{$this->name}()";
		$this->h->onBlurString .= "Ricomponi{$this->name}()";
		$this->m->onBlurString .= "Ricomponi{$this->name}()";

		$this->gg->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->mm->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->aaaa->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->h->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->m->attributes.=" onchange=\"Ricomponi{$this->name}()\"";

	}

	// create tags to generate HTML
	function gettag () {

		$this->editaEvidenziato();
		$g=$this->gg;
		$g->attributes.= $this->attributes;
		$m=$this->mm;
		$m->attributes.= $this->attributes;
		$a=$this->aaaa;
		$a->attributes.= $this->attributes;
		$h2=$this->h;
		$h2->attributes.= $this->attributes;
		$m2=$this->m;
		$m2->attributes.= $this->attributes;

		if(DATEFORMAT=="dd/mm/yyyy") {
			$fuori=$g->gettag()."/".$m->gettag()."/".$a->gettag();
		} elseif(DATEFORMAT=="mm/dd/yyyy") {
			$fuori=$m->gettag()."/".$g->gettag()."/".$a->gettag();
		} else {
			$fuori=$a->gettag()."/".$m->gettag()."/".$g->gettag();
		}


		$veradata = new hidden($this->name,$this->value);
		$fuori.=$veradata->gettag();
		if ($this->formname) $fuori.=" ".$this->getCalendarLink($this->formname);
		$fuori.=" ".$h2->gettag().":".$m2->gettag();

		$fuori.="<script>
		function Ricomponi{$this->name}() {
			var g = document.{$this->formname}.{$this->gg->name};
			var m = document.{$this->formname}.{$this->mm->name};
			var a = document.{$this->formname}.{$this->aaaa->name};
			var h = document.{$this->formname}.{$this->h->name};
			var i = document.{$this->formname}.{$this->m->name};

			document.{$this->formname}.{$this->name}.value = a.value+'-'+m.value+'-'+g.value+' '+h.value+':'+i.value+':00';

		}
		</script>
		";

		return $fuori;
	}

	function getCalendarLink($formaname) {
		$g=$this->gg;
		$m=$this->mm;
		$a=$this->aaaa;

		return "
		<script>
			$(function() {
				$( \"#{$this->name}\" ).datepicker({
				  showOn: \"button\",
				  buttonText: \"\",
				  firstDay: 1,
				  dateFormat: \"yy-mm-dd\",
				  defaultDate: \"".$a->value."-".$m->value."-".$g->value."\"
				});
				$( \"#{$this->name}\").on(\"change\",function(){
					if(!jQuery('#{$g->name}').is('[disabled=disabled]')) {
						c = $( \"#{$this->name}\").val().split( '-' );
						document.{$formaname}.{$g->name}.value=c[2];
						document.{$formaname}.{$m->name}.value=c[1];
						document.{$formaname}.{$a->name}.value=c[0];
						document.{$formaname}.{$this->h->name}.value='00';
						document.{$formaname}.{$this->m->name}.value='00';
					}
				});
			  });
		</script>";

	}



}



// NON USATO better with classes
/*
class dataOra2 extends data {
	var $h;
	var $m;

	function __construct ($name='', $value="", $formatoIN="gg-mm-aaaa hh:ii",$formname = "") {
		//"formato" is input format
		//hour input string is hh:mm (24 hours)
		parent::__construct($name, $value, $formatoIN,$formname);
		// $this->name=$name;
	    // $this->formato = $formatoIN;
		// $this->formname = $formname;
		
		if ($value && $value!=""){
			$value = substr($value,0,16); //"31-12-2007 19:03"
			
			$token = "-";
			if (!stristr($formatoIN,"-")) $token="/";
			$arDataOra = explode(" ",$value);
			$arOra = explode(":",$arDataOra[1]);
			$arData = explode($token,$arDataOra[0]);
			if ($formatoIN=="gg{$token}mm{$token}aaaa") {
				$g = $arData[0];	$m = $arData[1];	$a = $arData[2];
			} else if ($formatoIN=="mm{$token}gg{$token}aaaa") {
				$g = $arData[1];	$m = $arData[0];	$a = $arData[2];
			} else if ($formatoIN=="aaaa{$token}mm{$token}gg") {
				$g = $arData[2];	$m = $arData[1];	$a = $arData[0];
			}
			if (strlen($g)<2)$g="0".$g;
			if (strlen($m)<2)$m="0".$m;
			$this->gg = new intero($name."_gg",$g,2,2);
			$this->mm = new intero($name."_mm",$m,2,2);
			$this->aaaa = new intero($name."_aaaa",$a,4,4);

			$h2 = $arOra[0];
			$m2 = $arOra[1];
			if (strlen($h2)<2)$h2="0".$h2;
			if (strlen($m2)<2)$m2="0".$m2;
			$this->h = new intero($name."_h",$h2,2,2);
			$this->m = new intero($name."_m",$m2,2,2);
		}else {
			$this->gg = new intero($name."_gg","",2,2);
			$this->mm = new intero($name."_mm","",2,2);
			$this->aaaa = new intero($name."_aaaa","",4,4);
			$this->h = new intero($name."_h","",2,2);
			$this->m = new intero($name."_m","",2,2);
		}	
		$this->label=$this->name;
		$this->value=$value;

		//		onblur and onchangericompone compose hidden field
		//		------------------------------------------------------------
		$this->gg->onBlurString .= "Ricomponi{$this->name}()";
		$this->mm->onBlurString .= "Ricomponi{$this->name}()";
		$this->aaaa->onBlurString .= "Ricomponi{$this->name}()";
		$this->h->onBlurString .= "Ricomponi{$this->name}()";
		$this->m->onBlurString .= "Ricomponi{$this->name}()";

		$this->gg->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->mm->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->aaaa->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->h->attributes.=" onchange=\"Ricomponi{$this->name}()\"";
		$this->m->attributes.=" onchange=\"Ricomponi{$this->name}()\"";

	}

	// create tags to generate HTML
	function gettag () {

		$this->editaEvidenziato();
		$g=$this->gg;
		$m=$this->mm;
		$a=$this->aaaa;
		$h2=$this->h;
		$m2=$this->m;

		if(DATEFORMAT=="dd/mm/yyyy") {
			$fuori=$g->gettag()."/".$m->gettag()."/".$a->gettag();
		} else {
			$fuori=$m->gettag()."/".$g->gettag()."/".$a->gettag();
		}


		$veradata = new hidden($this->name,$this->value);
		$fuori.=$veradata->gettag();
		if ($this->formname) $fuori.=" ".$this->getCalendarLink($this->formname);
		$fuori.=" ".$h2->gettag().":".$m2->gettag();

		$fuori.="<script>
		function Ricomponi{$this->name}() {
			var g = document.{$this->formname}.{$this->gg->name};
			var m = document.{$this->formname}.{$this->mm->name};
			var a = document.{$this->formname}.{$this->aaaa->name};
			var h = document.{$this->formname}.{$this->h->name};
			var i = document.{$this->formname}.{$this->m->name};

			document.{$this->formname}.{$this->name}.value = a.value+'-'+m.value+'-'+g.value+' '+h.value+':'+i.value+':00';

		}
		</script>
		";

		return $fuori;
	}

	function getCalendarLink($formaname) {
		$g=$this->gg;
		$m=$this->mm;
		$a=$this->aaaa;

		return "
		<script>
			$(function() {
				$( \"#{$this->name}\" ).datepicker({
				  showOn: \"button\",
				  buttonText: \"\",
				  dateFormat: \"yy-mm-dd\",
				  defaultDate: \"".$a->value."-".$m->value."-".$g->value."\"
				});
				$( \"#{$this->name}\").on(\"change\",function(){
					c = $( \"#{$this->name}\").val().split( '-' );
					document.{$formaname}.{$g->name}.value=c[2];
					document.{$formaname}.{$m->name}.value=c[1];
					document.{$formaname}.{$a->name}.value=c[0];
				});
			  });
		</script>";

	}



}
*/
class submit extends pezzoDelForm {
	var $onclick;

	function __construct ($name='', $value="submit", $onclick="checkForm()" ) {
		parent::__construct();
		$this->name=$name;
		$this->value=$value;
		$this->label=$this->name;
		$this->onclick = $onclick;
	}

	// create tags to generate HTML
	function gettag () {
		$fuori =  "<input type=\"submit\" name=\"{$this->name}\" value=\"{$this->value}\" onClick=\"{$this->onclick}\">";
		return $fuori;
	}
	function gettagimage ($imgurl,$text,$imghref="javascript:checkForm()",$extratags="align=\"absmiddle\" border=\"0\"") {
		$fuori =  "<a href=\"{$imghref}\"><img src=\"{$imgurl}\" {$extratags}>{$text}</a>";
		return $fuori;
	}
}






class optionlist extends pezzoDelForm {

	var $arrayvalori;

	function __construct ($name, $valore='', $arrayvalori=array() ) {
		parent::__construct();
		$this->name=$name;
		$this->value=$valore;
		$this->label=$this->name;
		$this->arrayvalori=$arrayvalori;
		$this->extraHtml="";
	}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$out="";
		$out.="<select {$this->attributes} size=\"1\" name=\"{$this->name}\" id=\"{$this->name}\">";

		foreach($this->arrayvalori as $val=>$avideo) {
			$out.="<option value=\"$val\"";
			if ($this->value==$val) $out.=" selected ";
			$out.=">$avideo</option>\n";
		}
		$out.="</select>".$this->extraHtml;
		return $out;
	}

	/**
	 * load the options from sql to the array for the <select>
	 * 
	 * @param string $sql
	 * @param string $idfield the name of the field in the sql
	 * @param string $labelfield the name of the field to show to the user
	 * @param string $emptylabel the label of the empty "--select--" option
	 * 
	 * @return void
	 */
	function loadSqlOptions( $sql, $idfield, $labelfield, $emptylabel) {
		global $conn;
		$rs = $conn->query($sql) or trigger_error($conn->error." SQL: ".$sql);
		$ar = array();
		if ($emptylabel!="") $ar[""]="--".$emptylabel."--";
		//if($rs->num_rows > 1 || $rs->num_rows == 0) $ar[""]="--".$emptylabel."--";
		while($riga = $rs->fetch_array()) $ar[$riga[$idfield]]=$riga[$labelfield];
		$this->arrayvalori = $ar;
	}


}

class checkboxlist extends pezzoDelForm {

	var $arrayvalori;

	function __construct ($name, $valore='', $arrayvalori =array()) {
		parent::__construct();
		$this->name=$name;
		$this->value=$valore; // array of checked values
		$this->label=$this->name;
		$this->arrayvalori=$arrayvalori;
	}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$out="<div class='checkboxListContainer'>";
		foreach($this->arrayvalori as $val=>$avideo) {
			$out.="<label class='checkbox'><input {$this->attributes} type=\"checkbox\" name=\"{$this->name}[]\" value=\"$val\"";
			if (in_array($val,$this->value)) $out.=" checked ";
			$out.=">$avideo</label>\n";

		}
        $out.="</div>";
		return $out;
	}

}


class radiolist extends pezzoDelForm {

	var $arrayvalori;

	function __construct ($name, $valore='', $arrayvalori =array()) {
		parent::__construct();
		$this->name=$name;
		$this->value=$valore;
		$this->label=$this->name;
		$this->arrayvalori=$arrayvalori;
	}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$out="";
$q = 0;
		foreach($this->arrayvalori as $val=>$avideo) {
			$q++;
			$out.="<label class='radiobutton'><input {$this->attributes} type=\"radio\" name=\"{$this->name}\" id=\"{$this->name}_{$q}\" value=\"$val\"";
			if ($this->value==$val) $out.=" checked ";
			$out.=">$avideo</label>\n";
		}

		return $out;
	}

}

class colorlist extends radiolist {
	function __construct ($name, $valore='', $arrayvalori =array()) {
		parent::__construct($name, $valore, $arrayvalori);
		$this->name=$name;
		$this->value=$valore;
		$this->label=$this->name;
		$this->arrayvalori=empty($arrayvalori) ? 
			Array(
				""=>"transparent",
				"red"=>"red",
				"yellow"=>"yellow",
				"green"=>"green",
				"blue"=>"blue",
				"black"=>"black",
				"orange"=>"orange",
				"purple"=>"purple",
				"greenyellow"=>"greenyellow",
				"khaki"=>"khaki",
				"tomato"=>"tomato",
				"orangered"=>"orangered",
				"violet"=>"violet",
				"pink"=>"pink",
				"turquoise"=>"turquoise",
			) :  $arrayvalori;
	}

	// create tags to generate HTML
	function gettag () {
		return "<div class='colors'>".parent::gettag()."</div>";
	}
}

class checkbox extends pezzoDelForm {

	var $avideo="";
	var $checked;

	function __construct ($name, $valore='', $checked=true ) {
		parent::__construct();
		$this->name=$name;
		$this->value=$valore;
		$this->label=$this->name;
		$this->checked=$checked;
			}

	// create tags to generate HTML
	function gettag () {
		$this->editaEvidenziato();
		$out="";
		$out.="<label><input {$this->attributes} type=\"checkbox\" id=\"{$this->name}\" name=\"{$this->name}\" value=\"{$this->value}\"";
		if ($this->checked) $out.=" checked ";
		$out.="> ".$this->avideo."</label>";

		return $out;
	}

}



class form {

	var $jsCheckList = "";
	var $name;
	var $pathJsLib;
	var $action;
	var $method;
    var $extraAttributes = "";
    var $extraJsFunction = "";

    var $_honeypot = ""; // if present is passed along with the first form field name

	function __construct($name="dati", $honeypot = ""){
		global $root;
		$this->name = $name;
        $this->_honeypot = $honeypot;
		$this->pathJsLib=$root."src/template/controlloform.js?".rand(1,9999);
		$this->action=$_SERVER["PHP_SELF"];
				$this->method="POST";
	}
	function endform(){
		return "</form>";
	}

    function checkHoney(){
        if($this->_honeypot == "") return true;
        return (isset($_POST["nonce"]) ? $_POST["nonce"] : "") ==  md5($this->_honeypot . $this->name);
    }

	function startform(){
        $h="";
        if($this->_honeypot!="") {
            $nonce = md5($this->_honeypot . $this->name);
            $h = "
                var input=document.createElement('input');
                input.setAttribute('name','nonce');
                input.setAttribute('value','".$nonce."');
                input.setAttribute('type','hidden');
                document.{$this->name}.appendChild(input);";
            $h = preg_replace("/([\t\r\n]+| {2,})/","", $h);
        }
		$s = "<script src=\"{$this->pathJsLib}\"></script>";
		$s.="<script>\n";
		$s.="function checkForm() {\n";
		$s.="{$this->extraJsFunction}\n";
		$s.="with(document.{$this->name}) {\n";
		$s.="{$this->jsCheckList}\n";
		$s.="encodeTextAreas(document.{$this->name});\n";
        $s.=$h;
		$s.="submit();\n";
		$s.="}\n";
		$s.="}\n";
		$s.="</script>\n";
		$s.="<form {$this->extraAttributes} action=\"{$this->action}\" method=\"{$this->method}\" name=\"{$this->name}\">";
		return $s;
	}


	function addControllo($obj, $custom_check = "", $custom_msg=""){
		/*
			add javascript controls specified
		*/
		$classe = strtolower(get_class($obj));

		$obj->label = str_replace("\"","&quot;",$obj->label);

		if($custom_check!="") {

			$this->jsCheckList.= "if( {$custom_check} ) { alert (\"{$custom_msg}\", function(){{$obj->name}.focus();}); return; };";

		}

		if($classe == "fileupload" || $classe == "fileupload2") {
			if(!stristr($this->extraAttributes,'enctype="multipart/form-data"')) {
				$this->extraAttributes.= "enctype=\"multipart/form-data\"";
			}
		}
		if($classe=="intero"){
			$this->jsCheckList.="if (!testNumerico({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="testo"){
			$this->jsCheckList.="if (!testCampoTesto({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="password"){
			
			$this->jsCheckList.="if (!testCampoTesto({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;\n";
			$this->jsCheckList.="} else {\n".
				"let strong_password = document.getElementById('strong_password').value =='ON' ? 3 : 0;\n\n";
			$this->jsCheckList.="let tips = checkPasswordStrength({$obj->name}.value,strong_password);\n";
			$this->jsCheckList.="if(typeof(tips)=='string') {	alert ( tips, function(){{$obj->name}.focus();});	return; }
			}\n";

		}
		if($classe=="numerointero"){ // positive
			$this->jsCheckList.="if (!testNumericoIntPos({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="numerodecimale"){
			$this->jsCheckList.="if (!testNumericoDecimale({$obj->name},{$obj->obbligatorio},{$obj->decimali})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="email"){
			$this->jsCheckList.="if (!testEmail({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="url"){
			$this->jsCheckList.="if (!testUrl({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="autocomplete"){
			$this->jsCheckList.="if (!testCampoTesto({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="areatesto"){
			$this->jsCheckList.="if (!testCampoTesto({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="optionlist"){
			$this->jsCheckList.="if (!testCombobox({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="checkboxlist"){
			$this->jsCheckList.="if (!testSerieDiCheckbox(document.{$this->name}.elements['{$obj->name}[]'],{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="checkbox"){
			$this->jsCheckList.="if (!testCheckbox({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="radiolist"){
			$this->jsCheckList.="if (!testRadio({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";	
		}
		if($classe=="data"){
			if ($obj->formato=='gg-mm-aaaa')	$this->jsCheckList.="{$obj->name}.value={$obj->name}_gg.value+'-'+{$obj->name}_mm.value+'-'+{$obj->name}_aaaa.value;";
			if ($obj->formato=='aaaa-mm-gg')	$this->jsCheckList.="{$obj->name}.value={$obj->name}_aaaa.value+'-'+{$obj->name}_mm.value+'-'+{$obj->name}_gg.value;";
			$this->jsCheckList.="if (!testData({$obj->name},{$obj->obbligatorio},'{$obj->formato}')) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}_gg.focus();});";
			$this->jsCheckList.="return;}\n";
		}
		if($classe=="dataora"){
		
			if ($obj->formato=='gg-mm-aaaa')	
				$this->jsCheckList.="{$obj->name}.value={$obj->name}_gg.value+'-'+{$obj->name}_mm.value+'-'+{$obj->name}_aaaa.value+' '+{$obj->name}_h.value+':'+{$obj->name}_m.value;";
			if ($obj->formato=='aaaa-mm-gg')	$this->jsCheckList.="{$obj->name}.value={$obj->name}_aaaa.value+'-'+{$obj->name}_mm.value+'-'+{$obj->name}_gg.value+' '+{$obj->name}_h.value+':'+{$obj->name}_m.value;";
		
			$this->jsCheckList.="if (!testDataOra({$obj->name},{$obj->obbligatorio},'{$obj->formato}')) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}_gg.focus();});";
			$this->jsCheckList.="return;}\n";
			
		}
		if($classe=="urllink"){
			$this->jsCheckList.="if (!testUrl({$obj->name},{$obj->obbligatorio})) {";
			$this->jsCheckList.="let str = _e(\"The field '%s' is wrong.\");";
			$this->jsCheckList.="alert ( str.replace(\"%s\",\"{$obj->label}\"), function(){{$obj->name}.focus();});";
			$this->jsCheckList.="return;}\n";
		}
	}


    


}



