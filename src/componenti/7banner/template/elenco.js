/* AUTO UPDATE NUMBERS */
ricor = function(td){
	n0 = parseInt( jQuery(td).html() ); 
	n1 = parseInt( jQuery(td).attr("data-rel") );
	delta = n1 - n0;
	if (delta != 0) {
		var step = delta > 0 ? 1 : 0; // -1 is non sense;
		var timer = Math.floor( 20000 / Math.abs(delta) );
		if(timer < 50) {
			if(delta>0) step++; else step--;     // accelero un po'
			timer = 50;
		}
		n0 = n0 + step;
		if(n0 > 0) {
			jQuery(td).html( n0 );
			setTimeout(function(){
				ricor(td);
			},timer);
		}
	}
}

//
// update table values dynamically (increment numbers!)
function ln () {
	jQuery('#tempdati').load(document.location + ' #tab_7banner',function(){
		jQuery('#tempdati td').each(function(){
			if(jQuery(this).html().indexOf("<img")==-1) {
				var h = jQuery(this).html();
				var c = jQuery(this).attr("id");
				var reg = new RegExp('^([0-9]*)$');
				if(typeof(c)!="undefined" && reg.test(h) && jQuery(this).hasClass("numero")){
					jQuery('.corpo #'+c).attr("data-rel",h);
				} else {
					if(typeof(c)!="undefined") {
						if(h.indexOf("checkbox")==-1) { // not a checkbox
							jQuery('.corpo #'+c).html(h);
						}
					}
				}
			}
		});
		jQuery('#tempdati').html("");

		jQuery('.corpo td.numero').each(function(){
			var h = jQuery(this).html();
			var reg = new RegExp('^([0-9]*)$');
			
			if(reg.test(h)){
				n0 = parseInt(h); 
				n1 = parseInt( jQuery(this).attr("data-rel") );
				if(jQuery(this).html() != n1 ) {
					ricor(this);
				}
			}
		});

	
	
	});
}

jQuery(document).ready(function($) {
	//
	// update every minute
	var m = setInterval(function(){ 
		ln(); 
	}, 60000);
	sel = "#container_7banner .first";
	jQuery("#tab_7banner").css("width","100%");
	jQuery(sel).css("position","relative");
	jQuery(sel).append("<span id='wait'></span>");

	// first update after 10 seconds
	setTimeout(function(){ln();
		jQuery("#wait").html( _e("realtime data") );
	},10000);


	jQuery("#wait").html( "<span class='icon-spin5 animate-spin'></span>" +  _e("analyzing data stream") );


	//
	// call cron every 10 min
	var m2 = setInterval(function(){ 
		$.get("cron.php");
	}, 70000);


	/*
	if(getCookie("adadminNews1")!="done") {
		alert("Hi!<br><b>This is a message for administrators.</b><br>There is a place where you can follow the evolution of this software, discuss about it and ask for new features, share language files and script templates.<br>Follow this <a href='https://github.com/giuliopons/adadmin/discussions' target='_blank'><u><b>github link</b></u></a>.");
		setCookie("adadminNews1", "done", 30);
	}
	*/

} );