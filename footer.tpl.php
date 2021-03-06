			</div>
			<footer>
				<div class="left">
					<p class="vcard">Localmotive Organic Delivery<br/>
						Thomas and Celina Tumbach<br/>
						2351 Allendale Rd<br/>
						Okanagan Falls, BC V0H 1R2<br/>
						<b>Phone:</b> 250.497.6577<br/>
						<b>E-mail:</b> <a href="mailto:&#102;&#101;e&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;ot&#105;&#118;&#101;&#46;&#99;&#97;">&#102;&#101;&#101;&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;&#111;&#116;&#105;&#118;e&#46;c&#97;</a>
					</p>
					<p>
						<a href="<?= $urlPrefix . $config['docRoot'] ?>/privacy.php">Privacy policy</a> | <a href="<?= $urlPrefix . $config['docRoot'] ?>/refund-delivery.php">Refund and method-of-delivery policies</a>
					</p>
				</div>
				<div class="copyright">
					<p>Copyright &copy; 2008 &ndash; 2010 Localmotive Organic Delivery. All rights reserved.</p>
					<p>Website design and web application development by Paul d'Aoust of <a href="http://heliosstudio.ca/">Helios Communications</a>, a local studio.</p>
					<p>Hosted by <a href="http://www.expiry.com">Expiry Corporation</a>, a Kelowna company who run their servers on local, mostly green electricity.</p>
				</div>
			</footer>
		</div>

	</div>
	</body>
	<script language="JavaScript" type="text/javascript">
	var oTxtFields = $('.inlineLabels li');
	$.each(oTxtFields, function(){
		if ($('input:text, input:password, textarea', $(this)).length) $(this).addClass('inline');
		/* var label = $('label[for=' + $(this).attr('id') + ']');
		label.addClass('overlayed');
		if (!$(this).val() == '') {
			label.hide();
		}*/
		var inputs = $('input:text, input:password, textarea', this);
		$.each(inputs, function () {
			$(this)
				.focus(function(e){
					$('label[for=' + $(e.target).attr('name') + ']').hide();
				})
				.blur(function(e){
					if ($(e.target).val() == '') {
						$('label[for=' + $(e.target).attr('name') + ']').show();
					}
				})
			;
		});
	});
	</script>
	<? if ($user->personID != 1) { ?>
	<!-- Piwik -->
	<script type="text/javascript">
	var pkBaseURL = (("https:" == document.location.protocol) ? "https://stats.heliosstudio.ca/" : "http://stats.heliosstudio.ca/");
	document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
	</script><script type="text/javascript">
	try {
	var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 3);
	piwikTracker.trackPageView();
	piwikTracker.enableLinkTracking();
	} catch( err ) {}
	</script><noscript><p><img src="http://stats.heliosstudio.ca/piwik.php?idsite=3" style="border:0" alt="" /></p></noscript>
	<!-- End Piwik Tag -->
	<? } ?>
</html>
