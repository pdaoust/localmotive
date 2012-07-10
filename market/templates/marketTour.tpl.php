<script type="text/javascript" language="JavaScript">
function getItemInfo (itemID) {
	document.getElementById('itemInfo').src = 'itemInfo.php?itemID=' + itemID;
}
</script>

<style type="text/css">
	div#content { width: 1000px; }
</style>
<? if ($category->itemID >= 3 && $category->itemID <= 8) { ?>
<div id="marketStation" style="margin-left: auto; margin-right: auto; text-align: center;">
	<img src="img/mTop.png" style="width: 600px; height: 125px;" alt="Market Station"/><br/>
	<? if ($category->itemID == 3) { ?><img src="img/mDairy.png" style="width: 120px; height: 204px;" alt="Dairy "/><? } else { ?><a href="order.php?category=dairy"><img src="img/mDairyF.png" style="width: 120px; height: 204px;" alt="Dairy " onmouseover="this.src='img/mDairy.png'" onmouseout="this.src='img/mDairyF.png'"/></a><? } ?><? if ($category->itemID == 4) { ?><img src="img/mMeats.png" style="width: 97px; height: 204px;" alt="Meats "/><? } else { ?><a href="order.php?category=meats"><img src="img/mMeatsF.png" style="width: 97px; height: 204px;" alt="Meats " onmouseover="this.src='img/mMeats.png'" onmouseout="this.src='img/mMeatsF.png'"/></a><? } ?><? if ($category->itemID == 5) { ?><img src="img/mBulk.png" style="width: 92px; height: 204px;" alt="Bulk "/><? } else { ?><a href="order.php?category=bulk"><img src="img/mBulkF.png" style="width: 92px; height: 204px;" alt="Bulk " onmouseover="this.src='img/mBulk.png'" onmouseout="this.src='img/mBulkF.png'"/></a><? } ?><? if ($category->itemID == 6) { ?><img src="img/mProduce.png" style="width: 105px; height: 204px;" alt="Produce "/><? } else { ?><a href="order.php?category=produce"><img src="img/mProduceF.png" style="width: 105px; height: 204px;" alt="Produce " onmouseover="this.src='img/mProduce.png'" onmouseout="this.src='img/mProduceF.png'"/></a><? } ?><? if ($category->itemID == 7) { ?><img src="img/mBaked.png" style="width: 97px; height: 204px;" alt="Baked goods "/><? } else { ?><a href="order.php?category=baked"><img src="img/mBakedF.png" style="width: 97px; height: 204px;" alt="Baked goods " onmouseover="this.src='img/mBaked.png'" onmouseout="this.src='img/mBakedF.png'"/></a><? } ?><? if ($category->itemID == 8) { ?><img src="img/mExtras.png" style="width: 89px; height: 204px;" alt="Extras "/><? } else { ?><a href="order.php?category=extras"><img src="img/mExtrasF.png" style="width: 89px; height: 204px;" alt="Extras " onmouseover="this.src='img/mExtras.png'" onmouseout="this.src='img/mExtrasF.png'"/></a><? } ?>
</div>
<? } ?>

<h2><? echo ($_SESSION['pageArea'] == 'healthyharvest' ? 'Healthy Harvest' : 'Home Delivery'); ?> market tour</h2>

<div id="itemList">
	<h3 style="margin-top: 0;"><? echo $category->label; ?></h3>
	<? if ($category->description) echo '<p>' . $category->description . '</p>'; ?>
	<p class="tip">Tip: we've added extra info and photos to a lot of our items. Click on the item's name (your mouse pointer will turn into a question mark) and the extra info will appear in the 'Item info' box below.</p>
	<div id="itemTable">
		<table class="listing" style="width: 440px;">
	<?php
	$lastWeek = roundDate(time(), T_WEEK) - T_WEEK;
	$items = $category->getTree();
	array_shift($items);
	$i = 0;
	foreach ($items as $thisItem) {
		$isActive = $thisItem->isActive();
		$price = $thisItem->getPrice($customer->personID);
		$isLeafNode = $thisItem->isLeafNode();
		if (($order->orderType & O_BASE) == O_RECURRING) $isAvailableToRecurring = $thisItem->getAvailableToRecurring();
		else $isAvailableToRecurring = true;
		if (($isLeafNode && is_object($price)) || !$isLeafNode) {
			if ($isLeafNode) {
				$isAvailable = $thisItem->getQuantityAvailable();
				if (($isAvailable === true || ($isAvailable / $price->multiple >= $price->multiple)) && $isAvailableToRecurring) $isAvailable = true;
				else $isAvailable = false;
			} else $isAvailable = false;
			echo "\t\t\t<tr class=\"" . ($i % 2 ? 'even' : 'odd') . ($isActive && ($isAvailable || !$isLeafNode) ? null : ' inactive') . "\">\n";
			if ($isLeafNode) {
				echo "\t\t\t\t<td class=\"odd\"><div class=\"truncItemList\">" . str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . '<a href="javascript:getItemInfo(' . $thisItem->itemID . ')" class="itemLabel">' . $thisItem->label . '</a>' . ($thisItem->dateCreated > $lastWeek ? ' <img src="img/new.png" class="icon" alt="new!"/>' : null) . "</div></td>\n";
				echo "\t\t\t\t<td class=\"even\" style=\"text-align: right;\">" . ($isLeafNode ? money_format(NF_MONEY, $price->price) : null) . "</td>\n";
				echo "\t\t\t\t<td class=\"odd\">" . ($isLeafNode && $isAvailable && $isActive ? '<form method="POST" action="order.php" onsubmit="return addItem(this)"><input type="hidden" name="itemID" value="' . $thisItem->itemID . '"/><input type="hidden" name="action" value="addItem"/><input type="text" name="quantity" size="2"/><input type="submit" value="+"/></form>' : null) . "</td>\n";
			} else {
				echo "\t\t\t\t<td class=\"odd category\" colspan=\"3\"><div class=\"truncItemListCat\">" . str_repeat('&nbsp;&nbsp;&nbsp;', $thisItem->getDepth() - 1) . '<a href="javascript:getItemInfo(' . $thisItem->itemID . ')" class="itemLabel">' . $thisItem->label . '</a>' . "</div></td>\n";
			}
			echo "\t\t\t</tr>\n";
		}
		$i ++;
	}
	?>
		</table>
	</div>
	<h3>Item info</h3>
	<iframe id="itemInfo"></iframe>
</div>

<div id="shoppingListCol">
	<h3>Shopping list</h3>
	<p>As a customer, when you place an order, your items appear in this column.</p>
	<p><a href="healthyharvest_new_customer.php"><img src="../img/sign_up.jpg" alt="Sign up!" style="width: 110px; height: 120px; vertical-align: middle;"/></a></p>
</div>
<div style="clear: both;"></div>