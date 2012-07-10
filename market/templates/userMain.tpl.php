<h2>Welcome to the Localmotive Market, <em class="properName"><?= htmlEscape($user->contactName) ?></em>!</h2>

<? if ($user->personType & P_CUSTOMER) {
	if ($user->getBalance() < 0 && $user->getPayTypeID() == PAY_PAYPAL) showPaymentForm();
	$canCustomOrder = $user->canCustomOrder();
	if ($config['marketOpen']) {
		if ($openOrder) {
			$nextDay = $openOrder->getNextDeliveryDay(null, false, false);
			$nextActualDay = $openOrder->getNextDeliveryDay(null, true);
			$cutoffDay = $openOrder->getCutoffDay();
			$nextOrder = $openOrder->hasCreatedOrder($nextDay, false);
			$minOrder = ($user->getRouteID(false) ? $user->getMinOrderDeliver() : $user->getMinOrder()); ?>
			<h3><a href="order.php?orderID=<?= $openOrder->orderID ?>"><img src="img/std.png" class="icon" alt=""/> Order items from the market<!--<?= $openOrder->label ? ' (' . htmlEscape($openOrder->label) . ')' : null ?>--></a></h3>
			<? if ($totals['net'] < $minOrder || ($nextOrder && !$nextOrder->getDateCanceled()) || ($user->personType & P_CSA && $openOrder->orderType & O_CSA && !$openOrder->hasOtherCsaItem())) { ?>
				<div class="notice">
					<? if ($nextOrder && !$nextOrder->getDateCanceled()) { ?>
						<p>This order has already been processed for your next delivery. If you make changes to it, they will not take effect until the delivery after next.</p>
					<? } else if ($nextDay < $nextActualDay) { ?>
						<p>The deadline for your next delivery is past. If you make changes to your order, they will not take effect until the delivery after next.</p>
					<? }
					if ($totals['net'] < $minOrder) { ?>
						<p>Your order is <?= money_format(NF_MONEY, $minOrder - $totals['net']) ?> below the minimum order amount of <?= money_format(NF_MONEY, $minOrder) ?>. Please add items to ensure your order can be shipped.</p>
					<? }
					if ($user->personType & P_CSA && $openOrder->orderType & O_CSA && !$openOrder->hasOtherCsaItem()) { ?>
						<p>Your order does not have any CSA items on it. Please add a CSA item (hint: look for the <img src="img/std.png" class="icon" alt="csa"/> icon) to ensure your order can be shipped.</p>
					<? } ?>
				</div>
			<? } ?>
			<ul class="orderDetails">
				<li>
					<b><?= $openOrder->orderType & O_TEMPLATE ? 'Next d' : 'D' ?>elivery day</b>
					<time datetime="<?= strftime(TF_HTML5, $nextDay) ?>"><?= Date::human($nextDay) . ($nextOrder ? ($nextOrder->getDateCanceled() ? ' canceled' : null) : ($nextDay < $nextActualDay ? ' (not created)' : null)) ?></time>
				</li>
				<li>
					<b>Order total</b>
					<?
					if ($totals['gross'] == $totals['net']) echo money_format(NF_MONEY, $totals['gross']);
					else {
						echo '<span class="multiple">';
						echo money_format(NF_MONEY, $totals['net']).($totals['net'] < $minOrder ? ' <em title="This order needs to be at least '.money_format(NF_MONEY, $minOrder).' before it can be created and shipped.">(needs '.money_format(NF_MONEY, $minOrder - $totals['net']).' more)</em>' : null);
						if ($totals['hst']) echo '<br/>+ '.money_format(NF_MONEY, $totals['hst']).' HST';
						if ($totals['pst']) echo '<br/>+ '.money_format(NF_MONEY, $totals['pst']).' PST';
						if ($totals['shipping']) echo '<br/>+ '.money_format(NF_MONEY, $totals['shipping']).' shipping';
						echo '<br/>= '.money_format(NF_MONEY, $totals['gross']).' total';
						echo '</span>';
					} ?>
				</li>
				<? if ($openOrder->orderType & O_TEMPLATE) { ?>
					<li>
						<b>Occurs</b>
						<?= $openOrder->getPeriod() ?>
					</li>
				<? }
				if ($openOrder->orderType & O_DELIVER) {
					$cutoffDay = $openOrder->getCutoffDay(); ?>
					<li>
						<b>Order deadline</b>
						<time datetime="<?= strftime(TF_HTML5, $cutoffDay) ?>"><?= Date::human($cutoffDay) ?></time>
					</li>
				<? } ?>
			</ul>
			<p>To put your order on hold, <a href="order.php?orderID=<?= $openOrder->orderID ?>">enter the market</a>, scroll down to the bottom, and look for the form that says 'Put on hold'. Enter a date, and then press 'Save' (rather than 'Check out') at the bottom of the page.</p>
		<? } else if ($canCustomOrder) {
			$nextDay = $user->getNextDeliveryDay(); ?>
			<h3><a href="order.php?orderType=sale" class="icon nord"><img src="img/nord.png" class="icon" alt=" "/> Order items from the market</a></h3>
			<p>Shop for items for your next delivery on <time datetime="<?= strftime(TF_HTML5, $nextDay) ?>"><?= strftime(TF_HUMAN, $nextDay) ?></time>.</p>
		<? } else {
			$nextDay = $user->getNextDeliveryDay(); ?>
			<h3><a href="order.php" class="icon nord"><img src="img/nord.png" class="icon" alt=" "/> Create a new order</a></h3>
			<p>Resume your recurring order, with the first delivery <time datetime="<?= strftime(TF_HTML5, $nextDay) ?>"><?= Date::human($nextDay) ?>.</p>
		<? }
	}
	if (!$config['marketOpen']) { ?><p class="notice">Note: The market is currently closed.</p><? } ?>
	<h3><a href="activity.php?style=normal"><img src="img/act.png" class="icon" alt=" "/> Account activity</a></h3>
	<p>Get a statement of your credits, payments, and charges.</p>
	<ul class="orderDetails">
		<li><b>Account balance</b> <? $bal = $user->getBalance();
		echo '<span class="' . ($bal >= 0 ? 'credit' : 'debit') . '">' . money_format(NF_MONEY, abs($bal)) . ($bal > 0 ? ' credit' : ($bal < 0 ? ' owing' : null)) . '</span>'; ?></li>
	</ul>
	<p><a href="<?= $secureUrlPrefix  . $config['docRoot'] ?>/market/payment.php"><img src="img/pay.png" class="icon" alt=" "/> Make a payment</a></p>
	
	<h3><a href="orderHistory.php?recursive=1"><img src="img/ordh.png" class="icon" alt=" "/> Order history</a></h3>
	<p>Check the date, contents, and status of any order you have placed.</p>
	<h3><a href="signup.php"><img src="img/edit.png" class="icon" alt=" "/> Edit account details</a></h3>
	<p>Change your name, password, address, route or depot, and directions.</p>

<? } ?>

<!--</div>-->
<?php
$nextDeliveryDay = strftime('%Y-%m-%d', getNextDeliveryDay(null, false));
$deliveryDays = getDeliveryDays(true);
$daysOff = array ();
for ($i = 0; $i < 7; $i ++) {
	if (!in_array($i, $deliveryDays)) $daysOff[] = ($i ? $i : 7);
}
$daysOff = implode(', ', $daysOff);
?>
<div id="caldiv" style="position:absolute; visibility:hidden; margin: 0; padding: 0;"></div>
