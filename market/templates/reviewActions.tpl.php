<div id="payment" class="canOrder"><?php
	$minOrder = $customer->getMinOrder();
	$payTypeID = $customer->getpayTypeID();
	$route = $customer->getRoute();
	$nextDeliveryDay = $order->getNextDeliveryDay(null, false);
	$orderDeliveryDay = $order->getNextDeliveryDay();
	/* if ($orderDeliveryDay > $nextDeliveryDay) { ?><p class="notice">Note: Your order is past the deadline for the next delivery day. You can choose to:</p>
		<ul>
			<li>Receive your order <? echo strftime('%a, %d %B', $nextDeliveryDay); ?> anyway, and take your chances</li>
			<li>Defer your order to <? echo strftime('%a, %d %B', $orderDeliveryDay); ?></li>
			<li><a href="order.php?action=cancelOrder">Cancel your order</a></li>
			<li><a href="order.php">Return to your order and modify it</a></li>
			<? if ($order->getDateToDeliver() == $nextDeliveryDay) { ?><li><a href="order.php">Return to your order and modify it</a> (note: if you added items to a recurring order, just remove those items and close the order without cancelling or checking out)</li><? } ?>
		</ul>
		<p>What would you like to do?</p>
		<select id="dateAction" onchange="changeDateAction()">
			<option value="<? echo (int) A_IGNORE; ?>">Receive on <? echo strftime('%a, %d %B', $nextDeliveryDay); ?></option>
			<option value="<? echo (int) A_DEFER; ?>">Defer to <? echo strftime('%a, %d %B', $orderDeliveryDay); ?></option>
		</select>
	</div><? } */
	if ($order->orderType & (O_BASE | O_DELIVER) == (O_RECURRING | O_DELIVER) && $totals['net'] >= $minOrder) {
		$totalsRecurring = $order->getTotal(true, 'permanent'); ?>
		<div class="notice" id="permNotice"<?= ($totalsRecurring['net'] < $minOrder) ? null : ' style="display: none;"' ?>>
			<p>Your order total meets the minimum of <?= money_format(NF_MONEY, $minOrder) ?>. However, once you checkout, the non-recurring items will be removed, and it will only be <span id="permTotal"><?= money_format(NF_MONEY, $totalsRecurring['net']) ?></span>, which is <span id="permShort"><?= money_format(NF_MONEY, $minOrder - $totalsRecurring['net']) ?></span> short, so it won't be created next time unless you either:</p>
			<ul class="normal">
				<li>mark more items as recurring now, or</li>
				<li>come back and add more items to your order before the next deadline</li>
			</ul>
		</div>
	<? } ?>
	<h3>Delivery day</h3>
	<? if ($orderDeliveryDay) {
		if ($orderDeliveryDay > $nextDeliveryDay) { ?><p class="notice">Your order is past the deadline for <? echo strftime(TF_HUMAN, $nextDeliveryDay); ?>. It<? } else echo 'Your order'; ?> will be delivered <?= Date::human($orderDeliveryDay) ?>.</p><?
	} else echo '<p class="notice">It appears that your route does not have any further delivery days. If you believe this to be an error, please contact us.</p>';

	if ($customer->personType & P_CSA && $order->orderType & O_CSA) $needsCsa = !count($order->getCsaItems());
	else $needsCsa = false;
	if (($totals['net'] < $minOrder) || $needsCsa) { ?>
		<div class="notice short">
			<? if ($totals['net'] < $minOrder) { ?>
				<p>You need <?= money_format(NF_MONEY, $minOrder - $totals['net']) ?> more! <a href="order.php" class="button">&larr; go back to market</a></p>
			<? } else if ($needsCsa) { ?>
				<p>Please make sure you have at least one CSA item (e.g., a Healthy Harvest Box) in your order, and that it is marked as permanent (hint: clicking the <img src="img/inf.png" class="icon" alt="infinity" title="infinity"/> icon will make it permanent).</p>
			<? } ?>
		</div>
	<? } else include ($path . '/market/templates/payActions.tpl.php'); ?>
</div>
