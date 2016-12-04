<script type="text/javascript" src="{AssetURL}/sha512.min.js"></script>
<script>
	function makehash()
	{
		var oldpw = document.getElementById('oldpw').value;
		var newpw1 = document.getElementById('newpw1').value;
		var newpw2 = document.getElementById('newpw2').value;
		
		// Check if passwords have been changed
		if (oldpw + newpw1 + newpw2 != '')
		{
			if ((newpw1 == newpw2) && (newpw1.trim() != ''))
			{
				var uid = document.getElementById('uid').value;
				document.getElementById('oldhash').value = SHA512(uid + oldpw);
				var newpw = document.getElementById('newpw1').value;
				document.getElementById('newhash').value = SHA512(uid + newpw);
			}
			else
			{
				alert('The new passwords must match and may not be empty.');
				$('#reset').click();
				return false;
			}
		}
		return true;
	}
</script>
<form method="post" action="{BaseURL}?action=profile" onsubmit="return makehash()">
	<input type='hidden' id='uid' value='{UserName}' />
	<input type='hidden' name='oldhash' id='oldhash' value='' />
	<input type='hidden' name='newhash' id='newhash' value='' />
	<div class='editsection'>Username: <b>{UserName}</b></div>
	<div class='editsection'>Change Password:</div>
	<div class='addBorder'>
		<p style='margin-top: 5px'><input type='password' id='oldpw' placeholder='Current Password' autofocus /></p>
		<p><input type='password' id='newpw1' placeholder='New Password' /> <input type='password' id='newpw2' placeholder='Repeat New Password' /></p>
	</div>
	<div class='editsection'>Options:</div>
	<div class='addBorder'>
		<p style='margin-top: 5px'>Login Timeout: <input type='text' name='timeout' id='timeout' size='5' value='{Timeout}' /> seconds</p>
	</div>
	<p>&nbsp;<br /><input type='submit' value='Submit' /> <input id='reset' type='reset' value='Reset' /> <input type='button' value='Cancel' onclick='window.history.back()' /></p>
</form>
