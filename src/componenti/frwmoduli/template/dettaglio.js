function confermaDeleteComponente (idc,idm) {
	gconfirm(_e("Do you confirm to delete the item?"),"document.location.href = '../frwcomponenti/index.php?op=elimina&id="+idc+"&cd_module="+idm+"'");
}



jQuery(window).ready(function() {
	
	$('#nome').on('input', function() {
		const value = $(this).val().toUpperCase();
		$(this).val(value);
	  });

	  if($('#id').val()=="") {
		$('.mainfieldset:last').hide();
	  }


});