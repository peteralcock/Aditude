/*function confermaDelete(id) {
	if (gconfirm("Confermi l'eliminazione definitiva di questo record?\n\nIl modulo verrà rimosso insieme ai suoi\ncomponenti, che non saranno più\nutilizzabili da alcun utente.\n","document.location.href = 'index.php?op=elimina&id="+id+"'")) {}
}
*/

function abilitaModulo(id) {
	if (gconfirm("Riassegno i permessi del modulo a tutti gli utenti?\n","document.location.href = 'index.php?op=profila&id="+id+"'")) {}
}

/*function aggiornaGriglia() {
	$('combotiporeset').value='reset';
	document.getElementById("filtri").submit();

}

function confermaDeleteCheck(theForm) {

    var c;
	c=0;
    for (var i = 0; i < theForm.elements['gridcheck[]'].length; i++)
    {
       if (theForm.elements['gridcheck[]'][i].checked)
       {
		   c=1;
       }
    }
   if (c==0)
   {
	   if (theForm.elements['gridcheck[]'].length==undefined)
	   {
		   if (theForm.elements['gridcheck[]'].checked==false)
		   {
			   alert ('Non hai selezionato nessun record da eliminare');
			   return;
		   }
	   } else {
			   alert ('Non hai selezionato nessuna record da eliminare');
			   return;
	   }

   }

		if (confirm('Sei sicuro di voler eliminare i record selezionati?'))
		{

			theForm.op.value="eliminaSelezionati";
			theForm.submit();
		}

}


*/


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