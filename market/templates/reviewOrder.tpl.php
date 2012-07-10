<script type="text/javascript" language="JavaScript">
<? if (($order->orderType & O_BASE) == O_RECURRING && $order->orderType & O_DELIVER) { ?>
var updateTotal = true;
<? }
include('orderHelpers.js.php'); ?>
</script>
<h2>Review order </h2>
<p><a href="order.php" class="button">&larr; go back to market</a></p>
<? if (($order->orderType & O_BASE) == O_RECURRING) { ?><p class="info">If you like, you can mark some items as 'permanent'. They'll be shipped with every delivery, rather than just this once. Click the <img src="img/inf.png" alt="permanent" title="permanent"/> icon beside the items you want to make permament. (Note: some items cannot be permanent, as they're special seasonal items. <? if ($customer->personType & P_CSA && $order->orderType & O_CSA) { ?>Some items will stay permanent, as they're part of your commitment.<? } ?>)</p><? } ?>
<div id="shoppingListCol">
	<? include ($path . '/market/templates/shoppingList.tpl.php'); ?>
	<? include ($path . '/market/templates/reviewActions.tpl.php'); ?>
</div>
