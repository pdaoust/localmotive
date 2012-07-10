<?php
require_once ('market/marketInit.inc.php');
$pageArea = 'programs';
$pageSubArea = 'hhb';
$pageTitle = 'Healthy Harvest Box, local food delivery program - Localmotive Organic Food Co-op';
$svc = new Person (2);
include ('header.tpl.php'); ?>
<p class="notice">As of June 1st we begin weekly service. Online payment is now mandatory, cheques and cash payments no longer accepted. Only one type of Harvest Box is now available.</p>
<h2>Healthy Harvest Box</h2>
<div class="intro box">
	<p>The Healthy Harvest Box is a $25 mixed CSA (Consumer Supported Agriculture) box of fresh, seasonal produce, sourced as locally as possible from farms in the Okanagan-Similkameen region and around BC. Preference is given to organic local items. Boxes are available every week, two weeks, three weeks, or month, and are picked up at a convenient community location on Wednesdays <a href="#schedule">(check schedule)</a> or are delivered for an additional <?= (float) $svc->getShipping() ?>% delivery fee. <em>Start eating fresh</em> with this surprise box of veggies and fruit selected from the best seasonal items available in our region, and choose additional local grocery items to accompany your order.</p>
	<p class="rgt vmiddle"><a href="market/signup.php?svcID=<?= $svc->personID ?>" class="button large">sign up now!</a> &nbsp; <a href="market/order.php?tour=1&svcID=<?= $svc->personID ?>" class="button">tour the market</a></p>
</div>

<h3>How it works</h3>

<ol class="steps box">
	<li>
		<h4>Sign up</h4>
		<p>Sign up for the Healthy Harvest Box with our <a href="market/signup.php?svcID=2">online sign-up form</a>. Choose between home delivery <!--(<?= money_format(NF_MONEY, $svc->getMinOrderDeliver()) ?> minimum order)--> and group pickup <!--(<?= money_format(NF_MONEY, $svc->getMinOrder()) ?> minimum order)--> for your box service.
		<h5>Tips</h5>
		<ul class="normal">
			<li>Your e-mail address will be your login username for future visits to the market.</li>
			<li>If you choose home delivery, make sure your address is complete, and select the area you live in under 'Route'.</p>
			<li>Take advantage of the 'Directions' field to tell us how to find your house &mdash; how to access a locked gate, what your house looks like, where to leave the box, etc.</li>
			<li>If you choose group pickup, look for the group drop spot that is most convenient for you. See below for the <a href="#depots">list of current drop spots</a> and their schedules.</li>
		</ul>
	</li>

	<li>
		<h4>Choose a box type and a schedule</h4>
		<p>The Healthy Harvest Box is a $25 box, and is a <abbr title="consumers supporting agriculture">CSA</abbr> box program (consumers supporting agriculture). You choose the frequency of box that works best for you, either weekly or every two, three, or four weeks. By committing to regular orders, you allow us to plan crops with farms and supply a more consistent selection of local produce. Each year we plan with farms to figure out how much they can sell to us, and having a consistent clientele allows more accurate planning. You can change your schedule, but we ask you to try to minimize changes. Once you sign up, you don't need to place an order every week; our system creates orders for you based on your schedule. However, you can modify your order any time you like.</p>
		<p>Each box contains a variety of local produce, between 8 &ndash; 12 items, generally about 25 lbs. Preference is given to organic and local items. Boxes are worth about $30 retail price. No substitutions for items in boxes are currently available. You can add more grocery items to your box: meats, dairy, breads, bulk, beverages, etc. Find the list for each category of groceries by clicking on the corresponding section of the market.</p>
	</li>

	<li>
		<h4>Add more groceries to your box</h4>
		<p>Once you have signed up, you can order additional groceries to add to your box. <a href="market/">Log in to your account</a> using your email address and your password. You will now be at the main menu. From the main menu, choose 'Order items from the market', and you will be taken to the virtual farmers' market. Click on a market stall to browse the items for sale in that category. To add an item to your order, enter the quantity you want in the item's box (leave it blank if you only want one) and press the <span class="button small">+</span> button.</p>
		<p>Although we specialize in produce, you can choose from dairy, meats, baking, bulk goods, and other extras to build your order. Items that are visible but greyed out are unavailable or have sold out, due to supply shortages or seasonal changes in availability.</p>
		<p>Ordering is available from Friday at 4 pm until Sunday midnight. A 'Market Is Ready' email is sent out each Friday when the market is ready to take your orders. You can clearly see when you'll be receiving your next order, either from the main menu or the market. If you have missed the ordering window or already placed an order, your current order will be bumped into next period.</p>
	</li>

	<li>
		<h4>Choose items to receive every time</h4>
		<p>Once you have chosen all of your items, proceed to the checkout and verify your shopping list. At this point, you have the opportunity to select items that you would like to order on a recurring basis. To do so, click on the <img src="market/img/inf.png" alt="infinity" title="infinity" class="icon"/> symbol beside the item so it is highlighted in green, and it will be ordered on your behalf with every recurrence of your delivery. For example, if you would like a container of yogourt every week, simply add the item to your order, and upon checkout, click on the <img src="market/img/inf.png" alt="infinity" title="infinity" class="icon"/> symbol, and you will have it delivered each week or two as desired.</p>
		<p>You'll find that some items cannot be permanently added to your order. This is usually because they are seasonal or in short supply.</p>
	</li>

	<li>
		<h4>Pay for your box</h4>
		<p>You can pay online by credit card, or by cheque in advance. We use a secure payment provider, and you can <a href="#payment">read more about our payment policies below</a>.
		<? $payType = new PayType(PAY_CC);
		if ($payType->surcharge) echo "Each order is charged a {$payType->surcharge}% fee for credit card processing."; ?> You can use VISA or MasterCard, and can set up recurring payments.</a></p>
		<h5>Paying by cheque</h5>
		<p>If you pick up your order at a group drop spot and you're paying by cheque, you'll need to pay one delivery in advance. If you have signed up for home delivery, you'll need to pay for four deliveries in advance. (Don't forget that if you add items to your order after you write your cheque, you'll use up your credit faster!)</p>
	</li>

	<li>
		<h4>Receive your box!</h4>
		<h5>Home delivery</h5>
		<p>If you have signed up for home delivery, we'll need to know a bit about where you live, so please make sure the shipping address you entered is correct. If there are things that our drivers should know about, like a hard-to-find driveway or an apartment access code, please put that in the 'directions' field in your personal details. <a href="#homeDelivery">Read more about home delivery.</a></p>
		<h5>Group drop spots</h5>
		<p>If you have signed up to a group drop spot, you can pick up your box items on your group's delivery day. A list of items that goes in your box is given to you when you come to your group pickup location. Each item that comes with your box is laid out on a table, and a volunteer will help guide you along with a packing list of the items and quantities you get with your order. Package items into your own box, cloth bag, or other container to take home your box contents. We no longer provide rubbermaid tubs or boxes for customers. If you do not pick up your box at your drop spot during the hours of pickup, the box will be delivered automatically to your home, and a delivery surcharge of <?= (float) $svc->getShipping ?>% will be added to your order. </p>
	</li>
</ol>

<h3 id="boxcontents">What's in the box?</h3>

<p>You can either choose a 'surprise box' or customise your box's contents. The <b>Healthy Harvest Box</b> (a mix of organic and non-organic) is a surprise box, and because we choose the contents for you, we're able to put it together for wholesale price &mdash; typically 25% less than you'd pay in a supermarket. <b>Customised items</b> are closer to retail prices.</p>
<p>Download a <a href="localmotive-newsletter-sample.pdf" type="application/pdf">sample copy of our newsletter</a> from June 2010 to see what you might get in your surprise bin!</p>

<h3 id="schedule">Delivery schedule</h3>

<h4>Wednesdays</h4>
<dl class="normal">
	<dt>Penticton</dt>
	<dd>10 am to 12 noon</dd>
	<dt>Summerland</dt>
	<dd>3 pm to 5 pm</dd>
	<dt>Naramata</dt>
	<dd>3 pm to 5 pm</dd>
</dl>
<h4>Thursdays</h4>
<dl class="normal">
	<dt>Oliver</dt>
	<dd>12 noon to 1 pm</dd>
</dl>

<h3 id="depots">Pickup locations</h3>

<?php
$root = new Person (2);
$depots = $root->getChildren('groupName', false, array ('personType' => P_DEPOT));
$cities = array ();
foreach ($depots as $thisDepot) {
	if (!($thisDepot->personType & P_PRIVATE || !$thisDepot->isActive())) {
		$k = $thisDepot->personID;
		$ships = $thisDepot->getAddresses(AD_SHIP);
		$firstAddy = current($ships);
		if (!isset($cities[$firstAddy->city])) $cities[$firstAddy->city] = array ();
		$cities[$firstAddy->city][$k] = $thisDepot;
	}
}

$already = array ();
foreach ($cities as $city => $depots) {
	echo '<div class="info lgt">';
	echo '<h4>' . htmlEscape($city) . '</h4>';
	foreach ($depots as $thisDepot) {
		$boths = $thisDepot->getAddresses(AD_SHIP + AD_PAY, true);
		$ships = $thisDepot->getAddresses(AD_SHIP);
		$pays = $thisDepot->getAddresses(AD_PAY);
		echo "<div class=\"info\">\n";
		echo "<h5>" . htmlEscape($thisDepot->getLabel()) . "</h5>\n";
		echo "\t<p>";
		$details = array ();
		if ($thisDepot->email) $details[] = '<strong>E-mail</strong>: ' . munge(htmlEscape($thisDepot->email));
		if ($thisDepot->phone) $details[] = '<strong>Phone</strong>: ' . htmlEscape($thisDepot->phone);
		if ($thisDepot->description) $details[] = htmlEscape($thisDepot->description);
		echo implode('<br/>', $details);
		echo "\t</p>\n";
		foreach (array('boths', 'ships', 'pays') as $v) {
			if (count($$v)) {
				echo "\t<h6>";
				switch ($v) {
					case 'boths':
						echo 'Pickup &amp; payment';
						break;
					case 'ships':
						echo 'Pickup';
						break;
					case 'pays':
						echo 'Payment';
				}
				echo ' spot' . (count($$v) > 1 ? 's' : null) . "</h6>\n";
				echo "\t<ul>\n";
				foreach ($$v as $thisAddy) {
					echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null)) . "</li>\n";
					if ($v == 'boths') {
						if (isset($ships[$thisAddy->addressID])) unset($ships[$thisAddy->addressID]);
					}
					if ($v == 'boths' || $v == 'ships') {
						if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
					}
				}
				echo "\t</ul>\n";
			}
		}
		/*
		if (count($ships)) {
			echo "\t<h5>Pickup spot" . (count($ships) > 1 ? 's' : null) . "</h5>\n";
			echo "\t<ul>\n";
			foreach ($ships as $thisAddy) {
				echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null)) . "</li>\n";
				if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
			}
			echo "\t</ul>\n";
		}
		if (count($pays)) {
			echo "\t<h5>Payment spot" . (count($pays) > 1 ? 's' : null) . "</h5>\n";
			echo "\t<ul>\n";
			foreach ($pays as $thisAddy) {
				echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null)) . "</li>\n";
			}
			echo "\t</ul>\n";
		} */
		echo "</div>\n";
	}
	echo '</div>';
}

?>

<h3 id="homeDelivery">Delivery of boxes to homes or groups</h3>

<p>Delivery of boxes is available to homes for a <?= (float) $svc->getShipping() ?>% fee. Delivery is available to homes and offices in Summerland, Penticton, Naramata, and south to Oliver.</p>

<p>When you sign up, choose the delivery area that you are in, and enter any details about where you live. We prefer to leave your box of groceries <em>somewhere cool and shady</em> if possible (the north side of a building is best), so indicate where that might be in the 'directions' box of the signup form.</p>

<h3>Refrigerated items</h3>

<p>Any items that you have purchased that require refrigeration (indicated by the <img src="market/img/cold.png" alt="blue snowflake" title="blue snowflake" class="icon"/> icon) will be packaged with adequate cold packs to keep your items cool until you are home. It is even more ideal if you can leave out a cooler with your own ice packs in the cooler, and we will transfer refrigerated items into this cooler. Cold packs are expensive, and we appreciate it if you can keep your own stock of them if possible.</p>

<h3 id="payment">Credits and deposits</h3>

<p>Credit is given for any items that are missing or short from your order, or items that are of low quality based on your opinion. Credit is indicated on your receipt by the driver, or you can contact us with your concern. Balances to all accounts are processed on Fridays, and if you are anticipating a credit due to a missing item, it will be posted to your account at this time as well.</p>

<p>Some items, such as milk in glass, include a bottle deposit in the price. When you return the bottle, your account is credited for the deposit, and this credit will be applied to your next order.</p>

<h3>Box deposit and containers</h3>

<p>Your order is packaged into a reusable Rubbermaid tote box, and boxes are exchanged upon each delivery. Please ensure that your box is clean and ready for exchange as of 10 am on your delivery day. If you forget to return your box, your account will be charged a $7 fee per box until it is returned.</p>

<h3>Membership fee</h3>

<p>A yearly membership fee of <?= money_format(NF_MONEY, $svc->getDeposit()) ?> applies to all customers, helping to cover administration costs and overhead.</p>

<h3 class="boxOnHold">Box on Hold</h3>

<ol>
	<li>Log into your account and proceed to the market, where you would normally order additional items for your box.</li>
	<li>Look for the form near the bottom of the screen that says 'Put on hold', and enter the date of the Sunday prior to your box date.</li>
	<li>Choose the Sunday prior to the date you want to start receiving your box again.</li>
	<li>Press 'save' instead of 'check out'. Your box is now on hold!</li>
	<li>If you want to cancel your box, simply leave the resume date empty.</li>
</ol>

<h3>Long weekends!</h3>

<p>On long weekends with a Monday holiday, our delivery service is bumped by one day, so that deliveries are moved to Thursday of the following week.</p>

<h3>The nature of buying from the farm</h3>

<p>As we are sourcing as locally as possible for the contents of our boxes, it is important to note that many of the items we offer are highly seasonal and are often of limited supply. We expect that rhubarb will be ready in April, strawberries in June, corn in August, pumpkins in October, and kale in November (and maybe December and January and February...). When you eat local peaches that are ready in August, they are sweeter, more beautiful, and better for you than the dry peaches shipped in from South America in February. This seasonality applies to eggs, meats, and dairy products as well. The result is that, for example, free range eggs can be unavailable at times due to chickens who decide it is time to molt for a few weeks, or berry crops may be very limited at the beginning of their harvest season until peak harvest is reached.</p>

<p>In order to reduce the surprise of missing items that are not included in your boxes, the market indicates when an item has run out. When this happens, it will be greyed out, and you will not be able to add it to your order. We apologize in advance for any frustration that arises due to this reality, and are working to contract growers to cultivate more of popular items in the future.</p>

<p>For more info call 250-497-6577.</p>

<?php include ('footer.tpl.php'); ?>
