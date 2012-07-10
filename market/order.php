<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/price.inc.php');


$pageTitle = 'Localmotive - Market';
$noSidebars = true;
$fillContainer = true;
$payAction = 'order';

$customerID = false;
$order = null;
$customer = null;
if (!isset($_REQUEST['referrer'])) {
	if (isset($_REQUEST['action'])) {
		if ($_REQUEST['action'] == 'checkout') $referrer = 'review';
		else $referrer = 'order';
	} else $referrer = 'order';
} else switch ($_REQUEST['referrer']) {
	case 'review':
		$referrer = 'review';
		break;
	default:
		$referrer = 'order';
}

function eNotAllowedTree () {
	global $ajax;
	if (!$ajax) {
		require_once ($path . '/header.tpl.php');
		$error = 'Cannot create orders for this user!';
		$errorDetail = 'You are either not this person or not a manager of their account. Please press your browser\'s back button or close this window, and try again, or try to log in again.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
	} else {
		global $json;
		$json = true;
	}
	die ();
}

function eNotEditable () {
	global $ajax;
	if (!$ajax) {
		require_once ($path . '/header.tpl.php');
		$error = 'Cannot edit this order!';
		$errorDetail = 'This order has been marked as non-editable. You may view it, but you may not add to or delete from it, or cancel it.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
	} else {
		global $json;
		$json = true;
	}
	die ();
}

function getPermanent ($itemID) {
	global $order, $customer, $logger;
	if (!($order->orderType & O_TEMPLATE)) $permanent = true;
	else if ($order->orderType & (O_BASE | O_CSA) == (O_RECURRING | O_CSA) && $customer->personType & P_CSA) {
		if (!$item = new Item ((int) $itemID)) return false;
		$permanent = (bool) $item->getCsaRequired();
		$logger->addEntry('item should '.($permanent ? null : 'not ').'be permanent');
	} else $permanent = true;
	return $permanent;
}

if (isset($_REQUEST['orderID'])) {
	if ($order = new Order ((int) $_REQUEST['orderID'])) {
		if (!$user = tryLogin()) die ();
		if ($order->personID != $user->personID) $customer = $order->getPerson();
		else $customer = $user;
		if (!$customer->isIn($user)) eNotAllowedTree();
	} else {
		require_once ($path . '/header.tpl.php');
		$error = 'No such order!';
		$errorDetail = 'The order ' . (string) $_REQUEST['orderID'] . ' does not exist. If you believe this is a bug, please report it using the feedback tool at the bottom of this page.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
}

if (isset($_REQUEST['customerID'])) {
	if (!$customer = new Person ((int) $_REQUEST['customerID'])) {
		require_once ($path . '/header.tpl.php');
		$error = 'No such person!';
		$errorDetail = 'The person ' . (string) $_REQUEST['customerID'] . ' does not exist. If you believe this is a bug, please report it using the feedback tool at the bottom of this page.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
}

$tour = false;
if (isset($_REQUEST['tour']) && isset($_REQUEST['svcID'])) {
	if (isset($user) && $user) {
		require_once ($path . '/header.tpl.php');
		$error = 'Now why would you want to take a tour?';
		$errorDetail = 'You are already logged in as ' . htmlEscape($user->getLabel()) . '. Since you\'re already a member, it wouldn\'t make much sense to take a market tour! If you\'re trying to order, please proceed to <a href="'.$config['baseUri'].'/market/order.php">the market</a>.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
	if ($customer = new Person ((int) $_REQUEST['svcID'])) {
		$tour = true;
		$noLogin = true;
		if (isset($_SESSION['customerID']) && $_SESSION['customerID'] == $customer->personID && isset($_SESSION['orderID'])) {
			$order = new Order((int) $_SESSION['orderID']);
			if ($order && ($order->personID != $customer->personID)) $order = false;
		}
		if (!$order) $order = $customer->startOrder(($customer->getCanCustomOrder() ? O_SALE : O_RECURRING) | O_EDITABLE | (($customer->personType & P_CSA) ? O_CSA : 0));
		$user = new Person ();
	} else {
		require_once ($path . '/header.tpl.php');
		$error = 'Hmmm... something happened with your request.';
		$errorDetail = 'You requested to take a tour of a certain service, but that service doesn\'t exist. Please try going back to the previous page and try again. If that doesn\'t work, please <a href="' . $path . '/contactus.php">contact us</a>.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
}

if (!$tour) {
	if (!$user = tryLogin()) die();
}

if ($order && $customer) {
	if ($order->personID != $customer->personID) {
		require_once ($path . '/header.tpl.php');
		$error = 'This order is not available to this customer.';
		$errorDetail = 'We\'re sorry, but this order is not available to ' . ($customer->personID == $user->personID ? 'you' : htmlEscape($customer->getLabel())) . '. It probably belongs to someone else. If you believe this message is in error, please report it using our feedback form below.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
}

if (!$customer) {
	if (!isset($_REQUEST['orderType'])) {
		if (isset($_SESSION['customerID'])) {
			if (!$customer = new Person ((int) $_SESSION['customerID'])) {
				$customer = &$user;
			} else if (!$customer->isIn($user)) {
				// at this point, it doesn't make sense to check for the
				// presence of an orderID, because it won't be for this
				// customer anyway.
				$notice = 'Note: Another person was making an order through your computer. That person has logged out and we have created a fresh new order for you.';
				$customer = &$user;
			} else if (isset($_SESSION['orderID'])) {
				if (!$order = new Order ((int) $_SESSION['orderID'])) $order = null;
				else if ($order->personID != $customer->personID) {
					$order = null;
					$notice = 'Note: Another person was making an order through your computer. That person has logged out and we have created a fresh new order for you.';
				}
			}
		} else $customer = &$user;
	} else $customer = &$user;
}

// by this time, $customer should be created. Now we'll check if they're
// allowed to order.

$isActive = $customer->isActive();
$canOrder = ($customer->personType & P_CANORDER) || $tour;
if (!$isActive || !$canOrder) {
	require_once ($path . '/header.tpl.php');
	$error = 'Not allowed to order!';
	$errorDetail = 'This account ' . (!$isActive ? 'is not currently active ' : null) . (!$isActive && !$canOrder ? ', and ' : null) . (!$canOrder ? 'is not set up as a customer or supplier' : null) . '. If you believe you have received this message in error, please use the feeback form at the bottom of this page or <a href="../contact.php">contact us</a> through phone or e-mail.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

$forceNew = (isset($_REQUEST['forceNew']) && (bool) $_REQUEST['forceNew']);
if (!$order || $forceNew) {
	if (isset($_REQUEST['orderType'])) {
		switch ($_REQUEST['orderType']) {
			case 'recurring':
				$orderType = O_RECURRING;
				break;
			case 'supplier':
				$orderType = O_SUPPLIER;
				break;
			case 'purchase':
				$orderType = O_PURCHASE;
				break;
			case 'sale':
				$orderType = O_SALE;
				break;
			default:
				$orderType = (($customer->personType & P_SUPPLIER) ? O_SUPPLIER : ($customer->canCustomOrder() ? O_SALE : O_RECURRING));
		}
	} else $orderType = (($customer->personType & P_SUPPLIER) ? O_SUPPLIER : ($customer->canCustomOrder() ? O_SALE : O_RECURRING));
	$orderType |= O_EDITABLE;
	if (!isset($_REQUEST['noDeliver'])) $orderType |= O_DELIVER;
	if (($customer->personType & P_CSA) && !$customer->hasOpenOrder(array(O_CSA, O_CSA), true)) $orderType |= O_CSA;
	if (($orderType & O_BASE) == O_SALE) {
		if (!$config['marketOpen']) {
			require_once ($path . '/header.tpl.php');
			$error = 'The market is currently closed.';
			$errorDetail = 'We\'re sorry, but the market is currently closed. You can still review your old orders and manage the contents of your weekly order. Check the announcements page for more information.';
			require_once ($path . '/market/templates/error.tpl.php');
			require_once ($path . '/footer.tpl.php');
			die ();
		}
		if (!$customer->getCanCustomOrder()) {
			require_once ($path . '/header.tpl.php');
			$error = 'Custom ordering is unavailable.';
			$errorDetail = 'We\'re sorry, but weekly custom ordering is unavailable for your account or group. If you are a Healthy Harvest customer, please return to the <a href="index.php">main menu</a> and edit your recurring order instead.';
			require_once ($path . '/market/templates/error.tpl.php');
			require_once ($path . '/footer.tpl.php');
			die ();
		}
	}
	if (!$forceNew) $order = $customer->hasOpenOrder($orderType);
	if (!$order) {
		if (!$order = $customer->startOrder($orderType)) {
			logError('Cannot start/retrieve an order for person \'' . htmlEscape($customer->contactName) . '\' because of error type ' . $errorCodes[$customer->getError()]);
			include ($path . '/header.tpl.php');
			switch ($customer->getError()) {
				case E_NO_OBJECT_ID:
					$error = 'Not in a route!';
					$errorDetail = 'This person is not in a route or depot, and a person must be in a route or depot in order to calculate delivery times.';
					break;
				default:
					$error = 'An unexpected error has occurred!';
					$errorDetail = 'We have experienced an unexpected error. This account is active, but for some reason we can\'t retrieve or start an order. This error has been logged, and we will analyse it shortly. For assistance, please <a href="../contact.php">contact us</a> through phone or e-mail.';
			}
			include ($path . '/market/templates/error.tpl.php');
			include ($path . '/footer.tpl.php');
			die ();
		}
	}
}

if ($order->getDateDelivered() || $order->getDateCanceled() || (!($order->orderType & O_TEMPLATE) && $order->getDateCompleted())) {
	if ($order->getDateDelivered()) $w = 'delivered';
	else if ($order->getDateCanceled()) $w = 'canceled';
	else $w = 'completed';
	require_once ($path . '/header.tpl.php');
	$error = 'This order has been '.$w.'!';
	$errorDetail = 'We\'re sorry, but there appears to be a problem. This order has already been '.$w.' and cannot be modified. If you believe this to be a mistake, please <a href="../contact.php">contact us</a> through phone or e-mail.';
	include ($path . '/market/templates/error.tpl.php');
	include ($path . '/footer.tpl.php');
	die();
}
$canCustomOrder = $customer->getCanCustomOrder();

if ($order && !($order->orderType & O_TEMPLATE) && !$canCustomOrder && !$customer->isIn($user, false)) {
	require_once ($path . '/header.tpl.php');
	$error = 'Not allowed to custom-order!';
	$errorDetail = 'This account is limited to recurring orders only. If you believe you have received this message in error, please use the feeback form at the bottom of this page or <a href="../contact.php">contact us</a> through phone or e-mail.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

if ($customer->canDeliver()) {
	$logger->addEntry($customer->getShipping());
	$order->shipping = $customer->getShipping();
	$order->shippingType = $customer->getShippingType();
}
$totals = $order->getTotal();
if ($customer->getBalance(true) < $totals['net'] + $totals['shipping'] + $totals['hst'] + $totals['pst']) $order->setPayTypeID(PAY_CC);
else $order->setPayTypeID(PAY_ACCT);

if ($order->adjustDates()) $order->save();
$nextDeliveryDay = $order->getNextDeliveryDay(null, false);
$cutoffDay = $order->getCutoffDay();
$cutoffWeekday = strftime('%A', $nextDeliveryDay - $order->getCutoffDay() * T_DAY);

$_SESSION['customerID'] = $customer->personID;
$_SESSION['orderID'] = $order->orderID;

if (isset($_REQUEST['category'])) {
	switch (strtolower($_REQUEST['category'])) {
		case 'dairy':
			$category = new Item (2);
			break;
		case 'meats':
			$category = new Item (3);
			break;
		case 'bulk':
			$category = new Item (4);
			break;
		case 'baked':
			$category = new Item (6);
			break;
		case 'extras':
			$category = new Item (7);
			break;
		case 'produce':
		default:
			$category = new Item (5);
	}
	$_SESSION['categoryID'] = $category->itemID;
} else if (isset($_REQUEST['categoryID'])) {
	if ((int) $_REQUEST['categoryID']) $category = new Item ((int) $_REQUEST['categoryID']);
	else $category = new Item (5);
} else if (isset($_SESSION['categoryID'])) {
	if ((int) $_SESSION['categoryID']) $category = new Item ((int) $_SESSION['categoryID']);
	else $category = new Item (5);
} else $category = new Item (5);

$editable = ($order->orderType & O_EDITABLE || $customer->isIn($user, false)) || $tour;
$enableDelete = false;

if ($customer->getRouteID(false)) $minOrder = $customer->getMinOrderDeliver();
else $minOrder = $customer->getMinOrder();


if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'addItem':
			if (!$editable) eNotEditable();
			if ($order->isFromRecurringOrder()) {
				if ($customer->customCancelsRecurring) {
					logError('about to delete items, because custom cancels recurring');
					$itemsToDelete = array ();
					foreach ($order->orderItems as $thisItem) {
						$itemsToDelete[] = $thisItem->itemID;
					}
					foreach ($itemsToDelete as $thisItemID) {
						$order->deleteItem($thisItemID);
					}
				}
				$order->setFromRecurringOrder(false);
				$order->resetStars();
				$order->calculateStars();
				$order->save();
			}
			$qty = (int) $_REQUEST['quantity'];
			if (!$qty) $qty = 1;
			$permanent = getPermanent ($_REQUEST['itemID']);
			if ($price = $customer->getPrice((int) $_REQUEST['itemID'])) {
				$logger->push('addItem');
				$order->addQuantity((int) $_REQUEST['itemID'], (int) $qty * $price->multiple, null, null, null, false, $permanent);
				$logger->pop('addItem');
				$totals = $order->getTotal();
				// TODO: EXPLOIT: a customer could add an item, delete it, and check out, and get stars for a recurring order! How do we fix that? perhaps by having $order->fromRecurringOrder as an orderID, and comparing the contents against the recurring order's contents
				if ($ajax) {
					$out = array ();
					ob_start();
					include ($path . '/market/templates/shoppingListCol.tpl.php');
					$out['shoppingListCol'] = ob_get_clean();
					$out['qty'] = $order->getQuantity((int) $_REQUEST['itemID']);
					echo json_encode($out);
					die ();
				} else redirectThisPage('order.php');
			}
			break;
		case 'changeQty':
			$logger->push('changeQty');
			$ajax = true;
			if (!$editable) eNotEditable();
			$qty = (int) $_REQUEST['qty'];
			if ($qty < 0) {
				$qty = 0;
			}
			$itemID = (int) $_REQUEST['itemID'];
			$logger->log('itemID is', $itemID);
			if ($qty && $itemID) {
				$price = $customer->getPrice($itemID);
				$logger->log('about to set new qty');
				if ($price && ($qtyOrdered = $order->orderItems[$itemID]->quantityOrdered)) {
					$qty = $qty * $price->multiple;
					if ($order->orderType & O_CSA && !$order->hasOtherCsaItem($itemID) && !$qty) $qty = $price->multiple;
					$logger->log('setting new qty of', $qty);
					$order->setQuantity($itemID, $qty);
				}
			}
			$totals = $order->getTotal();
			$logger->pop('changeQty');
			if ($ajax) {
				$out = array ();
				ob_start();
				switch ($referrer) {
					case 'review':
						$payErrors = array ();
						list ($payTypes, $payType) = getCheckoutPayTypes($customer, $totals['gross']);
						include($path . '/market/templates/shoppingList.tpl.php');
						include($path . '/market/templates/reviewActions.tpl.php');
						break;
					case 'order':
					default:
						include($path . '/market/templates/shoppingListCol.tpl.php');
				}
				$out['shoppingListCol'] = ob_get_clean();
				$out['itemID'] = $itemID;
				$out['qty'] = $order->getQuantity($itemID);
				if (isset($_REQUEST['updateTotal'])) {
					$totals = $order->getTotal();
					$out['total'] = money_format(NF_MONEY, $totals['net']);
					$short = $minOrder - $totals['net'];
					$out['short'] = ($short > 0 ? money_format(NF_MONEY, $short) : 0);
					$totalsRecurring = $order->getTotal(true, 'permanent');
					$out['permTotal'] = money_format(NF_MONEY, $totalsRecurring['net']);
					$permShort = $minOrder - $totalsRecurring['net'];
					$out['permShort'] = ($permShort > 0 ? money_format(NF_MONEY, $permShort) : 0);
				}
				echo json_encode($out);
				die ();
			} else redirectThisPage('order.php');
			break;
		case 'changePrice':
			$ajax = true;
			if (!$editable) eNotEditable();
			if (!$customer->isIn($user, false)) eNotEditable();
			$price = round((float) $_REQUEST['price'], 2);
			$itemID = (int) $_REQUEST['itemID'];
			if ($price && $itemID) {
				$order->setPrice($itemID, $price);
			}
			$totals = $order->getTotal();
			if ($ajax) {
				$out = array ();
				ob_start();
				switch ($referrer) {
					case 'review':
						$payErrors = array ();
						list ($payTypes, $payType) = getCheckoutPayTypes($customer, $totals['gross']);
						include($path . '/market/templates/shoppingList.tpl.php');
						include($path . '/market/templates/reviewActions.tpl.php');
						break;
					case 'order':
					default:
						include($path . '/market/templates/shoppingListCol.tpl.php');
				}
				$out['shoppingListCol'] = ob_get_clean();
				if (isset($_REQUEST['updateTotal'])) {
					$totals = $order->getTotal();
					$out['total'] = money_format(NF_MONEY, $totals['net']);
					$short = $minOrder - $totals['net'];
					$out['short'] = ($short > 0 ? money_format(NF_MONEY, $short) : 0);
					$totalsRecurring = $order->getTotal(true, 'permanent');
					$out['permTotal'] = money_format(NF_MONEY, $totalsRecurring['net']);
					$permShort = $minOrder - $totalsRecurring['net'];
					$out['permShort'] = ($permShort > 0 ? money_format(NF_MONEY, $permShort) : 0);
				}
				echo json_encode($out);
				die ();
			} else redirectThisPage('order.php');
			break;
		case 'changeLabel':
			$ajax = true;
			if (!$editable) eNotEditable();
			$oldLabel = $order->label;
			if (isset($_REQUEST['label'])) {
				$order->label = ((string) $_REQUEST['label'] ? strip_tags((string) $_REQUEST['label']) : null);
				if ($order->save()) echo $order->label;
				else echo $oldLabel;
			} else echo $oldLabel;
			$json = true;
			die ();
			break;
		case 'addItems':
			if (!$editable) eNotEditable();
			if ($order->isFromRecurringOrder()) {
				if ($user->customCancelsRecurring) {
					logError('about to delete items');
					$itemsToDelete = array ();
					foreach ($order->orderItems as $thisItem) {
						$itemsToDelete[] = $thisItem->itemID;
					}
					foreach ($itemsToDelete as $thisItemID) {
						$order->deleteItem($thisItemID);
					}
				}
				$order->setFromRecurringOrder(false);
				$order->resetStars();
				$order->calculateStars();
				$order->save();
			}
			foreach ($_REQUEST['qty'] as $thisItemID => $thisQty) {
				$permanent = getPermanent ($thisItemID);
				if ((int) $thisQty > 0) $order->addQuantity((int) $thisItemID, (int) $thisQty, null, null, null, false, $permanent);
			}
			$totals = $order->getTotal();
			// TODO: EXPLOIT: a customer could add an item, delete it, and check out, and get stars for a recurring order! How do we fix that? perhaps by having $order->fromRecurringOrder as an orderID, and comparing the contents against the recurring order's contents
			if ($ajax) {
				include ($path . '/market/templates/shoppingListCol.tpl.php');
				die ();
			} else redirectThisPage('order.php');
			break;
		case 'deleteItem':
			if (!$editable) eNotEditable();
			$errorCode = null;
			$itemID = (int) $_REQUEST['itemID'];
			if (!$order->deleteItem($itemID)) $errorCode = $order->getError();
			$totals = $order->getTotal();
			if ($ajax) {
				$out = array ();
				ob_start();
				switch ($referrer) {
					case 'review':
						$payErrors = array ();
						list ($payTypes, $payType) = getCheckoutPayTypes($customer, $totals['gross']);
						include($path . '/market/templates/shoppingList.tpl.php');
						include($path . '/market/templates/reviewActions.tpl.php');
						break;
					case 'order':
					default:
						include($path . '/market/templates/shoppingListCol.tpl.php');
				}
				$out['shoppingListCol'] = ob_get_clean();
				$out['success'] = (!$errorCode);
				if (isset($_REQUEST['updateTotal'])) {
					$totals = $order->getTotal();
					$out['total'] = money_format(NF_MONEY, $totals['net']);
					$short = $minOrder - $totals['net'];
					$out['short'] = ($short > 0 ? money_format(NF_MONEY, $short) : 0);
					$totalsRecurring = $order->getTotal(true, 'permanent');
					$out['permTotal'] = money_format(NF_MONEY, $totalsRecurring['net']);
					$permShort = $minOrder - $totalsRecurring['net'];
					$out['permShort'] = ($permShort > 0 ? money_format(NF_MONEY, $permShort) : 0);
				}
				echo json_encode($out);
				die ();
			}
			break;
		case 'makePerm':
			if (!$editable) eNotEditable();
			if (!isset($_REQUEST['itemID'])) {
				$json = true;
				echo '0';
				die ();
			}
			$itemID = (int) $_REQUEST['itemID'];
			$out = array ();
			$out['success'] = (int) $order->setItemPermanent($itemID);
			ob_start();
			switch ($referrer) {
				case 'review':
					$payErrors = array ();
					list ($payTypes, $payType) = getCheckoutPayTypes($customer, $totals['gross']);
					include($path . '/market/templates/shoppingList.tpl.php');
					include($path . '/market/templates/reviewActions.tpl.php');
					break;
				case 'order':
				default:
					include($path . '/market/templates/shoppingListCol.tpl.php');
			}
			$out['shoppingListCol'] = ob_get_clean();
			if (!$out['success']) {
				switch ($order->getError()) {
					case E_NOT_AVAILABLE_TO_CUSTOMER:
						$out['errorMsg'] = 'Seasonal - cannot be permanent';
						break;
					case E_ORDER_TOO_SMALL:
						$out['errorMsg'] = 'CSA item - must be permanent';
				}
			}
			if (isset($_REQUEST['updateTotal'])) {
				$totals = $order->getTotal();
				$out['total'] = money_format(NF_MONEY, $totals['net']);
				$short = $minOrder - $totals['net'];
				$out['short'] = ($short > 0 ? money_format(NF_MONEY, $short) : 0);
				$totalsRecurring = $order->getTotal(true, 'permanent');
				$out['permTotal'] = money_format(NF_MONEY, $totalsRecurring['net']);
				$permShort = $minOrder - $totalsRecurring['net'];
				$out['permShort'] = ($permShort > 0 ? money_format(NF_MONEY, $permShort) : 0);
			}
			echo json_encode($out);
			die ();
			break;
		case 'makeRecurring':
			if (!$editable) eNotEditable();
			// $status = $order->setTemplate(true);
			if ($status = $order->setTemplate(true)) $order->save();
			if ($ajax) {
				$out = array ();
				ob_start();
				include ($path . '/market/templates/shoppingListCol.tpl.php');
				$out['shoppingListCol'] = ob_get_clean();
				$out['success'] = $status;
				echo json_encode($out);
				die ();
			} else redirectThisPage('order.php');
			break;
		case 'makeSale':
			if (!$editable) eNotEditable();
			// $status = $order->setTemplate(true);
			if ($status = $order->setTemplate(false)) $order->save();
			if ($ajax) {
				$out = array ();
				ob_start();
				include ($path . '/market/templates/shoppingListCol.tpl.php');
				$out['shoppingListCol'] = ob_get_clean();
				$out['success'] = $status;
				echo json_encode($out);
				die ();
			} else redirectThisPage('order.php');
			break;
		case 'cancelOrder':
			if (!$editable) eNotEditable();
			if (!$customer->isIn($user, false) && $order->orderType & O_CSA && ($customer->personType & P_CSA) && !$tour) eNotEditable();
			$orderID = $order->orderID;
			if ($order->delete()) {
				$error = 'Order canceled!';
				$errorDetail = 'The ' . ($order->orderType & O_TEMPLATE ? 'recurring ' : null). 'order #' . $orderID . ' has been canceled. You may close this window now or return to the <a href="index.php">main menu</a>.';
				if ($ajax) $status = 1;
			} else {
				$error = 'Could not cancel order!';
				$errorDetail = 'The ' . ($order->orderType & O_TEMPLATE ? 'recurring ' : null). 'order #' . $orderID . ' could not be canceled. You may close this window now or return to the <a href="index.php">main menu</a>.';
				if ($ajax) $status = 0;
			}
			if ($ajax) {
				echo $status;
				$json = true;
				die();
			}
			break;
		case 'setOption':
			if (!$editable) eNotEditable();
			if ($customer->isIn($user, false)) {
				if (isset($_REQUEST['option']) && isset($_REQUEST['value'])) {
					switch (strtolower($_REQUEST['option'])) {
						case 'csa':
							if ((bool) $_REQUEST['value']) $order->orderType |= O_CSA;
							else $order->orderType ^= O_CSA;
							break;
						case 'deliver':
							if ((bool) $_REQUEST['value']) $order->orderType |= O_DELIVER;
							else $order->orderType ^= O_DELIVER;
							break;
						case 'editable':
							if ((bool) $_REQUEST['value']) $order->orderType |= O_EDITABLE;
							else $order->orderType ^= O_EDITABLE;
					}
					$status = $order->save();
				} else $status = 0;
			} else $status = 0;
			if ($ajax) {
				if ($status) include ($path . '/market/templates/shoppingListCol.tpl.php');
				die ();
			}
			break;
		case 'changePeriod':
			if (!$editable) eNotEditable();
			if ($customer->isIn($user) && ($order->orderType & O_TEMPLATE)) {
				if (isset($_REQUEST['mult']) && ($mult = (int) $_REQUEST['mult']) && isset($_REQUEST['period']) && ($period = (int) $_REQUEST['period'])) {
					if (($period >= -12 && $period < 0) || ($period > 0 && !($period % T_DAY))) {
						// TODO: allow customers to change weekly, monthly, daily -- in case of future features
						if ($customer->isIn($user, false)) $order->period = $period * $mult;
						else $order->period = $mult * T_WEEK;
						$order->save();
					}
				}
			}
			if ($ajax) {
				include ($path . '/market/templates/shoppingListCol.tpl.php');
				die ();
			}
			break;
		case 'continueShopping':
			break;
		case 'save':
		case 'changeDates':
		case 'checkout':
			if (!$editable) eNotEditable();
			if (isset($_REQUEST['period']) && isset($_REQUEST['mult'])) {
				$period = (int) $_REQUEST['period'];
				if (!array_key_exists($period, $timeNames)) $period = T_WEEK;
				$mult = abs($_REQUEST['mult']);
				if ($mult < 1) $mult = 1;
				if ($mult > 4 && $period == T_WEEK && $customer->personType & P_CSA) $mult = 4;
				$period = $period * $mult;
			} else $period = false;
			if ($period) $order->period = $period;
			if ($customer->isIn($user, false) && $order->orderType & O_TEMPLATE && isset($_POST['toggles'])) {
				if (isset($_POST['editable'])) $order->orderType |= O_EDITABLE;
				else $order->orderType ^= O_EDITABLE;
				if (isset($_POST['csa'])) $order->orderType |= O_CSA;
				else $order->orderType ^= O_CSA;
				if (isset($_POST['deliver'])) $order->orderType |= O_DELIVER;
				else $order->orderType ^= O_DELIVER;
			}
			$route = $order->getRoute();
			if (isset($_REQUEST['dateStarted'])) {
				$dateStarted = strtotime($_REQUEST['dateStarted']);
				if ($dateStarted) {
					if ($route) {
						$firstOrder = $route->getNextDeliveryDay($dateStarted);
						$firstOrderFake = $route->getNextDeliveryDay($dateStarted, false);
						$firstCutoffDay = $route->getCutoffDay($firstOrderFake);
						if ($firstOrderFake - ($firstCutoffDay * T_DAY) < $dateStarted) {
							$dateStarted = $firstOrderFake - ($firstCutoffDay * T_DAY);
						}
						$dateStartedChanged = ($firstOrderFake != Date::round(strtotime($_REQUEST['dateStarted'])));
					} else $dateStartedChanged = false;
					$order->setDateStarted($dateStarted);
				}
			}
			$dateStarted = $order->getDateStarted();
			if (isset($_REQUEST['dateHold'])) {
				$dateCompleted = strtotime($_REQUEST['dateHold']);
				if ($dateCompleted) {
					$dateCompleted += T_DAY;
					if ($route) {
						$lastOrder = $route->getNextDeliveryDay($dateCompleted, false, $dateStarted, $order->period);
						$lastCutoffDay = $route->getCutoffDay($lastOrder);
						if ($lastOrder - ($lastCutoffDay * T_DAY) < $dateCompleted) {
							$dateCompleted = $lastOrder - ($lastCutoffDay * T_DAY);
							$dateCompletedAdjusted = true;
						} else $dateCompletedAdjusted = false;
					} else $dateCompletedAdjusted = false;
					$order->setDateCompleted($dateCompleted);
					if ($_REQUEST['dateResume']) {
						$dateResume = strtotime($_REQUEST['dateResume']);
						if ($dateResume) {
							if ($route) {
								$resumeOrder = $route->getNextDeliveryDay($dateResume);
								$resumeOrderFake = $route->getNextDeliveryDay($dateResume, false);
								$resumeCutoffDay = $route->getCutoffDay($firstOrderFake);
								if ($resumeOrderFake - ($resumeCutoffDay * T_DAY) < $dateResume) {
									$dateResume = $resumeOrderFake - ($resumeCutoffDay * T_DAY);
								}
								$dateResumeChanged = ($resumeOrderFake != Date::round(strtotime($_REQUEST['dateResume'])));
							} else $dateResumeChanged = false;
							$order->setDateResume($dateResume);
						} else $order->setDateResume(null);
					} else $order->setDateResume(null);
				}
			} else {
				$order->setDateCompleted(null);
				$order->setDateResume(null);
			}
			$order->adjustDates();
			$totals = $order->getTotal();
			$order->save();
			if (!$_SERVER['HTTPS'] && $_REQUEST['action'] == 'checkout') {
				header('Location: ' . $secureUrlPrefix . '/market/order.php?action=checkout');
			}
			if ($_REQUEST['action'] == 'save') break;
			if ($_REQUEST['action'] == 'changeDates') {
				include ($path . '/market/templates/orderActions.tpl.php');
				die ();
			}
			$totals = $order->getTotal(false);
			$noSidebars = true;
			$fillContainer = true;
			include ($path . '/header.tpl.php');
			$payErrors = array ();
			list ($payTypes, $payType) = getCheckoutPayTypes($customer, $totals['gross']);
			if ($tour) {
				$fillContainer = false;
				include ($path . '/market/templates/tourFinish.tpl.php');
			} else include ($path . '/market/templates/reviewOrder.tpl.php');
			include ($path . '/footer.tpl.php');
			die ();
			break;
		case 'complete':
			/*switch ($_REQUEST['payTypeID']) {
				case 'paypal':
					$paymentType = PAY_PAYPAL;
					break;
				case 'nova':
					$paymentType = PAY_CC;
					break;
				case 'cheque':
				default:
					$paymentType = PAY_CHEQUE;
			}*/
			$payErrors = array ();
			$totals = $order->getTotal();
			if (!isset($_POST['payTypeID'])) {
				$payErrors[] = 'payTypeID';
				$logger->addEntry('no payTypeID in form');
			} else if (!$customer->canUsePayType($_POST['payTypeID']) && $_POST['payTypeID'] != PAY_ACCT) {
				$payErrors[] = 'payTypeID';
				$logger->addEntry('cant use payTypeID');
			} else $payType = new PayType((int) $_POST['payTypeID']);
			switch ($payType->payTypeID) {
				case PAY_CHEQUE:
					$payErrors[] = 'payTypeID';
					$logger->addEntry('cant use cheque');
					break;
				case PAY_ACCT:
					if ($customer->getBalance(true) < $totals['gross']) $payErrors[] = 'nsf';
					break;
				case PAY_CC:
					require_once($path . '/market/classes/aktiveMerchant/lib/merchant.php');
					Merchant_Billing_Base::mode($config['pfMode']);
					try {
						$gateway = new Merchant_Billing_Payflow( array(
							'login' => $config['pfUser'],
							'user' => $config['pfUser'],
							'password' => $config['pfPwd'],
							'partner' => $config['pfPartner'],
							'currency' => 'CAD'
						));
					} catch (Exception $e) {
						$logger->addEntry('gateway error: ' . $e->getMessage());
					}
					$options = array(
						'order_id' => 'REF' . $gateway->generate_unique_id(),
						'description' => 'Payment auth on '.($order->orderType & O_RECURRING ? 'recurring order template ' : 'order ').$order->orderID
					);
					if (isset($_POST['useStoredCC']) && $_POST['useStoredCC']) {
						if ($customer->cc && $customer->txnID) {
							$response = $gateway->authorize($totals['gross'], $customer->txnID, $options);
						}
					}
					if (!isset($response)) list ($response, $ccPayErrors) = processCCFromForm($_POST, $customer, $totals['gross'], $payErrors);
					if (isset($ccPayErrors)) $payErrors = array_merge($payErrors, $ccPayErrors);
					if ($response) {
						$paySuccess = $response->success();
						$txnID = $response->PNRef;
					} else $paySuccess = false;
					break;
				case PAY_PAYPAL:
					// stub: have to create an IPN listener of some sort
				default:
					$payErrors[] = 'payTypeID';
			}
			/* if (isset($_REQUEST['dateAction'])) {
				switch ((int) $_REQUEST['dateAction']) {
					case A_DEFER:
						$dateAction = A_DEFER;
						break;
					case A_IGNORE:
					default:
						$dateAction = A_IGNORE;
				}
			} else $dateAction = A_IGNORE; */
			if (count($payErrors)) {
				if (!isset($totals)) $totals = $order->getTotal();
				list ($payTypes, $payTypeSugg) = getCheckoutPayTypes($customer, $totals['gross']);
				if (isset($payTypes[PAY_CHEQUE])) unset($payTypes[PAY_CHEQUE]);
				if (!$payType) $payType = $payTypeSugg;
				include ($path . '/header.tpl.php');
				include ($path . '/market/templates/payActions.tpl.php');
				include ($path . '/footer.tpl.php');
				die();
			}
			if (isset($_REQUEST['notes'])) $order->notes = $_REQUEST['notes'];
			if ($order->orderType & O_TEMPLATE) $orderStatus = $order->replicate();
			else if (($order->orderType & O_BASE) == O_SALE) $orderStatus = $order->complete();
			if (is_object($orderStatus)) {
				$recurring = $order;
				$order = $orderStatus;
			}
			if ($orderStatus) {
				if (isset($paySuccess) && $paySuccess) {
					$options = array (
						'order_id' => $order->orderID,
						'description' => 'Payment on '.($order->getRecurringOrderID() ? 'recurring ' : null).'order'
					);
					$response = $gateway->capture($totals['gross'], $response->PNRef, $options);
					if ($response) {
						if ($response->success()) {
							$order->addPayment($totals['gross'], $payType->payTypeID, $response->PNRef);
							if (isset($_POST['forgetCC'])) {
								$customer->cc = null;
								$customer->txnID = null;
								$customer->pad = false;
								$customer->save();
								$logger->addEntry('forgetting CC ' . $customer->cc);
							} else if (isset($_POST['rememberCC'])) {
								$customer->cc = $_POST['CardNum'];
								$customer->txnID = $response->PNRef;
								if (isset($_POST['pad'])) $customer->pad = true;
								$customer->save();
								$logger->addEntry('forgetting CC ' . $customer->cc);
							}
						} else $paySuccess = false;
					} else $paySuccess = false;
				}
				if (isset($paySuccess) && !$paySuccess) {
					if (isset($recurring)) {
						$order->cancel();
						$order = $recurring;
						unset($recurring);
					}
					require_once ($path . '/header.tpl.php');
					$error = 'Sorry, but your credit card payment wasn\'t processed.';
					$errorDetail = 'We\'re sorry, but the payment processor has declined your credit card payment.';
					if ($totals['gross'] <= $customer->getBalance(true) && $customer->canUsePayType(PAY_ACCT)) {
						$errorDetail .= ' You currently have ' . money_format(NF_MONEY, $balance) . ($balance > 0 ? ' account credit' : ($balance < 0 ? ' owing' : null)) . ($credit > 0 ? ' and a ' . money_format(NF_MONEY, $credit) . ' line of credit' : null) . '. If you would like to charge this order to your account, please confirm it, and the order\'s amount will be subtracted from your credit.';
						$errorDetail .= '</p><p>action="' . $secureUrlPrefix . '/market/order.php" method="POST"><input type="hidden" name="payTypeID" value="' . PAY_ACCT . '"/><input type="submit" value="Confirm order"/></form>';
					} else {
						$errorDetail .= ' Please phone us at 250-497-6577 to make alternate arrangements, at which point we\'ll confirm your order. Thank you!';
						unset($_SESSION['orderID']);
						unset($_SESSION['customerID']);
					}
					require_once ($path . '/market/templates/error.tpl.php');
					require_once ($path . '/footer.tpl.php');
					die ();
				}
				unset($_SESSION['orderID']);
				unset($_SESSION['customerID']);
				$bound = 'PHP-alt-' . md5(date('r', time()));
				$headers = "From: Localmotive Market <orders@localmotive.ca>\r\nReply-To: orders@localmotive.ca\r\n";
				$headers .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"';
				ob_start();
				echo "\n\n--" . $bound . "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
LocalMotive Organic Delivery
2351 Allendale Rd
Okanagan Falls, BC  V0H 1R2
250.497.6577

Thank you for your order!

You can review it and check its status anytime. Just log into your
account at http://www.localmotive.ca/market/

<?
				$fromEmail = true;
				include ($path . '/market/templates/invoiceText.tpl.php');
				$message .= "\n\n--" . $bound . "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
<p><strong>LocalMotive Organic Delivery</strong><br/>
2351 Allendale Rd<br/>
Okanagan Falls, BC  V0H 1R2<br/>
250.497.6577</p>

<h1>Thank you for your order!</h1>

<p>You can review it and check its status anytime. Just log into your account at <a href="http://www.localmotive.ca/market/">http://www.localmotive.ca/market/</a></p>
				<? $hidePackingIcon = true;
				include ($path . '/market/templates/invoice.tpl.php');
				$message = ob_get_clean();
				$mailSent = @mail($customer->email, 'Thank you for your order! (#' . $order->orderID . ')', $message, $headers);
				if (!$mailSent) logError('E-mail to ' . $customer->email . ' (personID ' . $customer->personID . ') failed');
				$fromEmail = false;
				include ($path . '/header.tpl.php');
				include ($path . '/market/templates/orderComplete.tpl.php');
				$hideLogo = true;
				include ($path . '/market/templates/invoice.tpl.php');
				include ($path . '/footer.tpl.php');
				die();
			} else {
				if ($paySuccess) @$gateway->void($txnID);
				include ($path . '/header.tpl.php');
				$error = 'An unexpected error has occurred!';
				$errorDetail = 'We have experienced an unexpected error. Your account is active, but for some reason we can\'t complete your order for you. '.($payType->payTypeID == PAY_CC ? 'Rest assured that, as a result, your credit card has <strong>not</strong> been billed. ' : null).'This error has been logged, and we will analyse it shortly. For assistance, please <a href="../contact.php">contact us</a> through phone or e-mail.';
				include ($path . '/market/templates/error.tpl.php');
				include ($path . '/footer.tpl.php');
			}
	}
}

$totals = $order->getTotal(false);
if ($route = $customer->getRoute()) $nextDeliveryDay = $route->getNextDeliveryDay(null, false);

include ($path . '/header.tpl.php');
include ($path . '/market/templates/order.tpl.php');
include ($path . '/footer.tpl.php');

?>
