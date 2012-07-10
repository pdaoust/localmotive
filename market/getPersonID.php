<?php

$_REQUEST['ajax'] = true;
include ('marketInit.inc.php');
if (!$user = tryLogin()) die ();

if (isset($_REQUEST['parentID']) && isset($_REQUEST['term'])) {
	if ((int) $_REQUEST['parentID'] && (string) $_REQUEST['term']) {
		$parent = new Person ((int) $_REQUEST['parentID']);
		if (!$parent) die ();
		if (!$parent->isIn($user)) die ();
		switch ($db->getDBType()) {
			case DB_PGSQL:
				$q = 'SELECT person.personID, person.contactName FROM person AS person, person AS parent WHERE person.lft BETWEEN parent.lft AND parent.rgt AND parent.personID = ' . (int) $_REQUEST['parentID'] . ' AND CONCAT(person.contactName, person.groupName) ~* \'[[:<:]]' . $db->cleanString($_REQUEST['term']) . '\' ORDER BY person.contactName';
				break;
			case DB_MYSQL:
				$q = 'SELECT person.personID, CONCAT_WS(\', \', person.contactName, person.groupName) AS label FROM person AS person, person AS parent WHERE parent.personID = '.(int) $_REQUEST['parentID'].' AND person.nodePath LIKE CONCAT(parent.nodePath, "%") AND CONCAT_WS(\' \', person.contactName, person.groupName) REGEXP \'[[:<:]]' . $db->cleanString($_REQUEST['term']) . '\' ORDER BY label';
		}
		$db->query($q, true);
		if ($db->getNumRows()) {
			$results = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$results[] = array('id' => $r->v('personID'), 'label' => $r->v('label'));
			}
			echo json_encode($results);
		}
	}
}
$json = true;
?>
