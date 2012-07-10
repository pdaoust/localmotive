<?php if (isset($_REQUEST['action'])) {
	if (($_REQUEST['action'] == 'cancelOrder' && !$order->orderID) || $_REQUEST['action'] == 'save') { ?>
<script type="text/javascript" language="JavaScript">
if (window.name == 'order') window.close();
else window.location = 'index.php';
</script><?
		die ();
	}
} ?><!-- TODO: can I replace Fancybox with something lighter, or is Fancybox already light? -->
<script type="text/javascript" language="JavaScript" src="<?= $config['docRoot'] ?>/js/jquery.fancybox.js"></script>
<script type="text/javascript" language="JavaScript">
var updateTotal = false;
function getItemInfo (itemID) {
	$('#itemInfo').attr('src', 'itemInfo.php?itemID=' + itemID);
}

function cancelOrder () {
	if (confirm('Are you sure you want to cancel this order?')) window.location = 'order.php?action=cancelOrder';
}

function addItem (itemID) {
	qtyOrd = $('#qtyOrd' + itemID);
	oldQtyOrd = qtyOrd.html();
	qtyOrd.html('adding...');
	$.ajax({
		data: {
			ajax: 1,
			action: 'addItem',
			itemID: itemID,
			quantity: $('#qty' + itemID).val()<?= $tour ? ', tour: 1, svcID: ' . $customer->personID : null ?>
		},
		dataType: 'json',
		url: 'order.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			qtyOrd = $('#qtyOrd' + itemID);
			qtyOrd.text('Couldn\'t add').addClass('notice');
			setTimeout(function () {
				qtyOrd.fadeOut(500, function () {
					qtyOrd.removeClass('notice').html(oldQtyOrd).fadeIn(1);
				});
			}, 3000);
		},
		success: function (r) {
			$('#shoppingListCol').html(r.shoppingListCol);
			$('#qty' + itemID).val('');
			qtyOrd.text(r.qty + ' ordered');
			bindEvents();
		},
		type: 'POST'
	});
}

$(function () {
	$('a.viewLg').fancybox();
});

</script>

<style type="text/css">
@import url('<?= $config['docRoot'] ?>/jquery.fancybox.css');
</style>

<!--<p><a href="javascript:cancelOrder()"><img src="img/n.png" class="icon" alt="X"/>Cancel order</a></p>-->

<h2><img class="iconLg" alt="" src="img/<?
if ($order->orderType & O_TEMPLATE) echo 'std_lg.png"/> Recurring o'; else echo 'ord_lg.png"/> O'; ?>rder <!--<? echo ($order->orderID ? '#' . $order->orderID : null); ?>--> for <? echo htmlEscape($customer->getLabel());
echo ' <!--(<span id="orderLabel" ' . ($editable ? 'class="editable" ' : null) . 'title="If you have multiple or special orders, you can add a description to your order to help remind you what this one is for.">' . ($order->label ? htmlEscape($order->label) : 'no description') . '</span>)-->'; ?></h2>

<div id="marketStation" style="margin-left: auto; margin-right: auto; text-align: center; width: 600px;">
	<img src="img/mTop.png" style="width: 600px; height: 125px;" alt="Market Station"/><br/>
	<? if ($category->itemID == 2) { ?><img src="img/mDairy.png" style="width: 120px; height: 204px;" alt="Dairy "/><? } else { ?><a href="order.php?category=dairy"><img src="img/mDairyF.png" style="width: 120px; height: 204px;" alt="Dairy " onmouseover="this.src='img/mDairy.png'" onmouseout="this.src='img/mDairyF.png'"/></a><? } ?><? if ($category->itemID == 3) { ?><img src="img/mMeats.png" style="width: 97px; height: 204px;" alt="Meats "/><? } else { ?><a href="order.php?category=meats"><img src="img/mMeatsF.png" style="width: 97px; height: 204px;" alt="Meats " onmouseover="this.src='img/mMeats.png'" onmouseout="this.src='img/mMeatsF.png'"/></a><? } ?><? if ($category->itemID == 4) { ?><img src="img/mBulk.png" style="width: 92px; height: 204px;" alt="Bulk "/><? } else { ?><a href="order.php?category=bulk"><img src="img/mBulkF.png" style="width: 92px; height: 204px;" alt="Bulk " onmouseover="this.src='img/mBulk.png'" onmouseout="this.src='img/mBulkF.png'"/></a><? } ?><? if ($category->itemID == 5) { ?><img src="img/mProduce.png" style="width: 105px; height: 204px;" alt="Produce "/><? } else { ?><a href="order.php?category=produce"><img src="img/mProduceF.png" style="width: 105px; height: 204px;" alt="Produce " onmouseover="this.src='img/mProduce.png'" onmouseout="this.src='img/mProduceF.png'"/></a><? } ?><? if ($category->itemID == 6) { ?><img src="img/mBaked.png" style="width: 97px; height: 204px;" alt="Baked goods "/><? } else { ?><a href="order.php?category=baked"><img src="img/mBakedF.png" style="width: 97px; height: 204px;" alt="Baked goods " onmouseover="this.src='img/mBaked.png'" onmouseout="this.src='img/mBakedF.png'"/></a><? } ?><? if ($category->itemID == 7) { ?><img src="img/mExtras.png" style="width: 89px; height: 204px;" alt="Extras "/><? } else { ?><a href="order.php?category=extras"><img src="img/mExtrasF.png" style="width: 89px; height: 204px;" alt="Extras " onmouseover="this.src='img/mExtras.png'" onmouseout="this.src='img/mExtrasF.png'"/></a><? } ?>
</div>

<?php
$lastWeek = roundDate(time(), T_WEEK) - T_WEEK;
$items = $category->getTree('itemType,sortOrder', 'tree');
/*$logger->log('Category', $category);
$logger->log('Items', $items);*/

$pathDepth = 0;

function renderMarket ($items) {
	global $pathDepth, $customer, $order, $lastWeek, $editable, $logger;
	$pathDepth ++;
	foreach ($items as $k => $v) {
		$thisItem = $v['node'];
		if ($thisItem->isOrderable($order)) {
			$price = $thisItem->getPrice($customer->personID);
			$qtyAvailable = $thisItem->getQuantityAvailable();
			/*if (($order->orderType & O_BASE) == O_RECURRING) {
				$isAvailableToThisOrder = $thisItem->getAvailableToRecurring();
			} else {
				$isAvailableToThisOrder = true;
			}
			if (($qtyAvailable === null || ($qtyAvailable / $price->multiple >= $price->multiple)) && $isAvailableToThisOrder) {
				$isAvailable = true;
			} else {
				$isAvailable = false;
			}*/
			$qtyOrdered = $order->getQuantity($thisItem->itemID);
			// START ITEM DISPLAY CODE ?>
			<li class="<?= ($thisItem->isActive() ? null : 'inactive') . ($thisItem->getOrganic() ? ' organic' : null) ?>" id="marketItem<?= $thisItem->itemID ?>">
				<? if ($thisItem->image) { ?>
					<a href="productImages/<?= $thisItem->itemID ?>_m.jpg" class="viewLg">
				<? } ?>
				<img src="productImages/<?= ($thisItem->image ? $thisItem->itemID . '_s.jpg' : ($thisItem->getCsaRequired() ? 'csa.png' : 'no.png')) ?>" alt="" class="product"/>
				<? if ($thisItem->image) { ?>
					</a>
				<? } ?>
				<span class="itemLabel">
					<?= ($thisItem->dateCreated > $lastWeek) ? ' <img src="img/new.png" class="icon" alt="new!"/>' : null ?>
					<?= $thisItem->getCsaRequired() ? ' <img src="img/std.png" class="icon" alt="CSA box" title="CSA box"/>' : null ?>
					<?= (($price->multiple > 1) ? $price->multiple . ' ct - ' : null) . htmlEscape($thisItem->label) ?>
				</span>
				<span class="addItem">
					<? if ($thisItem->isActive() && $editable) { ?>
						<input type="text" name="qty[<?= $thisItem->itemID ?>]" size="2" id="qty<?= $thisItem->itemID ?>" class="listQty"/>
						<input type="button" value="+" onclick="addItem(<?= $thisItem->itemID ?>)" style="padding: 0;"<?= (is_int($qtyAvailable) && $qtyAvailable <= $qtyOrdered) ? ' disabled title="You have ordered the last of the available items"' : null ?>/>
					<? } ?>
				</span>
				<? if ($qtyAvailable && $qtyAvailable < $thisItem->runningOutQuantity) { ?>
					<span class="notice">Only a few left!</span>
				<? } ?>
				<span class="unitPrice"><?= money_format(NF_MONEY, $price->price) ?></span>
				<span class="qtyOrd" id="qtyOrd<?= $thisItem->itemID ?>"><?= $qtyOrdered ? $qtyOrdered . ' ordered' : null ?></span>
			</li>
			<? // END ITEM DISPLAY CODE ?>
		<? } else if ($thisItem->hasOrderableChildren($order) || ($pathDepth == 1)) {
			$headerLevel = ($pathDepth > 5 ? 5 : $pathDepth); ?>
			<section class="marketSection">
				<h<?= ($headerLevel + 1) . ($thisItem->isActive() ? null : ' class="inactive"') ?>><?= $thisItem->getLabel() ?></h<?= $headerLevel ?>>
				<?= $thisItem->description ? '<p>'.htmlEscape($thisItem->description).'</p>' : null ?>
				<? if (count($v['children'])) { ?>
					<ul class="store">
						<? renderMarket($v['children']); ?>
					</ul>
				<? } else { ?>
					<p class="inactive">No items in this category</p>
				<? } ?>
			</section>
		<? }
	}
	$pathDepth --;
}

renderMarket($items);

?>
<!--<? if ($editable) { ?><input type="submit" value="Add items" style="float: right; margin-right: 20px;"/></form><? } ?>-->

<div id="shoppingListCol">
<? include ($path . '/market/templates/shoppingListCol.tpl.php'); ?>
</div>

<? if ($editable) { ?>
<script type="text/javascript" language="JavaScript">
<? $noOrder = false;
include('orderHelpers.js.php'); ?>
</script>
<? } ?>
