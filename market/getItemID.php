<?php

$_REQUEST['ajax'] = true;
require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/price.inc.php');

if (isset($_REQUEST['parentID']) && isset($_REQUEST['term'])) {
	if ((int) $_REQUEST['parentID'] && (string) $_REQUEST['term']) {
		switch ($db->getDBType()) {
			case DB_PGSQL:
				$q = 'SELECT item.itemID, item.label FROM item AS item, item AS parent WHERE item.lft BETWEEN parent.lft AND parent.rgt AND parent.itemID = ' . (int) $_REQUEST['parentID'] . ' AND item.label ~* \'[[:<:]]' . $db->cleanString($_REQUEST['term']) . '\' ORDER BY item.label';
				break;
			case DB_MYSQL:
				$q = 'SELECT item.itemID, item.label FROM item AS item, item AS parent WHERE parent.itemID = '.(int) $_REQUEST['parentID'].' AND item.nodePath LIKE CONCAT(parent.nodePath, "%") AND item.label REGEXP \'[[:<:]]' . $db->cleanString($_REQUEST['term']) . '\' ORDER BY item.label';
		}
		$db->query($q, true);
		if ($db->getNumRows()) {
			$results = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$results[] = array ('id' => $r->v('itemID'), 'label' => $r->v('label'));
			}
			echo json_encode($results);
		}
	}
}
$json = true;
?>
