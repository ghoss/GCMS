<script type="text/javascript" src="{AssetURL}/sha512.min.js"></script>
<script>
	function makehash()
	{
		var uid = document.getElementById('uid').value;
		var pw = document.getElementById('pw').value;
		document.getElementById('hash').value = SHA512(uid + pw);
		return true;
	}
</script>
<form method="post" action="{BaseURL}?action=login" onsubmit="makehash()">
	<input type='hidden' name='hash' id='hash' value='' />
	<p>Please enter your login credentials:</p>
	<p><input type='text' id='uid' placeholder='Username' autofocus /></p>
	<p><input type='password' id='pw' placeholder='Password' /></p>
	<p><input type='submit' value='Login' /> <input type='button' value='Cancel' onclick='window.history.back()' /></p>
</form>
