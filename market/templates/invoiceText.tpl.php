<?
if (!isset($hidePackingIcon)) $hidePackingIcon = false;
if (!isset($showBlankFields)) $showBlankFields = false;
if (!isset($hideLogo)) $hideLogo = false;
?>INVOICE #<? echo $order->orderID; ?> | <? echo strftime(TF_HUMAN, $order->getDateStarted()) . "\n"; // TODO: might not be the best date to use; maybe I should break down and add a dateCheckedOut ?>

        Name    <? echo $customer->contactName . "\n"; ?>
<? if ($customer->groupName) echo 'Organisation    ' . $customer->groupName . "\n"; ?>
     Address    <? echo $customer->address1; if ($customer->address2) echo "\n                " . $customer->address2; echo "\n";?>
                <? echo $customer->city . "\n";
if ($customer->directions) echo '  Directions    ' . wordwrap($customer->directions, 59, "\n                ", true) . "\n"; ?>       Phone    <? echo $customer->phone; ?>
<? if ($order->notes) echo '       Notes    ' . wordwrap($order->notes, 59, "\n                ", true);
echo "\n"; ?>

---------------------------------------------------------------------------
Qty  Item                                         Unit $  Disc  Amt   <? if ($showBlankFields) echo '  Cr'; echo "\n";?>
---------------------------------------------------------------------------
<?php
$i = 0;
$orderItems = $order->getOrderItemsInTree();
array_shift($orderItems);
foreach ($orderItems as $thisItem) {
	$isActive = $thisItem->isActive();
	switch (strtolower(get_class($thisItem))) {
		case 'item':
			echo '     ' . str_repeat(' ', $thisItem->getDepth() - 1) . $thisItem->label;
			break;
		case 'orderitem':
			$itemPrice = $thisItem->getPrice($customer->personID);
			if (!is_null($thisItem->unitPrice)) $unitPrice = $thisItem->unitPrice;
			else $unitPrice = $itemPrice->price;
			$discount = $thisItem->getDiscount();
			$discount = $thisItem->getDiscount();
			$specialPacking = $thisItem->getSpecialPacking();
			echo sprintf('%-3s', (int) $thisItem->quantityOrdered / $itemPrice->multiple) . '  ';
			$label = str_repeat(' ', $thisItem->getDepth() - 1) . (($itemPrice->multiple > 1) ? $itemPrice->multiple . ' ct - ' : null) . $thisItem->label;
			if (strlen($label) > 42) $label = substr($label, 0, 39) . '...';
			echo sprintf('%-42s', $label) . '  ';
			echo sprintf('%6s', money_format(NF_MONEY, $thisItem->unitPrice)) . '  ';
			echo ((float) $discount ? sprintf('%4s', (float) $discount . '%') : '    ') . '  ';
			echo sprintf('%7s', money_format(NF_MONEY, ($thisItem->quantityOrdered * $thisItem->unitPrice / $itemPrice->multiple * ((100 - $discount) / 100))));
	}
	echo "\n";
}
echo "\n";
$stars = $order->getStars();
$discount = $order->getDiscount();
$totals = $order->getTotal(false);
if ($totals['hst'] || $totals['pst'] || $stars || $discount || $totals['surcharge'])
	echo str_repeat(' ', 51) . 'Subtotal    ' . money_format(NF_MONEY, $totals['net']) . "\n";
if ($stars)
	echo str_repeat(' ', 54) . 'Stars    ' . str_repeat('&#x2605;', (int) $stars) . ' (' . (int) $stars . "%)\n";
if ($discount)
	echo str_repeat(' ', 51) . 'Discount    ' . sprintf('%6s', (float) $discount . '%') . "\n";
if ($stars + $discount)
	echo str_repeat(' ', 38) . 'Subtotal (after disc)    ' . sprintf('%6s', money_format(NF_MONEY, $subtotal)) . "\n";
if ($hst)
	echo str_repeat(' ', 56) . 'HST    ' . sprintf('%6s', money_format(NF_MONEY, $totals['hst'])) . "\n";
if ($pst)
	echo str_repeat(' ', 56) . 'PST    ' . sprintf('%6s', money_format(NF_MONEY, $totals['pst'])) . "\n";
if ($totals['shipping'])
	echo str_repeat(' ', ($order->shipping > 9 ? 45 : 46)) . 'Shipping (' . (float) $order->shipping . '%)    ' . sprintf('%6s', money_format(NF_MONEY, $totals['shipping']));
if ($totals['surcharge'])
	echo str_repeat(' ', ($order->surcharge > 9 ? 31 : 32)) . 'Card processing surcharge (' . (float) $order->surcharge . '%)' . sprintf('%6s', money_format(NF_MONEY, $totals['surcharge']));
echo str_repeat(' ', 54) . 'Total    ' . sprintf('%7s', money_format(NF_MONEY, $totals['gross'])) . "\n";
if (!isset($journalEntries)) $journalEntries = $order->getJournalEntries(true);
if (!count($journalEntries)) $journalEntries = false;
if ($journalEntries) {
	echo "\nAccount summary\n\n";
	reset($journalEntries);
	$firstJournalEntry = current($journalEntries);
	reset($journalEntries);
	echo str_repeat(' ', 48) . 'Old balance    ' . sprintf('%6s', ($firstJournalEntry->orderID != $order->orderID ? money_format(NF_MONEY, $firstJournalEntry->calculateBalance()) : money_format(NF_MONEY, 0))) . "\n";
	$payTypes = $getPayTypes();
	foreach ($journalEntries as $thisJournalEntry) {
		if ($thisJournalEntry->orderID == $order->orderID) {
			$balance = $thisJournalEntry->calculateBalance();
			echo sprintf('%59s     %6s', ($thisJournalEntry->payTypeID != PAY_ACCT ? $payType->label . ' ' : null) . $thisJournalEntry->notes, money_format(NF_MONEY, $thisJournalEntry->amount)) . "\n";
		}
	}
	$lastJournalEntry = end($journalEntries);
	echo str_repeat(' ', 48) . 'New balance    ' . sprintf('%6s', money_format(NF_MONEY, $lastJournalEntry->calculateBalance())) . "\n";
}
if ($showBlankFields) {
	echo sprintf('%60s', 'Adjustments on this invoice' . (($stars + $discount) ? ' -' . $stars + $discount . '% disc' : null)) . "\n";
	echo sprintf('%60s', 'Total (after adjustments)') . "\n";
} ?>
