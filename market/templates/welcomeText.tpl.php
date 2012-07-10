Welcome to Localmotive!

Thanks for signing up with Localmotive. We are happy to connect you with the yummiest local foods available, and thank you for helping to support local food growing.

Your account is now ready for use with the following settings:

    Login:    <? echo $email; ?> (your e-mail address)
    Password: <? echo $password; ?>


Here are the contact details you gave us:

    Contact name: <?= $user->contactName ?>

<? if ($user->groupName) { ?>    Organisation: <?= $user->groupName ?><? } ?>
<? if ($user->getRouteID(false)) { ?>    Route:        <? $route = $user->getRoute();
	echo $route->label; ?><? } ?>
    Phone:        <? echo $user->phone; ?>

<? if (isset($recurringOrder)) { ?>

And here are the details of your recurring order:

<?	foreach ($recurringOrder->orderItems as $v) { ?>    <?= $v->label . ' - ' . $v->quantityOrdered . ' @ ' . money_format(NF_MONEY, $v->getRealPrice()) ?>

<? }
	$totals = $recurringOrder->getTotal(); ?>
    Commitment:   <?= money_format(NF_MONEY, $totals['gross']) . ' ' . $recurringOrder->getPeriod() ?>

    Arrives on:   <?= strftime('%A', $recurringOrder->getNextDeliveryDay()) ?>

                  (Modifications can be made until <?= strftime('%A', $recurringOrder->getCutoffDay()) ?>

<? }
$parent = $user->getParent();
if ($parent->personType & P_DEPOT) { ?>

And finally, here is where to pick up and pay for your order:

    Contact/organisation: <?= $parent->getLabel() ?>

<? if ($parent->email) { ?>    E-mail:               <?= $parent->email ?>

<? }
if ($parent->phone) ?>    Phone:                <?= $parent->phone ?>
<? }
$boths = $parent->getAddresses(AD_SHIP + AD_PAY, true);
$ships = $parent->getAddresses(AD_SHIP);
$pays = $parent->getAddresses(AD_PAY);
if (count($boths)) { ?>


Pickup/payment spot<?= (count($ships) > 1 ? 's' : null) ?>

<? foreach ($boths as $thisAddy) { ?>
    <?= $thisAddy->careOf . ', ' . $thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null) ?>
<? 		if (isset($ships[$thisAddy->addressID])) unset($ships[$thisAddy->addressID]);
		if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
	}
}
if (count($ships)) { ?>


Pickup spot<?= (count($ships) > 1 ? 's' : null) ?>

<? foreach ($ships as $thisAddy) { ?>
    <?= $thisAddy->careOf . ', ' . $thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null) ?>
<?		if (isset($pays[$thisAddy->addressID])) unset($pays[$thisAddy->addressID]);
	}
}
if (count($pays)) { ?>


Payment spot<? (count($pays) > 1 ? 's' : null) ?>

<? foreach ($pays as $thisAddy) { ?>
    <?= $thisAddy->careOf . '</em>, ' . $thisAddy->address1 . ($thisAddy->address2 ? ', ' . $thisAddy->address2 : null) ?>
<?	}
} ?>
</dl>
<p>You can log into your account at <a href="http://www.localmotive.ca/market/">www.localmotive.ca/market/</a> and place an order, edit your recurring order, view your account history, and modify your account details.</p>
<p>Please let us know if you have any questions or suggestions for us to improve our service, and we will look forward to bringing you the finest freshest locally farmed foods available...</p>
<p>Sincerely, the LocalMotive Team!</p>
Welcome to Localmotive!

Thanks for signing up to <? $svc = $user->getCategory();
if ($svc) echo $svc->groupName;
else echo 'Localmotive';
?>. We are happy to connect you with the yummiest local foods available, and thank you for helping to support local food growing.
