<style type="text/css">
	div#main { width: 700px; }
</style>
<div>
	<img src="img/market_sign_size.gif" style="width: 700px; height: 80px;" alt="Market Station"/><br/>
	<img src="img/market_tarp_size.gif" style="width: 700px; height: 40px;" alt=" "/><br/>
	<? if ($category->itemID == 2) { ?><img src="img/market_station_dairy.gif" style="width: 140px; height: 238px;" alt="Dairy "/><? } else { ?><a href="market.php?category=dairy"><img src="img/market_station_dairy_faded.gif" style="width: 140px; height: 238px;" alt="Dairy " onmouseover="this.src='img/market_station_dairy.gif'" onmouseout="this.src='img/market_station_dairy_faded.gif'"/></a><? } ?><? if ($category->itemID == 3) { ?><img src="img/market_station_meats.gif" style="width: 114px; height: 238px;" alt="Meats "/><? } else { ?><a href="market.php?category=meats"><img src="img/market_station_meats_faded.gif" style="width: 114px; height: 238px;" alt="Meats " onmouseover="this.src='img/market_station_meats.gif'" onmouseout="this.src='img/market_station_meats_faded.gif'"/></a><? } ?><? if ($category->itemID == 4) { ?><img src="img/market_station_bulk.gif" style="width: 106px; height: 238px;" alt="Bulk "/><? } else { ?><a href="market.php?category=bulk"><img src="img/market_station_bulk_faded.gif" style="width: 106px; height: 238px;" alt="Bulk " onmouseover="this.src='img/market_station_bulk.gif'" onmouseout="this.src='img/market_station_bulk_faded.gif'"/></a><? } ?><? if ($category->itemID == 5) { ?><img src="img/market_station_produce.gif" style="width: 123px; height: 238px;" alt="Produce "/><? } else { ?><a href="market.php?category=produce"><img src="img/market_station_produce_faded.gif" style="width: 123px; height: 238px;" alt="Produce " onmouseover="this.src='img/market_station_produce.gif'" onmouseout="this.src='img/market_station_produce_faded.gif'"/></a><? } ?><? if ($category->itemID == 6) { ?><img src="img/market_station_baked.gif" style="width: 112px; height: 238px;" alt="Baked goods "/><? } else { ?><a href="market.php?category=baked"><img src="img/market_station_baked_faded.gif" style="width: 112px; height: 238px;" alt="Baked goods " onmouseover="this.src='img/market_station_baked.gif'" onmouseout="this.src='img/market_station_baked_faded.gif'"/></a><? } ?><? if ($category->itemID == 7) { ?><img src="img/market_station_extras.gif" style="width: 105px; height: 238px;" alt="Extras "/><? } else { ?><a href="market.php?category=extras"><img src="img/market_station_extras_faded.gif" style="width: 105px; height: 238px;" alt="Extras " onmouseover="this.src='img/market_station_extras.gif'" onmouseout="this.src='img/market_station_extras_faded.gif'"/></a><? } ?>
</div>

<div id="itemList">
	<h3><? echo $category->label; ?></h3>
	<table class="listing">
	<?php
	$items = $category->getTree(null, 'tree');
	array_shift($items);
	$i = 0;
	foreach ($items as $thisItem) {
		$isActive = $thisItem->isActive();
		$price = $thisItem->getPrice($user->personID);
		if ($isActive && is_object($price)) {
			$isLeafNode = $thisItem->isLeafNode();
			$isAvailable = $thisItem->getQuantityAvailable();
			if ($isAvailable === true || ($isAvailable / $price->multiple >= $price->multiple)) $isAvailable = true;
			else $isAvailable = false;
			echo "\t\t<tr class=\"" . ($i % 2 ? 'even' : 'odd') . ($isActive ? null : ' inactive') . "\">\n";
			echo "\t\t\t<td class=\"odd\">" . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $thisItem->depth) . (!$isLeafNode ? '<strong>' : null) . $thisItem->label . (!$isLeafNode ? '</strong>' : null) . "</td>\n";
			echo "\t\t\t<td class=\"even\">" . money_format(NF_MONEY, $price->price) . "</td>\n";
			echo "\t\t\t<td class=\"odd\">" . ($isLeafNode && $isAvailable ? '<form method="POST" action="market.php"><input type="hidden" name="action" value="addItem"/><input type="text" name="quantity" size="2"/><input type="submit" value="+"/></form>' : null) . "</td>\n";
			echo "\t\t</tr>\n";
		}
	}
	?>
	</table>
</div>

<div id="shoppingList">
<h3>Shopping list</h3>
</div>

<div id="status">
<table class="formLayout">
	<tr>
		<th>Name</th>
		<td><? echo $user->getLabel(); ?></td>
	</tr>
	<tr>
		<th>Delivery date</th>
		<td><? echo strftime('%A, %d %B', time() + $order->getNextDeliveryDay()); ?></td>
	</tr>
</table>
</div>

<pre><? print_r($order); ?></pre>
