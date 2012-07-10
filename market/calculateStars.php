<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) restrictedError();

$pageTitle = 'Localmotive - Calculate stars';

// query to find out who ordered this week
$db->start('calculateStars');
if (!$db->query('UPDATE person SET stars = 0 WHERE recent = FALSE OR recent IS NULL')) {
	databaseError($db);
	$db->rollback('calculateStars');
	die();
}
$recentPeople = array ();
if (!$db->query('SELECT personID FROM person WHERE recent = TRUE')) {
	databaseError($db);
	$db->rollback('calculateStars');
	die();
}
while ($r = $db->getRow(F_RECORD)) {
	$recentPeople[] = $r->v('personID');
}
$starAddPeople = array ();
foreach ($recentPeople as $thisPersonID) {
	$person = new Person ((int) $thisPersonID);
	$maxStars = $person->getMaxStars();
	if ($maxStars > $person->stars) $starAddPeople[] = $thisPersonID;
}
if (count($starAddPeople)) {
	if (!$db->query('UPDATE person SET stars = stars + 1 WHERE personID IN (' . implode(',', $starAddPeople) . ')')) {
		databaseError($db);
		$db->rollback('calculateStars');
		die();
	}
}
if (!$db->query('UPDATE person SET recent = FALSE')) {
	databaseError($db);
	$db->rollback('calculateStars');
	die();
}
$db->query('UPDATE config SET value = \'' . time() . '\' WHERE configID = \'lastStarCalcDate\'');
$db->commit('calculateStars');

include ($path . '/header.tpl.php');
include ($path . '/market/templates/calculateStars.tpl.php');
include ($path . '/footer.tpl.php');

?>
