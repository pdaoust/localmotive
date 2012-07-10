<?php

class Order extends MarketPrototype {
	public $orderID;
	public $personID;
	public $addressID;
	public $label;
	public $orderType;
	// public $csa;
	// public $editable = true;
	public $period;
	// public $lockToRoute = true;
	private $recurringOrderID;
	private $dateStarted;
	private $dateCompleted;
	private $dateToDeliver;
	private $dateResume;
	private $dateDelivered;
	private $dateCanceled;
	//public $payTypeID;
	//public $payType;
	// public $subtotal;
	public $hst;
	public $pst;
	public $surcharge;
	public $surchargeType;
	public $shipping;
	public $shippingType;
	public $discount;
	public $discountType;
	public $stars;
	public $notes;
	public $orderItems = array ();
	private $person;
	private $deliveryDays = array ();

	public function __construct ($orderInfo = null) {
		if (!$orderInfo) $orderInfo = null;
		switch (gettype($orderInfo)) {
			case 'integer':
				global $db;
				if (!$db->query('SELECT * FROM orders WHERE orderID = ' . $orderInfo)) {
					$this->setError(E_DATABASE, 'on query', 'Order::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'Creating object, no such orderID ' . $orderInfo . ' exists', 'Order::__construct()');
					return false;
				}
				$orderInfo = $r;
			case 'array':
				$orderInfo = new Record ($orderInfo);
			case 'object':
				if (get_class($orderInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$orderInfo is a ' . get_class($orderInfo) . ' rather than the expected Record', 'Order::__construct()');
					return false;
				}
				global $config;
				foreach ($this as $k => $v) {
					$v = $orderInfo->v($k);
					if (!is_null($v)) {
						switch ($k) {
							case 'dateStarted':
							case 'dateCheckedOut':
							case 'dateToDeliver':
							case 'dateCompleted':
							case 'dateDelivered':
							case 'dateCanceled':
							case 'dateResume':
								$v = myCheckDate($v);
								$this->$k = ($v ? $v : null);
								break;
							// case 'lockToRoute':
							// case 'editable':
							// case 'csa':
								$this->$k = $orderInfo->b($k);
								break;
							case 'hst':
							case 'pst':
								$this->$k = ($v ? $v : $config[$k]);
								break;
							case 'orderItems':
								// depends on being the last property
								// in the object
								if (is_array($v)) {
									foreach ($v as $i => $orderItem) {
										if (!is_object($orderItem)) unset($v[$i]);
										else if (get_class($orderItem) != 'OrderItem') unset($value[$i]);
									}
									$this->$k = $v;
								} else $this->populateOrderItems();
								break;
							case 'orderType':
								$this->$k = (int) $v & O_ALL;
								break;
							default:
								$this->$k = $v;
						}
					}
				}
				if (!$orderInfo->v('orderItems')) $this->populateOrderItems();
				if (!$this->validate()) return false;
				break;
			default:
				global $config;
				$this->orderID = null;
				$this->personID = null;
				$this->addressID = null;
				$this->label = null;
				$this->orderType = null;
				// $this->csa = false;
				// $this->editable = true;
				$this->period = null;
				// $this->lockToRoute = true;
				$this->recurringOrderID = null;
				$this->dateStarted = null;
				$this->dateCompleted = null;
				$this->dateToDeliver = null;
				$this->dateResume = null;
				$this->dateDelivered = null;
				$this->dateCanceled = null;
				//$this->payTypeID = null;
				$this->discount = null;
				$this->hst = $config['hst'];
				$this->pst = $config['pst'];
				$this->stars = null;
				$this->notes = null;
				$this->orderItems = array ();
				$this->person = null;
		}
		if (!($this->orderType & O_NO_STARS) && !$this->recurringOrderID) $this->calculateStars();
		if ($this->orderType == O_TEMPLATE) $this->adjustDates();
		$this->clearError();
		return true;
	}

	public function populateOrderItems ($orderItems = null) {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'tried to populate orderItems, but orderID ' . (int) $this->orderID . ' or personID ' . (int) $this->personID . ' are missing', 'Order::populateOrderItems()');
			return false;
		}
		if (!is_array($orderItems)) {
			global $db;
			if (!$db->query('SELECT * FROM orderItem WHERE orderID = ' . (int) $this->orderID)) {
				$this->setError(E_DATABASE, 'on query', 'Order::populateOrderItems()');
				return false;
			}
			$orderItemData = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$r->r['orderType'] = $this->orderType;
				$r->r['personID'] = $this->personID;
				$orderItemData[$r->v('itemID')] = $r;
			}
			foreach ($orderItemData as $thisOrderItemData) {
				if (!$thisOrderItem = new OrderItem ($thisOrderItemData)) {
					global $errorCodes;
					$this->setError(E_INVALID_DATA, 'populating orderItem ' . $thisOrderItem->itemID . ' returned error ' . $errorCodes[$thisOrderItem->getError()] . '(' . $thisOrderItem->getErrorDetail() . ')', 'Order::populateOrderItems()');
					return false;
				} else $this->orderItems[$thisOrderItemData->v('itemID')] = $thisOrderItem;
			}
		} else {
			foreach ($orderItems as $thisOrderItemData) {
				if (is_object($thisOrderItemData)) {
					$thisOrderItemData->r['orderType'] = $this->orderType;
					$thisOrderItemData->r['personID'] = $this->personID;
					$itemID = $thisOrderItemData->v('itemID');
				} else {
					$thisOrderItemData['orderType'] = $this->orderType;
					$thisOrderItemData['personID'] = $this->personID;
					if (isset($thisOrderItemData['itemid'])) $itemID = $thisOrderItemData['itemid'];
					else if (isset($thisOrderItemData['itemID'])) $itemID = $thisOrderItemData['itemID'];
				}
				if (!$thisOrderItem = new OrderItem ($thisOrderItemData)) {
					global $errorCodes;
					$this->setError(E_INVALID_DATA, 'populating orderItem ' . $thisOrderItem->itemID . ' returned error ' . $errorCodes[$thisOrderItem->getError()] . '(' . $thisOrderItem->getErrorDetail() . ')', 'Order::populateOrderItems()');
					return false;
				} else $this->orderItems[(int) $itemID] = $thisOrderItem;
			}
		}
		// if (!$this->dateCompleted || $this->orderType == O_RECURRING) $this->clearInactiveItems();
		return true;
	}

	public function clearInactiveItems () {
		if ($this->dateCompleted) {
			$this->setError(E_ORDER_COMPLETED, 'order ' . $this->orderID . ' completed on ' . Date::human($this->dateCompleted) . ', and this method is to be used only for cleaning orders-in-progress', 'Order::clearInactiveItems()');
			return false;
		}
		$clearedItems = array ();
		foreach ($this->orderItems as $thisItem) {
			if (!$thisItem->isActive() || !$thisItem->getPrice($this->personID)) {
				$this->deleteItem($thisItem->itemID);
				$clearedItems[] = $thisItem->itemID;
			}
		}
		/* if (count($clearedItems)) {
			global $logger;
			$logger->addEntry('Cleared items (' . implode(', ', $clearedItems) . ') from order ' . $this->orderID, null, 'Order::clearInactiveItems()');
		} */
		return true;
	}

	public function validate () {
		global $config;
		$errorFields = array ();
		$this->orderID = $this->orderID ? (int) $this->orderID : null;
		if ($this->personID) $this->personID = (int) $this->personID;
		else $errorFields['personID'] = $this->personID;
		$person = $this->getPerson();
		$this->addressID = (int) $this->addressID;
		if ($this->addressID && !$person->getAddress($this->addressID)) $this->addressID = false;
		$this->label = trim($this->label);
		$this->orderType &= O_ALL;
		if ($this->orderType & O_TEMPLATE) {
			$this->period = (int) $this->period;
			if (!$this->period) $errorFields['period'] = $this->period;
			$this->dateDelivered = null;
		} else {
			$this->period = null;
			$this->dateResume = null;
		}
		if ($this->orderType & O_SYSTEM) $this->orderType &= (O_ALL - O_EDITABLE);
		$this->recurringOrderID = ((int) $this->recurringOrderID ? (int) $this->recurringOrderID : null);
		if ($this->orderType & O_TEMPLATE) $this->recurringOrderID = null;
		$this->dateStarted = $this->checkDate($this->dateStarted);
		if (!$this->dateStarted && !is_null($this->dateStarted)) $errorFields['dateStarted'] = $this->dateStarted;
		$this->dateCompleted = $this->checkDate($this->dateCompleted);
		if (!$this->dateCompleted && !is_null($this->dateCompleted)) $errorFields['dateCompleted'] = $this->dateCompleted;
		$this->dateToDeliver = $this->checkDate($this->dateToDeliver);
		if (!$this->dateToDeliver && !is_null($this->dateToDeliver)) $errorFields['dateToDeliver'] = $this->dateToDeliver;
		$this->dateResume = $this->checkDate($this->dateResume);
		if (!$this->dateResume && !is_null($this->dateResume)) $errorFields['dateResume'] = $this->dateResume;
		$this->dateDelivered = $this->checkDate($this->dateDelivered);
		if (!$this->dateDelivered && !is_null($this->dateDelivered)) $errorFields['dateDelivered'] = $this->dateDelivered;
		/*$payTypes = getPayTypes();
		if ($this->payTypeID) {
			if (array_key_exists($this->payTypeID, $payTypes)) $this->payTypeID = $this->payTypeID;
			else if (!$this->payTypeID) $this->payTypeID = null;
			else $errorFields['payTypeID'] = $this->payTypeID;
		}*/
		$this->hst = $this->hst ? (int) $this->hst : $config['hst'];
		$this->pst = $this->pst ? (int) $this->pst : $config['pst'];
		$this->surcharge = ($this->surcharge ? round((float) $this->surcharge, 2) : null);
		$this->surchargeType &= N_ALL;
		$person = $this->getPerson();
		if ($this->orderType & O_DELIVER && $person->canDeliver()) {
			if (!is_null($this->shipping) && !is_null($this->shippingType)) {
				$this->shipping = ($this->shipping ? round((float) $this->shipping, 2) : null);
				$this->shippingType &= N_ALL;
			} else {
				$this->shipping = $person->getShipping();
				$this->shippingType = $person->getShippingType();
			}
		} else $this->shipping = $this->shippingType = null;
		$this->discount = ($this->discount ? round((float) $this->discount, 2) : null);
		$this->discountType &= N_ALL;
		if (!($this->orderType & O_TEMPLATE)) {
			$this->period = null;
			$this->dateResume = null;
			// TODO: hm, do I need this? I hope not, cuz it's screwing things up when I try to create orders from arrays
			/* if (!($this->stars || $this->stars === STARS_NO_CALCULATE || $this->stars === 0)) {
				if (!$this->calculateStars()) {
					if ($this->getError() == E_WRONG_ORDER_TYPE) $this->stars = STARS_NO_CALCULATE;
					else $errorFields['stars'] = $this->stars;
				}
			} */
		} else $this->stars = null;
		$this->notes = trim($this->notes);
		foreach ($this->orderItems as $thisKey => $thisOrderItem) {
			if (!(get_class($thisOrderItem) == 'OrderItem')) {
				$errorFields[] = 'orderItem' . $thisKey;
				unset($this->orderItems[$thisKey]);
			}
		}
		if (count($errorFields)) {
			$errorFields[] = 'Order verify';
			$this->setError(E_INVALID_DATA, $errorFields, 'Order::validate()');
			return false;
		} else return true;
	}

	public function save () {
		if (!$this->validate()) {
			return false;
		}
		global $db;
		if (!$this->orderID) {
			if (!$db->query('INSERT INTO orders (dateStarted, personID, orderType) VALUES (NOW(), ' . (int) $this->personID . ', ' . (int) $this->orderType . ')')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Order::save()');
				return false;
			}
			$this->orderID = $db->getLastID();
		}
		$q = 'UPDATE orders SET personID = ' . $this->personID;
		$q .= ', addressID = ' . ($this->addressID ? 'NULL' : (int) $this->addressID);
		$q .= ', label = ' . ($this->label ? '\'' . $db->cleanString($this->label) . '\'' : 'NULL');
		$q .= ', orderType = ' . (int) $this->orderType;
		// $q .= ', csa = ' . ($this->csa ? 'true' : 'false');
		// $q .= ', editable = ' . ($this->editable ? 'true' : 'false');
		$q .= ', period = ' . ($this->period ? $this->period : 'null');
		// $q .= ', lockToRoute = ' . ($this->lockToRoute ? 'true' : 'false');
		$q .= ', recurringOrderID = ' . ((int) $this->recurringOrderID ? (int) $this->recurringOrderID : 'null');
		$q .= ', dateStarted = ' . ($this->dateStarted ? ($this->dateStarted ? '\'' . $db->cleanDate($this->dateStarted) . '\'' : 'NOW()' ) : 'null');
		$q .= ', dateCompleted = ' . ($this->dateCompleted ? '\'' . $db->cleanDate($this->dateCompleted) . '\'' : 'null');
		$q .= ', dateToDeliver = ' . ($this->dateToDeliver ? '\'' . $db->cleanDate($this->dateToDeliver) . '\'' : 'null');
		$q .= ', dateResume = ' . ($this->dateResume ? '\'' . $db->cleanDate($this->dateResume) . '\'' : 'null');
		$q .= ', dateDelivered = ' . ($this->dateDelivered ? '\'' . $db->cleanDate($this->dateDelivered) . '\'' : 'null');
		$q .= ', dateCanceled = ' . ($this->dateCanceled ? '\'' . $db->cleanDate($this->dateCanceled) . '\'' : 'null');
		//$q .= ', payTypeID = ' . ($this->payTypeID ? $this->payTypeID : 'null');
		$q .= ', hst = ' . ($this->hst ? $this->hst : 'null');
		$q .= ', pst = ' . ($this->pst ? $this->pst : 'null');
		$q .= ', surcharge = ' . ($this->surcharge ? $this->surcharge : 'null');
		$q .= ', surchargeType = ' . ($this->surchargeType ? $this->surchargeType : 'null');
		$q .= ', shipping = ' . ($this->shipping ? $this->shipping : 'null');
		$q .= ', shippingType = ' . ($this->shippingType ? $this->shippingType : 'null');
		$q .= ', discount = ' . ($this->discount ? $this->discount : 'null');
		$q .= ', discountType = ' . ($this->discountType ? $this->discountType : 'null');
		switch ($this->stars) {
			case STARS_NO_CALCULATE:
				$q .= ', stars = -1';
				break;
			case null:
				$q .= ', stars = null';
				break;
			default:
				$q .= ', stars = ' . $this->stars;
		}
		$q .= ', notes = \'' . $db->cleanString($this->notes) . '\'';
		$q .= ' WHERE orderID = ' . $this->orderID;
		if (!$db->query($q, true)) {
			$this->orderID = null;
			$this->setError(E_DATABASE, 'on save', 'Order::save()');
			// echo 'LLL';
			return false;
		}
		foreach ($this->orderItems as $thisOrderItem) {
			if (!$thisOrderItem->save()) $this->setError($thisOrderItem->getError(), 'the item ' . $thisOrderItem->itemID . ' returned error ' . $GLOBALS['errorCodes'][$thisOrderItem->getError()] . ' (' . $thisOrderItem->getErrorDetail() . ')', 'Order::save()');
		}
		$this->clearError();
		return true;
	}

	public function delete () {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'can\'t delete an order that doesn\'t exist', 'Order::delete()');
			return false;
		}
		if ($this->dateCompleted && !($this->orderType & O_TEMPLATE)) {
			$this->setError(E_ORDER_COMPLETED, 'can\'t delete a completed order', 'Order::delete()');
			return false;
		}
		global $db;
		$db->start('deleteOrder' . $this->orderID);
		if (!$db->query('DELETE FROM orders WHERE orderID = ' . $this->orderID)) {
			$this->setError(E_DATABASE, 'on deletion of order ' . $this->orderID, 'Order::delete()');
			$db->rollback('deleteOrder' . $this->orderID);
			return false;
		}
		if (!$db->query('DELETE FROM orderItem WHERE orderID = ' . $this->orderID)) {
			$this->setError(E_DATABASE, 'on deletion of orderItems for order ' . $this->orderID, 'Order::delete()');
			$db->rollback('deleteOrder' . $this->orderID);
			return false;
		}
		$db->commit('deleteOrder' . $this->orderID);
		global $logger;
		$logger->addEntry('Deleted order ' . $this->orderID, null, 'Order::delete()');
		$this->__construct();
		$this->clearError();
		return true;
	}

	public function start ($orderType = O_SALE, $period = null) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Can\'t start an order without a personID', 'Order::start()');
			return false;
		}
		$person = $this->getPerson();
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'attempted to start an order for inactive person ' . $person->personID . ' (' . $person->contactName . ')', 'Order::start()');
			return false;
		}
		if ($this->orderID || $this->dateStarted) return true;
		$orderType &= O_ALL;
		$this->orderType = $orderType;
		$this->dateStarted = time();
		$this->deliveryDays = array ();
		if ($this->orderType & O_TEMPLATE) {
			$this->period = ((int) $period ? (int) $period : T_WEEK);
			$this->dateStarted = Date::round($this->dateStarted);
		}
		if (!($this->orderType & O_NO_STARS)) $this->calculateStars();
		$this->save();
		global $logger;
		$logger->addEntry('Started order ' . $this->orderID . ' of type ' . $this->orderType . ' for person ' . $this->personID, null, 'Order::start()');
		$this->clearError();
	}

	public function addQuantity ($itemID, $quantity, $unitPrice = null, $tax = null, $discount = null, $adjustDates = false, $permanent = true) {
		global $db, $logger;
		$logger->push(__METHOD__);
		// doesn't check for inventory inside kit items -- that screws everything up!
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Can\'t add item to an order whose orderID ' . $this->orderID . ' or personID ' . $this->personID . ' are missing', 'Order::addQuantity()');
			return false;
		}
		if ($this->dateCompleted && !($this->orderType & O_TEMPLATE)) {
			$this->setError(E_ORDER_COMPLETED, 'can\'t add item to completed order ' . $this->orderID . ' (completed on ' . Date::human($this->dateCompleted) . ')', 'Order::addQuantity()');
			return false;
		}
		if ($this->dateCanceled) {
			$this->setError(E_ORDER_CANCELED, 'can\'t add item to canceled order ' . $this->orderID . ' (canceled on ' . Date::human($this->dateCanceled) . ')', 'Order::addQuantity()');
			return false;
		}
		$person = $this->getPerson();
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'attempted to add items to order ' . $this->orderID . ' for inactive person ' . $person->personID . ' (' . $person->contactName . ')', 'Order::addQuantity()');
			return false;
		}
		$itemID = (int) $itemID;
		if (!$itemID) {
			$this->setError(E_INVALID_DATA, 'itemID is not valid', 'Order::addQuantity()');
			return false;
		}
		if (!array_key_exists($itemID, $this->orderItems)) {
			if (!$db->query('SELECT itemID FROM item WHERE itemID = ' . (int) $itemID)) {
				$this->setError(E_DATABASE, 'on grabbing of item ' . $itemID . ' to add to order ' . $this->orderID, 'Order::addQuantity()');
				return false;
			}
			if (!$r = $db->getRow()) {
				$this->setError(E_NO_OBJECT, 'Cannot add item ' . $itemID . ' to order ' . $this->orderID . '; does not exist in database', 'Order::addQuantity()');
				return false;
			}
			$newItem = new OrderItem;
			$newItem->orderID = $this->orderID;
			$newItem->constructFromItemID((int) $itemID);
			$newItem->orderType = $this->orderType;
			/* $validity = $newItem->create($itemID);
			if (!$validity) return false; */
			$this->orderItems[$itemID] = $newItem;
		}
		$logger->push('test');
		$totalQuantity = $this->orderItems[$itemID]->addQuantity((int) $quantity, $unitPrice, $tax, $discount, true);
		$logger->pop('test');
		if (!$totalQuantity && $totalQuantity !== 0) {
			$this->setError($this->orderItems[$itemID]->getError(), 'item ' . $itemID . ' returned error ' . $GLOBALS['errorCodes'][$this->orderItems[$itemID]->getError()] . ' (' . $this->orderItems[$itemID]->getErrorDetail() . ')');
			unset($this->orderItems[$itemID]);
			return false;
		}
		if ($totalQuantity === 0) {
			if ($this->orderItems[$itemID]->delete()) unset($this->orderItems[$itemID]);
			else {
				$this->setError($this->orderItems[$itemID]->getError(), 'Encountered error when trying to delete item ' . $itemID . ' from order ' . $this->orderID, 'Order::addQuantity()');
				return false;
			}
		} else {
			$logger->push('commit');
			$this->orderItems[$itemID]->addQuantity((int) $quantity, $unitPrice, $tax, $discount);
			if (!is_null($permanent) && $this->orderType & O_TEMPLATE) $this->setItemPermanent($itemID, (bool) $permanent);
			$logger->pop('commit');
		}
		$this->clearError();
		$logger->pop(__METHOD__);
		return $totalQuantity;
	}

	public function setQuantity ($itemID, $quantity, $unitPrice = null, $tax = null, $discount = null, $adjustDates = false) {
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems)) {
			$this->setError(E_NO_OBJECT, 'Cannot set quantity of ' . $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::setQuantity()');
			return false;
		}
		$this->clearError();
		$qtyOld = $this->orderItems[$itemID]->quantityOrdered;
		$diff = $quantity - $qtyOld;
		if ($diff) return $this->addQuantity($itemID, $diff, $unitPrice, $tax, $discount, $adjustDates, null);
		else return true;
	}

	public function getQuantity ($itemID) {
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems)) {
			// $this->setError(E_NO_OBJECT, 'Cannot get quantity of ' . $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::getQuantity()');
			return false;
		}
		$this->clearError();
		return $this->orderItems[$itemID]->quantityOrdered;
	}

	public function setPrice ($itemID, $price) {
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems)) {
			$this->setError(E_NO_OBJECT, 'Cannot set price of ' . $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::setPrice()');
			return false;
		}
		$this->clearError();
		return $this->orderItems[$itemID]->setCustomPrice($price);
	}

	public function deleteItem ($itemID, $changeDeliveryDay = false) {
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems)) {
			$this->setError(E_NO_OBJECT, 'Cannot delete ' . $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::deleteItem()');
			return false;
		}
		$this->clearError();
		return $this->setQuantity($itemID, 0);
	}

	public function setItemPermanent ($itemID, $permanent = null) {
		// if $permanent is null, it just toggles
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems)) {
			$this->setError(E_NO_OBJECT, 'Cannot change permanency of ' . $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::setItemPermanent()');
			return false;
		}
		if (!($this->orderType & O_TEMPLATE)) {
			$this->setError(E_WRONG_ORDER_TYPE, 'Can\'t set permanent item on a one-time order', 'Order::setItemPermanent()');
			return false;
		}
		global $logger;
		if (!$this->orderItems[$itemID]->getCanBePermanent()) {
			$this->setError(E_NOT_AVAILABLE_TO_CUSTOMER, 'This item cannot be set as permanent', 'Order:setItemPermanent()');
			return false;
		}
		$person = $this->getPerson();
		if ($person->personType & P_CSA && $this->orderType & O_CSA && $this->orderItems[$itemID]->getCsaRequired() && !$this->hasOtherCsaItem($itemID) && (($this->orderItems[$itemID]->permanent && is_null($permanent)) || (!is_null($permanent) && !$permanent))) {
			$this->setError(E_ORDER_TOO_SMALL, 'This item has to stay on the CSA order!', 'Order::setItemPermanent()');
			return false;
		}
		$oldPermanent = $this->orderItems[$itemID]->permanent;
		if (is_null($permanent)) $permanent = !$oldPermanent;
		$this->orderItems[$itemID]->permanent = (bool) $permanent;
		if (!$this->orderItems[$itemID]->save()) {
			$this->setError('E_INVALID_DATA', 'Something went wrong with the saving of this item', 'Order::setItemPermanent()');
			$this->orderItems[$itemID]->permanent = $oldPermanent;
		}
		return $this->orderItems[$itemID]->permanent;
	}

	public function hasOtherCsaItem ($itemID = null) {
		// returns an error if $itemID is not in shopping cart, unless $itemID is empty
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems) && $itemID) {
			$this->setError(E_NO_OBJECT, $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::hasOtherCSAItem()');
			return false;
		}
		$items = $this->getCsaItems();
		if (isset($items[$itemID])) unset($items[$itemID]);
		return (bool) count($items);
	}

	public function hasOtherPermanentItem ($itemID) {
		// returns an error if $itemID is not in shopping cart, unless $itemID is empty
		$itemID = (int) $itemID;
		if (!array_key_exists($itemID, $this->orderItems) && $itemID) {
			$this->setError(E_NO_OBJECT, $itemID . '; it hasn\'t been added to order ' . $this->orderID, 'Order::hasOtherPermanent()');
			return false;
		}

		$hasOther = false;
		foreach ($this->orderItems as $k => $v) {
			if ($v->itemID != $itemID) {
				if ($v->permanent) $hasOther = true;
			}
		}
		$items = $this->getPermanentItems();
		if (isset($items[$itemID])) unset($items[$itemID]);
		return (bool) count($items);
	}

	public function getCsaItems () {
		if (!($this->orderType & O_OUT)) {
			$this->setError(E_INVALID_DATA, 'Order is not an outgoing template', 'Order::getPermanentItems()');
			return false;
		}
		$items = array ();
		foreach ($this->orderItems as $k => $v) {
			if ($v->getCsaRequired() && ((($this->orderType & O_TEMPLATE) && $v->permanent) || !($this->orderType & O_TEMPLATE))) $items[$k] = $v;
		}
		return $items;
	}

	public function getPermanentItems () {
		if (($this->orderType & O_BASE) != O_RECURRING) {
			$this->setError(E_INVALID_DATA, 'Order is not an outgoing template', 'Order::getPermanentItems()');
			return false;
		}
		$items = array ();
		foreach ($this->orderItems as $k => $v) {
			if ($v->permanent) $items[$k] = $v;
		}
		return $items;
	}

	public function getNextDeliveryDay ($day = null, $accountForCutoff = true, $checkOrders = true) {
		if (!$day) $day = time();
		$day = Date::round($day, T_DAY);
		$dayOld = $day;
		$extraStatus = ($accountForCutoff ? 1 : 0) + ($checkOrders ? 2 : 0);
		if (isset($this->deliveryDays[$day]) && isset($this->deliveryDays[$day][$extraStatus])) return $this->deliveryDays[$day][$extraStatus];
		// returns the number of days between now and next delivery day, or date of next delivery day
		// doesn't set any errors; it's just a get() function
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Order::getNextDeliveryDay()');
			return false;
		}
		$person = $this->getPerson();
		// print_r($person);
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'person ' . $person->personID . ' is inactive; no next delivery day', 'Order::getNextDeliveryDay()');
			return false;
		}
		if ($this->dateDelivered) {
			$this->setError(E_ORDER_COMPLETED, 'order ' . $this->orderID . ' is delivered; calculating a delivery day wouldn\'t make sense', 'Order::getNextDeliveryDay()');
			return false;
		}
		if ($this->dateCanceled) {
			$this->setError(E_ORDER_CANCELED, 'order ' . $this->orderID . ' is canceled; calculating a delivery day wouldn\'t make sense', 'Order::getNextDeliveryDay()');
			return false;
		}
		global $config, $logger;
		$itemCutoffDay = 0;
		// may not work for recurring orders; not sure what to do here. Problems:
		// 1. Customer adds things on. If $itemCutoffDay is added on, then
		//    customer's schedule is thrown off by a week.
		// 2. Customer orders items, expecting to get them this week. If
		//    $itemCutoffDay is not added, then customer doesn't get item and
		//    wonders what's wrong.
		foreach ($this->orderItems as $thisOrderItem) {
			if ($thisOrderItem->getCutoffDay() > $itemCutoffDay) $itemCutoffDay = $thisOrderItem->getCutoffDay();
		}
		if ($this->orderType & O_DELIVER) {
			if (!$route = $this->getRoute()) {
				$this->setError(E_NO_OBJECT, 'Person doesn\'t belong to a route', 'Order::getNextDeliveryDay()');
				return false;
			}
		} else if (!($this->orderType & O_TEMPLATE) && !$this->recurringOrderID) return $day;
		if ($this->orderType & O_TEMPLATE) {
			$dateStarted = Date::round($this->dateStarted);
			// $itemCutoffDay = 0;
			// TODO: doesn't check for item cutoff days presently -- see above; it resets it to zero; I'm relying on Thomas to worry about that.
			if ($this->dateCompleted && $this->dateCompleted < $day) {
				if ($this->dateResume) {
					if ($day < $this->dateResume) $day = $this->dateResume;
					$dateStarted = $this->dateResume;
				}
				else return false;
			}
			global $dayNames;
			// dateToDeliver no longer determines weekday -- now determined by dateStarted
			if ($this->orderType & O_DELIVER) {
				if (!$route = $this->getRoute()) {
					$this->setError(E_NO_OBJECT, 'No route for this order', 'Oprder::getNextDeliveryDay()');
					return false;
				}
			}
			if ($this->period < 0) {
				/* $startDates = $this->dateStarted;
				if ($this->orderType & O_DELIVER) {
					// unpredictable things can happen if we try to lock monthly orders to weekly routes!
					$startDates = $route->getNextDeliveryDay($startDates);
					$startDates = getdate($startDates);
					$startDates = strtotime($startDates['year'] . '-' . $startDates['month'] . '-01');
				} else */
				$startDates = $dateStarted;
				if ($this->orderType & O_DELIVER) {
					$startDates = $route->getNextDeliveryDay($startDates);
				} else {
					$startDates = getdate($startDates);
					$nextDates = getdate($day);
					$yDiff = $nextDates['year'] - $startDates['year'];
					$mDiff = $nextDates['month'] - $startDates['month'];
					$mDiff = $yDiff * 12 + $mDiff;
					$dDiff = $nextDates['mday'] - $startDates['mday'];
					if ($dDiff > 0) $mDiff += 1;
					// some tricky math -- ceil($mDiff / abs($this->period)) divides the number of months diff by $this->period of months. then multiplies again by that, so it's rounded up to an even $this->period of months. See also similar algorithm at work down below ($intervals = ceil(($day etc) and in Route::getNextDeliveryDay
					$intervals = ceil($mDiff / abs($this->period));
					$day = Date::addMonths($intervals * abs($this->period), $dateStarted);
				}
			} else $intervals = ceil(($day - Date::round($dateStarted)) / $this->period);
			/*if ($this->orderType & O_DELIVER) $day = roundDate($route->getNextDeliveryDay($day + ($accountForCutoff ? $itemCutoffDay * T_DAY : 0), $accountForCutoff, ($this->period < 0 ? null : $this->dateStarted), ($this->period < 0 ? null : $this->period)));
			else if ($this->period > 0) {
				$intervals = ceil(($day - $this->dateStarted) / $this->period);
				$day = roundDate($this->dateStarted + $intervals * $this->period);
			}*/
			if ($checkOrders) {
				$hasOrder = true;
				while ($hasOrder) {
					if ($this->orderType & O_DELIVER) $try = roundDate($route->getNextDeliveryDay($day + ($accountForCutoff ? $itemCutoffDay * T_DAY : 0), $accountForCutoff, ($this->period < 0 ? null : $dateStarted), $this->period));
					else $try = $day;
					$hasOrder = (bool) $this->hasCreatedOrder($try, true);
					if (!$hasOrder) break;
					$intervals ++;
					if ($this->period < 0) $day = Date::addMonths($intervals * abs($this->period), $dateStarted);
					else $day = $dateStarted + $intervals * $this->period;
				}
				if (isset($try)) $finalDate = $try;
				else if ($this->orderType & O_DELIVER) $finalDate =
					roundDate(
						$route->getNextDeliveryDay(
							$day + ($accountForCutoff ? $itemCutoffDay * T_DAY : 0),
							$accountForCutoff,
							($this->period < 0 ? null : $dateStarted),
							($this->period < 0 ? null : $this->period)
						)
					);
				else $finalDate = ($this->period < 0 ? $day : roundDate($dateStarted + $intervals * $this->period));
			} else if ($this->orderType & O_DELIVER) $finalDate =
				roundDate(
					$route->getNextDeliveryDay(
						$day + ($accountForCutoff ? $itemCutoffDay * T_DAY : 0),
						$accountForCutoff,
						($this->period < 0 ? null : $dateStarted),
						($this->period < 0 ? null : $this->period)
					)
				);
			else $finalDate = ($this->period < 0 ? $day : roundDate($dateStarted + $intervals * $this->period));
		} else {
			if ($this->recurringOrderID) {
				if ($recurringOrder = new Order ($this->recurringOrderID)) $finalDate = $recurringOrder->getNextDeliveryDay($day, $accountForCutoff, $checkOrders);
			}
			if ($this->orderType & O_DELIVER) $finalDate = roundDate($route->getNextDeliveryDay($day + ($accountForCutoff ? $itemCutoffDay * T_DAY : 0), $accountForCutoff));
			else $finalDate = $day;
		}
		if (!isset($this->deliveryDays[$dayOld])) $this->deliveryDays[$dayOld] = array ($extraStatus => $finalDate);
		if ($this->orderType & O_TEMPLATE && $this->dateCompleted && ($this->dateCompleted < $finalDate && $this->dateResume != $dayOld)) {
			if ($this->dateResume > $this->dateCompleted) $finalDate = $this->getNextDeliveryDay($this->dateResume, $accountForCutoff, $checkOrders);
			else $finalDate = false;
		}
		return $finalDate;
	}

	public function getDateStarted () {
		$this->adjustDates();
		return $this->dateStarted;
	}

	public function getDateCompleted () {
		$this->adjustDates();
		return $this->dateCompleted;
	}

	public function getDateToDeliver () {
		return $this->dateToDeliver;
	}

	public function getDateDelivered () {
		return $this->dateDelivered;
	}

	public function getDateResume () {
		$this->adjustDates();
		return $this->dateResume;
	}

	public function getDateCanceled () {
		return $this->dateCanceled;
	}

	public function getPeriod ($includeStart = false) {
		if ($this->period && $this->dateStarted && ($this->orderType & O_TEMPLATE)) return Date::human($this->getNextDeliveryDay($this->dateStarted, false), $this->period, $includeStart);
		else return false;
	}

	public function setDateStarted ($dateStarted) {
		$dateStarted = roundDate(myCheckDate($dateStarted));
		if (!$dateStarted) {
			$this->dateStarted = null;
		} else {
			$this->dateStarted = $dateStarted;
			if ($this->dateCompleted <= $this->dateStarted) $this->dateCompleted = null;
			if ($this->dateResume <= $this->dateStarted) $this->dateResume = null;
		}
		$this->deliveryDays = array ();
		return true;
	}

	public function setDeliveryDay ($deliveryDayID) {
		if (!($this->orderType & O_TEMPLATE)) {
			$this->setError(E_WRONG_ORDER_TYPE, 'order ' . $this->orderID . ' is non-recurring', 'Order::setDeliveryDay()');
			return false;
		}
		global $dayNames;
		$this->dateToDeliver = roundDate(strtotime('next ' . $dayNames[(int) $deliveryDayID], $this->dateStarted));
		global $logger;
		$logger->addEntry('Set delivery day to ' . $dayNames[$deliveryDayID] . ' for order ' . $this->orderID . ' (not saved yet)', null, 'Order::setDeliveryDay()');
		return true;
	}

	public function setDateToDeliver ($dateToDeliver) {
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'order ' . $this->orderID . ' is recurring', 'Order::setDateToDeliver()');
			return false;
		}
		$dateToDeliver = roundDate(myCheckDate($dateToDeliver));
		if (!$dateToDeliver) {
			if ($this->dateCompleted) {
				$this->setError(E_INVALID_DATA, 'Can\'t clear dateToDeliver for order ' . $this->orderID . ', completed on ' . Date::human($this->dateCompleted), 'Order::setDateToDeliver()');
				return false;
			} else {
				// $logger->addEntry('Removed dateToDeliver from order ' . $this->orderID . ' (not saved yet)', 'Order::setDateCompleted()');
				$this->dateToDeliver = null;
			}
		} else {
			if ($this->dateDelivered) {
				$this->setError(E_INVALID_DATA, 'can\'t change dateToDeliver on order ' . $this->orderID . ', delivered on ' . Date::human($this->dateDelivered), 'Order::setDateToDeliver()');
				return false;
			}
			$this->dateToDeliver = $dateToDeliver;
			global $logger;
			$logger->addEntry('Set dateToDeliver to ' . Date::human($dateToDeliver) . ' for order ' . $this->orderID . ' (not saved yet)', null, 'Order::setDateToDeliver()');
			return true;
		}
	}

	public function setDateCompleted ($dateCompleted) {
		if ($this->orderType & O_TEMPLATE) {
			$dateCompleted = roundDate(myCheckDate($dateCompleted));
			if (!$dateCompleted) {
				$this->dateCompleted = null;
				$this->dateResume = null;
				return true;
			} else {
				$this->dateCompleted = $dateCompleted;
				if ($this->dateResume <= $this->dateCompleted) $this->dateResume = null;
			}
			$this->deliveryDays = array ();
			return true;
		} else {
			$this->setError(E_WRONG_ORDER_TYPE, 'order ' . $this->orderID . ' is not a recurring order', 'Order::setDateCompleted()');
			return false;
		}
	}

	public function setDateResume ($dateResume) {
		if ($this->orderType & O_TEMPLATE) {
			$dateResume = roundDate(myCheckDate($dateResume));
			if (!$dateResume) {
				$this->dateResume = null;
				global $logger;
				// $logger->addEntry('Removed dateResume from order ' . $this->orderID . ' (not saved yet)', 'Order::setDateResume()');
				$this->deliveryDays = array ();
				return true;
			} else {
				if ($this->dateCompleted && $this->dateCompleted < $dateResume) {
					$this->dateResume = $dateResume;
					$this->deliveryDays = array ();
					return true;
				} else {
					$this->setError(E_INVALID_DATA, 'Resume date is before complete date, which is just silly!', 'Order::setDateResume()');
					return false;
				}
			}
			return false;
		} else {
			$this->setError(E_WRONG_ORDER_TYPE, 'order ' . $this->orderID . ' is not a recurring order', 'Order::setDateResume()');
			return false;
		}
	}

	public function adjustDates () {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' is missing', 'Order::adjustDates()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE) {
			$today = $this->roundDate(time());
			if ($this->dateCompleted && $this->dateCompleted <= $today && $this->dateResume >= $this->dateCompleted) {
				$this->dateStarted = $this->dateResume;
				$this->dateCompleted = null;
				$this->dateResume = null;
				$this->deliveryDays = array ();
				return true;
			} else return false;
		} else {
			$this->setError(E_WRONG_ORDER_TYPE, 'order is not a template', 'Order::adjustDates');
			return false;
		}
	}

	public function isFromRecurringOrder () {
		return (bool) $this->getRecurringOrderID();
	}

	public function setFromRecurringOrder ($value) {
		if ($value) return false;
		else {
			$this->recurringOrderID = null;
			return true;
		}
	}

	public function getRecurringOrderID () {
		return $this->recurringOrderID;
	}

	public function setRecurringOrderID ($recurringOrderID) {
		if ((int) $recurringOrderID) $this->recurringOrderID = (int) $recurringOrderID;
		else if (!$recurringOrderID) $this->recurringOrderID = null;
		else {
			$this->setError(E_INVALID_DATA, 'Cannot set recurringOrderID with data of type ' . gettype($recurringOrderID), 'Order::setRecurringOrderID()');
			return false;
		}
		return true;
	}

	public function setTemplate ($template = true) {
		$template = (bool) $template;
		if (($template && ($this->orderType & O_TEMPLATE)) || (!$template && !($this->orderType & O_TEMPLATE))) return true;
		if ($template) {
			if (!$this->dateCompleted && !$this->dateCanceled) {
				$this->orderType |= O_TEMPLATE;
				$this->period = T_WEEK;
				$this->dateToDeliver = null;
				$this->dateResume = null;
				$this->dateDelivered = null;
				foreach ($this->orderItems as $v) {
					$this->orderItems[$v->itemID]->orderType = $this->orderType;
					$this->setItemPermanent($v->itemID, true);
				}
				return true;
			} else return false;
		} else {
			$this->orderType ^= O_TEMPLATE;
			$this->period = null;
			$this->dateToDeliver = null;
			$this->dateResume = null;
			$this->dateDelivered = null;
			foreach ($this->orderItems as $v) {
				$this->orderItems[$v->itemID]->orderType = $this->orderType;
				$this->setItemPermanent($v->itemID, false);
			}
			return true;
		}
	}

	public function getPerson () {
		if (!$this->personID) return false;
		if (!is_object($this->person)) {
			$person = new Person ((int) $this->personID);
			if ($person->personID) {
				$this->person = $person;
				return $person;
			} else return false;
		} else return $this->person;
	}

	public function getCutoffDay ($day = null) {
		if (!($this->orderType & O_DELIVER)) return 0;
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' are missing', 'Order::hasCreatedOrder()');
			return false;
		}
		if (!$day) $day = time ();
		if (!$day = myCheckDate($day)) {
			$this->setError(E_INVALID_DATA, '$day is not a valid day', 'Order::getCutoffDay()');
			return false;
		}
		$day = roundDate($day);
		$nextDay = $this->getNextDeliveryDay($day);
		if (!$route = $this->getRoute()) return $day;
		if (!$nextDeliveryDay = $route->getRouteDay($nextDay)) {
			$this->setError(E_NO_OBJECT, 'Route doesn\'t have any delivery days that line up with $day, which is ' . strftime('%c', $nextDay), 'Order::getCutoffDay()');
			return false;
		}
		if (!is_object($nextDeliveryDay)) return false;
		return $nextDay - $nextDeliveryDay->cutoffDay * T_DAY;
	}

	public function getRoute () {
		if (!$this->orderType & O_DELIVER) {
			$this->setError(E_WRONG_ORDER_TYPE, 'order is not a deliverable order!', 'Order::getRoute()');
			return false;
		}
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID specified in order ' . $this->orderID, 'Order::getRoute()');
			return false;
		}
		if (!$person = $this->getPerson()) {
			$this->setError(E_NO_OBJECT, 'person ' . $this->personID . ' doesn\'t exist in database', 'Order::getRoute()');
			return false;
		}
		if (!$person->personID) {
			$this->setError(E_NO_OBJECT, 'person ' . $this->personID . ' doesn\'t exist in database', 'Order::getRoute()');
			return false;
		}
		return $person->getRoute();
	}

	public function resetStars () {
		if (!is_null($this->stars)) {
			$this->stars = null;
			global $logger;
			// $logger->addEntry('Stars reset for order ' . $this->orderID, null, 'Order::resetStars()');
		}
	}

	public function calculateStars () {
		if (!is_null($this->stars)) {
			return true;
		}
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'No person specified yet for order ' . $this->orderID, 'Order::calculateStars()');
			return false;
		}
		if ($this->orderType & O_NO_STARS) {
			$this->setError(E_WRONG_ORDER_TYPE, 'stars don\'t apply to order ' . $this->orderID . ' (order type ' . $this->orderType . ', from recurring ' . ($this->recurringOrderID ? 'true' : 'false') . ')', 'Order::calculateStars()');
			$this->stars = STARS_NO_CALCULATE;
			return false;
		}
		$person = $this->getPerson();
		if ($this->stars != (int) $person->stars) {
			$this->stars = (int) $person->stars;
			// global $logger;
			// $logger->addEntry('Set stars for order ' . $this->orderID . ' to ' . $this->stars, null, 'Order::calculateStars()');
		}
		$this->clearError();
		return true;
	}

	public function getStars () {
		return $this->stars;
	}

	public function getDiscount () {
		return $this->discount;
	}

	// TODO: CONSISTENCY: spin this off into getTotalOrdered and getTotalDelivered, like OrderItem.
	// alternatively, combine OrderItem's getTotal[Ordered|Delivered] into one method
	public function getTotal ($calculateDiscount = true, $totalType = 'ordered') {
		if (!$this->personID || !$this->orderID) {
			$this->setError(E_NO_OBJECT_ID, 'personID ' . $this->personID . ' or orderID ' . $this->orderID . ' is missing', 'Order::getTotal()');
			return false;
		}
		$orderSubtotal = 0;
		$orderHST = 0;
		$orderPST = 0;
		foreach ($this->orderItems as $thisOrderItem) {
			if (!$thisOrderItem->checkQuantity()) {
				$this->orderItems[$thisOrderItem->itemID]->delete();
				unset($this->orderItems[$thisOrderItem->itemID]);
			} else {
				switch ($totalType) {
					case 'delivered':
						$lineSubtotal = $thisOrderItem->getSubtotalDelivered();
						break;
					case 'permanent':
						if ($this->orderType & O_TEMPLATE && $thisOrderItem->permanent) $lineSubtotal = $thisOrderItem->getSubtotalOrdered();
						else $lineSubtotal = 0;
						break;
					case 'ordered':
					default:
						$lineSubtotal = $thisOrderItem->getSubtotalOrdered();
				}
				// // echo 'line subtotal for ' . $thisOrderItem->itemID . ': ' . $lineSubtotal;
				$orderSubtotal += $lineSubtotal;
				if ($thisOrderItem->tax & TAX_HST) $orderHst += $lineSubtotal;
				if ($thisOrderItem->tax & TAX_PST) $orderPst += $lineSubtotal;
			}
		}
		$orderSubtotal1 = $orderSubtotal;
		$orderSubtotal = round($orderSubtotal * (100 - $this->discount) / 100 * (100 - ($this->stars > 0 ? $this->stars : 0)) / 100, 2);
		$discount = $orderSubtotal1 - $orderSubtotal;
		$orderHst = round($orderHST * (100 - $this->discount) / 100 * (100 - ($this->stars > 0 ? $this->stars : 0)) / 100 * $this->hst * 0.01, 2);
		$orderPst = round($orderPST * (100 - $this->discount) / 100 * (100 - ($this->stars > 0 ? $this->stars : 0)) / 100 * $this->pst * 0.01, 2);
		$person = $this->getPerson();
		if ($this->shipping && $this->shippingType && ($this->orderType & O_DELIVER) && $person->canDeliver()) {
			$orderShipping = round($this->shipping / 100 * ($this->shippingType & N_NET ? $orderSubtotal : ($orderSubtotal + $orderHst + $orderPst)), 2);
		} else $orderShipping = 0;
		if ($payType = $this->getPayType()) {
			$payType->surcharge = $this->surcharge;
			$payType->surchargeType = $this->surchargeType;
			$orderSurcharge = $payType->getSurcharge($payType->surchargeType & N_NET ? $orderSubtotal : ($orderSubtotal + $orderHst + $orderPst));
		} else $orderSurcharge = 0;
		$this->total = ($orderSubtotal + $orderHst + $orderPst + $orderSurcharge + $orderShipping);
		return array ('net' => $orderSubtotal, 'discount' => $discount, 'hst' => $orderHst, 'pst' => $orderPst, 'surcharge' => $orderSurcharge, 'shipping' => $orderShipping, 'gross' => $this->total);
	}

	public function cancel () {
		if ($this->dateDelivered) {
			$this->setError(E_ORDER_DELIVERED, 'order ' . $this->orderID . ' already delivered', 'Order::cancel()');
			return false;
		}
		if (!$this->orderID || !$this->personID) {
			$this->__construct();
			return true;
		}
		if ($this->dateCanceled) return true;
		if ($this->dateCompleted) {
			$totals = $this->getTotal();
			global $db;
			$db->start('cancelOrder' . $this->orderID);
			if (!$this->createJournalEntry('cancel', $totals['gross'], 0 - $totals['hst'], 0 - $totals['pst'])) {
				$db->rollback('cancelOrder' . $this->orderID);
				return false;
			}
			// $this->dateToDeliver = null;
			$this->dateCanceled = time();
			if (!$this->save()) {
				$db->rollback('cancelOrder' . $this->orderID);
				return false;
			}
			$db->commit('cancelOrder' . $this->orderID);
			global $logger;
			$logger->addEntry('Canceled order ' . $this->orderID, null, 'Order::cancel()');
			$this->clearError();
			return true;
		} else {
			return $this->delete();
		}
	}

	public function checkout ($dateAction = A_IGNORE, $specificDate = null) {
		if (!$this->validate()) return false;
		$person = $this->getPerson();
		if (!$person) {
			$this->setError(E_NO_OBJECT, 'no person for order ' . $this->orderID, 'Order::checkout()');
			return false;
		}
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'attempted to complete order ' . $this->orderID . ' for inactive person ' . $this->personID, 'Order::checkout()');
			return false;
		}
		if (!count($this->orderItems)) {
			$this->setError(E_ORDER_EMPTY, 'can\'t complete empty order ' . $this->orderID, 'Order::checkout()');
			return false;
		}
		if ($this->dateCompleted) {
			$this->setError(E_ORDER_COMPLETED, 'order ' . $this->orderID . ' already completed on ' . Date::human($this->dateCompleted), 'Order::checkout()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'attempted to check out order ' . $this->orderID . ' with type ' . $this->orderType, 'Order::checkout()');
			return false;
		}
		//if (!$this->setPayTypeID($payTypeID)) return false;
		if (!$this->recurringOrderID && $this->stars != STARS_NO_CALCULATE) {
			$this->calculateStars();
		}
		global $db, $config;
		// checks each item and finalises its price
		$this->clearInactiveItems();
		foreach ($this->orderItems as $thisItemID => $thisItem) {
			$this->orderItems[$thisItem->itemID]->checkout();
		}
		$totals = $this->getTotal();
		$minOrder = $person->getMinOrder();
		if ($totals['net'] < $minOrder) {
			$this->setError(E_ORDER_TOO_SMALL, $totals['net'] . ' for order ' . $this->orderID . ' is smaller than minimum amount of ' . $minOrder, 'Order::checkout()');
			return false;
		}
		// $this->subtotal = $totals['net'];
		if ($payType = $this->getPayType()) {
			$fig = ($payType->surchargeType & N_GROSS ? ($totals['gross']) : $totals['net']);
			$this->surcharge = $payType->getSurchargeTier($fig);
			$this->surchargeType = $payType->surchargeType & (N_ALL - N_TIER);
		}
		global $config, $db;
		$route = $this->getRoute();
		$nextDeliveryDay = $this->getNextDeliveryDay(null, false);
		$dateToDeliver = $this->getNextDeliveryDay();
		// logError('next delivery day ' . $db->cleanDate($nextDeliveryDay) . ', date to deliver ' . $db->cleanDate($dateToDeliver));
		if ($dateAction == A_KEEP && myCheckDate($this->dateToDeliver)) $this->dateToDeliver = myCheckDate($this->dateToDeliver);
		else {
			switch ($dateAction) {
				case A_DENY:
					if ($dateToDeliver > $nextDeliveryDay) {
						$this->setError(E_NOT_WITHIN_DELIVERY_CUTOFF, Date::human($dateToDeliver) . ' is too close to delivery day ' . Date::human($nextDeliveryDay), 'Order::checkout()');
						return false;
					}
					else $this->dateToDeliver = $nextDeliveryDay;
					break;
				case A_DEFER:
					$this->dateToDeliver = $dateToDeliver;
					global $logger;
					$logger->addEntry('Deferring dateToDeliver for order ' . $this->orderID . ' to ' . Date::human($this->dateToDeliver), null, 'Order::checkout()');
					break;
				case A_IGNORE:
				default:
					$this->dateToDeliver = $nextDeliveryDay;
					$logger->addEntry('Ignoring cutoffDay; setting dateToDeliver for order ' . $this->orderID . ' to ' . Date::human($this->dateToDeliver), null, 'Order::checkout()');
			}
		}
		$t = 'order' . $this->orderID;
		$db->start($t);
		// on rollback, don't forget to nullify $this->dateCompleted
		$q = 'UPDATE orders SET recurringOrderID = ' . ($this->recurringOrderID ? 'true' : 'false') . ', dateToDeliver = \'' . $db->cleanDate($this->dateToDeliver) . '\', notes = \'' . $db->cleanString($this->notes) . '\', surcharge = ' . ((float) $this->surcharge ? (float) $this->surcharge : 'null') . ', surchargeType = ' . ((int) $this->surchargeType ? (int) $this->surchargeType : 'null') . ', shipping = ' . ((float) $this->shipping ? (float) $this->shipping : 'null') . ', shippingType = ' . ((int) $this->shippingType ? (int) $this->shippingType : 'null');
		switch ($this->stars) {
			case STARS_NO_CALCULATE:
				$q .= ', stars = 0';
				break;
			case null:
				$q .= ', stars = null';
				break;
			default:
				$q .= ', stars = ' . $this->stars;
		}
		$q .= ' WHERE orderID = ' . (int) $this->orderID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on update of dateToDeliver et al for order ' . $this->orderID, 'Order::checkout()');
			$db->rollback($t);
			$this->dateToDeliver = null;
			return false;
		}
		$db->commit($t);
		global $logger;
		$logger->addEntry('Updated dateToDeliver (' . Date::human($this->dateToDeliver) . '), stars (' . $this->stars . ') et al for order ' . $this->orderID, null, 'Order::checkout()');
		$this->clearError();
		return true;
	}

	public function complete () {
		global $logger;
		// you know, I really should make this atomic. Properties that get modified are:
		//   dateToDeliver, payTypeID, dateCompleted
		if ($this->dateCompleted) {
			$this->setError(E_ORDER_COMPLETED, 'order ' . $this->orderID . ' already completed on ' . Date::human($this->dateCompleted), 'Order::complete()');
			return false;
		}
		if (!$this->validate()) {
			return false;
		}
		// only purchases and sales can be completed; recurring orders and supplier stocks must use their own methods to close down
		$person = $this->getPerson();
		if (!$person) {
			$this->setError(E_NO_OBJECT, 'no person for order ' . $this->orderID, 'Order::complete()');
			return false;
		}
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'attempted to complete order ' . $this->orderID . ' for inactive person ' . $person->personID, 'Order::complete()');
			return false;
		}
		if (!count($this->orderItems)) {
			$this->setError(E_ORDER_EMPTY, 'can\'t complete empty order ' . $this->orderID, 'Order::complete()');
			return false;
		}
		if (!$this->dateToDeliver && $this->orderType & O_DELIVER) {
			$this->dateToDeliver = $this->getNextDeliveryDay();
		}
		if ($this->orderType & O_CSA) {
			if (!count($this->getCsaItems())) {
				$this->setError(E_WRONG_ORDER_TYPE, 'Could not complete a CSA order without any CSA items on it', 'Order::complete()');
				return false;
			}
		}
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'attempted to complete order ' . $this->orderID . ' which is actually ' . $this->orderType, 'Order::complete()');
			return false;
		}
		global $db, $config;
		/*if (!$this->setPayTypeID($payTypeID)) {
			return false;
		}*/
		// from now on, we put in a dateToDeliver when the order has been checked out,
		// and if an order doesn't have a dateToDeliver, it doesn't get completed
		/* if (!$this->dateToDeliver) {
			if (!$route = $person->getRoute()) {
				$this->setError(E_NO_OBJECT, 'couldn\'t create route for checking delivery day on order ' . $this->orderID, 'Order::complete()');
				return false;
			}
			$this->dateToDeliver = $route->getNextDeliveryDay(null, false);
		} */
		$this->dateCompleted = time();
		// return false if this order's total is smaller than the group's minimum order
		$t = 'order' . $this->orderID;
		$db->start($t);
		/* if ($customCancelsRecurring || (is_null($customCancelsRecurring) && $person->customCancelsRecurring)) {
			if (!$db->query('SELECT orderID FROM orders WHERE personID = ' . $person->personID . ' && fromRecurringOrder && dateTodeliver = "' . $this->dateToDeliver . '"')) {
				// echo 'CCC';
				$this->setError(E_DATABASE, 'Order::complete(): on checking for recurring orders for the sake of cancelling recurring order');
				$db->rollback($t);
				return false;
			}
			if ($orderData = $db->getRow()) {
				$recurringOrder = new Order ($orderData['orderID']);
				if (!$recurringOrder->cancel()) {
					$this->setError($recurringOrder->getError(), $recurringOrder->getErrorDetail());
					$db->rollback($t);
					return false;
				}
			}
		} */
		foreach ($this->orderItems as $v) {
			$v->checkout();
		}
		$totals = $this->getTotal();
		// on rollback, don't forget to nullify $this->dateCompleted
		if (!$this->recurringOrderID && $this->stars != STARS_NO_CALCULATE) {
			$this->calculateStars();
		}
		$q = 'UPDATE orders SET dateCompleted = \'' . $db->cleanDate($this->dateCompleted) . '\', dateToDeliver = ' . ($this->dateToDeliver ? '\'' . $db->cleanDate($this->dateToDeliver) . '\'' : 'null') . ', surcharge = ' . ((int) $this->surcharge ? (int) $this->surcharge : 'null') . ', surchargeType = ' . ((int) $this->surchargeType ? (int) $this->surchargeType : 'null') . ', notes = \'' . $db->cleanString($this->notes) . '\', shipping = ' . ((float) $this->shipping ? (float) $this->shipping : 'null') . ', shippingType = ' . ((int) $this->shippingType ? (int) $this->shippingType : 'null');
		switch ($this->stars) {
			case STARS_NO_CALCULATE:
				$q .= ', stars = -1';
				break;
			case null:
				$q .= ', stars = null';
				break;
			default:
				$q .= ', stars = ' . $this->stars;
		}
		$q .= ' WHERE orderID = ' . (int) $this->orderID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on update of dateCompleted et al for order ' . $this->orderID, 'Order::complete()');
			$db->rollback($t);
			$this->dateCompleted = null;
			return false;
		}
		/*$payType = new PayType ($this->payTypeID);
		if ($payType = $this->getPayType()) {
			$fig = ($payType->surchargeType & N_GROSS ? $totals['gross'] : $totals['net']);
			$this->surcharge = $payType->getSurchargeTier($fig);
			$this->surchargeType = $payType->surchargeType & (N_ALL - N_TIER);
			$surcharge = $payType->getSurcharge($fig);
		} else $surcharge = 0;*/
		if (!$this->createJournalEntry('complete', 0 - $totals['gross'], $totals['hst'], $totals['pst'])) {
			// echo 'EEE';
			$db->rollback($t);
			$this->dateCompleted = null;
			return false;
		}
		if (!$this->recurringOrderID && $this->stars != STARS_NO_CALCULATE) {
			$person->recent = 1;
			$person->save();
		}
		$db->commit($t);
		global $logger;
		$logger->addEntry('Updated dateCompleted (' . Date::human($this->dateCompleted) . '), stars (' . $this->stars . ') et al for order ' . $this->orderID, null, 'Order::complete()');
		$this->clearError();
		return true;
	}

	public function deliver ($orderItems = null) {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID (' . $this->orderID . ') or personID (' . $this->personID . ') are not specified. Perhaps it hasn\'t been completed?', 'Order::deliver()');
			return false;
		}
		if (!is_array($orderItems) && !is_null($orderItems)) {
			$this->setError(E_INVALID_DATA, $orderItems . ' is not an array of quantities for order ' . $this->orderID, 'Order::deliver()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'cannot deliver recurring order ' . $this->orderID . '; use Order::replicate() and deliver that instead', 'Order::deliver()');
			return false;
		}
		if ($this->dateDelivered) {
			$this->setError(E_ORDER_DELIVERED, 'order ' . $this->orderID . ' already delivered on ' . Date::human($this->dateDelivered), 'Order::deliver()');
			return false;
		}
		if ($this->dateCanceled) {
			$this->setError(E_ORDER_CANCELED, 'order ' . $this->orderID . ' already canceled on ' . Date::human($this->dateCanceled), 'Order::deliver()');
			return false;
		}
		if (!$this->dateCompleted) {
			$this->setError(E_ORDER_NOT_COMPLETED, 'cannot deliver uncompleted order ' . $this->orderID, 'Order::deliver()');
			return false;
		}
		global $db;
		$db->start('order' . $this->orderID);
		if (!is_null($orderItems)) {
			foreach ($orderItems as $thisID => $thisQuantity) {
				if (!array_key_exists($this->orderItems[$thisID])) {
					$newItem = new OrderItem;
					// oh bad! I'm not using the constructor function
					// TODO: make this check for valid items
					$newItem->orderID = $this->orderID;
					$newItem->itemID = $thisID;
					$newItem->orderType = $this->orderType;
					$this->orderItems[$thisID] = $newItem;
				}
				if (is_int($thisQuantity)) {
					if (!$this->orderItems[$thisID]->deliver($orderItems[$thisID])) {
						// unset($this->orderItems[$thisID]);
						$db->rollback('order' . $this->orderID);
						$this->setError($this->orderItems[$thisID]->getError(), 'on order ' . $this->orderID . ', orderItem ' . $thisID . ' returned error ' . $GLOBALS['errorCodes'][$this->orderItems[$thisID]->getError()] . ' (' . $this->orderItems[$thisID]->getErrorDetail() . ')', 'Order::deliver()');
						return false;
					}
				}
			}
		} else {
			foreach ($this->orderItems as $thisOrderItem) {
				$this->orderItems[$thisOrderItem->itemID]->deliver($thisOrderItem->quantityOrdered);
			}
		}
		$oldTotals = $this->getTotal();
		$newTotals = $this->getTotal('delivered');
		$diffTotals = array ();
		$diffTotals['net'] = $oldTotals['net'] - $newTotals['net'];
		$diffTotals['hst'] = $oldTotals['hst'] - $newTotals['hst'];
		$diffTotals['pst'] = $oldTotals['pst'] - $newTotals['pst'];
		$diffTotals['surcharge'] = $oldTotals['surcharge'] - $newTotals['surcharge'];
		$diffTotals['total'] = $oldTotals['total'] - $newTotals['total'];
		$this->dateDelivered = $db->cleanDate(time());
		if ($this->save()) {
			if ($diffTotals['net']) {
				if (!$this->createJournalEntry('deliver', ($diffTotals['net'] + $diffTotals['surcharge']), 0 - $diffTotals['hst'], 0 - $diffTotals['pst'])) {
					$db->rollback('order' . $this->orderID);
					return false;
				}
			}
		} else {
			$db->rollback('order' . $this->orderID);
			return false;
		}
		$db->commit('order' . $this->orderID);
		global $logger;
		$logger->addEntry('Marked order ' . $this->orderID . ' as delivered', null, 'Order::deliver()');
		$this->clearError();
		return true;
	}

	public function createJournalEntry ($journalEntryType, $subtotal, $hst = null, $pst = null, $surcharge = null, $shipping = null) {
		if (!(float) $subtotal) {
			$this->setError(E_INVALID_DATA, 'subtotal has to be at least something (called from order ' . $this->orderID . ')', 'Order::createJournalEntry()');
			return false;
		}
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID for order ' . $this->orderID, 'Order::createJournalEntry()');
			return false;
		}
		global $db, $config;
		switch ($journalEntryType) {
			case 'complete':
				$notesPrefix = 'Total from ';
				$notesHstPrefix = 'HST from ';
				$notesPstPrefix = 'PST from ';
				break;
			case 'deliver':
				$notesPrefix = 'Adjustments from ';
				$notesHstPrefix = 'HST adjustments from ';
				$notesPstPrefix = 'PST adjustments from ';
				break;
			case 'cancel':
				$notesPrefix = 'Cancellation of ';
				$notesHstPrefix = 'HST from cancellation of ';
				$notesPstPrefix = 'PST from cancellation of ';
				break;
			default:
				$this->setError(E_INVALID_DATA, 'invalid payTypeID; if you\'re trying to add a payment, use Order::addPayment() instead', 'Order::createJournalEntry()');
				return false;
		}
		$db->start('journalEntry');
		$journalEntryInfo = array (
			'personID' => $this->personID,
			'orderID' => $this->orderID,
			'amount' => $subtotal,
			'dateCreated' => $this->dateCompleted,
			'notes' => $notesPrefix . ($this->label ? $this->label . ' - ' : null) . ($this->recurringOrderID ? 'recurring ' : '') . 'order # ' . $this->orderID . ((float) $surcharge ? '(surcharge of ' . money_format(NF_MONEY, $surcharge) . ' included)' : null),
			'payTypeID' => PAY_ACCT
		);
		$journalEntry = new JournalEntry ($journalEntryInfo);
		if ($journalEntry->save() === false) {
			$this->setError($journalEntry->getError(), 'journalEntry returned error ' . $GLOBALS['errorCodes'][$journalEntry->getError()] . ' (' . $journalEntry->getErrorDetail() . ')', 'Order::createJournalEntry()');
			$db->rollback('journalEntry');
			return false;
		}
		if (isset($this->journalEntries)) $this->journalEntries[$journalEntry->journalEntryID] = $journalEntry;
		if ($journalEntryType != 'pay') {
			$hst = round((float) $hst, 2);
			if ($hst) {
				$journalEntryHstInfo = array (
					'personID' => $config['hstAccountID'],
					'orderID' => $this->orderID,
					'amount' => $hst,
					'dateCreated' => $this->dateCompleted,
					'notes' => $notesHstPrefix . ($this->label ? $this->label . ' - ' : null) . ($this->recurringOrderID ? 'recurring ' : '') . 'order # ' . $this->orderID,
					'payTypeID' => PAY_ACCT
				);
				// print_r($journalEntryHstInfo);
				$journalEntryHst = new JournalEntry ($journalEntryHstInfo);
				if ($journalEntryHst->save() === false) {
					// echo 'NNN';
					$this->setError($journalEntryHst->getError(), 'journalEntry for HST returned error ' . $GLOBALS['errorCodes'][$journalEntryHst->getError()] . ' (' . $journalEntryHst->getErrorDetail() . ')', 'Order::createJournalEntry()');
					if (isset($this->journalEntries)) unset($this->journalEntries[$journalEntry->journalEntryID]);
					$db->rollback('journalEntry');
					return false;
				}
				if (isset($this->journalEntries)) $this->journalEntries[$journalEntryHst->journalEntryID] = $journalEntryHst;
			}
			$pst = round((float) $pst, 2);
			if ($pst) {
				$journalEntryPstInfo = array (
					'personID' => $config['pstAccountID'],
					'orderID' => $this->orderID,
					'amount' => $pst,
					'dateCreated' => $this->dateCompleted,
					'notes' => $notesPstPrefix . ($this->label ? $this->label . ' - ' : null) . ($this->recurringOrderID ? 'recurring ' : '') . 'order # ' . $this->orderID,
					'payTypeID' => PAY_ACCT
				);
				$journalEntryPst = new JournalEntry ($journalEntryPstInfo);
				if ($journalEntryPst->save() === false) {
					// echo 'OOO';
					$this->setError($journalEntryPst->getError(), 'journalEntry for PST returned error ' . $GLOBALS['errorCodes'][$journalEntryPst->getError()] . ' (' . $journalEntryPst->getErrorDetail() . ')', 'Order::createJournalEntry()');
					$db->rollback('journalEntry');
					if (isset($this->journalEntries)) {
						unset($this->journalEntries[$journalEntry->journalEntryID]);
						if (isset($journalEntryHst)) unset($this->journalEntries[$journalEntryHst->journalEntryID]);
					}
					return false;
				}
				if (isset($this->journalEntries)) $this->journalEntries[$journalEntryPst->journalEntryID] = $journalEntryPst;
			}
		}
		$this->clearError();
		$db->commit('journalEntry');
		/* global $logger;
		$logger->addEntry('Created journalEntry for order ' . $this->orderID, null, 'Order::createJournalEntry()'); */
		return true;
	}

	public function addCredit ($amount, $creditType = 'credit', $reason = null, $payTypeID = PAY_ACCT, $txnID = null) {
		global $logger;
		$logger->addEntry('txnID in addCredit ' . $txnID);
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' is missing', 'Order::addCredit()');
			return false;
		}
		$person = $this->getPerson();
		if ((int) $payTypeID && !$person->canUsePayType($payTypeID) && $payTypeID != PAY_ACCT) {
			$this->setError(E_INVALID_DATA, 'Person cannot use payTypeID', 'Order::addCredit()');
			return false;
		}
		if (!$this->dateCompleted) {
			$this->setError(E_ORDER_NOT_COMPLETED, 'cannot add a credit until order ' . $this->orderID . ' is completed', 'Order::addCredit()');
			return false;
		}
		if ($this->orderType & O_TEMPLATE) {
			$this->setError(E_WRONG_ORDER_TYPE, 'cannot put a payment on order ' . $this->orderID . ', cuz it\'s of type ' . $this->orderType, 'Order::addPayment()');
			return false;
		}
		$amount = round($amount, 2);
		if (!$amount || $amount < 0) {
			$this->setError(E_INVALID_DATA, 'amount ' . $amount . ' is either negative, zero, or not a number (order ' . $this->orderID . ')', 'Order::addPayment()');
			return false;
		}
		if (($this->orderType & O_DIR) == O_IN) $amount = 0 - $amount;
		$journalEntries = $this->getJournalEntries();
		$balance = $this->getBalance();
		if ($balance < 0) {
			$journalEntry = new JournalEntry (array (
				'personID' => $this->personID,
				'orderID' => $this->orderID,
				'amount' => min(array(round($amount, 2), abs($balance))),
				'notes' => ($creditType == 'payment' ? 'Payment on ' : 'Credit on ') . ($this->label ? $this->label . ' - ' : null) . (($this->orderType & O_DIR) == O_IN ? 'purchase ' : '') . 'order # ' . $this->orderID . ($reason ? ' (reason: ' . $reason . ')' : null),
				'payTypeID' => $payTypeID,
				'txnID' => $txnID
			));
			if (!$journalEntry->save()) {
				$this->setError($journalEntry->getError(), 'journalEntry returned error ' . $GLOBALS['errorCodes'][$journalEntry->getError()] . ' (' . $journalEntry->getErrorDetail() . ')');
				return false;
			}
			if (isset($this->journalEntries)) $this->journalEntries[$journalEntry->journalEntryID] = $journalEntry;
			$amount += $balance;
		} else return false;
		if ($amount > 0) {
			$journalEntry = new JournalEntry (array (
				'personID' => $this->personID,
				'amount' => $amount,
				'notes' => 'credit applied to account after paying outstanding ' . (($this->orderType & O_DIR) == O_IN ? 'purchase ' : '') . 'order # ' . $this->orderID
			));
			if (!$journalEntry->save()) {
				$this->setError($journalEntry->getError(), 'journalEntry returned error ' . $GLOBALS['errorCodes'][$journalEntry->getError()] . ' (' . $journalEntry->getErrorDetail() . ')');
				return false;
			}
		}
		/* global $logger;
		$logger->addEntry('Added credit ' . $journalEntry->journalEntryID . ' of amount ' . $journalEntry->amount . ' to order ' . $this->orderID, null, 'Order::addCredit()'); */
		return true;
	}

	public function addPayment ($amount, $payTypeID = PAY_CHEQUE, $txnID = null) {
		global $logger;
		$logger->addEntry('txnID in addPayment ' . $txnID);
		if (is_null($payTypeID)) {
			$this->setError(E_INVALID_DATA, 'No payTypeID', 'Order::addPayment()');
			return false;
		}
		$person = $this->getPerson();
		if (!$person->canUsePayType($payTypeID)) {
			$this->setError(E_INVALID_DATA, 'Cannot use payTypeID for this person', 'Order::addPayment()');
			return false;
		}
		if ($payTypeID == PAY_ACCT) {
			$this->setError(E_INVALID_DATA, 'Can\'t use account to make a payment', 'Order::addPayment()');
			return false;
		}
		return $this->addCredit($amount, 'payment', null, $payTypeID, $txnID);
	}

	public function getJournalEntries ($prevBalance = false) {
		if (!$this->orderID) {
			$this->setError(E_NO_OBJECT_ID, 'No orderID', 'Order::getJournalEntries()');
			return false;
		}
		//if (isset($this->journalEntries)) return $this->journalEntries;
		global $db;
		if (!$db->query('SELECT * FROM journalEntry WHERE orderID = ' . $this->orderID . ' ORDER BY journalEntryID')) {
			$this->setError(E_DATABASE, 'on query for order ' . $this->orderID, 'Order::getJournalEntries()');
			return false;
		}
		$journalEntries = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$journalEntries[$r->v('journalEntryID')] = $r;
		}
		foreach ($journalEntries as $thisID => $thisData) {
			$journalEntries[$thisID] = new JournalEntry($thisData);
		}
		if ($prevBalance && count($journalEntries)) {
			reset($journalEntries);
			$firstJournalEntry = current($journalEntries);
			if (!$db->query('SELECT * FROM journalEntry WHERE journalEntryID = (SELECT MAX(journalEntryID) FROM journalEntry WHERE journalEntryID < ' . $firstJournalEntry->journalEntryID . ' AND personID = ' . $this->personID . ')')) {
				$this->setError(E_DATABASE, 'on query for balance before order ' . $this->orderID, 'Order::getJournalEntries()');
				return false;
			}
			if ($r = $db->getRow(F_RECORD)) {
				$prevBalance = new JournalEntry ($r);
				$journalEntries[$r->v('journalEntryID')] = $prevBalance;
			} else {
				$prevBalance = new JournalEntry ();
				$prevBalance->label = 'Starting balance';
				$journalEntries[0] = $prevBalance;
			}
			ksort($journalEntries);
		}
		$this->journalEntries = $journalEntries;
		// no longer returns false on empty journal entries
		return $journalEntries;
	}

	public function getBalance () {
		if (!$this->orderID) {
			$this->setError(E_NO_OBJECT_ID, 'No orderID', 'Order::getJournalEntries()');
			return false;
		}
		$journalEntries = $this->getJournalEntries();
		$balance = 0;
		foreach ($journalEntries as $v) {
			$balance += $v->amount;
		}
		return $balance;
	}

	public function getOrderItemsInTree () {
		if (!count($this->orderItems)) return array ();
		global $ItemMapper;
		$args = func_get_args();
		array_unshift($args, $this->orderItems);
		global $logger;
		return call_user_func_array(array($ItemMapper, 'insertOrderItems'), $args);
	}

/*	public function closeRecurring ($dateCompleted, $dateResume = null) {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' are missing', 'Order::closeRecurring()');
			return false;
		}
		if (!($this->orderType & O_TEMPLATE)) {
			$this->setError(E_WRONG_ORDER_TYPE, 'Cannot close recurring order ' . $this->orderID . ' if it\'s NOT ACTUALLY A RECURRING ORDER!!!', 'Order::closeRecurring()');
			return false;
		}
		if (!$dateCompleted = $this->checkDate($dateCompleted)) {
			$this->setError(E_INVALID_DATA, $dateCompleted . ' is not a valid date', 'Order::closeRecurring()');
			return false;
		}
		if ($dateResume) {
			if (!$dateResume = $this->checkDate($dateResume)) $dateResume = null;
		}
		$this->dateCompleted = $this->roundDate($dateCompleted);
		if (strtotime($dateResume) > strtotime($dateCompleted)) $this->dateResume = $this->roundDate($dateResume);
		else $this->dateResume = null;
		global $db;
		if (!$db->query('UPDATE orders SET dateCompleted = \'' . $db->cleanDate($this->dateCompleted) . '\', dateResume = ' . ($this->dateResume ? '\'' . $db->cleanDate($this->dateResume) . '\'' : 'NULL') . ' WHERE orderID = ' . (int) $this->orderID, true)) {
			$this->setError(E_DATABASE, 'on update of order ' . $this->orderID, 'Order::closeRecurring()');
			return false;
		}
		global $logger;
		$logger->addEntry('Set recurring order ' . $this->orderID . ' to ' . Date::human($this->dateCompleted) . ($this->dateResume ? ' (resumes on ' . Date::human($this->dateResume) . ')' : null), null, 'Order::closeRecurring()');
		$this->clearError();
	}*/

	public function replicate ($dateStart = null, $dateEnd = null, $checkForDuplicates = true) {
		if ($dateStart) $dateStart = Date::round($dateStart, T_DAY);
		if ($dateEnd) $dateEnd = Date::round($dateEnd, T_DAY) + T_DAY - 1;
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' are missing', 'Order::replicate()');
			return false;
		}
		if (!($this->orderType & O_TEMPLATE)) {
			$this->setError(E_WRONG_ORDER_TYPE, 'cannot create new order from order ' . $this->orderID . ' of type ' . $this->orderType, 'Order::replicate()');
			return false;
		}
		if (!$person = $this->getPerson()) {
			$this->setError(E_NO_OBJECT, 'Could not create person ' . $this->personID . ' from order ' . $this->orderID . '.', 'Order::replicate');
			return false;
		}
		if (!$person->personID) {
			$this->setError(E_NO_OBJECT, 'Couldn\'t create person ' . $this->personID . ' from order ' . $this->orderID . '. Perhaps they don\'t exist?', 'Order::replicate()');
			return false;
		}
		if (!$person->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'attempted to create a new recurring order from template ' . $this->orderID . ' for inactive person ' . $person->personID, 'Order::replicate()');
			return false;
		}
		global $db, $config, $dayNames;
		if ($this->orderType & O_DELIVER) {
			if (!$route = $person->getRoute()) {
				$this->setError(E_NO_OBJECT, 'No route attached to person ' . $this->personID . ' for order ' . $this->orderID, 'Order::replicate()');
				return false;
			}
		}
		$this->adjustDates();
		// changed behaviour to not include cutoff day
		global $logger;
		// this sequence, commented out, would only check to see if this was supposed to arrive on the next delivery day.
		/* $nextRouteDeliveryDay = $route->getNextDeliveryDay();
		if ($nextDeliveryDay != $nextRouteDeliveryDay) { */
		// new behaviour is to see if it's supposed to arrive next week
		// TODO: CONSISTENCY: Do I round dates in other functions? I ain't doing it here...
		if ($dateStart && $dateEnd) {
			$nextDeliveryDay = $this->getNextDeliveryDay($dateStart, false, false);
			if ($nextDeliveryDay > $dateEnd) {
				$this->setError(E_NO_RECURRING_FOR_NEXT_DELIVERY_DAY, 'delivery day for stdg order ' . $this->orderID . ' is on ' . Date::human($nextDeliveryDay) . ' but next week is from ' . Date::human($dateStart) . ' to ' . Date::human($dateEnd), 'Order::replicate()');
				return false;
			}
			/*$dateToDeliver = $this->getNextDeliveryDay($dateStart, false, false);
			if ($dateToDeliver < $nextDeliveryDay) {
				$this->setError(E_NOT_WITHIN_DELIVERY_CUTOFF, 'next delivery day for stdg order ' . $this->orderID . ' is ' . Date::human($nextDeliveryDay) . ' but delivery day within given period is ' . (round(($nextDeliveryDay - $dateToDeliver) / T_DAY)) . ' days earlier (' . Date::human($dateToDeliver) . '), searching in period ' . Date::human($dateStart) . ' to ' . Date::human($dateEnd), 'Order::replicate()');
				return false;
			} else if ($dateToDeliver > $dateEnd) {
				$this->setError(E_NO_RECURRING_FOR_NEXT_DELIVERY_DAY, 'delivery day for stdg order ' . $this->orderID . ' is on ' . Date::human($nextDeliveryDay) . ' but next week is from ' . Date::human($dateStart) . ' to ' . Date::human($dateEnd), 'Order::replicate()');
				return false;
			} else $nextDeliveryDay = $dateToDeliver;*/
		} else $nextDeliveryDay = $this->getNextDeliveryDay();
		$nextDeliveryDay = roundDate($nextDeliveryDay);
		global $logger;
		if (!$nextDeliveryDay || ($this->dateCompleted && $nextDeliveryDay > $this->dateCompleted && (!$this->dateResume || $nextDeliveryDay < $this->dateResume))) {
			$this->setError(E_NO_MORE_RECURRING, 'no more recurring order dates for order ' . $this->orderID, 'Order::replicate()');
			return false;
		}
		// checks to see if there are any other recurring orders booked
		if ($this->hasCreatedOrder($nextDeliveryDay) && $checkForDuplicates) {
			$this->setError(E_RECURRING_ALREADY_ORDERED, 'This order already has a created order for it, #'.$this->hasCreatedOrder($nextDeliveryDay), 'Order::replicate()');
			return false;
		}
		// doesn't accommodate out-of-schedule orders
		// TODO: change to make it so recurring orders are never created if their template is below the min order?
		$totals = $this->getTotal();
		if ($totals['net']) {
			$newRecurring = new Order;
			$newRecurring->personID = $this->personID;
			$newRecurring->label = $this->label;
			$newRecurring->notes = $this->notes;
			$newRecurring->setRecurringOrderID($this->orderID);
			$newRecurring->start($this->orderType ^ O_TEMPLATE);
			$newRecurring->surcharge = $this->surcharge;
			$newRecurring->surchargeType = $this->surchargeType;
			$newRecurring->shipping = $this->shipping;
			$newRecurring->shippingType = $this->shippingType;
			foreach ($this->orderItems as $thisOrderItem) {
				global $logger;
				if (!$newRecurring->addQuantity($thisOrderItem->itemID, $thisOrderItem->quantityOrdered, $thisOrderItem->unitPrice, $thisOrderItem->tax, $thisOrderItem->getDiscount())) {
					$logger->addEntry('failed on item ' . $thisOrderItem->itemID . ' (' . $thisOrderItem->label . ')!');
				}
			}
			$newRecurring->stars = 0;
			// if ((int) $payTypeID) $this->setPayTypeID($payTypeID);
			$newRecurring->setDateToDeliver($nextDeliveryDay);
			// if (!$newRecurring->complete($this->payTypeID)) {
			if (!$newRecurring->complete()) {
				$this->setError($newRecurring->getError(), 'new recurring returned error ' . $GLOBALS['errorCodes'][$newRecurring->getError()] . ' (' . $newRecurring->getErrorDetail() . ')', 'Order::replicate()');
				// echo '111111';
				return false;
			}
			foreach ($this->orderItems as $v) {
				if (!$v->permanent) $this->deleteItem($v->itemID);
			}
			global $logger;
			$logger->addEntry('Created recurring order ' . $newRecurring->orderID . ' from template ' . $this->orderID . ' for person ' . $this->personID, null, 'Order::replicate()');
			$this->clearError();
			$this->deliveryDays = array ();
			return $newRecurring;
		} else {
			$this->setError(E_ORDER_EMPTY, 'Recurring order is zero; no point in creating', 'Order::replicate()');
			return false;
		}
	}

	public function setPayTypeID ($payTypeID = null) {}

	/*public function setPayTypeID ($payTypeID = null) {
		$payTypes = getPayTypes();
		$payTypeIDs = array_keys($payTypes);
		if ($payTypeID && !in_array((int) $payTypeID, $payTypeIDs)) {
			$this->setError(E_INVALID_DATA, '$payTypeID ' . $payTypeID . ' is not a valid payment type for order ' . $this->orderID, 'Order::setPayTypeID()');
			return false;
		}
		if (!$payTypeID && $this->payTypeID && !in_array((int) $this->payTypeID, $payTypeIDs)) {
			$this->setError(E_INVALID_DATA, '$this->payTypeID ' . $this->payTypeID . ' is not a valid payment type for order ' . $this->orderID, 'Order::setPayTypeID()');
			return false;
		}
		if ($payTypeID) $this->payTypeID = $payTypeID;
		if (!$this->payTypeID) {
			$person = $this->getPerson();
			$this->payTypeID = $person->getPayTypeID();
		}
		if (!$this->payTypeID) $this->payTypeID = null;
		else if (!isset($payTypes[(int) $this->payTypeID])) {
			$this->setError(E_INVALID_DATA, '$this->payTypeID ' . $this->payTypeID . ' is not a valid payment type for order ' . $this->orderID, 'Order::setPayTypeID()');
			return false;
		}
		if ($this->payTypeID && !$payTypes[$this->payTypeID]->isActive()) {
			$this->setError(E_INVALID_DATA, $this->payTypeID . ' is not active', 'Order::setPayTypeID()');
			return false;
		}
		$this->surcharge = $payTypes[$this->payTypeID]->surcharge;
		$this->surchargeType = $payTypes[$this->payTypeID]->surchargeType;
		return true;
	}*/

	public function getPayType () {}

	/*public function getPayType () {
		if (is_object($this->payType)) {
			if (get_class($this->payType) == 'PayType') return $this->payType;
		}
		if ((int) $this->payTypeID) {
			if (!$this->payType = new PayType ((int) $this->payTypeID)) {
				$this->payType = null;
				$this->payTypeID = null;
				return false;
			} else return $this->payType;
		} else return false;
	}*/

	public function hasCreatedOrder ($day = null, $idOnly = true) {
		if (!$this->orderID || !$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'either orderID ' . $this->orderID . ' or personID ' . $this->personID . ' are missing', 'Order::hasCreatedOrder()');
			return false;
		}
		if (!($this->orderType & O_TEMPLATE)) {
			$this->setError(E_WRONG_ORDER_TYPE, 'cannot create new order from order ' . $this->orderID . ' of type ' . $this->orderType, 'Order::hasCreatedOrder()');
			return false;
		}
		// if (!$day) $day = time ();
		if ($day) {
			if (!$day = myCheckDate($day)) {
				$this->setError(E_INVALID_DATA, '$day is not a valid day', 'Order::hasCreatedOrder()');
				return false;
			}
			$day = roundDate($day);
		}
		// $deliveryDay = $this->getNextDeliveryDay($day, false);
		global $db;
		if (!$db->query('SELECT orderID FROM orders WHERE (orderType & ' . O_BASE . ') = ' . ($this->orderType & O_BASE ^ O_TEMPLATE) . ' AND recurringOrderID = ' . $this->orderID . ' AND dateToDeliver ' . ($day ? ' = \'' . $db->cleanDate($day) . '\'': ' > NOW()') . ' AND (!dateCanceled OR dateCanceled IS NULL) AND (!dateDelivered OR dateDelivered IS NULL)')) {
			$this->setError(E_DATABASE, 'on search for sales to be delivered ' . Date::human($day) . ' and templated from recurring order ' . $this->orderID, 'Order::hasCreatedOrder()');
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) return false;
		else return ($idOnly ? $r->v('orderID') : new Order ((int) $r->v('orderID')));
	}
}

?>
