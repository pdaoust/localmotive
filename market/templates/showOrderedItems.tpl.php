<h2>Show ordered items for <? echo strftime(TF_HUMAN, $nextDeliveryDay); ?></h2>
<table class="listing">
	<tr class="odd">
		<th>Item</th>
		<th class="qty">Qty</th>
	</tr>
<?php
$i = 0;
foreach ($orderItems as $thisItem) {
	$specialPacking = $thisItem->getSpecialPacking();
	$isActive = $thisItem->isActive();
	$isItem = ($thisItem->isLeafNode() || !$thisItem->isInTree());
	echo "\t<tr class=\"" . ($i % 2 ? 'even' : 'odd') . ($specialPacking && $isItem ? ' specialPacking' : null) . ($isActive ? null : ' inactive') . "\">\n";
	if (!$isItem) { ?>
		<td class="category" colspan="2"><? echo str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . htmlEscape($thisItem->label); ?></td>
	<?php } else {
		echo "\t\t<td>" . str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . htmlEscape($thisItem->label) . "</td>\n";
		echo "\t\t<td class=\"number\">" . $quantities[$thisItem->itemID] . "</td>\n";
		echo "\t</tr>\n";
	}
	$i ++;
}
?>
</table>
