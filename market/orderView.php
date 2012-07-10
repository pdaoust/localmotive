<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');

if (!$user = tryLogin()) die ();

$style = (isset($_REQUEST['style']) ? ($_REQUEST['style'] == 'dialogue' ? 'dialogue' : 'normal') : 'normal');

if (isset($_REQUEST['orderID'])) {
	$order = new Order ((int) $_REQUEST['orderID']);
	if (!$order->orderID) {
		include ($path . '/header' . $template . '.tpl.php');
		$error = 'No such order!';
		$errorDetail = 'There is no order #' . (int) $_REQUEST['orderID'] . ' in the database.';
		include ($path . '/market/templates/error.tpl.php');
		include ($path . '/footer' . $template . '.tpl.php');
		die ();
	}
	$customer = $order->getPerson();
	if (!$customer->isIn($user)) {
		include ($path . '/header' . $template . '.tpl.php');
		$error = 'Access denied!';
		$errorDetail = 'You do not have access to this person\'s account details.';
		include ($path . '/market/templates/error.tpl.php');
		include ($path . '/footer' . $template . '.tpl.php');
		die ();
	}
	if (isset($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'cancel':
				if ($customer->isIn($user, false)) {
					if (!$order->getDateDelivered()) {
						if (!$order->getDateCompleted()) {
							$order->delete();
							include ($path . '/market/templates/close.tpl.php');
							die ();
						} else $order->cancel();
					}
				}
				break;
			case 'confirm':
				if ($customer->isIn($user, false)) {
					if (!$order->getDateCompleted() && $order->getDateToDeliver()) $order->complete();
				}
				break;
			case 'defer':
				if ($customer->isIn($user, false)) {
					$nextDeliveryDay = $order->getNextDeliveryDay();
					if ($order->getDateToDeliver() && $order->getDateToDeliver() < $nextDeliveryDay) {
						$order->setDateToDeliver($nextDeliveryDay);
						$order->save();
					}
				}
				break;
			case 'deliver':
				if ($customer->isIn($user, false)) {
					if ($order->getDateCompleted() && !$order->getDateDelivered()) {
						$order->deliver();
					}
				}
				break;
			case 'recordPayment':
				// TODO: error checking
				if ($customer->isIn($user, false)) {
					if (isset($_REQUEST['payAmount']) && isset($_REQUEST['payTypeID'])) {
						if (in_array((int) $_REQUEST['payTypeID'], $customer->getPayTypeIDs()) && ((float) $_REQUEST['payAmount'] > 0))
							$order->addPayment((float) $_REQUEST['payAmount'], (int) $_REQUEST['payTypeID']);
					}
				}
		}
	}
	$pageTitle = 'Order #' . $order->orderID . ' for ' . $customer->getLabel();
	$showBlankFields = false;
	$hideLogo = true;
	if (!$ajax) {
		include ($path . ($style == 'dialogue' ? '/market/templates/header_d.tpl.php' : '/header.tpl.php'));
		if ($user->personID == 1) include ($path . '/market/templates/orderTools.tpl.php');
		echo '<div id="invoice">';
	}
	include ($path . '/market/templates/invoice.tpl.php');
	if (!$ajax) {
		echo '</div>';
		include ($path . ($style == 'dialogue' ? '/market/templates/footer_d.tpl.php' : '/footer.tpl.php'));
	}
}

?>
