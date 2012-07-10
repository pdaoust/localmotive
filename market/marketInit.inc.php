<?php

// $path = '/var/www/localmotive';
$path = dirname(__FILE__);
$path = preg_replace('#\/market$#', '', $path);
if (!isset($pageArea)) $pageArea = 'market';
//error_reporting(E_ALL);
require_once($path . '/errorConfig.php');
session_start();
if (isset($_GET)) $_GET = cleanInput($_GET);
if (isset($_POST)) $_POST = cleanInput($_POST);
if (isset($_COOKIE)) $_COOKIE = cleanInput($_COOKIE);
if (isset($_FILES) && isset($_FILES['name'])) {
	foreach ($_FILES['name'] as $k => $v) {
		$_FILES['name'][$k] = html_entity_decode($v);
	}
}

function cleanInput ($var) {
	foreach ($var as $k => $v) {
		if (is_array($v)) $var[$k] = cleanInput ($v);
		else if (is_string($v)) $var[$k] = html_entity_decode($v);
	}
	return $var;
}

if (isset($_REQUEST['pageArea'])) {
	switch ($_REQUEST['pageArea']) {
		case 'healthyharvest':
			$pageArea = 'healthyharvest';
			$_SESSION['pageArea'] = 'healthyharvest';
			break;
		case 'home_delivery':
		default:
			$pageArea = 'home_delivery';
			$_SESSION['pageArea'] = 'home_delivery';
	}
} else if (isset($_SESSION['pageArea'])) {
	switch ($_SESSION['pageArea']) {
		case 'healthyharvest':
			$pageArea = 'healthyharvest';
			$_SESSION['pageArea'] = 'healthyharvest';
			break;
		case 'home_delivery':
		default:
			$pageArea = 'home_delivery';
			$_SESSION['pageArea'] = 'home_delivery';
	}
} else {
	$pageArea = 'home_delivery';
	$_SESSION['pageArea'] = 'home_delivery';
}

require_once ($path . '/market/classes/base.inc.php');
require_once ($path . '/market/config.inc.php');
$logger = new Logger($config['logType'], $config['logFile']);
$logger->addEntry('-------------------- MARK');
$logger->addEntry('WSOD loading misc');
require_once ($path . '/market/classes/misc.inc.php');
$logger->addEntry('WSOD loading address');
require_once ($path . '/market/classes/address.inc.php');
$logger->addEntry('WSOD loading person');
require_once ($path . '/market/classes/person.inc.php');
$logger->addEntry('WSOD loading journalEntry');
require_once ($path . '/market/classes/journalEntry.inc.php');
//require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/order.inc.php');
//require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');


@$secure = (bool) $_SERVER['HTTPS'];
if ($secure) {
	$urlPrefix = $config['urlPrefix'];
	$secureUrlPrefix = '';
} else {
	$urlPrefix = '';
	$secureUrlPrefix = $config['secureUrlPrefix'];
}

register_shutdown_function('abortTransaction');

if (isset($_REQUEST['ajax'])) {
	$ajax = true;
	error_reporting(E_ALL ^ E_NOTICE);
} else $ajax = false;

$json = false;

if (isset($_REQUEST['logout'])) {
	sessionDefaults();
	if (preg_match('/market/', dirname(__FILE__))) {
		$noLogin = true;
		include ($path . '/header.tpl.php');
		include ($path . '/market/templates/logout.tpl.php');
		include ($path . '/footer.tpl.php');
		die ('died on logout');
	}
}

function getLoggedInUser () {
	if (!isset($_SESSION['personID'])) sessionDefaults();
	$user = new Person ();
	// if there's a session, and we're marked as login, check the validity of the session
	if (isset($_SESSION['loggedIn'])) {
		if ($_SESSION['loggedIn']) {
			$user->checkSession();
		}
	}
	// otherwise, see if someone has sent login credentials
	else if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
		$user->authenticate($_REQUEST['username'], $_REQUEST['password'], isset($_REQUEST['rememberMe']) ? $_REQUEST['rememberMe'] : false);
	}
	// done trying to log user in! If they're not logged in by now, $_SESSION['loggedIn'] will tell us so.
	return $user;
}

function tryLogin () {
	global $config, $path, $pageArea;
	$user = getLoggedInUser();
	if (!isset($_SESSION['loggedIn'])) $_SESSION['loggedIn'] = false;
	if (!$_SESSION['loggedIn']) {
		$loginError = false;
		if (isset($_SESSION['failedLoginAttempts'])) {
			switch ($user->getError()) {
				case E_TOO_MANY_FAILED_LOGINS:
					$loginError = 'Sorry, but you have reached the maximum number of login attempts. To protect your account, we\'ve locked it for ' . (int) ($config['blockFailedLoginTime'] / 60) . ' minutes. Please try again later.';
					break;
				case E_NO_OBJECT:
					$loginError = 'Sorry, but your e-mail address doesn\'t match any in our system. Please try again.';
					break;
				case E_LOGIN_CREDENTIALS_INCORRECT:
					$loginError = 'Sorry, but you have entered an incorrect password. Please try again (hint: check to see your caps lock key isn\'t on.)';
					break;
				case E_OBJECT_NOT_ACTIVE:
					$loginError = 'Sorry, but this account appears to be closed down. If you believe you\'ve received this message in error, please e-mail us at <a href="mailto:&#102;&#101;e&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;ot&#105;&#118;&#101;&#46;&#99;&#97;">&#102;&#101;&#101;&#100;&#109;&#101;&#64;&#108;&#111;&#99;&#97;&#108;&#109;&#111;&#116;&#105;&#118;e&#46;c&#97;</a>.';
			}
		}
		global $ajax;
		if ($ajax) {
			echo '{"login": false}';
			die ();
		} else if ($pageArea == 'market' || ($loginError && $pageArea != 'market' && isset($_REQUEST['username']))) {
			$pageTitle = 'Localmotive Market - log in';
			include ($path . '/header.tpl.php');
			include ($path . '/market/templates/login.tpl.php');
			include ($path . '/footer.tpl.php');
		}
		return false;
	} else if ($user->personType & P_SLEEPING) {
		$svc = $user->getParent(P_CATEGORY);
		include ($path . '/header.tpl.php');
		include ($path . '/market/templates/reactivate.tpl.php');
		include ($path . '/footer.tpl.php');
		return false;
	}
	return $user;
}/**/

?>
