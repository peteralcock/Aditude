	function confermaDelete(id) {
		if (id=='999999')
		{
			alert ("non puoi eliminare questo profilo");
			return;
		}
		if (confirm("Confermi l'eliminazione definitiva di questo profilo?\n\nIl profilo verrà rimosso solo se non\nvi sono utenti con questo profilo!\n"))
			document.location.href = "index.php?op=eliminap&id="+id
	}

