<h1>Welcome to Localmotive!</h1>
<p>Thanks for signing up with Localmotive. We are happy to connect you with the yummiest local foods available, and thank you for helping to support local food growing.</p>
<h2>Your account is now ready for use with the following settings:</h2>
<ul>
	<li>Login: <?= htmlEscape($email) ?> (your e-mail address)</li>
	<li>Password: <?= htmlEscape($password) ?></li>
</ul>
<h2>Here are the contact details you gave us:</h2>
<dl>
	<dt>Contact name:</dt><dd><?= htmlEscape($user->contactName) ?></dd>
	<? if ($user->groupName) { ?><dt>Organisation:</dt><dd><?= htmlEscape($user->groupName) ?></dd><? } ?>
	<? if ($user->getRouteID()) { ?><dt>Route:</dt><dd><? $route = $user->getRoute();
	echo htmlEscape($route->label); ?></dd><? } ?>
	<dt>Phone:</dt><dd><?= htmlEscape($user->phone) ?></dd>
</dl>
<? if (isset($recurringOrder)) {
	echo '<h2>And here are the details of your recurring order:</h2><dl>';
	foreach ($recurringOrder->orderItems as $v) {
		echo '<dt>' . htmlEscape($v->label) . ' - ' . $v->quantityOrdered . ' @ ' . money_format(NF_MONEY, $v->getRealPrice()) . '</dt>';
	}
	$totals = $recurringOrder->getTotal();
	echo '<dd>' . money_format(NF_MONEY, $totals['gross']) . ' ' . $recurringOrder->getPeriod() . '</dd>';
	echo '<dd>Arrives on ' . strftime('%A', $recurringOrder->getNextDeliveryDay()) . '</dd>';
	echo '<dd>Modifications can be made until ' . strftime('%A', $recurringOrder->getCutoffDay()) . '</dd>';
} ?></dl>
<? $parent = $user->getParent();
if ($parent->personType & P_DEPOT) { ?><h2>And finally, here is where to pick up and pay for your order:</h2>
<dl>
	<dt>Contact/organisation:</dt><dd><? echo htmlEscape($parent->getLabel()) . '<br/>';
if ($parent->email) echo "<dt>E-mail:</dt><dd><a href=\"mailto:".htmlEscape($parent->email)."\">" . htmlEscape($parent->email) . "</a></dd>\n";
if ($parent->phone) echo "<dt>Phone:</dt><dd>" . htmlEscape($parent->phone) . "</dd>\n";
echo '</dl>';
$boths = $parent->getAddresses(AD_SHIP + AD_PAY, true);
$ships = $parent->getAddresses(AD_SHIP);
$pays = $parent->getAddresses(AD_PAY);
if (count($boths)) {
	echo "\t<h3>Pickup &amp; payment spot" . (count($ships) > 1 ? 's' : null) . "</h3>\n<ul>";
	foreach ($boths as $thisAddy) {
		echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1) . ($thisAddy->address2 ? ', ' . htmlEscape($thisAddy->address2) : null) . "</li>\n";
		if (isset($ships[$thisAddy->addressID])) unset($ships[$thisAddy->addressID]);
		if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
	}
	echo '</li>';
}
if (count($ships)) {
	echo "\t<h3>Pickup spot" . (count($ships) > 1 ? 's' : null) . "</h3>\n";
	echo '<ul>';
	foreach ($ships as $thisAddy) {
		echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1) . ($thisAddy->address2 ? ', ' . htmlEscape($thisAddy->address2) : null) . "</li>\n";
		if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
	}
	echo '</ul>';
}
if (count($pays)) {
	echo "\t<h3>Payment spot" . (count($pays) > 1 ? 's' : null) . "</h3>\n";
	echo '<ul>';
	foreach ($pays as $thisAddy) {
		echo "\t\t<li><em>" . htmlEscape($thisAddy->careOf) . '</em>, ' . htmlEscape($thisAddy->address1) . ($thisAddy->address2 ? ', ' . htmlEscape($thisAddy->address2) : null) . "</li>\n";
	}
	echo '</ul>';
} } ?>
<p>You can log into your account at <a href="http://www.localmotive.ca/market/">www.localmotive.ca/market/</a> and place an order, edit your recurring order, view your account history, and modify your account details.</p>
<p>Please let us know if you have any questions or suggestions for us to improve our service, and we will look forward to bringing you the finest freshest locally farmed foods available...</p>
<p>Sincerely, the LocalMotive Team!</p>
