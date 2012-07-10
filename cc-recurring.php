<?php
$pageTitle = 'Localmotive - Credit card storage and recurring billing policy';
$pageArea = 'policy';
include ('header.tpl.php');
?>

<h2>LocalMotive Organic Delivery - Credit card storage and recurring billing policy</h2>

<h3>Summary (in plain English)</h3>

<ul class="normal">
	<li>LocalMotive never stores any of your credit card details unless you allow us to. Even then, we only store the first digit and last four digits of your number (so you can verify which account is being billed).</li>
	<li>PayPal, our credit card processing gateway, does store your credit card details and allows us to review partial details, but does not allow us to see the full card number or the card verification code.</li>
	<li>We are extremely cautious about who we authorise to review even partial credit card details. Only select employees will have access to this data</li>
	<li>You are able at any time to remove a 'remembered' credit card number or cancel automatic recurring billing.</li>
	<li>If you have agreed to automatic recurring billing:
		<ul>
			<li>Your card is only automatically billed when we successfully create a recurring order for you.</li>
			<li>Your order will not be fulfilled if you do not have sufficient credit and we are not able to bill your credit card for a sufficient amount.</li>
			<li>The total you will be billed may change based on item price and availability, sales tax changes, and discounts/surcharges.</li>
		</ul>
	</li>
</ul>

<h3>Credit card storage</h3>

<p>LocalMotive Organic Delivery <strong>never</strong> stores the following credit card details in any of its servers:</p>

<ul class="normal">
	<li>full credit card number</li>
	<li>card verification code (CVC)</li>
	<li>card expiry date</li>
</ul>

<p>If a customer agrees to allow LocalMotive Organic Delivery to 'remember' their credit card details, or if a customer authorises LocalMotive to bill his or her credit card on an automatic, recurring basis, LocalMotive will store only the first digit and the last four digits of their credit card number in its server (to allow the customer to verify that the card number is correct). The customer's full credit card number and expiry date are stored securely in the servers of PayPal Inc, who is LocalMotive's credit card processing gateway. LocalMotive and its employees do not have access to the customer's full credit card number, but PayPal makes available to authorised employees and agents the first four and last four digits of the card number, and the card's expiration date, for reference purposes. These authorised people are limited to the following:</p>

<ul class="normal">
	<li>Owners/operators of LocalMotive</li>
	<li>LocalMotive employees who are responsible for accounting</li>
	<li>Contractors to LocalMotive who are responsible for development of credit card gateway integration</li>
</ul>

<p>If a customer requests to have his or her credit card details 'forgotten', LocalMotive will immediately remove from its database any credit card details it has stored. PayPal will still retain records of past transactions on that card, and will continue to make available the first four and last four digits of the card number, and the card's expiration date, to Localmotive and its authorised employees and agents.</p>

<h3>Automatic recurring billing</h3>

<p>If a customer authorises LocalMotive to bill his or her credit card on an automatic, recurring basis, LocalMotive will automatically bill him or her for the the exact total of his or her order (minus discounts, plus all applicable shipping, tax, and surcharges), on the order schedule he or she has agreed to. LocalMotive will bill him or her after the deadline for changes to a particular order, but before the delivery date of that order. The deadline for the customer's next order is displayed on the LocalMotive website, on the customer's welcome page and in the market.</p>

<p>The amount to be automatically billed to the customer's credit card may change from order to order because of changes in:</p>

<ul class="normal">
	<li>item prices</li>
	<li>item availability</li>
	<li>shipping fees</li>
	<li>sales tax</li>
	<li>surcharges and discounts</li>
</ul>

<p>If a customer has signed up for automatic recurring billing, but places an order manually, he or she will be billed at time of checkout rather than automatically after the ordering deadline. If LocalMotive is not able to create an order for a customer for any reason (such as service closures), his or her credit card will not be automatically billed for that order.</p>

<p>If a customer cancels his or her order, or cancels automatic recurring billing on his or her order, his or her credit card details will be immediately removed from LocalMotive's server. (PayPal will still keep a record of past transactions; see above for more details on the information PayPal records and makes available.)</p>

<p>If LocalMotive is not able to bill a customer's credit card for an order, and that customer does not have sufficient account credit for that order, that order will not be created or fulfilled.</p>

<?php include ('footer.tpl.php'); ?>
