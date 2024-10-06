<!DOCTYPE html>
<head>
	<!--

		RESET PASSWORD TEMPLATE

		This page is used during the reset password process.

		Basic instructions
		==================
		You can change this html as you prefer, just don't touch strings
		between graphs {  and  } which are used for translation by AdAdmin.
		Pay attention (don't touch) also to # symbols which are used to build
		smart tags that are changed from AdAdmim.


	-->
	<meta name="robots" content="noindex">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	##JQUERYINCLUDE##
	<script src="##root##src/template/comode.js?##rand##"></script>

	<title>{Reset password}</title>

	<script>

	$(document).ready(function() {
		$('tr').each(function(){
			if(!$(this).is(":visible")) $(this).remove();
		});
	} );

	</script>

	<style>
		p {text-align:center;padding-bottom:20px}
	</style>

</head>
<body onload="document.forms[0].email.focus();" class="nomenu">

	<form method="post" action="##actionurl##" id='loginform' name='loginform'>
		<p>
			#ciao# 
		</p>
		<input name="code" type="hidden" value="##code##">
		<table ##hideall##>
			<tr ##show##>
				<td>{Email}</td>
				<td><input name="email" type="text" maxlength="200" class='f' value="##email##"></td>
			</tr>
			<tr ##hide##>
				<td>{New password}</td>
				<td><input name="pass1" type="password" maxlength="20" class='f'></td>
			</tr>
			<tr ##hide##>
				<td>{Repeat password}</td>
				<td><input name="pass2" type="password" maxlength="20" class='f'></td>
			</tr>
			<tr>
				<td></td>
				<td align="right"> &nbsp;<input type="button" value="{Go}" id='login' onclick='document.forms[0].submit()'></td>
			</tr>
			<tr>
				<td colspan='2'>
				<div class="message"></div>
				</td>
		</table>
		<a href='##backlink##'>&laquo; {Back}</a>
	</form>
</body>
</html>