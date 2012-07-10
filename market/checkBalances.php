<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1 && !($user->personType & P_DEPOT)) restrictedError();

$pageTitle = 'Localmotive - Check balances';

if (isset($_REQUEST['nodeID'])) {
	if ((int) $_REQUEST['nodeID']) {
		$node = new Person ((int) $_REQUEST['nodeID']);
		if ($node->personID) {
			if (!$node->isIn($user)) $node = $user;
		} else $node = $user;
	} else $node = $user;
} else $node = $user;


$tree = $node->getTree('contactName');

if (!$db->query('SELECT personID, SUM(amount) as balance FROM journalEntry GROUP BY personID')) {
	databaseError($db);
	die ();
}
$bals = array ();
while ($bal = $db->getRow(F_RECORD)) {
	$bals[$bal->v('personID')] = $bal->v('balance');
}

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'reconcile':
			if (!$db->query('UPDATE person LEFT JOIN (SELECT personID, SUM(amount) AS newBalance FROM journalEntry GROUP BY personID) AS je ON person.personID = je.personID SET person.balance = CAST(je.newBalance AS DECIMAL(8,2))')) databaseError($db);
			else {
				foreach ($bals as $k => $v) {
					$tree[$k]->balance = $v;
				}
			}
	}
}

include ($path . '/header.tpl.php');
include ($path . '/market/templates/checkBalances.tpl.php');
include ($path . '/footer.tpl.php');

?>
