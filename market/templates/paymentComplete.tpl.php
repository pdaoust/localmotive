<style type="text/css">
	div#main { width: 1000px; }
</style>
<h2>Thank you for your payment!</h2>
<div class="info">
	<p>Thank you for your payment of <?= money_format(NF_MONEY, $amount) ?>! Your account balance was previously <?= money_format(NF_MONEY, $oldBalance) . ($oldBalance < 0 ? ' owing' : ($oldBalance > 0 ? ' credit' : null)) ?>, and your payment increased it to <?= money_format(NF_MONEY, $newBalance) . ($newBalance < 0 ? ' owing' : ($newBalance > 0 ? ' credit' : null)) ?>.</p>
	<p>You can review your account balance anytime. Just go to your <a href="<?= $urlPrefix ?>/market/activity.php?style=normal">account activity</a> page.</p>
	<?php if ($mailSent) { ?>
	<p>An e-mail confirmation of this payment has been sent to your address at <?= htmlEscape($customer->email) ?>.</p>
	<? } else { ?>
	<p class="notice">Note: We could not send an e-mail confirmation this payment to <?= htmlEscape($customer->email) ?>. It is possible that your address was mistyped. However, your payment was still processed.</p><? } ?>
	<p>You can now <a href="index.php">return to your account</a> or <a href="index.php?logout">log out</a>.</p>
</div>
