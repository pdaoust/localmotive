<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');

// if (!$user = tryLogin()) die ();

$style = (isset($_REQUEST['style']) ? ($_REQUEST['style'] == 'dialogue' ? 'dialogue' : 'normal') : 'normal');
$template = ($style == 'dialogue' ? '_d' : null);

$pageTitle = 'Log';
if (isset($_REQUEST['dayStart']) && isset($_REQUEST['monthStart']) && isset($_REQUEST['yearStart']))
	$startDate = strtotime((int) $_REQUEST['dayStart'] . ' ' . $monthNames[(int) $_REQUEST['monthStart']] . ' ' . (int) $_REQUEST['yearStart']);
else $startDate = time();
$startDate = roundDate($startDate);
if (isset($_REQUEST['dayEnd']) && isset($_REQUEST['monthEnd']) && isset($_REQUEST['yearEnd'])) {
	$endDate = strtotime((int) $_REQUEST['dayEnd'] . ' ' . $monthNames[(int) $_REQUEST['monthEnd']] . ' ' . (int) $_REQUEST['yearEnd']);
} else $endDate = time();
$endDate = roundDate($endDate) + T_DAY - 1;
// $endDate = roundDate($endDate) - T_HOUR - 1;
if (isset($_REQUEST['errorCode'])) $errorCode = (int) $_REQUEST['errorCode'];
else $errorCode = null;
$logEntries = $logger->getEntries($startDate, $endDate, ($errorCode ? $errorCode : null));
$logEntries = array_reverse($logEntries);

$dayStart = (int) strftime('%d', $startDate);
$monthStart = (int) strftime('%m', $startDate);
$yearStart = (int) strftime('%Y', $startDate);
$dayEnd = (int) strftime('%d', $endDate);
$monthEnd = (int) strftime('%m', $endDate);
$yearEnd = (int) strftime('%Y', $endDate);

include ($path . '/header' . $template . '.tpl.php');
include ($path . '/market/templates/viewLog.tpl.php');
include ($path . '/footer' . $template . '.tpl.php');

?>
