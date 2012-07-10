<h2>Ready to sign up?</h2>
<div class="info box">
	<p>Thanks for taking a tour of our service! If you like what you see, and you want to support farmers and other food producers in the Okanagan and Similkameen, we encourage you to...</p>
	<p><a href="signup.php?svcID=<?= $customer->personID ?>" class="button">sign up now!</a></p>
	<?= $customer->website ? '<p><a href="' . htmlEscape($customer->website) . '">Read more about ' . htmlEscape($customer->getLabel()) . '.</a></p>' : null ?>
</div>
