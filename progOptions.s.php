<ul class="progOptions box">
	<li>
		<h3 id="prog_hhb"><a href="prog_hhb.php">Healthy Harvest Box</a></h3>
		<p>Start eating fresh with this $25 mixed surprise box of fresh, local, seasonal produce, sourced from Okanagan and Similkameen farms. Sign up for a weekly, bi-monthly, or monthly box. Add other local food items to your order online! Delivery to your home or office is available for an additional <? $hhb = new Person(2); echo (float) $hhb->getShipping(); ?>% delivery fee.</p>
		<p><a href="prog_hhb.php">More info...</a></p>
		 <a href="market/signup.php?svcID=2" class="button callToAction">Sign up now!</a>
	</li><li class="end">
		<h3 id="prog_farm2fork"><a href="prog_farm2fork.php">Farm to Fork&trade; Delivery</a></h3>
		<p>The ultimate in local food service: have your local and organic produce delivered directly to your restaurant or store! Choose and customize your order online in just minutes from a variety of organic and local farms in the Okanagan-Similkameen and BC. Delivery is available weekly for Penticton, Summerland, Kelowna, and Naramata. Minimum order is $100.</p>
		<p><a href="prog_farm2fork.php">More info...</a></p>
		<a href="market/signup.php?svcID=3" class="button callToAction">Sign up now!</a>
	</li><li>
		<h3 id="prog_pantry"><a href="prog_pantry.php">Packed Pantry</a></h3>
		<p>A CSA (Consumer Shared Agriculture) program for preserves of Okanagan fruit and veggie items to store for winter! Select from a variety of canned fruits, jams, salsas, pickled veggies, and more. Canned with care using local produce.</p>
		<a href="market/pantry.php" class="button callToAction">Order yours now!</a>
	</li><li class="end">
		<h3 id="prog_events"><a href="calendar.php">Events and Workshops</a></h3>
		<p>Ever wanted to learn how to preserve your own canning, build a garden, make a chicken pen? Sign up for these events and workshops in your community.</p>
		<a href="calendar.php" class="button callToAction">View the calendar!</a>
	</li>
</ul>
<script type="text/javascript" language="JavaScript" src="<?= $config['docRoot'] ?>/js/jquery.equalizecols.js"></script>
<script type="text/javascript" language="JavaScript">
$('.progOptions li:first-child, .progOptions li:first-child + li').equalizeCols();
$('.progOptions li:nth-child(3), .progOptions li:nth-child(4)').equalizeCols();
</script>
