function remove(t,m) {

	if (confirm("Rimuovo il tag immediatamente, va bene?"))
	{
		url = 'deletetag.php?idarticolo='+escape(m)+"&idtag="+escape(t);

		new Ajax.Request(url, {
		method:'get',
		onSuccess: function(transport){
			var response = transport.responseText;
			if (response!="ok")
			{
				alert(response);
			} else {
				$('id_'+t+'_'+m).style.display='none';
			}
		},
		onFailure: function(){ }
		});
	}

}


function elimina(s,divid) {
	if (confirm('sicuro di eliminare l\'immagine adesso?'))
	{
		//chiamata ajax per cancellazione file.
		new Ajax.Request('deleteimg.php?f='+escape(s), {
		method:'get',
		onSuccess: function(transport){
			var response = transport.responseText;
			if (response.indexOf("ok")==0) {
				//$(divid).innerHTML = "";
				Effect.Puff(divid);
			} else {
				alert('Non sono riuscito a cancellare.');
			}
		},
		onFailure: function(){ alert('Errore Http, file non cancellato.'); }
		});
	}
}
