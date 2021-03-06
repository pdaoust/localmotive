<?php
require_once ('market/marketInit.inc.php');
$pageArea = 'programs';
$pageSubArea = 'farm2fork';
$pageTitle = 'Farm to Fork&trade;, local food delivery program - Localmotive Organic Food Co-op';
$svc = new Person(3);
include ('header.tpl.php'); ?>

<h2>Farm to Fork&trade; Delivery</h2>

<div class="intro">
	<h3>Why choose Farm to Fork&trade; Delivery?</h3>
	<p>Farm to Fork&trade; Delivery provides access to the freshest best quality local and organic ingredients for your clients. Choose from a variety of local and BC produce items from the convenience of your computer, and have them delivered two times per week. Every time you purchase from us, you are helping to build a food network in our region and keep <a href="farmers.php">farmers</a> motivated to grow food, while reducing CO<sub>2</sub> emissions.</p>
	<p class="rgt vmiddle"><a href="market/signup.php?svcID=<?= $svc->personID ?>" class="button large">sign up now!</a> &nbsp; <a href="market/order.php?tour=1&svcID=<?= $svc->personID ?>" class="button">tour the market</a></p>
</div>

<? if ((float) $svc->getDeposit()) { ?><h3>Yearly membership fees</h3>

<p>A <?= money_format(NF_MONEY, $svc->getDeposit()) ?> yearly membership fee is charged to each customer to help cover administration costs and other overhead.  Your membership fee will be billed automatically each year, according to the date your membership was activated.</p>
<? } ?>

<h3>Delivery schedule</h3>

<ul class="normal">
	<li><strong>Tuesdays</strong>: Penticton, Summerland, Naramata, and Kelowna</li>
	<li><strong>Thursdays</strong>: Oliver and Osoyoos</li>
</ul>

<h3>Ordering and ordering deadlines</h3>

<p>To order, <a href="market/">login to your account</a> using your e-mail address and your password. Follow the links to "Order items from the market", and you will be taken to the virtual farmers' market. <? if ($svc->getMinOrder()) { ?>Delivery minimum is <?= money_format(NF_MONEY, $svc->getMinOrder()) ?>. <? } ?>Choose from a variety of produce items, and add them to your shopping list. Proceed to the checkout when you are finished shopping. Ordering and delivery timelines are as follows:</p>

<ul class="normal">
	<li><strong>Tuesday delivery</strong>: Order from Friday noon until Sunday midnight</li>
	<li><strong>Thursday Delivery</strong>: Order from Friday noon until Sunday midnight</li>
</ul>

 <p>A "Market Update" email is sent out each Monday and Thursday at noon when the market is ready to take your orders. Orders placed outside of the ordering window may be bumped to the following delivery day. We prefer to process orders online to reduce administration costs, but are able to confirm by phone, fax, or e-mail if desired.</p>

<h3>Standing orders</h3>

<p>You can set up your order as a standing or 'recurring' order. This order will then be processed on a regular basis &mdash; every one, two, three, or four weeks. To set your order up as a recurring order, click the button labeled 'make recurring' at the bottom of the market, just below the shopping list. Your order will now allow you to choose how often you want to receive it.</p>

<p>Once you have chosen all of your items, proceed to the checkout and verify your shopping list. At this point, you have the opportunity to select items that you would like to order on a recurring basis. To do so, make sure the <img src="market/img/inf.png" alt="infinity" title="infinity" class="icon"/> symbol beside the item is highlighted in green, and these items will be ordered on your behalf.</p>

<p>You'll find that some items cannot be permanently added to your order. This is usually because they are seasonal or in short supply.</p>

<h3>Long weekends!</h3>
<p>On long weekends with a Monday holiday, our delivery service is bumped by one day, so that deliveries are on Wednesday and Friday of the following week.</p>

<h3>Payment</h3>

<p>We accept payment by VISA, Mastercard, and cheques made out to LocalMotive.</p>

<h3>Contact</h3>

<p><b>LocalMotive Organic Delivery</b><br/>
2351 Allendale Rd.<br/>
Okanagan Falls, BC V0H 1R2<br/>
<i>Phone:</i> 250-488-7615</p>

<? include ('footer.tpl.php'); ?>
