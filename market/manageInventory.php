<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/price.inc.php');
include ($path . '/market/templates/noderow.tpl.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) {
	if ($ajax) {
		echo '0';
		$json = true;
	} else restrictedError();
	die ();
}

$pageTitle = 'Localmotive - Manage inventory';

$verifyItemFields = false;
$errorItemFields = array ();
$showMoveControls = true;
$noSidebars = true;
$fillContainer = true;
$sortOrder = 'sortOrder';

if (isset($_REQUEST['nodeID'])) {
	if ((int) $_REQUEST['nodeID']) {
		$category = new Item ((int) $_REQUEST['nodeID']);
		if ($category->itemID) {
		} else $category = new Item (1);
	} else $category = new Item (1);
} else $category = new Item (1);

function outputItemData ($item, $status, $extras = null) {
	if (!is_array($extras)) $extras = array();
	if ($prices = $item->getPrices()) {
		global $db;
		if (!$db->query('SELECT contactName, groupName, personID FROM person WHERE personID in (' . implode(', ', array_keys($prices)) . ')')) {
			$json = true;
			echo '{"status": 0}';
			die ();
		}
		$names = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$names[$r->v('personID')] = $r->v('contactName') . ($r->v('contactName') && $r->v('groupName') ? ', ' : null) . $r->v('groupName');
		}
		$pricesT = array ();
		$priceVals = array ();
		foreach ($prices as $thisPrice) {
			$pricesT[$thisPrice->personID] = array (
				'price' => money_format(NF_MONEY_NOCURR, $thisPrice->price),
				'label' => $names[$thisPrice->personID],
				'multiple' => (int) $thisPrice->multiple,
				'hst' => (int) (bool) ($thisPrice->tax & TAX_HST),
				'pst' => (int) (bool) ($thisPrice->tax & TAX_PST)
			);
			$priceVal = money_format(NF_MONEY, $thisPrice->price) . ($thisPrice->tax ? '+' . (($thisPrice->tax & TAX_HST) ? 'G' : null) . (($thisPrice->tax & TAX_PST) ? 'P' : null) : null) . ($thisPrice->multiple == 1 ? ' ea' : ' per ' . $thisPrice->multiple);
			if (!in_array($priceVal, $priceVals)) $priceVals[] = $priceVal;
		}
	} else {
		$prices = null;
		$priceVals = null;
	}
	echo json_encode(array_merge(array (
		'status' => (int) $status,
		'itemID' => ($item->itemID ? (int) $item->itemID : null),
		'parentID' => $item->getParentID(),
		'sku' => $item->sku,
		'label' => $item->label,
		'itemType' => $item->itemType,
		'location' => $item->location,
		'distance' => $item->distance,
		'description' => $item->description,
		'notes' => '',
		'quantity' => $item->quantity,
		'reorderQuantity' => $item->reorderQuantity,
		'runningOutQuantity' => $item->runningOutQuantity,
		'cutoffDay' => $item->cutoffDay,
		'cutoffDayParent' => $item->getCutoffDay(false),
		'canOrderPastZero' => $item->canOrderPastZero,
		'canOrderPastZeroParent' => $item->getCanOrderPastZero(false),
		'trackInventory' => $item->trackInventory,
		'trackInventoryParent' => $item->getTrackInventory(false),
		'specialPacking' => $item->specialPacking,
		'specialPackingParent' => $item->getSpecialPacking(false),
		'availableToRecurring' => $item->availableToRecurring,
		'availableToRecurringParent' => $item->getAvailableToRecurring(false),
		'csaRequired' => $item->csaRequired,
		'csaRequiredParent' => $item->getCsaRequired(false),
		'organic' => $item->organic,
		'organicParent' => $item->getOrganic(false),
		'canBePermanent' => $item->canBePermanent,
		'canBePermanentParent' => $item->getCanBePermanent(false),
		'image' => (bool) $item->image,
		'activeStates' => $item->getActiveStates(),
		'path' => $item->getToken(),
		'active' => (bool) $item->active,
		'isActive' => (bool) $item->isActive(),
		'prices' => $pricesT,
		'priceInfo' => ($priceVals ? implode(', ', $priceVals) : null),
	), $extras));
}

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'loadItem':
			if (isset($_REQUEST['itemID'])) $itemID = (int) $_REQUEST['itemID'];
			else $itemID = 0;
			$extras = array ();
			if ($itemID) {
				$item = new Item ((int) $_REQUEST['itemID']);
			} else if (isset($_REQUEST['parentID'])) {
				$item = new Item;
				$parent = new Item ((int) $_REQUEST['parentID']);
			}
			if ($item->itemID || (isset($parent) && $parent->itemID)) {
				if (isset($parent) && $parent->itemID) $extras['parentID'] = $parent->itemID;
				outputItemData($item, 1, $extras);
			} else {
				echo '{"status": 0}';
			}
			$json = true;
			die ();
			break;
		case 'setParent':
			$db->start('miSetParent');
			if (!$item = new Item ((int) $_REQUEST['itemID'])) {
				$this->rollback('miSetParent');
				break;
			}
			if (!$item->setParent((int) $_REQUEST['parentID'])) $db->rollback('miSetParent');
			else {
				if ($item->save()) $db->commit('miSetParent');
				else $db->rollback('miSetParent');
			}
			break;
		case 'editItem':
			$itemID = (int) $_REQUEST['itemID'];
			if ($itemID) $editItem = new Item ($itemID);
			else $editItem = new Item;
			$editItem->sku = $_REQUEST['sku'];
			$editItem->label = $_REQUEST['label'];
			$editItem->itemType = (int) $_REQUEST['itemType'];
			$editItem->location = ($_REQUEST['location'] ? trim($_REQUEST['location']) : null);
			$editItem->distance = ($_REQUEST['distance'] ? (int) $_REQUEST['distance'] : null);
			$editItem->description = $_REQUEST['description'];
			$editItem->quantity = ($_REQUEST['quantity'] ? (int) $_REQUEST['quantity'] : null);
			$editItem->reorderQuantity = ((int) $_REQUEST['reorderQuantity'] ? (int) $_REQUEST['reorderQuantity'] : null);
			$editItem->runningOutQuantity = ((int) $_REQUEST['runningOutQuantity'] ? (int) $_REQUEST['runningOutQuantity'] : null);
			$editItem->cutoffDay = ($_REQUEST['cutoffDayNull'] ? null : (int) $_REQUEST['cutoffDay']);
			foreach (array('canOrderPastZero', 'trackInventory', 'specialPacking', 'availableToRecurring', 'csaRequired', 'organic', 'canBePermanent') as $k) {
				if (isset($_POST[$k])) {
					switch ((int) $_POST[$k]) {
						case -1:
							$editItem->$k = null;
							break;
						default:
							$editItem->$k = (bool) $_REQUEST['canOrderPastZero'];
					}
				}
			}
			$editItem->active = $_REQUEST['active'] ? true : false;
			$db->startLogging();
			//$db->start('saveItem');
			if (!$editItem->save()) {
				$db->rollback('saveItem');
				$error = $editItem->getError();
				if ($error == E_INVALID_DATA) {
					$status = -1;
					$errorFields = $editItem->getErrorDetail();
				} else $status = 0;
			} else {
				if (!$itemID) $editItem->setParent((int) $_REQUEST['parentID']);
				$status = ($itemID ? 1 : 2);
				foreach ($_REQUEST['price'] as $thisPersonID => $thisPrice) {
					if ($thisPrice['price'] == '') {
						if ($editItem->hasPrice((int) $thisPersonID)) {
							$editItem->deletePrice((int) $thisPersonID);
						}
					} else {
						if (!isset($thisPrice['hst'])) $thisPrice['hst'] = false;
						if (!isset($thisPrice['pst'])) $thisPrice['pst'] = false;
						if (!isset($thisPrice['multiple'])) $thisPrice['multiple'] = 1;
						$editItem->setPrice((int) $thisPersonID, (float) $thisPrice['price'], ($thisPrice['hst'] ? TAX_HST : 0) + ($thisPrice['pst'] ? TAX_PST : 0), (int) $thisPrice['multiple']);
					}
				}
				$deleteImage = (isset($_REQUEST['deleteImage']) ? (bool) $_REQUEST['deleteImage'] : false);
				if ($deleteImage && $editItem->image) {
					$editItem->removeImage();
				} else if (isset($_FILES['image'])) {
					// TODO: add special status for image upload result
					$editItem->addImage($_FILES['image']['tmp_name']);
				}
				$db->commit('saveItem');
			}
			if ($ajax) {
				$extras = array ();
				if ($status == 2) {
					$extras['position'] = (int) $editItem->getSpotInTree($sortOrder, $category->itemID);
					$isActive = $editItem->isActive();
					global $category;
					$treeToken = $editItem->getToken($category->itemID);
					ob_start();
					outputItemRow($editItem, $treeToken);
					$extras['newRow'] = ob_get_clean();
				}
				if (isset($errorFields)) $extras['errorFields'] = $errorFields;
				ob_start();
				echo '<textarea>';
				outputItemData ($editItem, $status, $extras);
				echo '</textarea>';
				$out = ob_get_clean();
				$logger->addEntry($out);
				echo $out;
				die ();
			}
			break;
		case 'moveNode':
			if (!$editItem = new Item ((int) $_REQUEST['nodeID'])) {
				if ($ajax) {
					echo '{ "status": 0}';
					die ();
				}
			} else {
				switch ($_REQUEST['direction']) {
					case 'down':
						$moveState = $editItem->moveRight();
						break;
					case 'up':
						$moveState = $editItem->moveLeft();
				}
				if ($ajax) {
					if (!$moveState) {
						echo '{ "status": 0 }';
						die ();
					}
					$path = $editItem->getPath();
					foreach ($path as $k => $v) {
						$path[$k] = sprintf('%05s', $v);
					}
					echo json_encode(array(
						'status' => 1,
						'nodeID' => 'node0_'.implode('_', $path),
						'd' => $moveState,
						'size' => $editItem->hasChildren()
					));
					die ();
				}
			}
			break;
		case 'deleteItem':
			if (isset($_REQUEST['itemID'])) {
				$item = new Item ((int) $_REQUEST['itemID']);
				$path = $item->getPath();
				$parentID = $item->getParentID();
				$success = $item->delete();
				if ($ajax) {
					if ($success) {
						foreach ($path as $i => $thisID) {
							$path[$i] = sprintf('%05s', $thisID);
						}
						$out = array (
							'status' => true,
							'nodeID' => 'node0_'.implode('_', $path)
						);
						array_pop($path);
						$parent = new Item ((int) $parentID);
						$out['parentID'] = 'node0_' . implode('_', $path);
						$out['parentIsLeaf'] = (bool) $parent->isLeafNode();
						echo json_encode($out);
					} else echo '{"status": 0}';
					die ();
				}
			}
			/*if (isset($_REQUEST['itemID'])) {
				$item = new Item ((int) $_REQUEST['itemID']);
				$path = $item->getPath();
				$parentID = $item->getParentID();
				$success = $item->delete();
				if ($ajax) {
					if ($success) {
						foreach ($path as $i => $thisID) {
							$path[$i] = sprintf('%05s', $thisID);
						}
						echo 'node0_' . implode('_', $path) . "\n";
						array_pop($path);
						$parent = new Item ((int) $parentID);
						echo 'node0_' . implode('_', $path) . ':' . (int) $parent->isLeafNode();
					} else echo '0';
					$json = true;
					die ();
				}
			}*/
			break;
		case 'loadMoveTree':
			if (isset($_REQUEST['itemID'])) {
				if ((int) $_REQUEST['itemID']) {
					$root = new Item (1);
					$tree = $root->getTree();
					$logger->log('loadMoveTree: tree is', $tree);
					$item = new Item((int) $_REQUEST['itemID']);
					$parentID = $item->getParentID();
					echo '<form action="manageInventory.php" method="POST"><input type="hidden" name="action" value="setParent"/><input type="hidden" name="itemID" value="' . (int) $_REQUEST['itemID'] . '"/>';
					echo '<select name="parentID">';
					foreach ($tree as $thisNode) {
						echo '<option value="' . $thisNode->itemID . '"' . ($thisNode->isIn($item) ? ' disabled="disabled"' : null) . '>' . str_repeat('&nbsp;&nbsp;', $thisNode->getDepth($root)) . htmlEscape($thisNode->label) . '</option>';
					}
					echo '</select> <input type="submit" value="move"/>';
					echo '</form>';
				}
			}
			die ();
	}
}

$tree = $category->getTree(null, 'tree');
$node = &$category;
$categories = $user->getTree(null, null, array('personType' => P_CATEGORY));

include ($path . '/header.tpl.php');
include ($path . '/market/templates/manageInventory.tpl.php');
include ($path . '/footer.tpl.php');

?>
