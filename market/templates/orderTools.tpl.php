<div class="noprint">
	<? if ($customer->isIn($user, false)) {
		$isAdmin = true;
		if (!$order->getDateDelivered() && !$order->getDateCanceled()) { ?>
			<a href="orderView.php?orderID=<?= $order->orderID ?>&action=cancel<?= $style == 'dialogue' ? '&style=dialogue' : null ?>"><img src="img/n.png" alt=" " class="icon"/> Cancel</a> &nbsp; 
		<? }
		if (!$order->getDateCompleted() && $order->getDateToDeliver()) { ?>
			<a href="orderView.php?orderID=<?= $order->orderID ?>&action=confirm<?= $style == 'dialogue' ? '&style=dialogue' : null ?>"><img src="img/y.png" alt=" " class="icon"/> Confirm</a> &nbsp; <? }
		if ($order->getDateToDeliver() && $order->getDateToDeliver() < $order->getNextDeliveryDay()) { ?>
			<a href="orderView.php?orderID=<?= $order->orderID ?>&action=defer<?= $style == 'dialogue' ? '&style=dialogue' : null ?>"><img src="img/dord.png" alt=" " class="icon"/> Defer to <?= strftime(TF_HUMAN, $order->getNextDeliveryDay()) ?></a> &nbsp; 
		<? }
		if ($order->getDateCompleted() && !$order->getDateDelivered() && !$order->getDateCanceled()) { ?>
			<a href="orderView.php?orderID=<?= $order->orderID ?>&action=deliver<?= $style == 'dialogue' ? '&style=dialogue' : null ?>"><img src="img/bin.png" alt=" " class="icon"/> Deliver</a> &nbsp; 
		<? } ?>
		<script type="text/javascript" language="JavaScript">
		$(function() {
			$('#recordPayment').ajaxForm({
				data: { ajax: 1 },
				timeout: <?= (int) $config['ajaxTimeout'] ?>,
				error: function () {
					console.log('failed to submit data');
				},
				success: function (r) {
					$('#payResult').html('<img src="img/y.png" class="icon" alt="Payment recorded"/>');
					$('#invoice').html(r);
				}
			});
		});
		</script>
		<br/>
		<!-- TODO: add error display for failed pay types etc -->
		<form action="orderView.php" id="recordPayment" method="post">
			<input type="hidden" name="personID" id="personID" value="<?= $customer->personID ?>"/>
			<input type="hidden" name="orderID" id="orderID" value="<?= $order->orderID ?>"/>
			<input type="hidden" name="action" value="recordPayment"/>
			Record payment of $<input type="text" name="payAmount" id="payAmount" size="8" maxlength="15"/> by
			<select name="payTypeID">
				<? $payTypes = getPayTypes();
				unset($payTypes[PAY_ACCT]);
				$payTypeIDs = $customer->getPayTypeIDs();
				$payTypeID = $customer->getPayTypeID();
				foreach ($payTypes as $k => $v) {
					if (in_array($k, $payTypeIDs)) { ?>
						<option value="<?= $v->payTypeID ?>"<?= ($v->payTypeID == $payTypeID) ? ' selected' : null ?>/><?= htmlEscape($v->labelShort) ?></option>
					<? }
				} ?>
			</select>
			<input type="submit" id="submitPayment" value="Go"/> <span id="payResult"></span>
		</form>
	<? } ?>
</div>
