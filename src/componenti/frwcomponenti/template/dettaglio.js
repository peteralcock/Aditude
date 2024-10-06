

	function eliminaf(id) {
		if (confirm("Confermi l'eliminazione definitiva di questa funzionalità?"))
			document.location.href = "index.php?op=eliminaf&id="+id
	}

	jQuery(window).ready(function() {
		$('#urliconamenu').on("change", function() {
			$('#iconshow').attr("class", $(this).val());
		});

        $('#nome').on('input', function() {
            const value = $(this).val().toUpperCase();
            $(this).val(value);
          });
		
		if($('#cd_module').val()>"") {
			$('.panel2 a.annulla').attr("href", "../frwmoduli/index.php?op=modifica&id="+$('#cd_module').val());
		}

    });
      
      

	/*
	function trim(str) {
		// Trimma gli eventuali spazi all'inizio e alla fine della stringa e trasforma tutti gli spazi doppi in spazi singoli
		return str.replace(/\s+/g," ").replace(/^\s+/,"").replace(/\s+$/,"")
	}

	function submitform(){
		with(document.dati){
			if (nome.value == ""){
				alert("Campo nome non inserito")
				nome.focus()
				return
			}

			if (label.value == ""){
				alert("Campo label non inserito")
				label.focus()
				return
			}


			if (descrizione.value == ""){
				alert("Campo descrizione non inserito")
				descrizione.focus()
				return
			}
			if (urlcomponente.value == ""){
				alert("Campo url del componente non inserito")
				urlcomponente.focus()
				return
			}

			submit()
		}
	}
	

		function openWin(winName, urlLoc, w, h, showStatus, isViewer) {
			l = (screen.availWidth - w)/2;
			t = (screen.availHeight - h)/2;
			features = "toolbar=no";		// yes|no
			features += ",location=no";		// yes|no
			features += ",directories=no";	// yes|no
			features += ",status=" + (showStatus?"yes":"no");	// yes|no
			features += ",menubar=no";		// yes|no
			features += ",scrollbars=" + (isViewer?"yes":"no");		// auto|yes|no
			features += ",resizable=" + (isViewer?"yes":"no");		// yes|no
			features += ",dependent";		// close the parent, close the popup, omit if you want otherwise
			features += ",height=" + h;
			features += ",width=" + w;
			features += ",left=" + l;
			features += ",top=" + t;
			winName = winName.replace(/[^a-z]/gi,"_");
			return window.open(urlLoc,winName,features);
		}

	function moveAlbero(id,idpadre){

		window.open("move_popup.php?id="+id+"&idpadre="+idpadre,"mover","width=300,height=300,toolbar=no,scrollbar=auto,resizable=no,status=no")
	}


	function selPag(){
		var code = ""
		code = window.showModalDialog("sel_pagina/index.asp",0,"dialogHeight: 355px; dialogWidth: 530px; dialogTop: ; dialogLeft: px; center: Yes; help: No; resizable: No; scroll: No; status: No;")

		if (code > ""){
			var arrOut = code.split("|")
			document.dati.idpagina.value = arrOut[0]
			paginaass.innerHTML = arrOut[1]
			pag_deselez.style.visibility = "visible"
		}

	}

	function deselPag(){
		document.dati.idpagina.value = 0
		paginaass.innerHTML = "nessuna"
		pag_deselez.style.visibility = "hidden"
	}

*/