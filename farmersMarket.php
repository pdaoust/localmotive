<?php
$pageTitle = 'Localmotive - Online Farmers Market';
$pageArea = 'farmers_market';
include ('header.tpl.php');
//include ('home_delivery_navbar.inc.php');
?>

<div class="intro">
	<h2>Online Farmers Market</h2>
	<p>Our Online Farmers Market is a perfect solution for to your restaurant, school, or business, allowing you to purchase wholesale directly from farmers and producers in the South Okanagan region. Set up a recurring or custom order at the Market and have your products delivered at no additional charge.</p>
	<p class="notice">The Farmers Market is closed as of Oct 16th, 2009 and will resume in 2010. Customers can still sign-up for the programme online; however, the accounts will not be activated until 2010. Please send us an email with any questions: <a href="mailto:&#102;&#101;e&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;ot&#105;&#118;&#101;&#46;&#99;&#97;">&#102;&#101;&#101;&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;&#111;&#116;&#105;&#118;e&#46;c&#97;</a>. Thank you for your support this year!</p>
</p>
	<? // include ('announce.csv'); ?>
</div>

<div style="text-align: center; margin: 0;">
	<a href="market/signup.php?svcID=3"><img src="img/sign_up.jpg" alt="Sign up!" style="width: 110px; height: 120px; vertical-align: middle;"/></a>
	<!-- <a href="market/marketTour.php?service=farmersMarket" target="_blank"><img src="img/market_tour.jpg" alt="Market tour" style="width: 140px; height: 160px; vertical-align: middle;"/></a> -->
	<a href="market/"><img src="img/place_order.jpg" alt="Place an order" style="width: 110px; height: 120px; vertical-align: middle;"/></a>
</div>
<h2 style="margin-top: 0;">Save money!<img src="img/gourds.jpg" class="alignright" alt="gourds"/></h2>
<p>By dealing directly with farmers, we get the best price possible for locally grown and produced foods. Purchase in bulk at wholesale prices, and benefit from savings on fuel that would normally be incurred traveling to different farms.</p>
<h2><img src="img/potatoes.jpg" class="alignleft" style="position: relative; left: -10px;" alt="potatoes"/>Save time!</h2>
<p>Shop for your local foods in minutes at our web-based market! Choose items from a variety of growers within the South Okanagan and Similkameen region, and have them delivered to your doorstep. Ordering and payment online takes only minutes out of your busy schedule.</p>
<h2>Save the environment!</h2>
<p>In this day and age, the average meal travels over 2500 km to your plate. Our selection of products comes from the Okanagan and Similkameen valleys. Combined with our efficient delivery system, we ensure that your food choices require as little CO<sub>2</sub> as possible.<img src="img/farmer.jpg" class="alignright" style="position: relative; left: 10px;" alt="farmer"/></p>
<h2>Save our farmers!</h2>
<p>Farmers in the Okanagan and BC are competing against unprecedented conditions: global trade, increasing land prices, and increased food regulations. As a result, many farmers are in massive debt and going out of business, and there is a significant lack of new farmers taking on this trade. By supporting these growers and producers, Localmotive strives to <img src="img/pears.jpg" class="alignleft" style="position: relative; left: -10px;" alt="pears"/> create a niche for more local products and hope for the future farming generation.</p>
<h2>Schedule</h2>
<p>Our market is updated with new products on a weekly basis. We deliver to most places in the Okanagan two times per week, on Tuesdays and Fridays.</p>
<h3>Order deadlines</h3>
<ul>
	<li><strong>Tuesday:</strong> Sunday night</li>
	<li><strong>Friday:</strong> Wednesday night</li>
</ul>
<!-- <p>We deliver on Tuesday to Saturday in Summerland, Penticton, Naramata, and Okanagan Falls, and we deliver Osoyoos and Oliver on Fridays. You can order the day before, or up until 7:30 in the morning, and expect to receive it that day.</p> -->
<?php
include ('footer.tpl.php'); ?>
