
	function trim(str) {
		// Trimma gli eventuali spazi all'inizio e alla fine della stringa e trasforma tutti gli spazi doppi in spazi singoli
		return str.replace(/\s+/g," ").replace(/^\s+/,"").replace(/\s+$/,"")
	}

	function submitform(){
		with(document.dati){
			if (descrizione.value == ""){
				alert("Campo descrizione non inserito")
				descrizione.focus()
				return
			}
			if (!testNumericoIntPos(idprofilo,true)){
				alert("Campo ID non inserito correttamente")
				idprofilo.focus()
				return
			}
			if (label.value == ""){
				alert("Campo label non inserito")
				label.focus()
				return
			}

			submit()
		}
	}

