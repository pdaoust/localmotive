<style type="text/css">
	div#main { width: 1000px; }
</style>
<h2>Thank you for your order!</h2>
<div class="info">
	<p>We look forward to seeing you. If you like, you may print out this invoice for future reference, but it will always be available in your account, in your <a href="orderHistory.php">order history</a>.</p>
	<?php if ($mailSent) { ?>
	<p>An e-mail of this invoice has been sent to your address at <?= htmlEscape($customer->email) ?>.</p>
	<? } else { ?>
	<p class="notice">Note: We could not send an e-mail of this invoice to <?= htmlEscape($customer->email) ?>. It is possible that your address was mistyped. However, your order was still processed.</p><? } ?>
	<p>You can now <a href="index.php">return to your account</a> or <a href="index.php?logout">log out</a>.</p>
</div>
