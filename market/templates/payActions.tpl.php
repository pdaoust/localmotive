<h3><?= $payAction == 'payment' ? 'Make a payment on your account' : 'Pay and confirm order' ?></h3>
<form action="<?= $secureUrlPrefix ?>/market/<?= $payAction == 'payment' ? 'payment' : 'order' ?>.php" method="POST">
	<input type="hidden" name="action" value="complete"/>
<? if (count($payErrors)) { ?>
	<div class="notice">
	<? if (in_array('nsf', $payErrors)) { ?><p>You have attempted to charge this order to your account credit, but you do not have enough account credit. Please choose another payment method.</p><? }
	else if (in_array('gateway', $payErrors)) { ?>
		<p>Sorry, but the payment processor has responded with this message:<br/><b title="Error code <?= htmlEscape($response->Result) ?>"><?
			if (in_array('userauth', $payErrors)) echo 'The payment gateway isn\'t set up properly.';
			if (in_array('declined', $payErrors)) echo 'The payment was declined. (Sometimes this can happen if you\'ve entered an incorrect expiry date or card verification code.)';
			if (in_array('referral', $payErrors)) echo 'The payment was declined, but can be authorised through your credit card issuer by phone.';
			if (in_array('CardNum', $payErrors)) echo 'Your card number is invalid.';
			if (in_array('ExpDate', $payErrors)) echo 'The expiry date you entered doesn\'t match the card issuer\'s records.';
			if (in_array('CVNum', $payErrors)) echo 'The card verification code you entered was incorrect.';
			if (in_array('duplicate', $payErrors)) echo 'This transaction has already been processed. Perhaps you pressed the \'Reload\' button?';
			if (in_array('nsf', $payErrors)) echo 'You have insufficient credit on your card.';
			if (in_array('txlimit', $payErrors)) echo 'This purchase exceeds your per-transaction limit.';
			if (in_array('unavailable', $payErrors)) echo 'The gateway didn\'t respond. Please try again a bit later.';
			if (in_array('data', $payErrors)) echo 'There was a problem with the submitted data.';
			if (in_array('origID', $payErrors)) echo 'Your credit card on file was inaccessible. This can happen if it\'s been a long time since you made a purchase. Please re-enter your credit card number.';
		?></b></p>
	<? } else { ?>
		<p>Please check your payment details; any errors are described below.</p>
	<? } ?>
	</div>
<? } ?>

	<div id="cc" class="payTypeForm">
		<ul class="form">
			<li class="pay_cc pay_paypal pay_acct<? if (in_array('payTypeID', $payErrors)) echo ' errorField'; ?>">
				<label for="payTypeID">Payment type</label>
				<select name="payTypeID" id="payTypeID">
					<? foreach ($payTypes as $v) {
						if ($v->payTypeID != PAY_ACCT || ($v->payTypeID == PAY_ACCT && $customer->getBalance(true) >= $totals['gross']))
							echo "\t\t<option value=\"{$v->payTypeID}\"" . ($v->payTypeID == $customer->getPayTypeID() ? ' selected="selected"' : null) . ">{$v->label}" . (($v->surchargeType & N_PERCENT && $v->surcharge) ? ' (' . (float) $v->surcharge . '% surcharge)' : null) . "</option>\n";
					} ?>
				</select>
				<? if (in_array('payTypeID', $payErrors)) {?><span class="notice">The payment type you chose<? is_object($payType) ? ', \'' . htmlEscape($payType->label) . '\',' : null ?> is not available. Please choose another payment type.</span><? } ?>
			</li>
			<? if (isset($amount)) { ?>
			<li class="pay_cc pay_paypal pay_acct<? if (in_array('amount', $payErrors)) echo ' errorField'; ?>">
				<label for="amount">Amount</label>
				<span class="widget">$ <input type="text" name="amount" size="7"<?= (float) $amount ? ' value="' . money_format(NF_MONEY_NOCURR, (float) $amount) . '"' : null ?>/>
				<? if (in_array('amount', $payErrors)) { ?><span class="notice">Please enter an amount above $0.</span><? } ?>
			</li>
			<? }
			if (array_key_exists(PAY_CC, $payTypes)) {
				$hasStoredCC = ($customer->cc && $customer->txnID); ?>
			<li class="pay_cc<? if (in_array('CardNum', $payErrors)) echo ' errorField'; ?>">
				<label for="<?= $hasStoredCC ? 'cardAction' : 'CardNum' ?>">Credit card number </label>
				<span class="widget">
					<? if ($hasStoredCC) {?>
						<select name="cardAction" id="cardAction">
							<option value="useStoredCC"<?= (isset($_POST['cardAction']) && $_POST['cardAction'] == 'useStoredCC') ? ' selected="selected"' : null ?>>Use stored credit card</option>
							<option value="useNewCC"<?= (isset($_POST['cardAction']) && $_POST['cardAction'] == 'useNewCC') ? ' selected="selected"' : null ?>>Enter a new credit card</option>
						</select><br/>
						<span id="storedCCnum"><?= htmlEscape($customer->cc) ?></span><span id="newCCnum" class="ccFormField">
					<? } else { ?>
						<span><input type="hidden" name="cardAction" id="cardAction" value="useNewCC"/>
					<? } ?>
					<input type="text" name="CardNum" id="CardNum"<?= isset($_POST['CardNum']) ? ' value="' . htmlEscape($_POST['CardNum'], ENT_QUOTES) . '"' : null ?> size="16" maxlength="16" class="ccFormField"/><label title="Store your credit card details for faster checkout. Note: we never, ever store your full credit card details on our server; they're stored securely with our payment processor."><input type="checkbox" name="rememberCC" id="rememberCC" value="1" class="ccFormField"<?= (isset($_POST['rememberCC']) && $_POST['rememberCC']) ? ' checked="checked"' : null ?>/> remember</label></span>
				</span>
				<? if (in_array('CardNum', $payErrors)) { ?><span class="notice">Please check your credit card number.</span><? } ?>
				<span class="hint">We accept VISA and MasterCard</span>
			</li>
			<li class="pay_cc ccFormField<? if (in_array('expDate', $payErrors)) echo ' errorField'; ?>">
				<label for="ExpDateMonth">Expiry Date</label>
				<span class="widget">
					<input type="text" name="ExpDateMonth" size="2" maxlength="2"<?= isset($_POST['ExpDateMonth']) ? 'value="' . ((int) $_POST['ExpDateMonth'] ? sprintf('%02d', (int) $_POST['ExpDateMonth']) : null) . '"' : null ?> class="ccFormField"/>/<input type="text" name="ExpDateYear" size="2" maxlength="2"<?= isset($_POST['ExpDateYear']) ? 'value="' . ((int) $_POST['ExpDateYear'] ? sprintf('%02d', (int) $_POST['ExpDateYear']) : null) . '"' : null ?> class="ccFormField"/>
				</span>
				<? if (in_array('expDate', $payErrors)) { ?><span class="notice">Please check your expiry date<?= in_array('expDatePast', $payErrors) ? '; it looks like this card has expired' : in_array('expDateMatch', $payErrors) ? '; it doesn\'t match the one your card issuer has on record' : null ?>.</span><? } ?>
				<span class="hint">mm/yy</span>
			</li>
			<li class="pay_cc ccFormField<? if (in_array('CVNum', $payErrors)) echo ' errorField'; ?>">
				<label for="CVNum">Card verification code</label>
				<span class="widget">
					<input type="password" name="CVNum" size="4" maxlength="4" class="ccFormField"/>
				</span>
				<? if (in_array('CVNum', $payErrors)) { ?><span class="notice">Please check your verification code.</span><? } ?>
				<span class="hint">Found on back of card, beside signature stripe. E.g., 123</span>
			</li>
			<? if ($customer->hasOpenOrder(array(O_RECURRING, O_BASE))) { ?>
			<li class="pay_cc">
				<label for="pad">Automatic billing</label>
				<span class="widget">
					<input type="checkbox" name="pad" id="pad" <?= (($customer->pad && $customer->cc && $customer->txnID) || isset($_POST['pad'])) ? ' checked="checked"' : null ?>/><br/>
					By checking this box, you authorise LocalMotive Organic Delivery to automatically bill your credit card on your behalf, and you verify that you have read and agree to the terms of LocalMotive's <a href="/cc-recurring.php">recurring billing policy.</a>
				</span>
				<span class="hint">Make sure you've clicked 'remember' or 'use stored credit card' beside your credit card number</span>
			</li>
			<? } else if ($customer->pad && $customer->cc && $customer->txnID) { ?><input type="hidden" name="pad" value="1"/><? }
			}
			if (array_key_exists(PAY_ACCT, $payTypes)) {
				$credit = $customer->getCredit();
				$balance = $customer->getBalance();
			?>
			<li class="pay_acct">You currently have <?= money_format(NF_MONEY, abs($balance)) . ($balance > 0 ? ' account credit' : ' owing') . ($credit > 0 ? ' and a ' . money_format(NF_MONEY, $credit) . ' line of credit' : null) ?>. Please confirm your order, and the order's amount will be subtracted from your credit.</li>
			<? }
			if (array_key_exists(PAY_PAYPAL, $payTypes)) { ?>
			<li class="pay_paypal errorField">
				Sorry, but using PayPal at checkout is currently unavailable. If you would like to pay with PayPal, please log into your account and send the money directly to feedme@localmotive.ca
			</li>
			<? }
			if ($payAction == 'order') { ?>
			<li class="pay_acct pay_paypal pay_cc">
				<label for="notes">Notes or comments</label>
				<textarea name="notes"><? if (isset($_POST) && isset($_POST['notes'])) echo htmlEscape($_POST['notes']); ?></textarea>
			</li>
			<? }
			if (array_key_exists(PAY_PAYPAL, $payTypes) || array_key_exists(PAY_CC, $payTypes)) { ?>
			<li class="pay_cc pay_paypal">
				<label for="submit"> </label>
				<input type="submit" value="Process payment"/>
			</li>
			<? }
			if (array_key_exists(PAY_ACCT, $payTypes)) { ?>
			<li class="pay_acct">
				<label for="submit"> </label>
				<input type="submit" value="Bill my account"/>
			</li>
			<? } ?>
		</ul>
	</div>
</form>
<? if ((isset($_POST['action']) && $_POST['action'] != 'checkout') || !isset($_POST['action'])) { ?>
<script language="JavaScript">
	<? include('orderHelpers.js.php'); ?>
</script>
<? } ?>
