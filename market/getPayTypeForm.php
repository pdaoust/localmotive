<?php

$_REQUEST['ajax'] = true;
include ('marketInit.inc.php');

if (isset($_REQUEST['total'])) {
	$total = (float) $_REQUEST['total'];
	$total = round($total, 2);
	if ($total <= 0) die ('{\'payTypeID\': 0, \'form\': \'<p class="notice">The total of this order is zero. If you believe you have received this message in error, please contact us using the comment box below.</p>\'}');
} else die ('{\'payTypeID\': 0, \'form\': \'<p class="notice">The total of this order is zero. If you believe you have received this message in error, please contact us using the comment box below.</p>\'}');

if (isset($_REQUEST['payTypeID'])) {
	if ((int) $_REQUEST['payTypeID']) {
		$payType = new PayType ((int) $_REQUEST['payTypeID']);
		if (!$payType->payTypeID) die ();
		if (!$payType->isActive()) die ('{\'payTypeID\': 0, \'form\': \'<p class="notice">This payment type is not available right now. Please choose another payment type.</p>\'}');
		else {
 			$surcharge = $payType->getSurcharge($total);
echo '{"payTypeID": ' . $payType->payTypeID . ', "form": "';
			ob_start();
			switch ($payType->payTypeID) {
				case 2: ?>
					<h4>Step 1: pay through PayPal</h4>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="POST" target="_blank">
						<input type="hidden" name="cmd" value="_xclick"/>
						<input type="hidden" name="business" value="feedme@localmotive.ca"/>
						<input type="hidden" name="item_name" value="Payment for order #<? echo $order->orderID; ?>"/>
						<input type="hidden" name="item_number" value="1"/>
						<input type="hidden" name="amount" value="<?= $total ?>"/>
						<input type="hidden" name="handling" value="<?= $payType->getSurcharge($total) ?>"/>
						<input type="hidden" name="no_shipping" value="1"/>
						<input type="hidden" name="no_note" value="1"/>
						<input type="hidden" name="currency_code" value="CAD"/>
						<input type="hidden" name="lc" value="CA"/>
						<input type="hidden" name="bn" value="PP-BuyNowBF"/>
						<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but02.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
					</form>
					<p class="notice">Note: A <? if ($payType->surchargeType & N_PERCENT) echo $payType->surcharge . '%';
		else if ($payType->surchargeType & N_FLAT) echo '$' . $payType->surcharge; ?> surcharge of $<?= $surcharge ?> will be added on to your payment to cover PayPal's handling fees. It will appear as 'shipping and handling' on your PayPal receipt. Your balance will not reflect this payment until we receive confirmation from PayPal.</p>
					<h4>Step 2: Complete order</h4>
					<?
					break;
				case 1:
				default:
					$hasRoute = false;
					if (isset($_REQUEST['hasRoute'])) {
						$hasRoute = (bool) $_REQUEST['hasRoute'];
					}
					if ($hasRoute) echo '<p>Please have your payment ready for us when we come to deliver your order. Thank you!</p>';
					else echo '<p>Please drop off your payment, by cheque or cash, to your community, business, or neighbourhood depot. Thank you!</p><h4>Complete order</h4>';
			}
			echo jsSafeString(ob_get_clean());
			echo '"}';
		}
	} else echo "{'payTypeID': 0, 'form': ''}";
}

?>
