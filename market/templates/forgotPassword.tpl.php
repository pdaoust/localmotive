<? if (!$resetStatus) {
	if (isset($mailStatus)) {
		if (!$mailStatus) { ?>
<h2>Incorrect e-mail address</h2>
<p class="notice">We found your account, but its e-mail address doesn't appear to be valid. Please <a href="/contact.php">contact us</a> so we can repair your account.</p>
<?		}
	} else if ($noPerson) { ?>
<h2>No account</h2>
<p class="notice">We did not find an account with that e-mail address. Please check the e-mail address you typed in.</p>
<? } ?><h2>Forgot your password?</h2>
<p>Please enter your e-mail address below. We will reset your password, create a new temporary password, and send it to your e-mail address. As soon as you receive this e-mail, come back to your account, log in, and change your password.</p>
<p><form method="post" action="forgotPassword.php" enctype="multipart/form-data">Your e-mail address <input type="text" name="email" <? if (isset($email)) echo 'value="' . htmlEscape($email) . '" '; ?>size="50"/> <input type="submit" name="reset" value="Reset password"/></form></p>
<? } else { ?>
<h2>Thank you!</h2>
<p>An e-mail has been sent to the address <?= htmlEscape($email) ?>. Please <a href="index.php">log into your account</a> with your temporary password as soon as you receive this e-mail, and create a new password.</p>
<? } ?>
