<?php

require_once ('marketInit.inc.php');

if (!$user = tryLogin()) die ('cannot login');

$pageTitle = 'Localmotive - Your account';
$pageArea = 'market';
if ($user->isAdmin() && !($user->personType & P_CANORDER)) $noSidebars = true;

require_once ($path . '/market/classes/deliveryDay.inc.php');
$nextDeliveryDay = getNextDeliveryDay (null, false);

include ($path . '/header.tpl.php');

if ($user->personType & P_CANORDER) {
	require_once ($path . '/market/classes/order.inc.php');
	require_once ($path . '/market/classes/item.inc.php');
	require_once ($path . '/market/classes/orderItem.inc.php');
	require_once ($path . '/market/classes/price.inc.php');
	require_once ($path . '/market/classes/route.inc.php');
}

if ($user->personType & P_CUSTOMER) {
	$db->startLogging();
	$openOrder = $user->hasOpenOrder(array(O_RECURRING | O_EDITABLE, O_BASE_EDITABLE));
	$db->stopLogging();
	if (!$openOrder) $openOrder = $user->hasOpenOrder(O_SALE);
	if (is_object($openOrder) && $openOrder->label == 'Yearly membership fee') $openOrder = false;
	if ($openOrder) $totals = $openOrder->getTotal();
	$payPal = new PayType(PAY_PAYPAL);
	include ($path . '/market/templates/userMain.tpl.php');
}

if ($user->isAdmin()) include ($path . '/market/templates/adminMain.tpl.php');

include ($path . '/footer.tpl.php');

?>
