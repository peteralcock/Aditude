	// function confermaDelete(id) {
	// 	if (confirm("Confermi l'eliminazione definitiva di questo componente?\n\nIl componente verrà rimosso insieme alle\nsue funzionalità, che non saranno più\nutilizzabili da alcun utente.\n"))
	// 		document.location.href = "index.php?op=eliminac&id="+id
	// }

	function abilitaComponente(id) {
		if (confirm("Quest'operazione (ri)assegna le funzionalità di questo componente\nagli utenti del sistema che corrispondono ai profili specificati\nnella configurazione del componente.\n\nQuesta operazione può determinare cambiamenti negli utenti attualmente\nconnessi e può richiedere l'esecuzione di molte query se si distribuisce una nuova\nfunzionalità a centinaia di utenti."))
			document.location.href = "index.php?op=profila&id="+id
	}

jQuery(document).ready(function() {
	if(!$('body').hasClass('profile999999')) {

		$('a.modifica').each(function() {
			id = null;
			const regex = /[?&]id=(\d+)/;
			const match = $(this).attr('href').match(regex);
			if(match) id= match[1];
			if(id<1000) $(this).hide();
		});

		$('a.elimina').each(function() {
			id = null;
			const regex = /confermaDelete\('(\d+)'\)/;
			const match = $(this).attr('href').match(regex);
			if(match) id= match[1];
			if(id<1000) $(this).hide();
		});
	}
});