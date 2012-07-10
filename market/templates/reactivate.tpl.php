<h2>Welcome back, <em class="properName"><?= $user->contactName ?></em>!</h2>

<p>Thank you for re-joining Localmotive Organic Delivery. Things may have changed since last time you used our service, so we'd like you to:</p>
<ol>
	<li>Review our services</li>
	<li>Choose the service you'd like to sign up with</li>
	<li>Check over all your information before you reactiveate your account</li>
</ol>

<h3>Here are our services...</h3>
<p>Previously, you were a member of the <em><?= htmlEscape($svc->groupName) ?></em> service.</p>
<ul class="progOptions box reactivate">
	<li>
		<h3 id="prog_hhb"><a href="<?= $config['docRoot'] ?>/prog_hhb.php">Healthy Harvest Box</a></h3>
		<p>Start eating fresh with this mixed surprise box of fresh, local, season produce, sourced from Okanagan and Similkameen farms at wholesale prices. Sign up for a weekly, bi-monthly, or monthly box, either a $20 Harvest Box or a $30 Organic Box. Add other local food items to your order online! Delivery is available with a $50 minimum order.</p>
		 <a href="<?= $config['docRoot'] ?>/market/signup.php?svcID=2&reactivate=1" class="button callToAction"><?= $svc->personID == 2 ? 'reactivate my account' : 'switch to this service' ?></a>
	</li><li class="end">
		<h3 id="prog_farm2fork"><a href="<?= $config['docRoot'] ?>/prog_farm2fork.php">Farm to Fork&trade; Delivery</a></h3>
		<p>The ultimate in local food service: have your local and organic produce delivered directly to your restaurant or kitchen! Choose and customize your order online in just minutes from a variety of organic and local farms in the Okanagan-Similkameen. Delivery is available Tuesdays and Fridays for Osoyoos, Oliver, Okanagan Falls, Kaleden, Penticton, Summerland, and Naramata. Minimum order is $100.</p>
		<a href="<?= $config['docRoot'] ?>/market/signup.php?svcID=3&reactivate=1" class="button callToAction"><?= $svc->personID == 3 ? 'reactivate my account' : 'switch to this service' ?></a>
	</li>
</ul>
<script type="text/javascript" language="JavaScript" src="<?= $config['docRoot'] ?>/js/jquery.equalizecols.js"></script>
<script type="text/javascript" language="JavaScript">
$('.progOptions li').equalizeCols();
</script>
