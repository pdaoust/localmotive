<?php
require_once ('market/marketInit.inc.php');
if (!isset($noSidebars)) $noSidebars = false;
if (!isset($fillContainer)) $fillContainer = false;
if (!isset($logger)) global $logger;
if (!isset($urlPrefix)) global $urlPrefix;
if (!isset($secureUrlPrefix)) global $secureUrlPrefix;
if (!isset($ItemMapper)) global $ItemMapper;
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8"/>
		<title><?= htmlEscape($pageTitle) ?></title>
		<meta http-equiv="content-style-type" content="text/css"/>
		<link type="text/css" rel="stylesheet" href="<?= $config['docRoot'] ?>/style.css"/>
		<link href="<?= $config['docRoot'] ?>/style_print.css" rel="stylesheet" type="text/css" media="print"/>
		<link href="<?= $config['docRoot'] ?>/cal/nogray_calendar_vs1.css" rel="stylesheet" type="text/css"/>
		<script src="<?= $config['docRoot'] ?>/js/jquery-1.4.2.min.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery-ui-1.8rc3.custom.min.js" type="text/javascript" language="JavaScript"></script>
		<link href="<?= $config['docRoot'] ?>/jquery-ui.css" rel="stylesheet" type="text/css"/>
		<!--[if lte IE 7]>
		<style type="text/css">
			header { position: relative; top: 110px; z-index: 200; }
			#account li { display: inline; }
		</style>
		<![endif]-->
		<script src="<?= $config['docRoot'] ?>/js/labs_json.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery.selectboxes.pack.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery.alphanumeric.pack.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery.scrollTo-1.4.2-min.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery.sprintf.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/js/jquery.form.js" type="text/javascript" language="JavaScript"></script>
		<script src="<?= $config['docRoot'] ?>/market/templates/javascriptTools.js.php" type="text/javascript" language="JavaScript"></script>
		<script type="text/javascript" language="JavaScript">
		function toggleSurpriseOptions() {
			if (document.newCustomer.wantssurprisebin.checked) {
				for (i = 0; i < document.newCustomer.surprisebin.length; i ++) {
					document.newCustomer.surprisebin[i].disabled = false;
				}
				document.newCustomer.period.disabled = false;
				document.newCustomer.period.selectedIndex = 1;
			} else {
				for (i = 0; i < document.newCustomer.surprisebin.length; i ++) {
					document.newCustomer.surprisebin[i].disabled = true;
				}
				document.newCustomer.period.disabled = true;
				document.newCustomer.period.selectedIndex = 0;
			}
		}

		</script>
		<!--[if lte IE 8]>
		<script language="JavaScript">
		// HTML5-for-IE fix
		// For discussion and comments, see: http://remysharp.com/2009/01/07/html5-enabling-script/
		(function(){if(!/*@cc_on!@*/0)return;var e = "abbr,article,aside,audio,bb,canvas,datagrid,datalist,details,dialog,eventsource,figure,footer,header,hgroup,mark,menu,meter,nav,output,progress,section,time,video".split(',');for(var i=0;i<e.length;i++){document.createElement(e[i])}})()
		</script>
		<![endif]-->
	</head>

	<body>
	<div id="container">
		<header>
			<h1 id="logo"<?= $fillContainer ? ' class="sm"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/">LocalMotive - Connecting you to food from local &amp; BC farms</a></h1>
			<nav>
				<ul>
					<li id="programs" <?= ($pageArea == 'programs') ? 'class="selected"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/programs.php" rel="nofollow">Our Programs</a></li>
					<li id="farmers" <?= ($pageArea == 'farmers') ? 'class="selected"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/farmers.php">Our Farmers</a></li>
					<li id="market" <?= ($pageArea == 'market') ? 'class="selected"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/market/">Market</a></li>
					<li id="aboutus" <?= ($pageArea == 'aboutus') ? 'class="selected"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/aboutus.php">About Us</a></li>
					<li id="contactus" <?= ($pageArea == 'contactus') ? 'class="selected"' : null ?>><a href="<?= $urlPrefix . $config['docRoot'] ?>/contactus.php">Contact Us</a></li>
				</ul>
			</nav>

			<?php
			if (isset($noLogin)) {
				if (!$noLogin) {
					if (!isset($user)) $user = tryLogin ();
				} else $user = false;
			} else if (!isset($user)) $user = tryLogin ();
			if ($user) {
				if ($user->personID) {
					$noUser = false; ?>
			<div id="account" class="box auth">
				<h2>Welcome, <?= htmlEscape($user->getLabel(true)) ?>!</h2>
				<? if ($user->personType & P_CUSTOMER && !($user->personType & P_SLEEPING)) {
					$balance = $user->getBalance();
					if ($openOrder = $user->hasOpenOrder(O_RECURRING)) {
						if ($nextOpenOrder = $openOrder->hasCreatedOrder(null, false)) $nextDay = $nextOpenOrder->getDateToDeliver();
						else $nextDay = $openOrder->getNextDeliveryDay(null, false, false);
					} else if ($route = $user->getRoute()) {
						$nextDay = $route->getNextDeliveryDay();
						$deadline = $nextDay - $route->getCutoffDay($nextDay) * T_DAY;
						$nextDay = false;
					} else $nextDay = false; ?>
				<ul id="acctInfo">
					<li><strong>balance:</strong> <span class="<?= ($balance < 0) ? 'owing' : ($balance > 0) ? 'credit' : null ?>"><?= money_format(NF_MONEY, $balance) . ($balance < 0 ? ' &middot; <a href="'.$secureUrlPrefix.$config['docRoot'].'/market/payment.php">pay</a>' : null) ?></span> <!--&middot; <a href="<?= $config['docRoot'] ?>/market/">top up</a>--></li>
					<? if ($nextDay) { ?><li><strong>next delivery:</strong> <?= Date::human($nextDay) ?></li><? } else if (isset($deadline)) { ?><li><strong>order deadline:</strong> <?= Date::human($deadline) ?></li><? } ?>
				</ul>
				<? } ?>
				<ul id="acctNav">
					<? if ($user->personType & P_CUSTOMER && !($user->personType & P_SLEEPING)) { ?>
					<li class="first"><a href="<?= $urlPrefix . $config['docRoot'] ?>/market/order.php">order</a></li>
					<li><a href="<?= $urlPrefix . $config['docRoot'] ?>/market/">account</a></li>
					<? } ?>
					<li><a href="<?= (strstr($_SERVER['PHP_SELF'], $config['docRoot'] . '/market/') ? $config['docRoot'] . '/market/' : $_SERVER['PHP_SELF']) ?>?logout">log out</a></li>
				</ul>
			</div><?php
				} else $noUser = true;
			} else $noUser = true;
			if ($noUser) { ?>
			<div id="account" class="box noauth">
				<a href="<?= $secureUrlPrefix . $config['docRoot'] ?>/market/">Log in</a> | <a href="<?= $urlPrefix . $config['docRoot'] ?>/market/signup.php">Sign up</a>
			</div>
			<?php } ?>
		</header>

		<div id="paper">
			<? if (!$noSidebars) {
				require_once($path . '/market/classes/item.inc.php');
				require_once($path . '/market/classes/price.inc.php');
				$newItems = $ItemMapper->getNewItems(5, (is_object($user) ? $user : null));
				if (is_object($user)) {
					$uri = $urlPrefix . $config['docRoot'] . '/market/order.php';
					if ($orderID = $user->hasOpenOrder(O_SALE, true)) $uri .= '?orderID=' . $orderID;
					else if ($orderID = $user->hasOpenOrder(O_RECURRING, true)) $uri .= '?orderID=' . $orderID;
					else $uri .= '?';
				} else $uri = false; ?>
			<aside id="freshItems" class="box first">
				<h2>Fresh Items</h2>
				<ul>
					<? foreach ($newItems as $v) {
						if ((is_object($user) && $price = $v->getPrice($user->personID)) || !is_object($user)) { ?>
							<li>
								<? if ($uri) {
									$categoryID = reset(array_intersect($v->getPath(), array (2, 3, 4, 5, 6, 7)));
								} ?>
								<strong><?= htmlEscape($v->getLabel()) ?></strong><br/>
								<?= isset($price) ? money_format(NF_MONEY, $price->price) . ($price->multiple > 1 ? ' per ' . $price->multiple : null) . ' &middot; ' : null ?>
								<? if ($uri) { ?><a href="<?= $uri . '&categoryID=' . $categoryID . '#marketItem' . $v->itemID ?>">order</a><? } ?>
								<? if ($v->isRunningOut()) { ?><em class="notice">Running out soon!</em><? } ?>
							</li>
						<? }
					} ?>
				</ul>
			</aside>

			<!--<aside id="news" class="box">
				<h2>News</h2>
				<ul>
					<li>
						<strong>Healthy Harvest Box opens May 5th</strong><br/>
						posted 30 March 2010 &middot; <a href="<?= $urlPrefix . $config['docRoot'] ?>/prog_hhb.php">info</a>
					</li>
					<li>
						<strong>Farm-To-Fork&trade; Delivery now open!</strong><br/>
						posted 30 March 2010 &middot; <a href="<?= $urlPrefix . $config['docRoot'] ?>/prog_farm2fork.php">info</a>
					</li>
				</ul>
			</aside>-->
			<? } ?>

			<div id="content"<?= $fillContainer ? ' class="fill"' : null ?>>
				<!--[if lte IE 7]>
				<p>Does this page look funny? You may be using an out-of-date web browser. Please consider <a href="<?= $urlPrefix . $config['docRoot'] ?>/browser.php">upgrading your browser</a>.</p>
				<![endif]-->
				<!--<div class="notice">
					<h4>Now accepting credit cards!</h4>
					<p>We are finally accepting credit cards without the need to go through PayPal. If you have any troubles, please e-mail <a href='mailto&#58;%&#55;7eb&#103;&#37;&#55;5%&#55;9&#64;&#108;oc&#97;&#108;&#109;otive&#46;c%61'>&#119;ebgu&#121;&#64;local&#109;o&#116;&#105;ve&#46;c&#97;</a>. Thank you!</p>
				</div>-->
