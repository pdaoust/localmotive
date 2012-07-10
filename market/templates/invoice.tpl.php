<?
if (!isset($isAdmin)) $isAdmin = false;
if (!isset($hidePackingIcon)) $hidePackingIcon = false;
if (!isset($showBlankFields)) $showBlankFields = false;
if (!isset($hideLogo)) $hideLogo = false;
if (isset($fromEmail)) {
	if ($fromEmail) { ?>
<style type="text/css">
<? include ('../styles_global.css'); ?>
</style>
	<? }
} else $fromEmail = false;
?>
<?php if (!$hideLogo) { ?>
	<div class="invoiceLogo"><img src="<? if ($fromEmail) echo $secureUrlPrefix . $config['docRoot']; ?>/img/logoInvoice.png" alt="Localmotive"/></div>
	<div class="invoiceHeader">
		<h2>Localmotive Organic Delivery</h2>
		<p>2351 Allendale Rd<br/>
		Okanagan Falls, BC V0H 1R2<br/>
		<strong>Phone</strong> 250-497-6577<br/>
		<strong>E-mail</strong> feedme@localmotive.ca<br/>
		<strong>Website</strong> www.localmotive.ca</p>
<? } else { ?><div class="invoiceHeader"><? } ?>
	<h3>Invoice #<? echo $order->orderID; ?> <span style="font-size: smaller; font-style: italic; color: #888888;"> for <?= $customer->getLabel() . ($order->label ? ' (' . htmlEscape($order->label) . ')' : null) // TODO: might not be the best date to use; maybe I should break down and add a dateCheckedOut ?></span></h3>
</div>
<dl class="orderDetails">
	<?
	$addresses = $customer->getAddresses(AD_SHIP + AD_MAIL);
	if (count($addresses)) {
		foreach ($addresses as $thisAddy) { ?>
		<dt><?= ($thisAddy->addressType & AD_SHIP ? ($thisAddy->addressType & AD_MAIL ? 'Shipping/billing' : 'Shipping') : 'Billing') ?> address</dt>
		<dd><?= htmlEscape($thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null)) ?><br/>
		<?= htmlEscape($thisAddy->city . ', ' . $thisAddy->prov . ' ' . $thisAddy->postalCode) ?><br/>
		Phone <?= htmlEscape(($thisAddy->phone ? $thisAddy->phone : $customer->phone) . ($thisAddy->directions ? '<br/>' . $thisAddy->directions : null)) ?></dd><? }
	}
	if ($order->notes) { ?>
		<dt>Notes</dt>
		<dd><?= htmlEscape($order->notes) ?></dd>
	<? } ?>
	<dt>Started</dt>
	<dd><? echo strftime(TF_HUMAN, $order->getDateStarted()); ?></dd>
	<? if ($order->getDateToDeliver()) { ?>
		<dt>Deliver on</dt>
		<dd><?= strftime(TF_HUMAN, $order->getDateToDeliver()) ?></dd>
	<? }
	if ($order->getDateCompleted()) { ?>
		<dt>Confirmed</dt>
		<dd><?= strftime(TF_HUMAN, $order->getDateCompleted()) ?></dd>
	<? }
	if ($order->getDateCanceled()) { ?>
		<dt>Canceled</dt>
		<dd><?= strftime(TF_HUMAN, $order->getDateCanceled()) ?></dd>
	<? }
	if ($order->getDateDelivered()) { ?>
		<dt>Delivered</dt>
		<dd><?= strftime(TF_HUMAN, $order->getDateDelivered()) ?></dd>
	<? } ?>
</dl>

<table class="listing">
	<tr class="even">
		<? if (!$hidePackingIcon) { ?><th class="qty"><img src="img/bin.png" class="icon" alt="packed"/></th><? } ?>
		<th class="qty">Qty</th>
		<th>Item</th>
		<th class="figure">Unit $</th>
		<th class="figure">Discount</th>
		<th class="figure">Amount</th>
		<? if ($showBlankFields) { ?><th class="figure">Credit</th><? } ?>
	</tr>
<?php
$i = 0;
$orderItems = $order->getOrderItemsInTree();
array_shift($orderItems);
foreach ($orderItems as $thisItem) {
	$isActive = $thisItem->isActive();
	$specialPacking = $thisItem->getSpecialPacking();
	echo "\t<tr class=\"" . ($i % 2 ? 'even' : 'odd') . ($isActive ? null : ' inactive') . ($specialPacking && $thisItem->isLeafNode() ? ' specialPacking' : null) . "\">\n";
	if (!$hidePackingIcon) echo "\t\t<td>&nbsp;</td>\n";
	switch (strtolower(get_class($thisItem))) {
		case 'item': ?>
		<td>&nbsp;</td>
		<td class="category" colspan="<? echo ($showBlankFields ? '5' : '4'); ?>"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . htmlEscape($thisItem->label) ?></div></td>
		<?php break;
		case 'orderitem':
			$itemPrice = $thisItem->getPrice($customer->personID);
			if (!is_null($thisItem->unitPrice)) $unitPrice = $thisItem->unitPrice;
			else $unitPrice = $itemPrice->price;
			$discount = $thisItem->getDiscount();
			echo "\t\t<td>" . ($thisItem->quantityOrdered / $itemPrice->multiple > 1 ? '<span class="mult">' : null) . ($thisItem->quantityOrdered / $itemPrice->multiple) . ($thisItem->quantityOrdered / $itemPrice->multiple > 1 ? '</span>' : null) . "</td>\n";
			echo "\t\t<td>" . str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . (($itemPrice->multiple > 1) ? $itemPrice->multiple . ' ct - ' : null) . htmlEscape($thisItem->label) . "</td>\n";
			echo "\t\t<td class=\"number\">" . money_format(NF_MONEY, $unitPrice) . "</td>\n";
			echo "\t\t<td class=\"number\">" . ((float) $discount ? (float) $discount . '%' : '&nbsp;') . "</td>\n";
			echo "\t\t<td class=\"number\">" . money_format(NF_MONEY, ($thisItem->quantityOrdered * $unitPrice / $itemPrice->multiple * ((100 - $discount) / 100))) . "</td>\n";
			if ($showBlankFields) echo "\t\t<td>&nbsp;</td>\n";
	}
	echo "\t</tr>\n";
	$i ++;
} ?>
	<tbody class="totals">
<?
$stars = $order->getStars();
$discount = $order->getDiscount();
$totals = $order->getTotal();
$colspan = ($hidePackingIcon ? 4 : 5);
if ($totals['hst'] || $totals['pst'] || $stars || $discount || $totals['surcharge'] || $totals['shipping']) { ?>
	<tr>
		<td colspan="<?= $colspan ?>" >Subtotal</th>
		<td class="number"><?= money_format(NF_MONEY, $totals['net'] + $totals['discount']) ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr>
	<? if ($stars > 0) { ?>
	<tr>
		<td colspan="<?= $colspan ?>">Stars</td>
		<td class="number"><span class="red"><?= (int) $stars ?></span> (<?= (int) $stars ?>%)'; ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr><? }
	if ($discount) { ?>
	<tr>
		<td colspan="<?= $colspan ?>">Discount (<?= (float) $discount ?>%)</td>
		<td class="number"><?= money_format(NF_MONEY, $totals['discount']) ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr><? }
	if (($stars > 0 ? $stars : 0) + $discount) { ?>
	<tr>
		<td colspan="<?= $colspan ?>">Subtotal (after disc)</td>
		<td class="number"><?= money_format(NF_MONEY, $totals['net']) ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr><? } ?>
	<tr>
		<td colspan="<?= $colspan ?>">HST</td>
		<td class="number"><?= money_format(NF_MONEY, $totals['hst']) ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr>
<!--	<tr>
		<td colspan="<?= $colspan ?>">PST</td>
		<td class="number"><? echo money_format(NF_MONEY, $totals['pst']); ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr> -->
	<? if ($totals['shipping']) { ?>
	<tr>
		<th colspan="<?= $colspan ?>">Shipping (<?= $order->shipping ?>%)</th>
		<td><?= money_format(NF_MONEY, $totals['shipping']) ?></td>
	</tr>
	<? }
	if ($totals['surcharge']) { ?>
	<tr>
		<th colspan="<?= $colspan ?>">Card processing surcharge (<?= $order->surcharge ?>%)</th>
		<td><?= money_format(NF_MONEY, $totals['surcharge']) ?></td>
	</tr>
	<? }
} ?>
	<tr>
		<td colspan="<?= $colspan ?>">Total</td>
		<td class="number"><?= money_format(NF_MONEY, $totals['gross']) ?></td>
		<? if ($showBlankFields) { ?><td>&nbsp;</td><? } ?>
	</tr>
	</tbody>
	<?php
		if (!isset($journalEntries)) $journalEntries = $order->getJournalEntries(true);
		if (!count($journalEntries)) $journalEntries = false;
		if ($journalEntries) {
			$payTypes = getPayTypes();
			echo "\t<tr class=\"categoryHeader\"><td colspan=\"" . ($colspan + ($showBlankFields ? 2 : 1)) . "\">Account summary</td></tr>\n";
			reset($journalEntries);
			$firstJournalEntry = current($journalEntries);
			$lastJournalEntry = end($journalEntries);
			reset($journalEntries);
			// TODO: I should really strip this out of here; it's BAD.
			if (!$db->query('SELECT MIN(journalEntryID) AS firstEntry FROM journalEntry WHERE personID = ' . $customer->personID)) {
				databaseError($db);
				die ();
			}
			$first = false;
			if ($firstEntry = $db->getRow()) {
				if ($firstEntry['firstEntry'] == $firstJournalEntry->journalEntryID) $first = true;
			}
			echo "<tbody class=\"totals\">\n";
			echo "\t<tr>\n";
			echo "\t\t<td colspan=\"" . $colspan . "\">" . ($first ? 'Membership fee' : 'Old balance') . "</td>\n";
			echo "\t\t<td class=\"number\">" . ($firstJournalEntry->orderID != $order->orderID ? money_format(NF_MONEY_ACCT, $firstJournalEntry->calculateBalance()) : money_format('%(n', 0)) . "</td>\n";
			if ($showBlankFields) echo "\t\t<td>&nbsp;</td>\n\t</tr>\n";
			foreach ($journalEntries as $thisJournalEntry) {
				if ($thisJournalEntry->orderID == $order->orderID) {
					echo "\t<tr>\n";
					echo "\t\t<td colspan=\"" . $colspan . "\">" . htmlEscape(($thisJournalEntry->payTypeID != PAY_ACCT ? $payTypes[$thisJournalEntry->payTypeID]->label . ' ' : null) . $thisJournalEntry->notes) . "</td>\n";
					echo "\t\t<td class=\"number\">" . money_format(NF_MONEY_ACCT, $thisJournalEntry->amount) . "</td>\n";
					if ($showBlankFields) echo "\t\t<td></td>\n\t</tr>\n";
				}
			}
			echo "\t<tr>\n";
			echo "\t\t<td colspan=\"" . $colspan . "\">New balance</td>\n";
			echo "\t\t<td class=\"number\">" . money_format(NF_MONEY_ACCT, $lastJournalEntry->calculateBalance()) . "</td>\n";
			if ($showBlankFields) echo "\t\t<td>&nbsp;</td>\n\t</tr>\n";
		}
		if ($showBlankFields) { ?>
	<tr>
		<td colspan="<?= $colspan ?>">Adjustments on this invoice<? if (($stars > 0 ? $stars : 0) + $discount) echo ' (-' . ($stars + $discount) . '% disc)'; ?></td>
		<td>&nbsp;</td>
		<td></td>
	</tr>
	<tr>
		<td colspan="<?= $colspan ?>">New balance (after adjustments)</td>
		<td>&nbsp;</td>
		<td></td>
	</tr><? } ?>
	</tbody>
</table>
<p>Note: All negative figures indicate an amount owing on your account, and all positive figures indicate a credit.</p>
