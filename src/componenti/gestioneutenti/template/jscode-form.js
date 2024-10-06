

	function trim(str) {
		return str.replace(/\s+/g," ").replace(/^\s+/,"").replace(/\s+$/,"")
	}

	function submitform(){
		with(document.dati){
			if (lausername.value == ""){
				alert("It's missing the Username value.")
				lausername.focus()
				return
			}

			if (lapassword.value == "" && id.value==""){
				alert("It's missing the Password.")
				lapassword.focus()
				return
			}


			if (nome.value == ""){
				alert("It's missing the Name value.")
				nome.focus()
				return
			}
			if (cognome.value == ""){
				alert("It's missing the Surname value.")
				cognome.focus()
				return
			}


			submit()
		}
	}
