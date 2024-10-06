<!DOCTYPE html>
<html class="login-template">
<head>
	<!--

		LOGIN LAYOUT TEMPLATE

		Basic instructions
		==================
		You can change this html as you prefer, just don't touch strings
		between graphs {  and  } which are used for translation by AdAdmin.
		Pay attention (don't touch) also to # symbols which are used to build
		smart tags that are changed from AdAdmim.

	-->
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="robots" content="noindex">

	<style>
		/* promo link to codecanyon */
		#promo {position:fixed;right:0;bottom:0;display:inline-block;padding:5px 10px}
	</style>
	
	##JQUERYINCLUDE##
	<script src="##root##src/template/comode.js?##rand##"></script>

	<title>{AdAdmin login page}</title>

	<script>
	jQuery(document).ready(function($) {
		/*
			behaviour for demo installation on Barattalo server
			you can remove this on your template
		*/
		if(document.location.href.indexOf("ambdemo")!=-1) $('#demohere').css("display","inline-block");
	} );
	</script>
</head>
<body onload="document.forms[0].##usernamevar##.focus();" class="nomenu login-template">

	<div id="maincontainer">
	
		<form method="post" action="##actionurl##" id='loginform' name='loginform'>
			<table>
				<tr>
					<td class="logo">
					<img src="##LOGO##" id="logo">
					AdAdmin ##VER##</td>
				</tr>
				<tr>
					<td>{user}<br/>
					<input name="##usernamevar##" type="text" maxlength="20" class='f'></td>
				</tr>
				<tr>
					<td>{password}<br/>
					<input name="##passwordvar##" type="password" maxlength="20" class='f' onkeypress="submitonenter('loginform',event,this)"></td>
				</tr>
				<tr>
					<td><a class="btn" href="javascript:;" onclick="document.forms[0].submit()">{Login}</a></td>
				</tr>
				<tr>
					<td>
						<div class="message">##msg##</div>
						<br>

						<!-- FORGOT PASS procedure link -->
						<a href="##root##src/resetpassword.php" ##hiderecover##>&raquo; {forgot password?}</a>

						<!-- DEMO BUTTON, you can remove in your custom theme -->
						<a id='demohere' href="javascript:;" onclick="show('alertBox');" class="btn" style="float:right;display:none">DEMO HERE</a>

						<!-- SIGN IN is available if payments are ON -->
						<div id="signin"  ##hidesignin##>
							<a href="##root##src/componenti/gestioneutenti/signin.php">&raquo; {Sign in}</a>
						</div>
					</td>
			</table>
		</form>

		<!-- change your text here and customize your nav links -->
		<div id="introbox">
			<div class="textcontainer">
			<h1>Advertise now!</h1>
			<h2>Adv solution for you</h2>
			<p>This is a fake text, you can replace it with whatever you want just by editing the file named layout-login.php. Here you can also change buttons label and links.
			</p>
			<nav>
				
				<a href="https://www.zepsec.com/adadmin-adv-software/">WEB SITE</a>
				
				<a href="https://1.envato.market/adadmin">CONTACT US</a>

			</nav>
		</div>

	</div>

	<!-- LOGIN CREDENTIALS INFO on my demo installation, you can remove in your custom theme -->
	<div id='alertBox' style='display:none;padding-bottom:50px;position:fixed; top:50px;left:10px;'>
		<h1>NOTE FOR GUEST USERS</h1>
		<p>You can access AdAdmin back office demo with these credentials, remember this is a demo version and data will be erased everyday:<br><br>
		Administrator: <code>admin</code> password: <code>admin</code><br>
		Webmaster: <code>www</code> password: <code>www</code><br>
		Advertiser: <code>aaa</code> password: <code>aaa</code><br><br>
		You can see some <a href="../example/adadmin-frontend.html"><u>example banners here</u></a> and you
		can <a href="https://1.envato.market/adadmin"><u>buy adadmin here</u></a><br>
		<a id="closeBtn" href="javascript:;" onclick="$('#alertBox').hide();" style='float:right'>OK</a>
		</p>
	</div>

</body>
</html>