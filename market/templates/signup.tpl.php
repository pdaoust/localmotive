<? if (!isset($reactivate)) $reactivate = false; ?>
<h2><? if ($reactivate || !$user->personID) { ?>Sign up for <?= htmlEscape($svc->groupName) ?><? } else { ?>Edit your information<? } ?></h2>
<? if (($reactivate || !$user->personID) && (($deposit = (float) $svc->getDeposit()) || $svc->website)) { ?>
<div class="info">
	<? if ($svc->website) { ?><p>Want to know how this service works? <a href="<?= htmlEscape($svc->website) ?>">Read all about it.</a></p><? } ?>
	<? if ((float) $deposit) { ?><p>This service has a yearly fee of <?= money_format(NF_MONEY, $deposit) ?>.</p><? }
	if ($user->personID) { ?><p>Please review your information to make sure it's all in order.<? } ?></p>
</div>
<? } ?>
<? if (count($errorFields)) { ?>
	<div class="notice">
		<? if ((count($errorFields) == 1 && reset($errorFields) == 'captcha') || in_array('captcha', $errorFields)) { ?>
			<p>The answer you gave to the skill-testing question was incorrect. We've generated a new one for you; please try again!</p>
		<? }
		if (!(count($errorFields) == 1 && reset($errorFields) == 'captcha')) { ?>
			<p>You have some errors in your information. Please review the fields marked in red and re-submit the form. <? if (!$user->personID) { ?> (Don't forget to re-enter your password <? if (isset($question)) echo 'and answer the skill-testing question '; ?>before you submit.)<? } ?></p>
		<? } ?>
	</div>
<? } ?>
<form id="editPersonForm" action="signup.php" method="POST">
	<input type="hidden" name="personID" value="<? echo $user->personID; ?>"/>
	<input type="hidden" name="action" value="save"/>
	<input type="hidden" name="svcID" value="<?= $svc->personID ?>"/>
	<? if ($reactivate) { ?><input type="hidden" name="reactivate" value="1"/><? } ?>
	
	<h3 class="steps">Personal info</h3>
	<ul class="form">
		<li<? if (in_array('contactName', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="contactName">Name</label>
			<input type="text" name="contactName" value="<?= htmlEscape($user->contactName) ?>"/>
			<? if (in_array('contactName', $errorFields)) { ?><span class="notice">Please enter either your name or your organisation's name.</span><? } ?>
		</li>

		<? if ($svc->personID != 2) { ?><li<? if (in_array('groupName', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="groupName">Organisation</label>
			<input type="text" name="groupName" value="<?= htmlEscape($user->groupName) ?>"/>
			<span class="hint">you can leave this blank if you like</span>
			<? if (in_array('groupName', $errorFields)) { ?><span class="notice">Please enter either your name or your organisation's name.</span><? } ?>
		</li>
		<? } ?>

		<? if (!$user->personID) { ?>
		<li<? if (in_array('email', $errorFields) || in_array('emailDuplicate', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="email">E-mail</label>
			<input type="text" id="email" name="email" value="<?= htmlEscape($user->email) ?>"/>
			<span class="notice"<? if (in_array('emailDuplicate', $errorFields)) echo null; else echo ' style="display: none;"'; ?> id="emailDuplicate">Someone has already registered with that e-mail address. If you are trying to re-register because you lost your password, please <a href="forgotPassword.php">reset your password</a> instead.</span>
			<? if (in_array('email', $errorFields)) { ?><span class="notice">Please <?= $user->email ? 'check that you entered your e-mail address properly' : 'enter an e-mail address' ?>.</span><? } ?>
			<span class="hint">this will be your login</span>
		</li>
		<? } ?>

		<li<? if (in_array('passwordError', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="password1">Password</label>
			<span class="widget">
				<input type="password" name="password1" id="password1" size="12"<? if (in_array('passwordError', $errorFields)) echo ' class="errorField"'; ?>/>
				<input type="password" name="password2" id="password2" size="12"/>
			</span>
			<span class="hint">please type your password in twice to ensure accuracy<?= $user->personID ? ' &mdash; leave blank if you want to keep your current password' : null ?></span>
			<span class="notice" id="passwordError" style="display: none;"><? if (in_array('passwordError', $errorFields)) { ?><br/><? if (!$personData['password']) echo 'Your password was empty.'; else echo 'Your passwords did not match.'; ?><? } ?></span>
			<? if (count($errorFields) && !$user->personID) { ?><span class="notice" id="passwordRedo">Please re-enter your password.</span><? } ?>
		</li>

		<li<? if (in_array('phone', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="phone">Phone</label>
			<input type="text" name="phone" value="<?= htmlEscape($user->phone) ?>"/>
			<? if (in_array('phone', $errorFields)) { ?><span class="notice">Please enter your phone number.</span><? } ?>
			<span class="hint">In case we need to contact you regarding your order</span>
		</li>

		<li<? if (in_array('address1', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="address1">Address</label>
			<span class="widget">
				<? $personAddress = false;
				if (count($user->addresses)) $personAddress = reset($user->addresses); ?>
				<input type="text" name="address1" value="<? if ($personAddress) echo htmlEscape($personAddress->address1); ?>" class="fill"/><br/><input type="text" name="address2" value="<? if ($personAddress) echo htmlEscape($personAddress->address2); ?>" class="fill"/>
			</span>
			<? if (in_array('address1', $errorFields)) { ?><span class="notice">Please enter your address.</span><? } ?>
			<span class="hint">For billing and home delivery purposes</span>
		</li>

		<li<? if (in_array('city', $errorFields) || in_array('prov', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="city">City</label>
			<span class="widget"><input type="text" name="city" value="<? if ($personAddress) echo htmlEscape($personAddress->city); ?>"/>, <input type="text" name="prov" size="<?= isset($config['provMax']) ? $config['provMax'] : '2' ?>" maxlength="<?= isset($config['provMax']) ? $config['provMax'] : '2' ?>" value="<? $prov = false;
			if ($personAddress) $prov = $personAddress->prov;
			if ($prov) echo htmlEscape($prov);
			else if (isset($config['provDefault'])) echo htmlEscape($config['provDefault']); ?>"/></span>
			<? if (in_array('city', $errorFields) || in_array('prov', $errorFields)) { ?><span class="notice">Please enter your <?= (in_array('city', $errorFields) ? 'city' : null) . (in_array('city', $errorFields) && in_array('prov', $errorFields) ? ' and ' : null) . (in_array('prov', $errorFields) ? 'province' : null) ?>.</span><? } ?>
		</li>

		<li<? if (in_array('postalCode', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="postalCode">Postal code</label>
			<input type="text" name="postalCode" size="7" maxlength="7" value="<? if ($personAddress) echo htmlEscape($personAddress->postalCode); ?>"/>
			<? if (in_array('postalCode', $errorFields)) { ?><span class="notice">Please make sure you have typed in your postal code properly. <? if (preg_match('/[0Oo1I]+/', $personAddress->postalCode)) { ?>Check to make sure you haven't confused the numeral '0' and the letter 'O', or the numeral '1' and the letter 'I'.<? } ?></span><? } ?>
		</li>

		<li>
			<label for="directions">Directions</label>
			<textarea rows="3" name="directions"<? if (in_array('directions', $errorFields)) echo ' class="errorField"'; ?>><? if ($personAddress) echo htmlEscape($personAddress->directions); ?></textarea>
			<span class="hint">for a hard-to-find spot, entry instructions for an apartment building, etc &mdash; not applicable for group pickup</span>
		</li>
	</ul>
	<h3 class="steps">Delivery method</h3>
	<ul class="form">
		<? if (count($depots)) { ?>
		<li>
			<label for="delivery">Delivery</label>
			<select name="delivery" id="delivery">
				<option value="depot"<?= isset($delivery) && $delivery == 'depot' ? ' selected="selected"' : null ?>>Group pickup spot</option>
				<option value="home"<?= isset($delivery) && $delivery == 'home' ? ' selected="selected"' : null ?>>Home delivery</option>
			</select>
			<span class="hint">
			<? $minOrder = $svc->getMinOrder();
			$minOrderDeliver = $svc->getMinOrderDeliver();
			if ($minOrderDeliver > $minOrder) { ?><span>Home delivery requires a <?= money_format(NF_MONEY, $minOrderDeliver) ?> minimum order.</span><? }
			if ($svc->getShipping()) { ?><span>A <?
				switch (true) {
					case $svc->getShippingType() & N_PERCENT:
						echo (float) $svc->getShipping() . '%';
						break;
					case $svc->getShippingType() & N_FLAT:
						echo money_format(NF_MONEY, $svc->getshipping());
				} ?>
			home delivery surcharge will apply.</span><? } ?>
			</span>
		</li>
		<? } ?>

		<li id="routeRow"<? if (in_array('shippingError', $errorFields) || in_array('routeID', $errorFields)) echo '  class="errorField"'; ?>>
			<label for="routeID">Route</label>
			<select name="routeID" id="routeID">
				<option value=""> </option>
				<?php
				if (isset($personData['routeID'])) $routeID = $personData['routeID'];
				else if ($user->getRouteID(false)) $routeID = $user->getRouteID(false);
				foreach ($routes as $thisRoute) {
					$schedule = $thisRoute->getSchedule();
					echo "\t\t\t\t\t<option value=\"" . $thisRoute->routeID . '"' . ($routeID == $thisRoute->routeID ? ' selected="selected"' : null) . '>' . htmlEscape($thisRoute->label . (count($schedule) ? ' (' . implode(', ', $schedule) . ')' : null)) . "</option>\n";
				}
				?>
			</select>
			<? if (in_array('shippingError', $errorFields) || in_array('routeID', $errorFields)) { ?><span class="notice">Please choose a route.</span><? } ?>
			<!--<p>You can find your closest route on <a href="javascript:spawnRouteMap()">our route map</a>. If you are not in any of the route outlines, just choose the nearest route and we'll accommodate you.</p>-->
		</li>

		<? if (count($depots)) { ?>
		<li id="depotRow"<? if (in_array('shippingError', $errorFields) || in_array('depotID', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="depotID">Pickup spot</label>
			<select name="depotID" id="depotID">
			<? foreach ($depots as $v) {
				foreach ($v->addresses as $v2) {
					if ($v2->addressType & AD_SHIP) echo '<option value="' . $v->personID . ':' . $v2->addressID . '"' . ($depotID == $v->personID ? ' selected="selected"' : null) . '>' . htmlEscape(($v2->careOf ? $v2->careOf : ($v->groupName ? $v->groupName : $v->contactName)) . ', ' . $v2->address1 . ', ' . $v2->city) . '</option>';
				}
			} ?>
			</select>
			<? if (in_array('shippingError', $errorFields) || in_array('depotID', $errorFields)) { ?><span class="notice">Please choose a depot where you can pick up your bin and drop off payments.</span><? } ?>
		</li>
		<? } ?>

		<li id="depotKey"<? if (in_array('privateKey', $errorFields)) echo ' class="errorField"'; ?> style="<?= in_array('privateKey', $errorFields) ? null : 'display: none; ' ?>">
			<label for="privateKey">Secret key</label>
			<input type="password" name="privateKey" id="privateKey"<?= isset($privateKey) ? ' value="' . htmlEscape($privateKey) . '"' : null ?>/>
			<span class="hint">This is a private depot and requires a secret key. If you do not have one, please contact the administrator of this depot.</span>
			<span id="privateKeyStatus" class="notice"<?= in_array('privateKey', $errorFields) ? ' style="display: none;"' : null ?>><?= in_array('privateKey', $errorFields) ? 'The secret key you typed in is not valid.' : null ?></span>
		</li>

		<? if ($reactivate || !$user->personID) { ?>
		<li id="csaItemRow"<? if (in_array('period', $errorFields) || in_array('csaItemID', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="csaItemID">Recurring order</label>
			<span class="widget">
				<? if (count($csaItems) > 1) { ?>
					<select name="csaItemID" id="csaItemID">
						<!--<option value="0">None (custom ordering)</option>-->
						<? foreach ($csaItems as $v) {
							$price = $v->getPrice($svc->personID);
							echo '<option value="' . $v->itemID . '"' . ($orderData['csaItemID'] == $v->itemID ? ' selected="selected"' : null) . '>' . htmlEscape($v->label) . ' - ' . money_format(NF_MONEY, ($price->price * $price->multiple)) . ($price->multiple == 1 ? null : ' per ' . $price->multiple) . '</option>';
						} ?>
					</select><br/>
				<? } else if (count($csaItems)) { ?>
				<input type="hidden" name="csaItemID" id="csaItemID" value="<?= reset($csaItems)->itemID ?>"/><? } ?>
				<label class="noform">every <select name="period" id="period">
					<? foreach (array(0, 1, 2, 3, 4) as $v) {
						if (!($svc->personType & P_CSA) || $v) echo '<option value="' . (int) $v . '">' . $v . '</option>';
					} ?>
				</select> week(s)</label>

			</span>
			<? if (in_array('csaItemID', $errorFields)) { ?><span class="notice">The service you are joining requires a commitment. Please choose an item to go on your recurring order.</span><? } ?>
			<? if (in_array('period', $errorFields)) { ?><span class="notice">Please choose how often you want to receive your order.</span><? } ?>
			<span class="hint"><?= ($svc->personType & P_CSA) ? null : 'optional; ' ?>You can add and remove items on your recurring order once your account is set up</span>
		</li>
		<? } ?>
	</ul>
	
	<h3 class="steps">Optional info</h3>
	<ul class="form">
		<?
		if (!isset($payTypes)) $payTypes = $svc->getPayTypes();
		foreach ($payTypes as $k => $v) {
			if (!$v->isActive()) unset($payTypes[$k]);
		}
		$defaultPayTypeID = ($user->personID ? $user->getPayTypeID() : $svc->getPayTypeID());
		if (count($payTypes) > 1) { ?>
		<li<? if (in_array('payTypeID', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="payTypeID">Preferred payment type</label>
			<select name="payTypeID" id="payTypeID">
				<option value="">Select a payment type...</option>
				<? foreach ($payTypes as $k => $v) {
					if ($v->isActive() && $k != 'default') { ?>
						<option value="<?= $v->payTypeID ?>"<?= $defaultPayTypeID == $v->payTypeID ? ' selected="selected"' : null ?>><?= htmlEscape($v->label) ?></option>
					<? }
				} ?>
			</select>
			<span class="hint">you can change this any time</span>
			<? if (in_array('payTypeIDInvalid', $errorFields)) { ?><span class="notice">Please make sure you choose a proper payment type.</span><? }
			if (in_array('payTypeIDInactive', $errorFields)) { ?><span class="notice">That payment type is currently unavailable &mdash; how did you manage to select it?!</span><? } ?>
		</li>
		<? }
		if ($user->cc && $user->txnID) { ?>
		<li id="storedCCfield">
			<label>Stored credit card</label>
			<span class="widget">
				<span id="storedCCnum"><?= htmlEscape($user->cc) ?></span><br/>
				<label title="remove credit card number from our processor's database"><input type="checkbox" name="forgetCC" id="forgetCC"/> forget</label><br/>
				<label><input type="checkbox" name="pad" id="pad"<?= $user->pad ? ' checked="checked"' : null ?>/> automatic billing</label><br/>
				By checking 'automatic billing', you authorise LocalMotive Organic Delivery to automatically bill your credit card on your behalf, and you verify that you have read and agree to the terms of LocalMotive's <a href="/cc-recurring.php">recurring billing policy.</a>
			</span>
		</li>
		<? } ?>
		<li>
			<label for="notes">Notes</label>
			<textarea rows="5" cols="30" name="notes"><?= htmlEscape($user->notes) ?></textarea>
			<span class="hint">Special instructions, allergies, questions, etc</span>
		</li>
		
		<? if (!$user->personID) { ?>
		<li>
			<label for="referral">Referral</label>
			<span class="widget">
				<select name="referral">
					<option value=""></option>
					<option value="Kevin Proto (Locals Supporting Locals)">Kevin Proto (Locals Supporting Locals)</option>
					<option value="Friend or colleague">Friend or colleague</option>
					<option value="Word-of-mouth">Word-of-mouth</option>
					<option value="Flyer">Flyer</option>
					<option value="Internet search">Internet search</option>
					<option value="Link from another website">Link from another website</option>
					<option value="E-mail">E-mail</option>
				</select>
			</span>
			<span class="hint">We'd like to know how you found out about our service</span>
		</li>
		<? } ?>
	</ul>

	<h3 class="steps"><?= $user->personID ? ($reactivate ? 'Reactivate your account' : 'Confirm changes') : 'Sign up!' ?></h3>
	<ul class="form">
		<? if (isset($question)) { ?>
		<li<? if (in_array('captcha', $errorFields)) echo ' class="errorField"'; ?>>
			<label for="captcha">Skill-testing question</label>
			<span class="widget"><div><?= $question ?></div><input type="text" name="captcha"/></span>
			<span class="hint">Help us combat spam by verifying that you're a real human &mdash; no offense intended!</span>
		</li>
		<? } ?>

		<li><span class="label">&nbsp;</span><input type="submit" value="<?= $user->personID ? ($reactivate ? 'Reactivate!' : 'Save') : 'Sign up!' ?>"/></li>
	</ul>
</form>
<p>Wondering what we do with your information? Read our <a href="/privacy.php">Privacy policy</a>. Curious about refunds and delivery standards? Read our <a href="/refund-delivery.php">Refund and method-of-delivery policies</a>.</p>

<script language="JavaScript" type="text/javascript">

$(function () {
	$('#password1, #password2').val('');
	$('#email').change(function () {
		email = $('#email').val();
		$.getJSON('signup.php', { action: 'checkDuplicateEmail', email: email }, function (json) {
			if (json.duplicate) {
				$('#emailDuplicate').show().parents('li').addClass('errorField');
			} else {
				$('#emailDuplicate').hide().parents('li').removeClass('errorField');
			}
		});
	});
	$('#password1').blur(function () {
		if ($('#password2').val()) {
			warnUnmatchedPassword(Boolean($('#password1').val() != $('#password2').val()));
		}
	});
	$('#password2').keyup(function () {
		password1 = $('#password1');
		password2 = $('#password2');
		if (password2.val()) warnUnmatchedPassword(Boolean(password1.val().substr(0, password2.val().length) != password2.val()));
		else warnUnmatchedPassword(false);
	});
	$('#password2').blur(function () {
		if ($('#password2').val()) warnUnmatchedPassword(Boolean($('#password1').val() != $('#password2').val()));
		else warnUnmatchedPassword(false);
	});
	$('#depotID').change(function () {
		if ($('#depotID option:selected').val()) {
			$.getJSON('signup.php', { 'action': 'hasPrivateKey', 'depotID': $('#depotID option:selected').val()}, function (json) {
				if (json.privateKey) {
					$('#depotKey').show();
					$('#privateKey').keyup(function () {
						privateKeyE = $('#privateKey').val();
						if (!privateKeyE) $('#privateKeyStatus').css('display', 'none');
						else if (privateKeyE != json.privateKey.substr(0, privateKeyE.length)) {
							$('#depotKey').removeClass('info')('#depotKey').addClass('errorField');
							$('#privateKeyStatus').text('Your secret key is not valid!').css('display', 'block');
						} else {
							$('#depotKey').removeClass('errorField').addClass('info');
							if (privateKeyE.length < json.privateKey.length) $('#privateKeyStatus').text('So far, so good. Keep typing!');
							else $('#privateKeyStatus').text('Your secret key has been validated!');
							$('#privateKeyStatus').css('display', 'block');
						}

					});
				} else $('#depotKey').hide();
			});
		}
	}).change();
	$('#delivery').change(function () {
		delivery = $('#delivery').val();
		switch (delivery) {
			case 'depot':
				$('#depotRow').show();
				$('#routeRow').hide();
				break;
			case 'home':
				$('#depotRow').hide();
				$('#routeRow').show();
		}
	}).change();
	$('#csaItemID').change(function () {
		if (Number($(this).val())) {
			if (!Number($('#period').val())) $('#period').val('1');
		} else {
			$('#period').val('0');
		}
	});
	$('#period').change(function () {
		if (!Number($(this).val())) {
			$('#csaItemID').val('0');
		}
	});
	$('#editPersonForm').unbind('submit');
	$('#payTypeID').change(function () {
		if ($(this).val() == <?= PAY_CC ?>) $('#storedCCfield').slideDown('slow');
		else $('#storedCCfield').slideUp('slow');
	});
	$('#forgetCC').change(function () {
		$('#pad').attr('disabled', $(this).attr('checked'));
	});
});

function warnUnmatchedPassword (status) {
	passwordError = $('#passwordError');
	passwordRow = passwordError.parents('li');
	if (status) {
		passwordError.text('Your passwords don\'t match.').css('display', 'inline-block');
		passwordRow.addClass('errorField');
	} else {
		passwordError.hide();
		$('#passwordRedo').remove();
		passwordRow.removeClass('errorField');
	}
}

</script>
