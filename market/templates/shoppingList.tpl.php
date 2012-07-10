<h3 style="margin-top: 0;">Shopping list</h3>
<div id="shoppingList">
	<? if ($order->isFromRecurringOrder() && $user->customCancelsRecurring) { ?><p>This is your next recurring order. Once you start adding items, the recurring order will be cleared and your new items will appear. If you do not want your custom orders to cancel your recurring orders, please <a href="signup.php">edit your account details</a> (the option is near the bottom of the page).</p><? } ?>
	<table class="listing">
		<tr>
			<th>Item</th>
			<th class="qtyPrice">Qty/Price</th>
			<th class="figure">Disc</th>
			<th class="figure">Amt</th>
		</tr>
	<?php
	$specialPacking = 0;

	function renderShoppingList ($tree) {
		global $specialPacking, $customer, $editable, $order, $user;
		foreach ($tree as $v) {
			$thisItem = $v['node'];
			$classes = array ();
			if ($thisItem->getSpecialPacking()) {
				array_push($classes, 'specialPacking');
				if ($thisItem->itemType == I_ITEM) {
					$specialPacking ++;
				}
			}
			if (!$thisItem->isActive()) {
				array_push($classes, 'inactive');
			} ?>
			<tr <?= count($classes) ? ' class="'.implode(' ', $classes).'"' : null ?>>
				<? if ($thisItem->itemType & I_CATEGORY && count($v['children'])) { ?>
					<td class="category" colspan="4"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 2).htmlEscape($thisItem->label) ?></td>
					<? renderShoppingList($v['children']);
				} else {
					$price = $thisItem->getPrice($customer->personID);
					$unitPrice = $thisItem->getRealPrice();
					$discount = $thisItem->getDiscount(); ?>
					<td class="ordItm" id="listLabel<?= $thisItem->itemID ?>"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 2).($price->multiple > 1 ? $price->multiple . ' ct - ' : null) ?>
						<? if ($order->orderType & O_BASE == O_TEMPLATE && $editable) { ?>
							<a href="javascript:makePerm(<?= $thisItem->itemID ?>)">
								<img src="img/inf<?= $thisItem->permanent ? null : '_g' ?>.png" class="icon" id="perm<?= $thisItem->itemID ?>" alt="Change the permanency of this item" title="<?= ($thisItem->permanent ? 'recurring item' : 'one-time item') . ', change to ' . (!$thisItem->permanent ? 'recurring' : 'one-time') ?>"/>
							</a>
						<? }
						if ($editable) { ?>
							<a href="javascript:deleteItem(<?= $thisItem->itemID ?>)">
								<img src="img/del.png" class="icon" title="remove item" alt="remove item"/>
							</a>
						<? } ?>
						<?= $thisItem->label ?>
						<? if (in_array('specialPacking', $classes)) { ?>
							<img src="img/cold.png" class="icon" alt="Perishable" title="Perishable!"/>
						<? }
						if (isset($errorCode) && isset($itemID)) {
							if ($itemID == $thisItem->itemID && $errorCode) { ?>
								<span class="notice">This item cannot be removed!</span>
							<? }
						} ?>
					</td>
					<td id="priceQty<?= $thisItem->itemID ?>">
						<? if ($editable || $customer->isIn($user, false)) { ?>
							<input type="text" size="2" maxlength="3" id="listQty<?= $thisItem->itemID ?>" data-itemid="<?= $thisItem->itemID ?>" value="<?= $thisItem->getQtyGrouped() ?>" class="listQty"/>
						<? } else { ?>
							<?= $thisItem->getQtyGrouped() ?>
						<? } ?>
						@ $
						<? $unitPriceFormatted = money_format(NF_MONEY_NOCURR, $unitPrice);
						if ($customer->isIn($user, false)) { ?>
							<input type="text" size="6" maxlength="8" id="listPrice<?= $thisItem->itemID ?>" data-itemid="<?= $thisItem->itemID ?>" value="<?= $unitPriceFormatted ?>" class="listPrice"/>
						<? } else { ?>
							<?= $unitPriceFormatted ?>
						<? } ?>
						ea
					</td>
					<td class="red number"><?= (float) $discount ? (float) $discount . '%' : '&nbsp;' ?></td>
					<td class="number"><?= money_format(NF_MONEY, $thisItem->getSubtotalOrdered()) ?></td>
				<? } ?>
			</tr>
		<? }
	}

	if ($orderItems = $order->getOrderItemsInTree('itemType,label')) {
		$orderItems = array_shift($orderItems);
		$orderItems = $orderItems['children'];
		renderShoppingList($orderItems);

		$totals = $order->getTotal(); ?>
		<tbody class="totals">
			<tr>
				<th colspan="3">Subtotal</th>
				<td><?= money_format(NF_MONEY, $totals['net']) ?></td>
			</tr>
			<?php
			$stars = $order->getStars();
			if ($stars > 0) { ?>
				<tr>
					<th colspan="3">Stars</th>
					<td>
						<span class="red"><?= str_repeat('&#x2605;', (int) $stars) ?></span>
						<?= (int) $stars ?>%)
					</td>
				</tr>
			<? }
			$discount = $order->getDiscount();
			if ($discount) { ?>
				<tr>
					<th colspan="3">Discount</th>
					<td><?= (float) $discount ?>%</td>
				</tr>
			<? }
			if ($stars + $discount) { ?>
				<tr>
					<th colspan="3">Subtotal (after disc)</th>
					<td><?= money_format(NF_MONEY, $totals['net']) ?></td>
				</tr>
			<? } ?>
			<tr>
				<th colspan="3">HST</th>
				<td><?= money_format(NF_MONEY, $totals['hst']) ?></td>
			</tr>
			<? if ($totals['shipping']) { ?>
				<tr>
					<th colspan="3">Shipping (<?= (float) $order->shipping ?>%)</th>
					<td><?= money_format(NF_MONEY, $totals['shipping']) ?></td>
				</tr>
			<? }
			if ($totals['surcharge']) { ?>
				<tr>
					<th colspan="3">Card processing surcharge (<?= (float) $order->surcharge ?>%)</th>
					<td><?= money_format(NF_MONEY, $totals['surcharge']) ?></td>
			</tr>
			<? } ?>
			<tr>
				<th colspan="3">Total</th>
				<td><?= money_format(NF_MONEY, $totals['gross']) ?></td>
			</tr>
		</tbody>
	<? } ?>
	</table>
	<? if ($specialPacking) { ?>
		<p class="specialPacking"><img src="img/cold.png" class="icon" alt="Perishable" title="Perishable!"/> <strong>Note:</strong> Please leave a cooler and enough cold packs for the item<?= $specialPacking > 1 ? 's' : null ?> highlighted in blue.</p>
	<? } ?>
</div>
