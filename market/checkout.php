<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/price.inc.php');

$pageTitle = 'Localmotive - Checkout';

if (!$user = tryLogin()) die ();
$customerID = false;
if (isset($_REQUEST['customerID'])) {
	if ((int) $_REQUEST['customerID']) $customerID = (int) $_REQUEST['customerID'];
}
if (!$customerID && isset($_SESSION['customerID'])) {
	if ((int) $_SESSION['customerID']) $customerID = (int) $_SESSION['customerID'];
}
if ($customerID) {
	if ($customerID != $user->personID) {
		$customer = new Person ($customerID);
		if (!$customer->personID) {
			require_once ($path . '/header.tpl.php');
			$error = 'No such user!';
			$errorDetail = 'This person could not be found in the database. Please press your browser\'s back button or close this window, and try again.';
			require_once ($path . '/market/templates/error.tpl.php');
			require_once ($path . '/footer.tpl.php');
			die ();
		}
	} else $customer = &$user;
} else $customer = &$user;

if (!$customer->isIn($user)) {
	require_once ($path . '/header.tpl.php');
	$error = 'Cannot create orders for this user!';
	$errorDetail = 'You are either not this person or not a manager of their account. Please press your browser\'s back button or close this window, and try again, or try to log in again.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

$isActive = $customer->isActive();
$isCustomer = $customer->personType & P_CUSTOMER;
if (!$isActive || !$isCustomer) {
	require_once ($path . '/header.tpl.php');
	$error = 'Not allowed to order!';
	$errorDetail = 'This account ' . (!$isActive ? 'is not currently active ' : null) . (!$isActive && !$isCustomer ? ', and ' : null) . (!$isCustomer ? 'is not set up as a customer' : null) . '. If you believe you have received this message in error, please <a href="../contact.php">contact us</a> through phone or e-mail.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

if (!$config['marketOpen']) {
	require_once ($path . '/header.tpl.php');
	$error = 'The market is currently closed.';
	$errorDetail = 'We\'re sorry, but the market is currently closed. You can still review your old orders and manage the contents of your weekly order. Check the announcements page for more information.';
	require_once ($path . '/market/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

$_SESSION['customerID'] = $customer->personID;

if ($order = $customer->startOrder()) {
	$_SESSION['orderID'] = $order->orderID;
} else {
	logError('Cannot start/retrieve an order for person \'' . $customer->contactName . '\'');
	require_once ($path . '/header.tpl.php');
	$error = 'An unexpected error has occurred!';
	$errorDetail = 'We have experienced an unexpected error. Your account is active, but for some reason we can\'t retrieve your order for you. This error has been logged, and we will analyse it shortly. For assistance, please <a href="../contact.php">contact us</a> through phone or e-mail.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

list ($subtotal, $hst, $pst) = $order->getTotal();
if ($subtotal < $customer->getMinOrder()) $tooSmall = true;
else $tooSmall = false;
list ($subtotal, $hst, $pst) = $order->getTotal(false);
$route = $customer->getRoute();
$nextDeliveryDay = $route->getNextDeliveryDay(null, false);
$today = roundDate(time());
if ($nextDeliveryDay == $today) $nextDeliveryDay = $route->getNextDeliveryDay($today + T_DAY, false);
$orderDeliveryDay = $order->getNextDeliveryDay();

switch ($_REQUEST['action']) {
	case 'cancelOrder':
		$order->delete();
		unset($_SESSION['orderID']);
		unset($_SESSION['customerID']);
		// TODO: maybe there should be a more detailed page than this, for cancellation of the order.
		redirectThisPage('index.php');
		break;
	case 'continueShopping':
		redirectThisPage('order.php');
		break;
	case 'pay':
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
		if (isset($_REQUEST['payTypeID'])) {
			if ((int) $_REQUEST['payTypeID']) {
				if ($payType = new PayType ((int) $_REQUEST['payTypeID'])) {
					if (!$payType->isActive()) {
						$payTypeError = true;
						break;
					}
				} else {
					$payTypeError = true;
					break;
				}
			}
		}
		if (isset($_REQUEST['dateAction'])) {
			switch ((int) $_REQUEST['dateAction']) {
				case A_DEFER:
					$dateAction = A_DEFER;
					break;
				case A_IGNORE:
				default:
					$dateAction = A_IGNORE;
			}
		} else $dateAction = A_IGNORE;
		if (isset($_REQUEST['notes'])) $order->notes = $_REQUEST['notes'];
		if ($order->checkout($dateAction)) {
			unset($_SESSION['orderID']);
			unset($_SESSION['customerID']);
			$bound = 'PHP-alt-' . md5(date('r', time()));
			$headers = "From: Localmotive Market <orders@localmotive.ca>\r\nReply-To: orders@localmotive.ca\r\n";
			$headers .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"';
			$message = "\n\n--" . $bound . "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n";
			$message .= "Thank you for your order!\n";
			$message .= "You can modify your order until a few days before it is delivered. You can also review it anytime. Just log into your account at http://www.localmotive.ca/market/\n\n";
			$journalEntries = array ();
			if (!$db->query('SELECT MAX(journalEntryID) AS journalEntryID FROM journalEntry WHERE personID = ' . $customer->personID)) {
				databaseError($db);
				die();
			}
			if ($r = $db->getRow(F_RECORD)) $journalEntries[0] = new JournalEntry ((int) $r->v('journalEntryID'));
			else $journalEntries[0] = new JournalEntry;
			$journalEntries[1] = new JournalEntry;
			$journalEntries[1]->journalEntryID = $r->v('journalEntryID') + 1;
			$journalEntries[1]->orderID = $order->orderID;
			$journalEntries[1]->personID = $customer->personID;
			list ($finalSubtotal, $finalHst, $finalPst) = $order->getTotal();
			$journalEntries[1]->amount = 0 - ($finalSubtotal + $finalHst + $finalPst);
			$journalEntries[1]->balance = $journalEntries[1]->amount + $journalEntries[0]->calculateBalance();
			$journalEntries[1]->notes = 'Total from order # ' . $order->orderID;
			$fromEmail = true;
			ob_start();
			include ($path . '/market/templates/invoiceText.tpl.php');
			$message .= ob_get_clean();
			$message .= "\n\n--" . $bound . "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n";
			$message .= "<h1>Thank you for your order!</h1>\n";
			$message .= "<p>You can modify your order until a day or two before it is delivered. You can also review it anytime. Just log into your account at <a href=\"http://www.localmotive.ca/market/\">http://www.localmotive.ca/market/</a></p>\n";
			$hidePackingIcon = true;
			ob_start();
			include ($path . '/market/templates/invoice.tpl.php');
			$message .= ob_get_clean();
			$mailSent = @mail($customer->email, 'Thank you for your order! (#' . $order->orderID . ')', $message, $headers);
			if (!$mailSent) logError('E-mail to ' . $customer->email . ' (personID ' . $customer->personID . ') failed');
			include ($path . '/header.tpl.php');
			include ($path . '/market/templates/orderComplete.tpl.php');
			$hideLogo = true;
			include ($path . '/market/templates/invoice.tpl.php');
			include ($path . '/footer.tpl.php');
			die();
		} else if ($order->getError() == E_INVALID_DATA) {
			// supposed to check to see if payTypeID is invalid or inactive; may catch the wrong error if I add any extra E_INVALID_DATA responses to Order::checkout()
			$payType = new PayType ((int) $order->payTypeID);
			$payTypeError = true;
		} else {
			include ($path . '/header.tpl.php');
			$error = 'An unexpected error has occurred!';
			$errorDetail = 'We have experienced an unexpected error. Your account is active, but for some reason we can\'t complete your order for you. This error has been logged, and we will analyse it shortly. For assistance, please <a href="../contact.php">contact us</a> through phone or e-mail.';
			include ($path . '/market/templates/error.tpl.php');
			include ($path . '/footer.tpl.php');
		}
	case 'checkout':
		if (!$_SERVER['HTTPS']) redirectThisPage($secureUrlPrefix . '/market/checkout.php?checkout');
		include ($path . '/header.tpl.php');
		include ($path . '/market/templates/reviewOrder.tpl.php');
		include ($path . '/footer.tpl.php');
}

?>
