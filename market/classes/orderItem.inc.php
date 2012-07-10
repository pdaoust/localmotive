<?php

class OrderItem extends Item {
	public $orderID;
	public $quantityOrdered;
	public $quantityDelivered;
	public $permanent;
	public $unitPrice;
	private $discount;
	public $tax;
	public $orderType;
	private $personID;
	private $person;

	public function __construct ($orderItemInfo = null, $orderItemInfo2 = null) {
		switch (gettype($orderItemInfo)) {
			case 'integer':
				if (is_int($orderItemInfo2) && $orderItemInfo2 && $orderItemInfo) {
					global $db;
					if (!$db->query('SELECT * FROM orderItem WHERE orderID = ' . $orderItemInfo . ' AND itemID = ' . $orderItemInfo2)) {
						$this->setError(E_DATABASE, 'on query', 'OrderItem::__construct()');
						return false;
					}
					if (!$r = $db->getRow(F_ASSOC)) {
						$this->setError(E_NO_OBJECT, 'either order ' . $orderItemInfo . ' or item ' . $orderItemInfo2 . ' do not exist', 'OrderItem::__construct()');
						return false;
					}
					if (!$this->constructFromItemID($orderItemInfo2)) return false;
				} else {
					$this->setError(E_INVALID_DATA, 'either orderID ' . $orderItemInfo . ' or itemID ' . $orderItemInfo2 . ' is not an integer', 'OrderItem::__construct()');
					return false;
				}
				$orderItemInfo = $r;
			case 'array':
				$orderItemInfo = new Record ($orderItemInfo);
			case 'object':
				if (get_class($orderItemInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$orderInfo is a ' . get_class($orderItemInfo) . ' rather than the expected Record', 'Order::__construct()');
					return false;
				}
				if ($orderItemInfo->v('label')) {
					$s = $this->constructFromRecord($orderItemInfo);
					if (!$s) return false;
				} else {
					foreach ($this as $k => $v) {
						$v = $orderItemInfo->v($k);
						if (!is_null($v)) $this->$k = $v;
					}
					if ($orderItemInfo->v('itemID')) $k = 'itemID';
					else {
						$this->setError(E_NO_OBJECT_ID, 'array does not have an itemID in it!', 'OrderItem::__construct()');
						return false;
					}
					if (!$this->constructFromItemID($orderItemInfo->v($k))) return false;
				}
				if (!$this->validate()) return false;
				break;
			default:
				$this->orderID = null;
				$this->itemID = null;
				$this->quantityOrdered = null;
				$this->quantityDelivered = null;
				$this->permanent = null;
				$this->unitPrice = null;
				$this->discount = null;
				$this->tax = 0;
				$this->orderType = null;
				$this->personID = null;
				$this->person = null;
		}
		$this->clearError();
	}

	public function validate () {
		$errorFields = array ();
		$this->orderID = (int) $this->orderID;
		if (!$this->orderID) $errorFields[] = 'orderID';
		$this->itemID = (int) $this->itemID;
		if (!$this->itemID) $errorFields[] = 'itemID';
		$this->quantityOrdered = (int) $this->quantityOrdered;
		if (!is_null($this->quantityDelivered)) $this->quantityDelivered = (int) $this->quantityDelivered;
		if (!$this->quantityOrdered) $errorFields[] = 'quantity';
		if (!is_null($this->unitPrice)) $this->unitPrice = round((float) $this->unitPrice, 2);
		if (!$this->getCanBePermanent()) $this->permanent = false;
		else if (!($this->orderType & O_TEMPLATE)) $this->permanent = false;
		else $this->permanent = (bool) $this->permanent;
		if ((float) $this->discount) $this->discount = round((float) $this->discount, 2);
		else $this->discount = null;
		$this->tax = (int) $this->tax & TAX_ALL;
		if (count($errorFields)) {
			// $errorFields = 'OrderItem validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'OrderItem::validate()');
			return false;
		} else return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db, $config;
		if (!$db->query('DELETE FROM orderItem WHERE orderID = ' . $this->orderID . ' AND itemID = ' . $this->itemID)) {
			$this->setError(E_DATABASE, 'on deletion of old OrderItem record', 'OrderItem::save()');
			return false;
		}
		$q = 'INSERT INTO orderItem (orderID, itemID, quantityOrdered, quantityDelivered, permanent, unitPrice, discount, tax) VALUES (';
		$q .= (int) $this->orderID;
		$q .= ', ' . (int) $this->itemID;
		$q .= ', ' . (int) $this->quantityOrdered;
		$q .= ', ' . (!is_null($this->quantityDelivered) ? (int) $this->quantityDelivered : 'null');
		$q .= ', ' . ($this->permanent ? 'true' : 'false');
		$q .= ', ' . (is_null($this->unitPrice) ? 'null' : ((float) $this->unitPrice ? (float) $this->unitPrice : 0));
		$q .= ', ' . ((float) $this->discount ? (float) $this->discount : 0);
		$q .= ', ' . (int) $this->tax . ')';
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on update of item ' . $this->itemID . ' for order ' . $this->orderID, 'OrderItem::save()');
			return false;
		}
		$this->clearError();
		global $logger;
		// $logger->addEntry('Saved item ' . $this->itemID . ' for order ' . $this->orderID . ' (qty ' . $this->quantityOrdered . ', unitPrice ' . (is_null($this->unitPrice) ? 'null' : money_format(NF_MONEY, $this->unitPrice)) . ', discount ' . (is_null($this->discount) ? 'null' : $this->discount) . ', tax ' . implode(', ', $this->tax) . ')', null, 'OrderItem::save()');
		return true;
	}

	public function delete () {
		// when I put in database integrity checking in verify (), this function should check to see if the order is completed yet, if it's a sale or purchase
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' missing', 'OrderItem::delete()');
			return false;
		}
		global $db;
		/* don't need this anymore; available quantity is auto-generated
		$result = mysql_query('BEGIN');
		$result = mysql_query('SELECT quantityOrdered FROM orderItem WHERE orderID = ' . $this->orderID . ' && itemID = ' . $this->itemID);
		if (!$oldQuantity = mysql_fetch_assoc($result)) return false;
		$this->quantityOrdered = $oldQuantity['quantityOrdered']; */
		if (!$db->query('DELETE FROM orderItem WHERE orderID = ' . $this->orderID . ' AND itemID = ' . $this->itemID)) {
			$this->setError(E_DATABASE, 'on deletion of item ' . $this->itemID . ' from order ' . $this->orderID, 'OrderItem::delete()');
			return false;
		}
		global $logger;
		$this->__construct(null);
		return true;
	}

	public function addQuantity ($quantity, $unitPrice = null, $tax = null, $discount = null, $testOnly = false) {
		global $logger, $db;
		// when I put in database integrity checking in verify (), this function should check to see if the order is completed yet, if it's a sale or purchase
		// doesn't check if item is active, or orderable
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' missing', 'OrderItem::addQuantity()');
			return false;
		}
		$quantity = (int) $quantity;
		$quantity += $this->quantityOrdered;
		if ($quantity < 1) {
			if (!$testOnly) $this->delete();
			return 0;
		}
		if (!$this->isActive() || !$this->isLeafNode()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'Item ' . $this->itemID . ' not active, or not a leaf node (order ' . $this->orderID . ')', 'OrderItem::addQuantity()');
			return false;
		}
		if (!(int) $quantity) {
			$this->setError(E_INVALID_DATA, $quantity . ' is not a quantity (item ' . $this->itemID . ', order ' . $this->orderID . ')', 'OrderItem::addQuantity()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE && !$this->getAvailableToRecurring()) {
			$this->setError(E_NOT_AVAILABLE_TO_CUSTOMER, 'tried to add item ' . $this->itemID . ' to recurring order ' . $this->orderID . ', but item is not available to recurring orders', 'OrderItem::addQuantity()');
			return false;
		}
		if (!$db->query('SELECT orderType, dateCompleted, personID FROM orders WHERE orderID = ' . $this->orderID)) {
			$this->setError(E_DATABASE, 'on checking of order details', 'OrderItem::addQuantity()');
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'order ' . $this->orderID . ' does not exist', 'OrderItem::addQuantity()');
			return false;
		}
		if (!$person = $this->getPerson()) {
			$this->setError(E_NO_OBJECT, 'order ' . $this->orderID . ' doesn\'t have a person attached to it', 'OrderItem::addQuantity()');
			return false;
		}
		$this->orderType = $r->v('orderType');
		// completed orders cannot be added to; use deliver() to make additions and substitutions
		if ($r->v('dateCompleted') && !($this->orderType & O_TEMPLATE)) {
			$this->setError(E_ORDER_COMPLETED, 'order ' . $this->orderID . ' already completed on ' . Date::human(strtotime($r->v('dateDelivered'))), 'OrderItem::addQuantity()');
			$db->rollback('orderItem' . $this->itemID);
			return false;
		}
		// TODO: we'll have to change this if we ever do returns
		// TODO: Does this work at all?! I think it would still retain the same quantity regardless of whether it bombs or not
		global $db;
		// this transaction seems hardly worth it, because if we rollback, no data was written anyway. I guess it's good, though, because it makes absolutely sure a person can order
		$db->start('orderItem' . $this->itemID);
		if (($this->orderType & O_DIR == O_IN) || $this->checkQuantity(false, $quantity)) {
			// checks to see if this item is available to this customer
			// TODO: CLEANUP: seems like a convoluted way of getting the customer, because $this->personID should always be added when an OrderItem is constructed.
			if ($price = $this->getPrice($this->personID)) {
				$logger->log('price', $price);
				$newUnitPrice = (is_null($unitPrice) ? null : round((float) $unitPrice, 2));
				$newTax = (is_null($tax) ? $price->tax : (int) $tax);
				// TODO: FUTURE: If we offer discounts (coupons, sales, etc) on certain items, we'll need to know whether a discount was auto-calculated or not. Perhaps an extra property/field?
				if (is_null($discount)) {
					$bulkDiscountQuantity = $person->getBulkDiscountQuantity();
					if ($quantity / $price->multiple >= $bulkDiscountQuantity) {
						$newDiscount = $person->getBulkDiscount();
					}
					else $newDiscount = 0;
				} else $newDiscount = round((float) $discount, 2);
			} else if ($this->orderType & O_DIR & O_OUT) {
				// if this order is for a customer, and the item isn't available, then say goodbye!
				$this->setError(E_NOT_AVAILABLE_TO_CUSTOMER, 'item ' . $this->itemID . ' not available to person ' . $this->personID, 'Order::addQuantity()');
				$db->rollback('orderItem' . $this->itemID);
				return false;
			}
			// multiples are only enforced on sales and recurring orders
			if (($this->orderType & O_OUT) && $price->multiple && $quantity % $price->multiple) {
				$this->setError(E_INCORRECT_MULTIPLE, $quantity . ' is not divisible by ' . $priceInfo['multiple'] . '(item ' . $this->itemID . ' on order ' . $this->orderID . ')', 'OrderItem::addQuantity()');
				$db->rollback('orderItem' . $this->itemID);
				return false;
			}
			if (!$testOnly) {
				$this->unitPrice = (is_null($newUnitPrice) ? $this->unitPrice : $newUnitPrice);
				$this->discount = (is_null($newDiscount) ? $this->discount : $newDiscount);
				$this->tax = (is_null($newTax) ? $this->tax : $newTax);
				$logger->log('tax:', $this->tax);
				$this->quantityOrdered = $quantity;
				if (!$this->save()) {
					$db->rollback('orderItem' . $this->itemID);
					return false;
				}
				$db->commit('orderItem' . $this->itemID);
			} else $db->rollback('orderItem' . $this->itemID);
			$this->clearError();
			return $quantity;
		} else {
			if (!$testOnly) $this->delete();
			if ($testOnly) $db->rollback('orderItem' . $this->itemID);
			else $db->commit('orderItem' . $this->itemID);
			if (!$testOnly) $logger->addEntry('deleted item ' . $this->itemID . ' from order ' . $this->orderID . ' because quantity was zero or not available', null, 'Order::addQuantity()');
			// $this->quantityOrdered -= $quantity;
			// should unavailable items throw an error? I don't think so
			$this->clearError();
			return 0;
		}
	}

	public function subtractQuantity ($quantity) {
		// I hope this check is sufficient
		if (!$this->quantityOrdered) {
			$this->clearError();
			return 0;
		}
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' is missing', 'OrderItem::subtractQuantity()');
			return false;
		}
		if (!$quantity) {
			$this->setError(E_INVALID_DATA, 'can\'t subtract 0!', 'OrderItem::subtractQuantity()');
			return false;
		}
		$this->clearError();
		return $this->addQuantity(0 - $quantity);
	}

	public function checkQuantity ($autoAdjust = false, $qty = null) {
		// I hope this gives the proper return value and returns false!
		if ((int) $qty) $qty = (int) $qty;
		else $qty = $this->quantityOrdered;
		if (!($price = $this->getPrice($this->personID))) {
			$this->setError(E_NO_OBJECT, 'There is no price for item '.$this->itemID);
			return false;
		}
		global $logger;
		$qty = floor($qty / $price->multiple) * $price->multiple;
		if ($qty < 1) {
			if ($autoAdjust) $qty = 0;
			return false;
		}
		if (!$this->getTrackInventory()) {
			return true;
		}
		global $db, $logger;
		// integrity problem here; there's a small margin between item creation and modification
		if ($this->orderType & O_OUT) {
			$qtyAvailable = $this->getQuantityAvailable();
			if ($this->getCanOrderPastZero() || ($qtyAvailable >= $qty)) return true;
			// if this function is allowed to auto-adjust, let's give the person whatever is left, or adjust the quantity to zero
			if ($autoAdjust) {
				$this->quantityOrdered = floor($qtyAvailable / $price->multiple) * $price->multiple;
			}
			return false;
		} else return true;
	}

	public function getSubtotalOrdered ($calculateDiscount = true) {
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' is missing', 'OrderItem::getSubtotalOrdered()');
			return false;
		}
		if (!$this->quantityOrdered) return false;
		if (!$person = $this->getPerson()) return false;
		if (!$price = $this->getPrice($this->personID)) return false;
		$unitPrice = (is_null($this->unitPrice) ? $price->price : $this->unitPrice) / $price->multiple;
		return round($this->quantityOrdered * $unitPrice * ($calculateDiscount ? (100 - $this->discount) / 100 : 1), 2);
	}

	public function getSubtotalDelivered ($calculateDiscount = true) {
		// doesn't figure out if item didn't warrant discount; should do that in deliver ()
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'OrderItem::getSubtotalDelivered(): either orderID or itemID is missing');
			return false;
		}
		if (!$this->quantityDelivered) return false;
		if (!$person = $this->getPerson()) return false;
		if (!$price = $this->getPrice($this->personID)) return false;
		$unitPrice = (is_null($this->unitPrice) ? $price->price : $this->unitPrice) / $price->multiple;
		return round($this->quantityDelivered * $this->unitPrice * ($calculateDiscount ? (100 - $this->discount) / 100 : 1), 2);
	}

	public function checkout () {
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' is missing', 'OrderItem::checkout()');
			return false;
		}
		$person = $this->getPerson();
		if ($price = $this->getPrice($this->personID)) {
			// if $unitPrice and $tax are not specified, replace them with the default values
			// $newUnitPrice = (is_null($unitPrice) ? $price->price : round((float) $unitPrice, 2));
			if (is_null($this->unitPrice)) $this->unitPrice = $price->price;
			// TODO: FUTURE: If we offer discounts (coupons, sales, etc) on certain items, we'll need to know whether a discount was auto-calculated or not. Perhaps an extra property/field?
			if (is_null($this->discount)) {
				if ($this->quantityOrdered / $price->multiple >= $person->getBulkDiscountQuantity()) $this->discount = $person->getBulkDiscount();
			}
		} else if ($this->orderType & O_IN) {
			// if this order is for a customer, and the item isn't available, then say goodbye!
			$this->setError(E_NOT_AVAILABLE_TO_CUSTOMER, 'item ' . $this->itemID . ' not available to person ' . $this->personID, 'Order::checkout()');
			return false;
		}
		$this->save();
	}

	public function deliver ($quantity) {
		// if this is an invalid or empty orderItem, or if it's not the right type of order, say 'no'!
		// should also check status of order, but for now we'll trust that it's only called from Order
		// what about if we delivered too many? should the person be charged?
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' is missing', 'OrderItem::deliver()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'cannot deliver an item in a recurring order or supplier list ' . $this->orderID, 'OrderItem::deliver()');
			return false;
		}
		$this->quantityDelivered = (int) $quantity;
		global $db;
		$db->start('orderItem' . $this->itemID);
		// only updates the inventory on those items that need it
		if (!$db->query('UPDATE item SET quantity = quantity ' . ($this->orderType & O_OUT ? '-' : '+') . $this->quantityDelivered . ' WHERE itemID = ' . $this->itemID . ' AND trackInventory')) {
			$this->setError(E_DATABASE, 'on modification of inventory for item ' . $this->itemID, 'OrderItem::deliver()');
			return false;
		}
		if (!$this->save()) {
			$db->rollback('orderItem' . $this->itemID);
			return false;
		}
		$db->commit('orderItem' . $this->itemID);
	}

	public function getDiscount () {
		return $this->discount;
	}

	public function getPerson () {
		if (!$this->personID && !is_object($this->person)) {
			global $db;
			if (!$db->query('SELECT personID FROM orders WHERE orderID = ' . $this->orderID)) {
				$this->setError(E_DATABASE, 'on grabbing personID from order ' . $this->orderID, 'OrderItem::getPerson()');
				return false;
			}
			if (!$r = $db->getRow(F_RECORD)) {
				$this->setError(E_DATABASE, 'Order ' . $this->orderID . ' doesn\'t exist', 'OrderItem::getPerson()');
				return false;
			}
			$this->personID = (int) $r->v('personID');
		}
		if (!is_object($this->person)) {
			$person = new Person ((int) $this->personID);
			if ($person->personID) {
				$this->person = $person;
				return $person;
			} else {
				// echo 'poo again';
				return false;
			}
		} else return $this->person;
	}

	// just making methods that modify the Item unavailable

	public function deletePrice ($personID) {
		return false;
	}

	public function setPrice ($personID, $price, $hst, $pst, $multiple) {
		return false;
	}

	public function setCustomPrice ($price, $hst = null, $pst = null) {
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' missing', 'OrderItem::setCustomPrice()');
			return false;
		}
		if (!$this->active || !$this->isLeafNode()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'Item ' . $this->itemID . ' not active, or not a leaf node (order ' . $this->orderID . ')', 'OrderItem::setCustomPrice()');
			return false;
		}
		if (!$price = round((float) $price, 2)) {
			$this->setError(E_INVALID_DATA, 'Price ' . $price . ' is not a valid decimal number', 'OrderItem::setCustomPRice()');
			return false;
		}
		$this->unitPrice = $price;
		if (!$this->save()) return false;
		return true;
	}

	public function getRealPrice () {
		$person = $this->getPerson();
		if (is_null($this->unitPrice)) {
			$price = $this->getPrice($person->personID);
			return $price->price;
		} else return $this->unitPrice;
	}

	public function addKitItems ($kitItems) {
		return false;
	}

	public function removeKitItems ($kitItems) {
		return false;
	}

	public function addImage ($imageFilename) {
		return false;
	}

	public function getQtyGrouped () {
		if (!$this->orderID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or itemID ' . $this->itemID . ' is missing', 'OrderItem::getQtyGrouped()');
			return false;
		}
		if (!$this->quantityOrdered) return false;
		if (!$person = $this->getPerson()) return false;
		if (!$price = $this->getPrice($this->personID)) return false;
		return $this->quantityOrdered / $price->multiple;
	}
}

?>
