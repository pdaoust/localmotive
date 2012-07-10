<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');

if (!$user = tryLogin()) die ();

$style = (isset($_REQUEST['style']) ? ($_REQUEST['style'] == 'dialogue' ? 'dialogue' : 'normal') : 'normal');

if (isset($_REQUEST['personID'])) {
	if (!$person = new Person ((int) ($_REQUEST['personID']))) {
		$error = 'No such person!';
		$errorDetail = 'This account doesn\'t exist.';
		include ($path . '/header' . $template . '.tpl.php');
		include ($path . '/market/templates/error.tpl.php');
		include ($path . '/footer' . $template . '.tpl.php');
		die();
	}
} else $person = &$user;

if (!$person->isIn($user)) {
	$error = 'Access denied!';
	$errorDetail = 'You do not have access to this person\'s account details.';
	include ($path . '/header' . $template . '.tpl.php');
	include ($path . '/market/templates/error.tpl.php');
	include ($path . '/footer' . $template . '.tpl.php');
	die ();
}
$pageTitle = 'Account activity for ' . $person->getLabel();

$timestamp = 0;
if (isset($_REQUEST['timestamp'])) $timestamp = roundDate(myCheckDate($_REQUEST['timestamp']));
if ($timestamp) {
	$dateStart = $timestamp;
	$dateEnd = $timestamp + T_DAY - 1;
} else {
	if (isset($_REQUEST['dateStart'])) $dateStart = myCheckDate($_REQUEST['dateStart']);
	if (!$dateStart) $dateStart = strtotime('-1 month', time());
	$dateStart = roundDate($dateStart);

	if (isset($_REQUEST['dateEnd'])) $dateEnd = myCheckDate($_REQUEST['dateEnd']);
	if (!$dateEnd) $dateEnd = time();
	$dateEnd = roundDate($dateEnd) + T_DAY - 1;
	if ($dateEnd < $dateStart) $dateEnd = $dateStart + T_DAY - 1;
}

$journalEntries = $person->getJournalEntries($dateStart, $dateEnd);
$journalEntries = array_reverse($journalEntries);

if (!$db->query('SELECT * from journalEntry WHERE journalEntryID = (SELECT MAX(journalEntryID) FROM journalEntry WHERE personID = ' . $person->personID . ')')) {
	databaseError($db);
	die ();
}
if ($r = $db->getRow(F_RECORD)) $currBal = new JournalEntry ($r);
else $currBal = null;

$noSidebars = true;
$fillContainer = true;
include ($path . ($style == 'dialogue' ? '/market/templates/header_d.tpl.php' : '/header.tpl.php'));
include ($path . '/market/templates/activity.tpl.php');
include ($path . ($style == 'dialogue' ? '/market/templates/footer_d.tpl.php' : '/footer.tpl.php'));
?>
