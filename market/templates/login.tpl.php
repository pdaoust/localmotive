<? if (!(isset($fromOtherTemplate) && $fromOtherTemplate)) { ?><h2>Sign in to the Localmotive market</h2><? } ?>
<? if ($loginError) { ?><p class="notice"><? echo $loginError; ?></p><? } ?>
<form action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
<ul class="form">
	<li>
		<label for="username">E-mail</label>
		<input type="text" size="25" name="username"<?= isset($_POST['username']) ? ' value="'.htmlEscape($_POST['username']).'"' : null ?>/>
	</li>
	<li>
		<label for="password">Password</label>
		<input type="password" size="25" name="password"/>
	</li>
<!-- TODO: reactivate remember box	<tr>
		<th>Remember me</th>
		<td><input type="checkbox" name="rememberMe" value="1"/></td>
	</tr> -->
	<li>
		<span class="label">&nbsp;</span>
		<input name="submit" type="submit" value="Sign in"/>
	</li>
</ul>
</form>
<p><a href="forgotPassword.php">Forgot your password?</a></a></p>
