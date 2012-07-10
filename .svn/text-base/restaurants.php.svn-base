<?php
$pageTitle = 'Localmotive - Restaurant Delivery';
$pageArea = 'restaurants';
$pageSubArea = 'restaurants';
include ('header.tpl.php');
include ('restaurants_navbar.inc.php');

$minorderfilecontents = file("restaurants_minorder.csv");
$minorder = trim($minorderfilecontents[0]);

?>


<div class="intro" style="clear: both;">
	<p>FINALLY!  A company dedicated to linking farm products from your local growers exclusively to only the finest restaurants and wineries of the Okanagan Valley!</p>
</div>
<div style="text-align: center;">
	<a href="restaurants_new_customer.php"><img src="img/sign_up.jpg" alt="Sign up!" style="width: 110px; height: 120px; vertical-align: middle;"/></a>
	<a href="restaurants_market.php" target="_blank"><img src="img/market_tour.jpg" alt="Market tour" style="width: 140px; height: 160px; vertical-align: middle;"/></a>
	<a href="restaurants_place_an_order.php"><img src="img/place_order.jpg" alt="Market tour" style="width: 110px; height: 120px; vertical-align: middle;"/></a>
</div>

<p style="margin-top: 0;">Beginning in July 2007, once a week, we source directly from <strong>organically certified growers</strong> in the Okanagan Valley, ensuring a great supply and a dynamic selection of the highest quality and most unique ingredients for your cuisine. We work with only the most dedicated stewards of the land, who are committed to keeping quality and production high, so as to maintain reasonable prices. Harvested the day before delivery and immediately cooled, your products will be as good as if you grew them in your own kitchen garden! <strong>We have limited spaces for the 2007 season, so sign up now to avoid disappointment.</strong></p>

<h2>Ordering</h2>
<p>Every Friday we set up a new list of items for sale on our online market. Place your order by logging in to your Localmotive account and selecting items. Conveniently view pictures of the products for sale, adding them to your shopping list, and then make payment directly by credit card or cheque. A receipt of your transaction will be e-mailed to you for confirmation purposes once you have successfully submitted your order.<!-- Minimum order is only $<? echo $minorder; ?>.--></p>

<h2>Ordering Deadlines</h2>
<p>Place your order by Monday at 12 noon for Wednesday or Thursday delivery. Orders place past deadlines will be filled to the best of our ability, but will not be guaranteed.</p>

<h2>Delivery Schedule</h2>
<ul>
	<li><strong>Naramata:</strong> Thursdays</li>
	<li><strong>Kelowna:</strong> Wednesday mornings</li>
	<li><strong>Peachland, Summerland, and Penticton:</strong> Wednesday afternoons</li>
</ul>
<?php include ('footer.tpl.php'); ?>
