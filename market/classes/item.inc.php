<?php

// 'modified preorder tree traversal' concepts taken (gratefully) from tutorial at
// http://mrnaz.com/static/articles/trees_in_sql_tutorial/index.php

class ItemMapper extends MarketPrototype {
	private $_newItems;
	private $_tree;
	private $_runningOutItems;
	private $_emptyItems;
	private $_reorderItems;

	public function __construct () { return true; }

	public function validate () { return true; }

	public function getNewItems ($limit = 10, $person = null) {
		global $logger;
		if (!is_array($this->_newItems)) {
			global $db;
			if (!is_object($person) && (int) $person) {
				$person = new Person ((int) $person);
			}
			if (!is_object($person) || (is_object($person) && (get_class($person) != 'Person' || !$person->personID))) {
				$person = new Person(1);
			}
			if (!(int) $limit) $limit = 10;
			if (!$db->query('SELECT a.*, price.personID AS pricePersonID, price.price AS pricePrice, price.tax AS priceTax, price.multiple AS priceMultiple FROM price, item a LEFT JOIN item b '
				.'ON b.nodePath LIKE CONCAT(a.nodePath, "/%") '
				.'WHERE (a.active = 1 OR a.active IS NULL) '
				.'AND price.personID IN ('.implode(',', $person->getNodePath()).') '
				.'AND price.itemID = a.itemID '
				.'GROUP BY a.itemID '
				.'HAVING (COUNT(b.itemID) < 1) '
				.'ORDER BY dateCreated DESC' . ((int) $limit ? ' LIMIT 0,' . (int) $limit : null), true)) {
				$this->setError(E_DATABASE, 'Couldnt get items', 'ItemMapper::getNewItems()');
				return false;
			}
			$this->_newItems = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$prices = array (
					$r->v('pricePersonID') => array (
						'itemID' => $r->v('itemID'),
						'personID' => $r->v('pricePersonID'),
						'price' => $r->v('pricePrice'),
						'tax' => $r->v('priceTax'),
						'multiple' => $r->v('priceMultiple')
					)
				);
				$r->s('prices', $prices);
				$this->_newItems[$r->v('itemID')] = new Item($r);
			}
			foreach ($this->_newItems as $k => $v) {
				if (!$v->isActive()) unset($this->_newItems[$k]);
				$qty = $v->getQuantityAvailable();
				if (!$qty && !is_null($qty)) unset($this->_newItems[$k]);
			}
		}
		return $this->_newItems;
	}

	public function getTree () {
		if (!is_array($this->_tree)) {
			$root = new Item (1);
			$this->_tree = call_user_func_array(array($root, 'getTree'), func_get_args());
		}
		return $this->_tree;
	}

	public function getRunningOutItems () {
		if (!is_array($this->runningOutItems)) {
			global $db, $user;
			if (!$db->query('SELECT item.*, SUM(orderItem.quantityOrdered) AS qtyOrdered FROM item LEFT JOIN orderItem ON item.itemID = orderItem.itemID GROUP BY item.itemID HAVING item.runningOutQuantity AND item.trackInventory AND (item.quantity - qtyOrdered <= item.runningOutQuantity)')) {
				$this->setError(E_DATABASE, 'Couldnt get items', 'ItemMapper::getRunningOutItems()');
				return false;
			}
			$this->_runningOutItems = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$this->runningOutItems[$r->v('itemID')] = new Item($r);
			}
		}
		return $this->_runningOutItems;
	}

	public function getEmptyItems () {
		if (!is_array($this->runningOutItems)) {
			global $db, $user;
			if (!$db->query('SELECT item.*, SUM(orderItem.qtyOrdered) AS qtyOrdered FROM item LEFT JOIN orderItem ON item.itemID = orderItem.itemID GROUP BY item.itemID HAVING item.quantity - qtyOrdered <= 0')) {
				$this->setError(E_DATABASE, 'Couldnt get items', 'ItemMapper::getRunningOutItems()');
				return false;
			}
			$this->_emptyItems = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$this->_emptyItems[$r->v('itemID')] = new Item($r);
			}
			foreach ($this->_emptyItems as $k => $v) {
				if (!$v->getTrackInventory()) unset($this->_emptyItems[$k]);
			}
		}
		return $this->_emptyItems;
	}

	public function getReorderItems () {
		if (!is_array($this->runningOutItems)) {
			global $db, $user;
			if (!$db->query('SELECT item.*, SUM(orderItem.qtyOrdered) AS qtyOrdered FROM item LEFT JOIN orderItem ON item.itemID = orderItem.itemID GROUP BY item.itemID HAVING item.reorderQuantity AND (item.quantity - qtyOrdered <= item.reorderQuantity)')) {
				$this->setError(E_DATABASE, 'Couldnt get items', 'ItemMapper::getRunningOutItems()');
				return false;
			}
			$this->_reorderItems = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$this->_reorderItems[$r->v('itemID')] = new Item($r);
			}
		}
		return $this->_reorderItems;
	}

	/* inserts ordered items into a tree; takes the following arguments:
	 *
	 * 		$items: list of items to insert; prunes all nodes that don't
	 * 		contain at least one of these items
	 *
	 * 		$sortOrder: the order of the items in the tree
	 *
	 * 		$format: always overridden to 'tree'; cannot retrieve 'list'
	 * 		(there's a spot for it only to maintain consistency with
	 * 		MarketTree::getTree(), which takes this argument)
	 *
	 * 		$criteria: prunes off nodes that don't meet this criteria
	 */
	public function insertOrderItems () {
		global $logger;
		$args = func_get_args();
		$items = array_shift($args);
		if (!is_array($items)) {
			$this->setError(E_INVALID_DATA, '$items must be an array of items', 'ItemMapper::insertOrderItems()');
			return false;
		}
		foreach ($items as $k => $v) {
			if (!is_object($v)) {
				$this->setError(E_INVALID_DATA, 'element ' . $k . ' in items is not an object', 'ItemMapper::insertOrderItems()');
				return false;
			}
			if (get_class($v) != 'Item' && get_class($v) != 'OrderItem') {
				$this->setError(E_INVALID_DATA, 'element ' . $k . ' in items is not an Item', 'ItemMapper::insertOrderItems()');
				return false;
			}
		}
		if (!count($args)) {
			$args = array (null, null);
		}
		$args[1] = 'tree';
		$tree = call_user_func_array(array($this, 'getTree'), $args);
		$tree = $this->recurseInsertOrderItems($items, $tree);
		return $tree;
	}

	public function recurseInsertOrderItems (&$items, $tree) {
		global $logger;
		foreach ($tree as $k => &$v) {
			$inserted = 0;
			$node = &$v['node'];
			if ($node->itemType == I_ITEM) {
				if (isset($items[$node->itemID])) {
					$inserted ++;
					$node = $items[$node->itemID];
					unset($items[$node->itemID]);
				}
			} else {
				if (count($v['children'])) {
					$v['children'] = $this->recurseInsertOrderItems($items, $v['children']);
					$inserted += count($v['children']);
				}
			}
			if (!$inserted) {
				unset($tree[$k]);
			}
		}
		return $tree;
	}

	public function getItems ($items) {
		if (!is_array($items)) {
			$this->setError(E_INVALID_DATA, '$items must be an array of itemIDs', 'ItemMapper::getItems()');
			return false;
		}
		foreach ($items as $k => $v) {
			if (!(int) $v) unset($items[$k]);
			else $items[$k] = (int) $v;
		}
		if (!$db->query('SELECT * FROM item WHERE itemID IN (' . implode(',', $items) . ')')) {
			$this->setError(E_DATABASE, 'couldnt get items', 'ItemMapper::getItems()');
			return false;
		}
		$items = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$items[$r->v('itemID')] = new Item ($r);
		}
		return $items;
	}

}

$ItemMapper = new ItemMapper ();

class Item extends MarketTree {
	public $itemID;
	public $dateCreated;
	public $itemType;
	public $active = true;
	public $isKit = false;
	public $sku;
	public $label;
	public $location;
	public $distance;
	public $description;
	public $image = false;
	public $quantity;
	public $reorderQuantity;
	public $runningOutQuantity;
	public $canOrderPastZero;
	public $cutoffDay;
	public $trackInventory;
	public $supplierID;
	public $specialPacking;
	public $availableToRecurring;
	public $csaRequired;
	public $organic;
	public $canBePermanent;
	private $prices = array ();
	public $depth;
	protected $sortFields = array ('sortOrder', 'label', 'quantity', 'location', 'distance', 'sku');
	private $_orderableC = array (
		'orders' => array (),
		'people' => array (),
		'none' => false
	);

	public function __construct ($itemInfo = null) {
		switch (gettype($itemInfo)) {
			case 'integer':
				if (!$this->constructFromItemID($itemInfo)) return false;
			case 'array':
				$itemInfo = new Record ($itemInfo);
			case 'object':
				if (get_class($itemInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$itemInfo is a ' . get_class($itemInfo) . ' rather than the expected Record', 'Item::__construct()');
					return false;
				}
				if (!$this->constructFromRecord($itemInfo)) return false;
				break;
			case 'null':
			default:
				$this->itemID = null;
				$this->dateCreated = null;
				$this->itemType = null;
				$this->active = true;
				$this->isKit = false;
				//$this->lft = null;
				//$this->rgt = null;
				$this->nodePath = null;
				$this->sortOrder = null;
				$this->sku = null;
				$this->label = null;
				$this->location = null;
				$this->distance = null;
				$this->description = null;
				$this->image = false;
				$this->quantity = null;
				$this->reorderQuantity = null;
				$this->runningOutQuantity = null;
				$this->canOrderPastZero = null;
				$this->trackInventory = null;
				$this->supplierID = null;
				$this->specialPacking = null;
				$this->availableToRecurring = null;
				$this->csaRequired = null;
				$this->organic = null;
				$this->canBePermanent = null;
				$this->prices = array ();
				$this->depth = null;
				$this->_orderableC = array (
					'orders' => array (),
					'people' => array (),
					'none' => false
				);
		}
		$this->clearError();
	}

	public function constructFromItemID ($itemID) {
		global $logger;
		$itemID = (int) $itemID;
		if (!$itemID) {
			$this->__construct(null);
			return false;
		}
		global $db;
		if (!$db->query('SELECT * FROM item WHERE itemID = ' . $itemID)) {
			$this->setError(E_DATABASE, 'on getting item ' . $itemID . ' from database', 'Item::constructFromItemID()');
			return false;
		}
		if (!$itemData = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'no item ' . $itemID, 'Item::constructFromItemID()');
			return false;
		}
		if (!$db->query('SELECT * FROM price WHERE itemID = ' . $itemID)) {
			$this->setError(E_DATABASE, 'on getting prices', 'Item::constructFromItemID()');
			return false;
		}
		$prices = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$prices[$r->v('personID')] = new Price ($r);
		}
		$itemData->r['prices'] = $prices;
		return $this->constructFromRecord($itemData);
	}

	public function constructFromArray ($itemInfo) {
		if (is_array($itemInfo)) $itemInfo = new Record ($itemInfo);
		else {
			$this->setError(E_INVALID_DATA, '$itemInfo is ' . (gettype($itemInfo) == 'object' ? get_class($itemInfo) : gettype($itemInfo)) . ' rather than the expected Array or Record', 'Item::constructFromArray()');
			return false;
		}
		return $this->constructFromRecord($itemInfo);
	}

	public function constructFromRecord ($itemInfo) {
		if (!is_object($itemInfo)) {
			$this->setError(E_INVALID_DATA, '$itemInfo is ' . gettype($itemInfo) . ' rather than the expected Array or Record', 'Item::constructFromRecord()');
			return false;
		}
		if (get_class($itemInfo) != 'Record') {
			$this->setError(E_INVALID_DATA, '$itemInfo is a ' . get_class($deliveryDayInfo) . ' rather than the expected Record', 'Item::constructFromRecord()');
			return false;
		}
		foreach ($this as $k => $v) {
			$v = $itemInfo->v($k);
			if (!is_null($v)) {
				switch ($k) {
					case 'dateCreated':
						$this->$k = (is_null($v) ? null : strtotime($v));
						break;
					case 'active':
					case 'isKit':
					case 'image':
					case 'canOrderPastZero':
					case 'trackInventory':
					case 'specialPacking':
					case 'availableToRecurring':
					case 'organic':
					case 'csaRequired':
					case 'canBePermanent':
					case 'itemType':
						$this->$k = (is_null($v) ? null : $itemInfo->b($k));
						break;
					case 'prices':
						if (is_array($v)) {
							foreach ($v as $i => $thisPrice) {
								if (is_object($thisPrice)) {
									if (get_class($thisPrice) != 'Price') unset($v[$i]);
								} else unset($v[$i]);
							}
							$this->prices = $v;
						} else $this->prices = array ();
						break;
					case 'nodePath':
						$this->nodePath = $this->toNodePath($v);
						break;
					default:
						$this->$k = $v;
				}
			}
		}
		if (!$this->validate()) return false;
		return true;
	}

	public function newObject ($objectData = null) {
		return new Item ($objectData);
	}

	public function validate () {
		global $db;
		$errorFields = array ();
		$this->itemID = $this->itemID ? (int) $this->itemID : null;
		$this->dateCreated = $this->checkDate($this->dateCreated);
		if (!$this->dateCreated && !is_null($this->dateCreated)) $errorFields[] = 'dateCreated';
		if (!is_null($this->itemType)) {
			$this->itemType &= I_ALL;
		}
		$this->active = $this->active ? true : false;
		$this->isKit = $this->isKit ? true : false;
		$this->sku = trim($this->sku);
		$this->label = trim($this->label);
		$this->location = trim($this->location);
		$this->distance = (is_null($this->distance) ? null : (int) $this->distance);
		if (!$this->label) $errorFields[] = 'label';
		$this->description = trim($this->description);
		$this->image = (bool) $this->image;
		$this->quantity = (is_null($this->quantity) ? null : (int) $this->quantity);
		$this->reorderQuantity = (is_null($this->reorderQuantity) ? null : (int) $this->reorderQuantity);
		$this->runningOutQuantity = (is_null($this->runningOutQuantity) ? null : (int) $this->runningOutQuantity);
		$this->canOrderPastZero = (is_null($this->canOrderPastZero) ? null : (bool) $this->canOrderPastZero);
		$this->trackInventory = (is_null($this->trackInventory) ? null : (bool) $this->trackInventory);
		$this->cutoffDay = (is_null($this->cutoffDay) ? null : (int) $this->cutoffDay);
		$this->supplierID = $this->supplierID ? (int) $this->supplierID : null;
		$this->specialPacking = (is_null($this->specialPacking) ? null : (bool) $this->specialPacking);
		$this->availableToRecurring = (is_null($this->availableToRecurring) ? null : (bool) $this->availableToRecurring);
		$this->csaRequired = (is_null($this->csaRequired) ? null : (bool) $this->csaRequired);
		$this->organic = (is_null($this->organic) ? null : (bool) $this->organic);
		$this->canBePermanent = (is_null($this->canBePermanent) ? null : (bool) $this->canBePermanent);
		// maybe I won't bother testing for valid personIDs, because it's a database performance hit, owing to the fact that every time an item is created by array, it validates, thereby nullifying the performance gain of creating a whole bunch of items from a query
		/* if (!is_null($this->supplierID)) {
			$testSupplierID = mysql_query('select personID from person where personID = ' . $this->supplierID . ' && supplier = true', $db);
			if (!mysql_fetch_assoc($testSupplierID)) $errorFields[] = 'supplierID';
		} */
		if (!is_array($this->prices)) $this->prices = array ();
		foreach ($this->prices as $thisKey => $thisPrice) {
			if (get_class($thisPrice) != 'Price') {
				$errorFields[] = 'price' . $thisKey;
				unset($this->prices[$thisKey]);
			}
		}
		if (count($errorFields)) {
			$errorFields[] = 'Item validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'Item::validate()');
			return false;
		}
		return true;
		// no clearError here, to ensure integrity of cascading error thingies
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		if (!$this->itemID) {
			if (!$db->query('INSERT INTO item (dateCreated) VALUES (NOW())')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Item::save()');
				return false;
			}
			$this->itemID = $db->getLastID();
		}
		$q = 'UPDATE item SET ';
		$q .= 'active = ' . ($this->active ? 'true' : 'false');
		if (!$this->itemID) $q .= ', dateCreated = NOW()';
		$q .= ', isKit = ' . ($this->isKit ? 'true' : 'false');
		$q .= ', nodePath = ' . (is_null($this->nodePath) ? 'NULL' : '"'.$this->getPathString().'"');
		$q .= ', sortOrder = '. (is_null($this->sortOrder) ? 'NULL' : (int) $this->sortOrder);
		$q .= ', itemType = '. (is_null($this->itemType) ? 'NULL' : (int) $this->itemType);
		$q .= ', sku = ' . ($this->sku ? '"' . $db->cleanString($this->sku) . '"' : 'null');
		$q .= ', label = \'' . $db->cleanString($this->label) . '\'';
		$q .= ', location = \'' . $db->cleanString($this->location) . '\'';
		$q .= ', distance = ' . (is_null($this->distance) ? 'null' : (int) $this->distance);
		$q .= ', description = \'' . $db->cleanString($this->description) . '\'';
		$q .= ', image = ' . ($this->image ? 'true' : 'false');
		$q .= ', quantity = ' . (int) $this->quantity;
		$q .= ', reorderQuantity = ' . (is_null($this->reorderQuantity) ? 'null' : (int) $this->reorderQuantity);
		$q .= ', runningOutQuantity = ' . (is_null($this->runningOutQuantity) ? 'null' : (int) $this->runningOutQuantity);
		$q .= ', canOrderPastZero = ' . (is_null($this->canOrderPastZero) ? 'null' : ($this->canOrderPastZero ? 'true' : 'false'));
		$q .= ', trackInventory = ' . (is_null($this->trackInventory) ? 'null' : ($this->trackInventory ? 'true' : 'false'));
		$q .= ', cutoffDay = ' . (is_null($this->cutoffDay) ? 'null' : (int) $this->cutoffDay);
		$q .= ', supplierID = ' . ($this->supplierID ? $this->supplierID : 'null');
		$q .= ', specialPacking = ' . (is_null($this->specialPacking) ? 'null' : ($this->specialPacking ? 'true' : 'false'));
		$q .= ', availableToRecurring = ' . (is_null($this->availableToRecurring) ? 'null' : ($this->availableToRecurring ? 'true' : 'false'));
		$q .= ', csaRequired = ' . (is_null($this->csaRequired) ? 'null' : ($this->csaRequired ? 'true' : 'false'));
		$q .= ', organic = ' . (is_null($this->organic) ? 'null' : ($this->organic ? 'true' : 'false'));
		$q .= ', canBePermanent = ' . (is_null($this->canBePermanent) ? 'null' : ($this->canBePermanent ? 'true' : 'false'));
		$q .= ' WHERE itemID = ' . $this->itemID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on save', 'Item::save()');
			return false;
		}
		$error = 0;
		$errorDetail = array ();
		foreach ($this->prices as $thisIndex => $thisPrice) {
			if (!$thisPrice->save()) {
				$error |= $thisPrice->getError();
				$errorDetail[$thisIndex] = array ('error' => $thisPrice->getError(), 'errorDetail' => $thisPrice->getErrorDetail());
			}
		}
		if (count($errorDetail)) {
			$this->setError($error, $errorDetail, 'Item::save()');
			return false;
		}
		global $logger;
		$logger->addEntry('Saved item ' . $this->itemID, null, 'Item::save()');
		$this->clearError();
		return true;
	}

	public function delete ($deleteChildren = false) {
		// TODO: might not need 'has associated data' check now that I've put
		// constraints on. Just check to see if query failed on account
		// of constraints, I guess.
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'Can\'t delete an empty item!', 'Item::delete()');
			return false;
		}
		global $db;
		$t = 'deleteItem' . $this->itemID;
		$db->start($t);
		// don't want to orphan children for now; maybe I can figure out something better. For now, though, it never deletes children.
		if (!$this->deleteFromTree(false)) return false;
		$hasAssociatedData = $this->hasAssociatedData();
		if (!$this->hasAssociatedData()) {
			if (!$db->query('DELETE FROM item WHERE itemID = ' . $this->itemID)) {
				$this->setError(E_DATABASE, 'on deletion of item ' . $this->itemID, 'Item::delete()');
				$db->rollback($t);
				return false;
			}
			if (!$db->query('DELETE FROM price WHERE itemID = ' . $this->itemID)) {
				$this->setError(E_DATABASE, 'on deletion of associated prices from item ' . $this->itemID, 'Item::delete()');
				$db->rollback($t);
				return false;
			}
		} else {
			if (!$db->query('UPDATE item SET active = false WHERE itemID = ' . $this->itemID)) {
				$this->setError(E_DATABASE, 'on setting of \'active\' flag for item ' . $this->itemID, 'Item::delete()');
				$db->rollback($t);
				return false;
			}
		}
		$db->commit($t);
		global $logger;
		$logger->addEntry(($hasAssociatedData ? 'Archived' : 'Deleted') . ' item ' . $this->itemID, null, 'Item::delete()');
		$this->removeImage();
		if (!$hasAssociatedData) $this->__construct(null);
		else {
			$this->active = false;
		}
		return true;
	}

	public function hasAssociatedData () {
		if (!$this->itemID) return false;
		global $db;
		if (!$db->query('SELECT orderID FROM orderItem WHERE itemID = ' . $this->itemID)) {
			$this->setError(E_DATABASE, 'on check for associated data for item ' . $this->itemID, 'Item::hasAssociatedData()');
			return false;
		}
		if ($db->getRow()) return true;
	}

	public function deletePrice ($personID) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no item ID', 'Item::deletePrice()');
			return false;
		}
		if (!is_int($personID) || !array_key_exists($personID, $this->prices)) {
			$this->setError(E_NO_OBJECT, 'no personID, or there isn\'t even a price for that person', 'Item::deletePrice()');
			return false;
		}
		// should we check if delete() works?
		$this->prices[$personID]->delete();
		unset($this->prices[$personID]);
		$this->clearError();
	}

	public function hasPrice ($personID) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::hasPrice()');
			return false;
		}
		if (!(int) $personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Item::hasPrice()');
			return false;
		}
		return array_key_exists($personID, $this->prices);
	}

	public function getPrice ($person, $recursive = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getPrice()');
			return false;
		}
		if ($this->itemType != I_ITEM) {
			$this->setError(E_INVALID_DATA, 'this node '.$this->itemID.' is not a sellable item', 'Item::getPrice()');
			return false;
		}
		if (!isPerson($person)) {
			if ((int) $person) {
				$person = new Person ((int) $person);
			} else {
				$this->setError(E_INVALID_DATA, 'person is not a person', 'item::getPrice()');
				return false;
			}
		}
		if (!$person->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Item::getPrice()');
			return false;
		}
		if (!array_key_exists($person->personID, $this->prices)) {
			if ($recursive) {
				$nodePath = $person->getNodePath(false);
				global $logger;
				while (count($nodePath)) {
					$thisParentID = array_pop($nodePath);
					if (isset($this->prices[$thisParentID])) {
						return $this->prices[$thisParentID];
					}
				}
			}
			$this->setError(E_NO_OBJECT, 'No price on item ' . $this->itemID . ' for person ' . $person->personID, 'Item::getPrice()');
			return false;
		}
		$this->clearError();
		// $logger->addEntry($this->prices[$personID], null, 'Item::getPrice()');
		return $this->prices[$person->personID];
	}

	public function getPrices () {
		return $this->prices;
	}

	public function setPrice ($personID, $price, $tax, $multiple) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::setPrice()');
			return false;
		}
		if (!(int) $personID) {
			$this->setError(E_INVALID_DATA, 'personID ' . $personID . ' is empty or not an integer', 'Item::setPrice()');
			return false;
		}
		$multiple = ((int) $multiple ? abs((int) $multiple) : 1);
		if (!isset($this->prices[$personID])) {
			$priceData = array (
				'personID' => (int) $personID,
				'itemID' => $this->itemID,
				'price' => (float) $price,
				'tax' => (int) $tax,
				'multiple' => (int) $multiple
			);
			$this->prices[$personID] = new Price ($priceData);
		} else {
			$this->prices[$personID]->price = (float) $price;
			$this->prices[$personID]->tax = (int) $tax;
			$this->prices[$personID]->multiple = (int) $multiple;
		}
		if (!$this->prices[$personID]->save()) {
			$this->setError($this->prices[$personID]->getError(), 'Price object returned ' . $GLOBALS['errorCodes'][$this->prices[$personID]->getError()] . ' (' . $this->prices[$personID]->getErrorDetail() . ')', 'Item::setPrice()');
			unset($this->prices[$personID]);
			return false;
		}
		$this->clearError();
	}

	// kitItems aren't cached; is this a big performance hit?

	public function getKitItems () {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getKitItems()');
			return false;
		}
		if (!$this->isKit) {
			$this->setError(E_INVALID_DATA, 'item ' . $this->itemID . ' isn\'t a kit', 'Item::getKitItems()');
			return false;
		}
		global $db;
		$kitItems = array ();
		if (!$db->query('SELECT item.* FROM item, kitItem WHERE kitItem.kitID = ' . $this->itemID . ' AND item.itemID = kitItem.itemID', $db)) {
			$this->setError(E_DATABASE, 'on query', 'Item::getKitItems()');
			return false;
		}
		while ($r = $db->getRow(F_RECORD)) {
			$thisItem = new Item ($r);
			$kitItems[] = $thisItem;
		}
		$this->clearError();
		return $kitItems;
	}

	public function addKitItems ($kitItems) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::addKitItems()');
			return false;
		}
		if (!$this->isKit) {
			$this->setError(E_INVALID_DATA, 'item ' . $this->itemID . ' isn\'t a kit', 'Item::addKitItems()');
			return false;
		}
		global $db;
		if (!is_array($kitItems)) $kitItems = array ($kitItems);
		$q = 'INSERT INTO kitItem (kitID, itemID) VALUES ';
		foreach ($kitItems as $thisKitItem) {
			// should probably make up some clever statement that checks if the itemID is valid, without having to do another select
			if (is_object($thisKitItem)) {
				if (get_class($thisKitItem) == 'Item') $thisItemID = $thisKitItem->itemID;
				else $thisItemID = null;
			} else if ((int) $thisKitItem) $thisItemID = (int) $thisKitItem;
			else $thisItemID = null;
			if ($thisItemID) $q .= '(' . $this->id . ', ' . $thisItemID . '), ';
		}
		$q = substr($q, 0, -2);
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on addition of kit items (' . serialize($kitItems) . ') to ' . $this->itemID, 'Item::addKitItems()');
			return false;
		}
		global $logger;
		$logger->addEntry('Added kit items to item ' . $this->itemID, null, 'Item::addKitItems()');
		$this->clearError();
		return true;
	}

	public function removeKitItems ($kitItems) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::removeKitItems()');
			return false;
		}
		if (!$this->isKit) {
			$this->setError(E_INVALID_DATA, 'item ' . $this->itemID . ' isn\'t a kit', 'Item::removeKitItems()');
			return false;
		}
		global $db;
		if (!is_array($kitItems)) $kitItems = array ($kitItems);
		foreach ($kitItems as $i => $thisKitItem) {
			// should probably make up some clever statement that checks if the itemID is valid, without having to do another select
			if (is_object($thisKitItem)) {
				if (get_class($thisKitItem) == 'Item') $kitItems[$i] = $thisKitItem->itemID;
				else unset($kitItems[$i]);
			} else if ((int) $thisKitItem) $kitItems[$i] = (int) $thisKitItem;
			else unset($kitItems[$i]);
		}
		$q = 'DELETE FROM kitItem WHERE kitID = ' . $this->itemID . ' AND itemID in (' . implode(', ', $kitItems) . ')';
		if (!$result = $db->query($q)) {
			$this->setError(E_DATABASE, 'on remove', 'Item::removeKitItems()');
			return false;
		}
		global $logger;
		$logger->addEntry('Removed kit items from item ' . $this->itemID, null, 'Item::removeKitItems()');
		$this->clearError();
		return true;
	}

	public function getLabel () {
		return $this->label;
	}

	public function getCutoffDay ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getCutoffDay()');
			return false;
		}
		return $this->getProperty('cutoffDay', (bool) $includeThis);
	}

	public function getSpecialPacking ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getSpecialPacking()');
			return false;
		}
		return $this->getProperty('specialPacking', (bool) $includeThis);
	}

	public function getAvailableToRecurring ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getAvailableToRecurring()');
			return false;
		}
		return $this->getProperty('availableToRecurring', (bool) $includeThis);
	}

	public function getCsaRequired ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getCsaRequired()');
			return false;
		}
		return $this->getProperty('csaRequired', (bool) $includeThis);
	}

	public function getCanOrderPastZero ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getCanOrderPastZero()');
			return false;
		}
		return $this->getProperty('canOrderPastZero', (bool) $includeThis);
	}

	public function getTrackInventory ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getTrackInventory()');
			return false;
		}
		return $this->getProperty('trackInventory', (bool) $includeThis);
	}

	public function getOrganic ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getOrganic()');
			return false;
		}
		return $this->getProperty('organic', (bool) $includeThis);
	}

	public function getCanBePermanent ($includeThis = true) {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::getCanBePermanent()');
			return false;
		}
		return $this->getProperty('canBePermanent', (bool) $includeThis);
	}

	public function getQuantityAvailable () {
		// I hope this gives the proper return value and returns false!
		if (!$this->getTrackInventory() || $this->getCanOrderPastZero()) {
			return null;
		}
		global $db;
		// first, check all the already-ordered items on orders not delivered (not including this order).
		// I think 'not delivered' is right -- that means people who have ordered or are ordering in this period, but haven't had their bins packed yet.
		if (!$db->query('SELECT SUM(orderItem.quantityOrdered) AS quantityOrderedTotal FROM orderItem, orders WHERE orderItem.itemID = ' . $this->itemID . ' AND orderItem.orderID = orders.orderID AND orders.dateDelivered IS NULL AND orders.dateToDeliver IS NOT NULL AND (!orders.dateCanceled OR orders.dateCanceled IS NULL) AND orders.orderType & ' . O_BASE . ' = ' . O_SALE)) {
			$this->setError(E_DATABASE, 'on checking of quantity purchased but not delivered for item ' . $this->itemID, 'Item::getQuantityAvailable()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) $quantityOrderedTotal = $r->v('quantityOrderedTotal');
		// then, check all the available but not-shipped items from suppliers
		if (!$db->query('SELECT SUM(orderItem.quantityOrdered) AS quantityAvailableTotal FROM orderItem, orders WHERE orderItem.itemID = ' . $this->itemID . ' AND orderItem.orderID = orders.orderID AND orders.dateCompleted IS NULL AND orders.dateCompleted > NOW() AND (!orders.dateCanceled OR orders.dateCanceled IS NULL) AND orders.orderType & ' . O_BASE . ' = ' . O_SUPPLIER)) {
			$this->setError(E_DATABASE, 'on checking of available but not shipped items from suppliers for item ' . $this->itemID, 'Item::getQuantityAvailable()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) $quantityAvailableTotal = $r->v('quantityAvailableTotal');
		// check the quantity in inventory against the quantity already ordered this period, plus the quantity available from supplier
		$quantityAvailableNet = $quantityAvailableTotal + $this->quantity - $quantityOrderedTotal;
		if ($quantityAvailableNet) return true;
		return false;
	}

	public function addImage ($tempFileLocation) {
		// TODO: flesh out error checking routines for each step
		global $config;
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::addImage()');
			return false;
		}
		if (!move_uploaded_file($tempFileLocation, 'productImages/' . $this->itemID . '_t.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'no such file in location ' . $tempFileLocation, 'Item::addImage()');
			return false;
		} else $tempFileLocation = 'productImages/' . $this->itemID . '_t.jpg';
		$src = @imagecreatefromjpeg($tempFileLocation);
		if (!$src) {
			$this->setError(E_INVALID_DATA, 'Invalid image!', 'Image::addImage()');
			return false;
		}
		$orig_w = imagesx($src);
		$orig_h = imagesy($src);
		$orig_asp = $orig_w / $orig_h;
		$t_max_w = 150;
		$t_max_h = 110;
		$t_asp = $t_max_w / $t_max_h;
		$m_max = 600;
		if ($orig_asp < $t_asp) {//narrower than thumb
			$t_h = $t_max_w / $orig_asp;
			$t_w = $t_max_w;
		} elseif ($orig_asp > $t_asp) {//wider than thumb
			$t_w = $t_max_h * $orig_asp;
			$t_h = $t_max_h;
		} else {//same ratio
			$t_w = $t_max_w;
			$t_h = $t_max_h;
		}
		$t_mid_x = $t_w / 2;
		$t_mid_y = $t_h / 2;
		if (!$proc_t = imagecreatetruecolor(round($t_w), round($t_h))) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail proc canvas', 'Image::addImage()');
			return false;
		}
		if (!imagecopyresampled($proc_t, $src, 0, 0, 0, 0, $t_w, $t_h, $orig_w, $orig_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt shrink thumb', 'Image::addImage()');
			return false;
		}
		if (!$thumb = imagecreatetruecolor($t_max_w, $t_max_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail canvas', 'Image::addImage()');
			return false;
		}
		if (!imagecopyresampled($thumb, $proc_t, 0, 0, ($t_mid_x - ($t_max_w / 2)), ($t_mid_y - ($t_max_h / 2)), $t_max_w, $t_max_h, $t_max_w, $t_max_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt crop thumb', 'Image::addImage()');
			return false;
		}
		if (!imagejpeg($thumb, 'productImages/' . (int) $this->itemID . '_s.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail canvas', 'Image::addImage()');
			return false;
		}
		if ($orig_asp > 1) {//landscape
			$new_w = $m_max;
			$new_h = ceil($m_max / $orig_asp);
		} elseif ($orig_asp < 1) {//portrait
			$new_h = $m_max;
			$new_w = ceil($m_max * $orig_asp);
		} else {//square
			$new_w = $new_h = $m_max;
		}
		if (!$dest_m = imagecreatetruecolor($new_w, $new_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create med canvas', 'Image::addImage()');
			return false;
		}
		if (!imagecopyresampled($dest_m, $src, 0 , 0 , 0, 0, $new_w, $new_h, $orig_w, $orig_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt resample med img', 'Image::addImage()');
			return false;
		}
		if (!imagejpeg($dest_m, 'productImages/' . (int) $this->itemID . '_m.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail canvas', 'Image::addImage()');
			return false;
		}
		unlink($tempFileLocation);
		$this->image = true;
		global $db;
		if (!$db->query('UPDATE item SET image = TRUE WHERE itemID = ' . (int) $this->itemID)) {
			$this->setError(E_DATABASE, 'on update of item ' . $this->itemID, 'Item::addImage()');
			return false;
		}
		global $logger;
		$logger->addEntry('Added image and thumbnail for item ' . $this->itemID, null, 'Item::addImage()');
		$this->clearError();
		return true;
	}

	public function removeImage () {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::removeImage()');
			return false;
		}
		if (file_exists('productImages/' . $this->itemID . '_s.jpg')) unlink ('productImages/' . $this->itemID . '_s.jpg');
		if (file_exists('productImages/' . $this->itemID . '_m.jpg')) unlink ('productImages/' . $this->itemID . '_m.jpg');
		$this->image = false;
		global $db;
		if (!$db->query('UPDATE item SET image = FALSE WHERE itemID = ' . (int) $this->itemID)) {
			$this->setError(E_DATABASE, 'on update of item ' . $this->itemID, 'Item::removeImage()');
			return false;
		}
		global $logger;
		$logger->addEntry('Removed image from item ' . $this->itemID, null, 'Item::addImage()');
		$this->clearError();
		return true;
	}

	public function isRunningOut () {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::isRunningOut()');
			return false;
		}
		$qty = $this->getQuantityAvailable();
		if (is_null($qty)) return false;
		if ($this->runningOutQuantity >= $qty) return true;
		return false;
	}

	public function isOrderable ($orderer = null) {
		global $logger;
		if ($this->itemType & I_CATEGORY) {
			return false;
		}
		if (!$orderer) {
			return (bool) count($this->getPrices());
		} else {
			$isOrderable = true;
			if (isOrder($orderer)) {
				// look for memoized values
				if (isset($this->_orderableC['orders'][$orderer->orderID])) {
					return $this->_orderableC['orders'][$orderer->orderID];
				}
				$person = $orderer->getPerson();
				$order = $orderer;
				if ($order->orderType & O_RECURRING && !$this->getAvailableToRecurring()) {
					$isOrderable = false;
				}
			} else if (isPerson($orderer)) {
				// look for memoized values
				if (isset($this->_orderableC['people'][$orderer->personID])) {
					return $this->_orderableC['people'][$orderer->personID];
				}
				$person = $orderer;
				$order = null;
			} else {
				$this->setError(E_INVALID_DATA, '$orderer must be an order or empty', 'Item::isOrderable()');
				return false;
			}
			// is there a price for this person?
			if (!($price = $this->getPrice($person))) {
				$isOrderable = false;
			}
			// how about a sufficient quantity for inventory-managed items?
			$qtyAvailable = $this->getQuantityAvailable();
			if (!is_null($qtyAvailable) && ($qtyAvailable / $price->multiple < $price->multiple)) {
				$isOrderable = false;
			}
			// save to cache
			if ($order) {
				$this->_orderableC['orders'][$order->orderID] = $isOrderable;
			} else {
				$this->_orderableC['people'][$person->personID] = $isOrderable;
			}
			return $isOrderable;
		}
	}

	public function hasOrderableChildren ($orderer = null) {
		if (!($this->itemType & I_CATEGORY)) {
			return false;
		}
		if (isOrder($orderer)) {
			// look for memoized values
			if (isset($this->_orderableC['orders'][$orderer->orderID])) {
				return $this->_orderableC['orders'][$orderer->orderID];
			}
			$person = $orderer->getPerson();
			$order = $orderer;
		} else if (isPerson($orderer)) {
			// look for memoized values
			if (isset($this->_orderableC['people'][$orderer->personID])) {
				return $this->_orderableC['people'][$orderer->personID];
			}
			$person = $orderer;
			$order = null;
		} else if (!$orderer) {
			// look for memoized values
			if (!is_null($this->_orderableC['none'])) {
				return $this->_orderableC['none'];
			}
			$person = null;
			$order = null;
		} else {
			$this->setError(E_INVALID_DATA, '$orderer must be an order or empty', 'Item::isOrderable()');
			return false;
		}
		$hasOrderableChildren = false;
		$children = $this->getChildren(null, false, array ('itemType' => I_ITEM));
		foreach ($children as $thisChild) {
			if ($thisChild->isOrderable($orderer)) {
				$hasOrderableChildren = true;
			}
		}
		if (!$orderer) {
			// memoize the result
			$this->_orderableC['none'] = $hasOrderableChildren;
		} else {
			// memoize the result
			if ($order) {
				$this->_orderableC['orders'][$order->orderID] = $hasOrderableChildren;
			} else {
				$this->_orderableC['people'][$person->personID] = $hasOrderableChildren;
			}
		}
		global $logger;
		return $hasOrderableChildren;
	}

	protected function matchCriteria ($object, $criteria) {
		if (!is_array($criteria) || !count($criteria)) return true;
		global $logger;
		$status = true;
		if (isRecord($object)) {
			$object = new Item($object);
		}
		if (!isItem($object)) {
			return false;
		}
		foreach ($criteria as $k => $v) {
			switch ($k) {
				case 'leafNode':
				case 'isLeafNode':
					if (!$object->isLeafNode()) $status = false;
					break;
				case 'price':
					if (isPerson($v)) {
						$personID = $v->personID;
					} else {
						$personID = (int) $v;
					}
					if ($personID) {
						if (!$object->getPrice($personID)) $status = false;
					} else {
						if (!$object->getPrices()) $status = false;
					}
					break;
				// uh-oh! Can't do it past this point without some serious craziness. Oh well.
				case 'canOrderPastZero':
					if (!$object->getCanOrderPastZero()) $status = false;
					break;
				case 'trackInventory':
					if (!$object->getTrackInventory()) $status = false;
					break;
				case 'supplierID':
					$v = (int) $v;
					if ($v) {
						if ($object->supplierID != $v) $status = false;
					}
					break;
				case 'specialPacking':
					if (!$object->getSpecialPacking()) $status = false;
					break;
				case 'availableToRecurring':
					if (!$object->getAvailableToRecurring()) $status = false;
					break;
				case 'csaRequired':
					if (!$object->getCsaRequired()) $status = false;
					break;
				case 'organic':
					if (!$object->getOrganic()) $status = false;
					break;
				case 'active':
					if (!$object->isActive()) $status = false;
					break;
				case 'itemType':
					switch ($v) {
						case I_CATEGORY:
							if (!($object->itemType & $v)) $status = false;
							break;
						case I_ITEM:
							if ($object->itemType) $status = false;
					}
			}
		}
		return $status;
	}

	public function sortBranch ($a, $b) {
		global $logger;
		$a = $this->getNodeForSort($a);
		$b = $this->getNodeForSort($b);
		$fields = $this->getSortFields();
		$sortOrder = 0;
		while (!$sortOrder && ($field = array_shift($fields))) {
			$fieldName = $field['field'];
			switch ($fieldName) {
				case 'itemType':
					$sortOrder = ($a->itemType & I_CATEGORY) - ($b->itemType & I_CATEGORY);
					break;
				case 'isLeafNode':
					$sortOrder = (int) $a->isLeafNode() - (int) $b->isLeafNode();
					break;
				case 'label':
				case 'sku':
				case 'location':
					$sortOrder = strnatcmp($a->$fieldName, $b->$fieldName);
					break;
				case 'quantity':
				case 'distance':
					$sortOrder = $a->$fieldName - $b->$fieldName;
					break;
				case 'sortOrder':
				default:
					$sortOrder = $a->getSortOrder() - $b->getSortOrder();
					//$logger->log('sort order', $a->getLabel(), ($sortOrder > 0 ? 'is after' : 'is before'), $b->getLabel(), '('.$sortOrder.')');
			}
			$sortOrder *= $field['dir'];
		}
		return $sortOrder;
	}
}
?>
