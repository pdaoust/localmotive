<?
if (!isset($dateStartedChanged)) $dateStartedChanged = false;
if (!isset($dateCompletedAdjusted)) $dateCompletedAdjusted = false;
if (!isset($dateResumeChanged)) $dateResumeChanged = false;
if ($order->orderType & O_TEMPLATE) {
	if ($order->period > 0) {
		if (!($order->period % T_WEEK)) $period = T_WEEK;
		else if (!($order->period % T_DAY)) $period = T_DAY;
		else $period = null;
	} else if ($order->period < 0) {
		if (!($order->period % T_YEAR)) $period = T_YEAR;
		else if (!($order->period % T_MONTH)) $period = T_MONTH;
		else $period = null;
	} else $period = null;
	if ($period) $mult = $order->period / $period;
	$payPal = new PayType (PAY_PAYPAL);
	$total = $totals['net'] + $totals['hst'] + $totals['pst'];
	$surcharge = $payPal->getSurcharge($payPal->surchargeType & N_GROSS ? $totals['gross'] : $totals['net']);
}
if ($referrer == 'order') { ?>
	<h3>Scheduling</h3>
	<? $totals = $order->getTotal(false);
	/*if ($editable && ($order->orderType & O_DELIVER)) { ?>
		<p class="info">We process your order on <?= strftime('%A', $order->getCutoffDay()) ?>s, so please choose the <?= strftime('%A', $order->getCutoffDay()) ?> prior to your next delivery when you hold or resume this order.</p>
	<? }*/
	if ($order->orderType & O_TEMPLATE) {
		$nextDeliveryDay = $order->getNextDeliveryDay(null, false, false);
		$thisDeliveryDay = $order->getNextDeliveryDay();
		$logger->addEntry('checking for existing order');
		$hasOrder = $order->hasCreatedOrder(null, false);
		if ($hasOrder) $nextDeliveryDay = $hasOrder->getDateToDeliver();
		if ($nextDeliveryDay) { ?>
			<ul class="orderDetails">
				<li>
					<b>Next <?= ($order->orderType & O_DELIVER ? 'delivery' : 'occurrence') ?></b>
					<?= Date::human($nextDeliveryDay) . ($nextDeliveryDay != $thisDeliveryDay ? ($hasOrder ? ' (already processed)' : ' (deadline past)') : null) ?>
				</li>
				<? if ($thisDeliveryDay && $nextDeliveryDay != $thisDeliveryDay) { ?>
					<li>
						<b>These changes take effect</b>
						<?= Date::human($thisDeliveryDay) ?>
					</li>
				<? }
				if ($order->orderType & O_DELIVER && $thisDeliveryDay) { ?>
					<li>
						<b>Order deadline</b>
						<?= Date::human($order->getCutoffDay()) ?>
					</li>
				<? } ?>
			</ul>
		<? } else echo '<p class="notice">No scheduled date</p>'; ?></p>
	<? } else {
		$hasOrder = false; ?>
		<ul class="orderDetails">
			<li>
				<b>Delivery date</b>
				<? $nextDeliveryDay = $order->getNextDeliveryDay();
				if ($nextDeliveryDay) echo toHumanDate($nextDeliveryDay);
				else echo '<span class="notice">No scheduled date</span>'; ?>
			</li>
			<li>
				<b>Order deadline</b>
				<?= Date::human($order->getCutoffDay()) ?>
			</li>
		</ul>
	<? }
	if ($order->orderType & O_TEMPLATE) {
		if ($editable) { ?><form id="orderSettings" method="post" action="order.php" style="clear: both;"><input type="hidden" name="action" value="save"/><?= $tour ? '<input type="hidden" name="tour" value="1"/><input type="hidden" name="svcID" value="' . $customer->personID . '"/>' : null ?><? } ?>
		<ul class="orderDetails">
			<li>
				<label>
					<b>Deliver every</b>
					<input type="text" name="mult" id="mult" size="2" maxlength="3"<? if (!$editable) echo ' disabled="disabled"'; ?> value="<?= (isset($mult) ? $mult : null) ?>"/>
					<select id="period" name="period"<? if (!$editable) echo ' disabled="disabled"'; ?>>
						<? foreach ($timeNames as $k => $v) {
							echo '<option value="' . $k . '"' . ($period == $k ? ' selected="selected"' : ($customer->isIn($user, false) ? null : ' disabled="disabled"')) . '>' . $v . ($k ? '(s)' : null) . '</option>';
						} ?>
					</select>
				</label>
				<? if ($customer->isIn($user, false)) { ?>
					<input type="checkbox" name="deliver" id="deliver" class="orderFlag"<?= ($order->orderType & O_DELIVER) ? ' checked="checked" ' : null ?> value="1"/> <label for="deliver" title="Un-check this for orders such as membership fees, ticket sales, and other items that do not go out for delivery">By vehicle</label>
				<? } ?>
			</li>

			<? if ($editable) {
				$today = Date::round(time());
				$deliver = ($order->orderType & O_DELIVER);
				$dateStarted = $order->getDateStarted();
				$route = $order->getRoute();
				$firstDelivery = $order->getNextDeliveryDay($dateStarted, true, false); ?>
				<li>
					<label>
						<b>Start<?= $firstDelivery < $today ? 'ed' : null ?> on</b>
						<input type="text" name="dateStarted" id="dateStarted" class="dateField" value="<?= trim(strftime(TF_PICKER_VAL, $firstDelivery)) ?>" size="10"/>
						<? /* if ($deliver) {
							$firstCutoffDay = $order->getCutoffDay($firstDelivery);
							if ($firstDelivery >= $today) { ?>
								<span class="hint">first delivery: <?= Date::human($firstDelivery) ?></span>
							<? }
						} */ ?>
					</label>
					<?= ($dateStartedChanged ? '<span class="hint">Date has been changed to nearest delivery day</span>' : null) ?>
				</li>
				<li>
					<label>
						<b>Put on hold after</b>
						<input type="text" name="dateHold" id="dateHold" class="dateField"<? if ($order->getDateCompleted()) echo ' value="' . trim(strftime(TF_PICKER_VAL, $order->getDateCompleted() - T_DAY)) . '"'; ?> size="10"/> <input type="image" id="removeHold" title="Remove hold date" src="img/del_g.png" alt="Remove hold date"/>
					</label>
					<? if ($deliver && $route) {
						$dateCompleted = $order->getDateCompleted();
						$lastOrder = $route->getNextDeliveryDay($dateCompleted, false, $dateStarted, $order->period);
						$lastCutoffDay = $route->getCutoffDay($lastOrder);
						if ($lastOrder - ($lastCutoffDay * T_DAY) > $dateCompleted) { ?>
							<span class="hint">Note: You will receive a bin on this day</span>
						<? }
					} ?>
					<?= $dateCompletedAdjusted ? '<span class="hint">This date has been moved to before the ordering deadline</span>' : null ?>
				</li>
				<li>
					<label>
						<b>Then resume on</b>
						<? if ($deliver && $order->getDateCompleted() && $order->getDateResume()) {
							$dateResume = $order->getDateResume();
							$resumeOrder = $order->getNextDeliveryDay($dateResume, true, false);
						} ?>
						<input type="text" name="dateResume" id="dateResume" class="dateField"<? if ($order->getDateCompleted() && $order->getDateResume()) echo ' value="' . trim(strftime(TF_PICKER_VAL, $resumeOrder)) . '"'; ?> size="10"/> <input type="image" id="removeResume" title="Remove resume date" src="img/del_g.png" alt="Remove resume date"/>

						<!--<span class="hint">first delivery: <?= Date::human($firstOrder) ?>-->
						<? if (($route = $order->getRoute()) && isset($dateResume)) {
							$firstOrderFake = $route->getNextDeliveryDay($dateResume, false);
							$firstCutoffDay = $route->getCutoffDay($firstOrderFake);
							if ($firstOrderFake - ($firstCutoffDay * T_DAY) < $dateResume) { ?>
								(tip: if you want a bin on <?= strftime(TF_PICKER_VAL, $firstOrderFake) ?>, change this date to <?= strftime(TF_PICKER_VAL, $firstOrderFake - ($firstCutoffDay * T_DAY)) ?>, the previous order deadline)
							<? }
						} ?></span>
					</label>
					<?= ($dateResumeChanged ? '<span class="hint">Date has been changed to nearest delivery day</span>' : null) ?>
				</li>
				<? if ($customer->isIn($user, false)) { ?>
					<li>
						<b>Options</b>
						<span class="multiple">
							<input type="checkbox" name="editable" id="editable" class="orderFlag"<?= ($order->orderType & O_EDITABLE) ? ' checked="checked"' : null ?>/> <label for="editable">Customer can edit or delete (un-check for mbr fees)</label><br/>
							<? if ($customer->personType & P_CSA) { ?>
								<input type="checkbox" name="csa" id="csa" class="orderFlag"<?= ($order->orderType & O_CSA) ? ' checked="checked"' : null ?>/> <label for="csa">CSA order</label>
							<? } ?>
						</span>
						<input type="hidden" name="toggles" value="1"/>
					</li>
				<? }
			} ?>
			</ul>
		</form>
	<? }
//		if (isset($_SESSION['demo'])) echo 'Demo user - checkout disabled';
	$canCheckOut = (!($totals['net'] < $minOrder) && $order->orderType & O_DELIVER) || !($order->orderType & O_DELIVER);
	if ($customer->personType & P_CSA && $order->orderType & O_CSA) {
		$needsCsa = !(count($order->getCsaItems()));
	}
	else $needsCsa = false;
	$noOrder = ((isset($thisDeliveryDay) && $thisDeliveryDay == $nextDeliveryDay) || !($order->orderType & O_TEMPLATE));
	if (!$canCheckOut || !$noOrder || $needsCsa || $hasOrder) {
		echo '<div class="notice clear">';
		if (!$canCheckOut) echo '<p>This order is ' . money_format(NF_MONEY, $minOrder - $totals['net']) . ' below the minimum order amount.</p>';
		if ((!$noOrder || $hasOrder) && $canCheckOut && !$needsCsa) echo '<p>'.($hasOrder ? 'Your order has already been created for the next delivery day.' : 'The deadline for your next delivery day is already past.').' You can save your changes, though, and they\'ll take effect starting with the delivery after next, on ' . Date::human($thisDeliveryDay) . '.</p>';
		if ($needsCsa) echo '<p>Please make sure you have at least one CSA item (e.g., a Healthy Harvest Box) in your order (hint: look for the <img src="img/std.png" class="icon" alt="infinity" title="infinity"/> icon).</p>';
		echo '</div>';
	}
	echo '<div class="clear">';
	if (($order->orderType & O_BASE) == O_RECURRING) {
		if ($editable && $canCheckOut && !$needsCsa) {
			if (!($order->orderType & O_CSA) || !($customer->personType & P_CSA)) echo '<a href="order.php?action=makeSale' . ($tour ? ' + \'&tour=1&svcID=' . $customer->personID . '\'' : null) . '" class="button" title="Turn this order into a one-time order"><del>&infin;</del> take off schedule</a> &nbsp; </a>';
			echo '<a href="javascript:document.getElementById(\'orderSettings\').submit()" class="button">&larr; Save</a>';
		}
	} else if (($order->orderType & O_BASE) == O_SALE) {
		if ($editable) echo '<a href="order.php?action=makeRecurring' . ($tour ? ' + \'&tour=1&svcID=' . $customer->personID . '\'' : null) . '" class="button" title="Turn this order into a regularly scheduled order">&infin; put on schedule</a> &nbsp;';
	}
	if ($editable) {
		if ($order->orderType & O_CSA && $customer->personType & P_CSA && !$customer->isIn($user, false)) { ?><!--<img src="img/n.png" class="icon" alt=" "/> <span title="This order is part of your service commitment and can't be deleted.">Can't be deleted</span> &nbsp; --><? } else { ?> &nbsp; <a href="javascript:cancelOrder()" class="button">&times; <?= $order->orderType & O_TEMPLATE ? 'delete' : 'cancel' ?> order</a><? }
	}
	if ($canCheckOut && $noOrder && !$needsCsa) echo ' &nbsp; <a href="' . (($order->orderType & O_BASE) == O_RECURRING ? 'javascript:document.getElementById(\'orderSettings\').action.value=\'checkout\';javascript:document.getElementById(\'orderSettings\').submit()' : $secureUrlPrefix . '/market/order.php?action=checkout') . '" class="button" style="float: right;">Check out &rarr;</a>';
	echo '</div>';
} ?>
