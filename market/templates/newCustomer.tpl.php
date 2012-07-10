<style type="text/css">
	th { text-align: right; vertical-align: top; padding-right: 0.5em; }
	td { text-align: left; padding-left: 0.5em; }
</style>
<h1>New customer</h1>
<table>
	<tr>
		<th>Contact name</th>
		<td><?= htmlEscape($user->contactName) ?></td>
	</tr>
<? if ($user->groupName) { ?>
	<tr>
		<th>Organisation name</th>
		<td><?= htmlEscape($user->groupName) ?></td>
	</tr>
<? } ?>
	<tr>
		<th>E-mail</th>
		<td><?= htmlEscape($user->email) ?></td>
	</tr>
	<tr>
		<th>Addresses</th>
		<td><? foreach ($user->addresses as $thisAddress) {
			echo '<address>' . ($thisAddress->addressType & AD_SHIP ? '<strong>Shipping</strong>' : '<strong>Mailing</strong>') . '<br/>';
			echo htmEscape($thisAddress->address1) . ($thisAddress->address2 ? '<br/>' . htmlEscape($thisAddress->address2) : null) . '<br/>' . htmlEscape($thisAddress->city) . ', ' . htmlEscape($thisAddress->prov) . ' ' . htmlEscape($thisAddress->postalCode);
			if ($thisAddress->directions) echo '<br/>' . htmlEscape($thisAddress->directions);
			echo '</address>';
		} ?></td>
	</tr>
<? if ($user->getRouteID(false)) { ?>
	<tr>
		<th>Route</th>
		<td><? $route = $user->getRoute();
			echo htmlEscape($route->label); ?></td>
	</tr>
<? } ?>
	<tr>
		<th>Phone</th>
		<td><?= htmlEscape($user->phone) ?></td>
	</tr>
<? $parent = $user->getParent();
if ($parent->personType & P_DEPOT) { ?>
	<tr>
		<th>Depot</th>
		<td><?= htmlEscape($parent->contactName . ', ' . $parent->groupName) . '<br>'; ?></td>
	</tr>
<? }

if (isset($_POST['referral'])) { ?>
	<tr>
		<th>Referral</th>
		<td><?= htmlEscape($_POST['referral']); ?></td>
	</tr>
<? }
if (isset($recurringOrder)) {
	echo '<tr><th>Recurring order</th><td>';
	foreach ($recurringOrder->orderItems as $v) {
		echo '<dt>' . htmlEscape($v->label) . ' - ' . $v->quantityOrdered . ' @ ' . money_format(NF_MONEY, $v->getRealPrice()) . '</dt>';
	}
	$totals = $recurringOrder->getTotal();
	echo '<dd>' . money_format(NF_MONEY, $totals['gross']) . ' ' . $recurringOrder->getPeriod() . '</dd>';
	echo '<dd>Arrives on ' . strftime('%A', $recurringOrder->getNextDeliveryDay()) . '</dd>';
	echo '<dd>Modifications can be made until ' . strftime('%A', $recurringOrder->getCutoffDay()) . '</dd>';
	echo '</td></tr>';
} ?>
</table>
