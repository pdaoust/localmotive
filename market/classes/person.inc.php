<?php

// this is the hierarchical version of Person. Whee!

$logger->addEntry('WSOD defining PersonMapper');

class PersonMapper extends MarketPrototype {
	private static $_tree;

	public function validate () { return true; }

	public static function getTree () {
		if (!is_array(self::$_tree)) {
			$root = new Person (1);
			$this->_tree = $root->getTree();
		}
		return self::$_tree;
	}

	public static function getPeople ($people) {
		if (!is_array($people)) {
			$this->setError(E_INVALID_DATA, '$people must be an array of personIDs', 'PersonMapper::getPeople()');
			return false;
		}
		foreach ($people as $k => $v) {
			if (!(int) $v) unset($people[$k]);
			else $people[$k] = (int) $v;
		}
		if (!$db->query('SELECT * FROM person WHERE personID IN (' . implode(',', $people) . ')')) {
			$this->setError(E_DATABASE, 'couldnt get people', 'ItemMapper::getPeople()');
			return false;
		}
		$people = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$people[$r->v('personID')] = new Person ($r);
		}
		return $people;
	}
}

$logger->addEntry('WSOD singletoning PersonMapper');

$PersonMapper = new PersonMapper;

$logger->addEntry('WSOD defining Person');


class Person extends MarketTree {
	public $personID;
	public $dateCreated;
	public $lastLogin;
	public $active = true;
	public $personType;
	private $deliverySlot;
	public $contactName;
	public $groupName;
	public $email;
	private $password;
	public $privateKey;
	public $addresses = array ();
	public $phone;
	private $routeID;
	public $customCancelsRecurring;
	public $canCustomOrder;
	public $payTypeIDs;
	public $payTypeID;
	public $compost = false;
	public $minOrder;
	public $minOrderDeliver;
	public $bulkDiscount;
	public $bulkDiscountQuantity;
	public $shipping;
	public $shippingType;
	public $maxStars;
	public $deposit;
	public $credit;
	// public $balance = 0;
	public $stars = 0;
	public $recent = false;
	public $bins = 0;
	public $coldpacks = 0;
	public $bottles = 0;
	public $notes;
	public $description;
	public $website;
	public $image;
	private $sessionID;
	private $cookieID;
	protected $sortFields = array ('sortOrder', 'contactName', 'name', 'groupName', 'email', 'city', 'balance', 'deliverySlot', 'routeID', 'route');
	public $cc;
	public $txnID;
	public $pad = false;
	public $depth;
	private $_payTypes;

	public function __construct ($personInfo = null, $password = null) {
		if (!$personInfo) $personInfo = null;
		global $personTypes, $paymentTypes, $logger;
		switch (gettype($personInfo)) {
			case 'integer':
				global $db;
				$q = 'SELECT * FROM person WHERE personID = ' . $personInfo;
				if (!$db->query($q)) {
					$this->setError(E_DATABASE, 'on getting person ' . $personInfo, 'Person::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'no person ' . $personInfo, 'Person::__construct()');
					return false;
				}
				$personInfo = $r;
			case 'array':
				$personInfo = new Record ($personInfo);
			case 'object':
				if (get_class($personInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$personInfo is a ' . get_class($personInfo) . ' rather than the expected Record', 'Person::__construct()');
					return false;
				}
				global $logger;
				if ($personInfo->e('addresses')) {
					$addresses = $personInfo->v('addresses');
					if (is_array($addresses)) {
						foreach ($addresses as $k => $v) {
							if (!is_object($v)) unset($addresses[$k]);
							else if (get_class($v) != 'Address') unset($addresses[$k]);
						}
						$personInfo->s('addresses', $addresses);
					} else $personInfo->s('addresses', array ());
				}
				if (!$personInfo->e('addresses') && $personInfo->v('personID')) {
					$q = 'SELECT * FROM address WHERE personID = ' . $personInfo->v('personID');
					global $logger, $db;
					if (!$db->query($q)) {
						$this->setError(E_DATABASE, 'on getting addresses for person ' . $personInfo->v('personID'), 'Person::__construct()');
						return false;
					}
					$addresses = array ();
					while ($r = $db->getRow(F_RECORD)) {
						$r->s('personID', $personInfo->v('personID'));
						$addresses[$r->v('addressID')] = new Address ($r);
					}
					global $logger;
					$personInfo->s('addresses', $addresses);
				}
				foreach ($this as $k => $v) {
					$v = $personInfo->v($k);
					if (!is_null($v)) {
						switch ($k) {
							case 'active':
							case 'compost':
							case 'recent':
							case 'pad':
								$this->$k = $personInfo->b($k);
								break;
							case 'customCancelsRecurring':
							case 'canCustomOrder':
								$this->$k = (is_null($v) ? null : $personInfo->b($k));
								break;
							case 'dateCreated':
							case 'lastLogin':
								$this->$k = ($v ? null : strtotime($v));
								break;
							case 'password':
								$this->password = base64_decode($v);
								break;
							case 'payTypeIDs':
								$this->payTypeIDs = (is_null($v) ? null : explode(',', $v));
								foreach ($this->payTypeIDs as $k => $v) {
									if (!$v) unset($this->payTypeIDs[$k]);
								}
								break;
							case 'cc':
							case 'txnID':
								if ($personInfo->e('personID')) {
									$key = getKey();
									$this->$k = Cryptastic::decrypt($v, $key, true);
									if (!preg_match('/^[0-9a-zA-Z\*]+$/', $this->$k)) Cryptastic::decrypt($v, $key);
									if (!preg_match('/^[0-9a-zA-Z\*]+$/', $this->$k)) $this->$k = null;
								} else $this->$k = $v;
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
				break;
			case 'null':
			default:
				$this->personID = null;
				$this->dateCreated = null;
				$this->lastLogin = null;
				$this->active = true;
				$this->nodePath = null;
				$this->sortOrder = null;
				$this->personType = null;
				$this->deliverySlot = null;
				$this->failedLoginAttempts = 0;
				$this->contactName = null;
				$this->groupName = null;
				$this->groupID = null;
				$this->email = null;
				$this->password = null;
				$this->privateKey = null;
				$this->addresses = array ();
				$this->phone = null;
				$this->routeID = null;
				$this->customCancelsRecurring = null;
				$this->canCustomOrder = null;
				$this->payTypeIDs = null;
				$this->payTypeID = null;
				$this->compost = false;
				$this->minOrder = null;
				$this->minOrderDeliver = null;
				$this->bulkDiscount = null;
				$this->bulkDiscountQuantity = null;
				$this->shipping = null;
				$this->shippingType = null;
				$this->maxStars = null;
				$this->deposit = null;
				$this->credit = null;
				// $this->balance = 0;
				$this->stars = 0;
				$this->recent = 0;
				$this->bins = 1;
				$this->coldpacks = 0;
				$this->bottles = 0;
				$this->notes = null;
				$this->description = null;
				$this->website = null;
				$this->image = null;
				$this->sessionID = null;
				$this->cookieID = null;
				$this->cc = null;
				$this->txnID = null;
				$this->pad = false;
				$this->_payTypes = null;
		}
		$this->clearError();
		return true;
	}

	public function newObject ($objectData = null) {
		return new Person ($objectData);
	}

	public function validate () {
		global $personTypes, $paymentTypes;
		$errorFields = array ();
		$this->personID = (int) $this->personID ? (int) $this->personID : false;
		$this->dateCreated = $this->checkDate($this->dateCreated);
		if (!$this->dateCreated && !is_null($this->dateCreated)) $this->dateCreated = null;
		$this->active = (bool) $this->active;
		$this->personType = abs((int) $this->personType);
		$this->personType = $this->personType & P_ALL;
		$this->contactName = trim ($this->contactName);
		$this->groupName = trim ($this->groupName);
		if (!$this->contactName && !$this->groupName) {
			$errorFields[] = 'contactName';
			$errorFields[] = 'groupName';
		}
		$this->email = trim ($this->email);
		if (!$this->email) $this->email = null;
		if ($this->email) {
			if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) $errorFields[] = 'email';
		}
		// if (!trim($this->password) && $this->personType & P_CUSTOMER)) $errorFields[] = 'password';
		$needsAddress = $this->personType & (P_CUSTOMER | P_SUPPLIER | P_DEPOT | P_PRIVATE);
		if ($this->personType & P_PRIVATE) $this->privateKey = trim($this->privateKey);
		else $this->privateKey = null;
		if (!count($this->addresses) && $needsAddress) $errorFields[] = 'address';
		$this->phone = trim($this->phone);
		if (!$this->phone && $needsAddress) $errorFields[] = 'phone';
		if (!$this->routeID) $this->deliverySlot = null;
		$this->customCancelsRecurring = (is_null($this->customCancelsRecurring) ? null : (bool) $this->customCancelsRecurring);
		$this->canCustomOrder = (is_null($this->canCustomOrder) ? null : (bool) $this->canCustomOrder);
		// doesn't check for existence of payType
		if (!is_null($this->payTypeIDs)) {
			if (!is_array($this->payTypeIDs)) $this->payTypeIDs = array ($this->payTypeIDs);
			global $payTypeIDs;
			foreach ($this->payTypeIDs as $k => $v) {
				if ((int) $v) {
					if (!in_array((int) $v, $payTypeIDs)) unset($this->payTypeIDs[$k]);
					else $this->payTypeIDs[$k] = (int) $v;
				} else {
					if ($v == 0 || $v == '') $this->payTypeIDs[$k] = '';
					else unset($this->payTypeIDs[$k]);
				}
			}
		}
		$this->payTypeID = ($this->payTypeID ? (int) $this->payTypeID : null);
		$this->compost = $this->compost ? true : false;
		if (!is_null($this->minOrder)) $this->minOrder = round((float) $this->minOrder, 2);
		if (!is_null($this->minOrderDeliver)) $this->minOrderDeliver = round((float) $this->minOrderDeliver, 2);
		if (!is_null($this->bulkDiscount)) $this->bulkDiscount = round((float) $this->bulkDiscount, 2);
		if (!is_null($this->bulkDiscountQuantity)) $this->bulkDiscountQuantity = (int) $this->bulkDiscountQuantity;
		if (!is_null($this->shipping)) $this->shipping = round($this->shipping, 2);
		if (!is_null($this->shippingType)) $this->shippingType &= N_ALL;
		if (!is_null($this->maxStars)) $this->maxStars = (int) $this->maxStars;
		if (!is_null($this->deposit)) $this->deposit = round((float) $this->deposit, 2);
		if (!is_null($this->credit)) $this->credit = round((float) $this->credit, 2);
		//$this->balance = trim($this->balance);
		//if (!(is_numeric($this->balance) || !is_null($this->balance))) $errorFields[] = 'balance';
		//else $this->balance = round((float) $this->balance, 2);
		// $this->stars = trim($this->stars);
		$this->stars = (int) $this->stars;
		$this->recent = (int) $this->recent;
		// $this->bins = trim($this->bins);
		if (!(int) ($this->bins) && $this->bins) $errorFields[] = 'bins';
		else $this->bins = (int) $this->bins;
		// $this->coldpacks = trim($this->coldpacks);
		if (!is_int($this->coldpacks) && $this->coldpacks) $errorFields[] = 'coldpacks';
		else $this->coldpacks = (int) $this->coldpacks;
		// $this->bottles = trim($this->bottles);
		if (!is_int($this->bottles) && $this->bottles) $errorFields[] = 'bottles';
		else $this->bottles = (int) $this->bottles;
		$this->notes = trim($this->notes);
		$this->description = ($this->personType & (P_SUPPLIER + P_DEPOT + P_DELIVERER) ? trim($this->description) : null);
		$this->website = trim($this->website);
		if ($this->website) {
			if (substr($this->website, 0, 4) != 'http') $this->website = 'http://' . $this->website;
			if (!filter_var($this->website, FILTER_VALIDATE_URL)) $this->website = null;
		}
		$this->website = ($this->personType & (P_SUPPLIER + P_CATEGORY) ? $this->website : null);
		if (!($this->personType & P_CUSTOMER)) {
			// get rid of meaningless information if they aren't a customer
			$this->customCancelsRecurring = false;
			$this->stars = 0;
			$this->compost = false;
			$this->bins = 0;
			$this->coldpacks = 0;
			$this->bottles = 0;
		}
		$this->txnID = trim($this->txnID);
		if ($this->cc && $this->txnID) {
			$this->cc = (string) $this->cc;
			if (strlen($this->cc) < 16 || strlen($this->cc) > 16) {
				$errorFields[] = 'cc';
				$this->cc = null;
			} else {
				$this->cc = substr($this->cc, 0, 1) . '***********' . substr($this->cc, -4);
				$this->payTypeID = PAY_CC;
			}
		} else {
			$this->cc = null;
			$this->txnID = null;
			$this->pad = false;
		}
		$this->pad = (bool) $this->pad;
		// didn't validate content of boolean or enum fields
		if (count($errorFields)) {
			// $errorFields[] = 'Person validate';
			global $logger;
			$this->setError(E_INVALID_DATA, $errorFields, 'Person::validate()');
			return false;
		}
		$this->clearError();
		return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		// $this->setDeliverySlot($this->deliverySlot);
		global $db, $logger;
		$t = 'savePerson' . $this->personID;
		$db->start($t);
		if (!$this->personID) {
			if (!$db->query('INSERT INTO person (contactName) VALUES (\'temp\')')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Person::save()');
				return false;
			}
			$this->personID = $db->getLastID();
		}
		$q = 'UPDATE person SET ';
		$q .= 'dateCreated = ' . ($this->dateCreated ? '\'' . $db->cleanDate($this->dateCreated) . '\'' : 'NOW()');
		$q .= ', active = ' . (is_null($this->active) ? 'null' : ($this->active ? 'true' : 'false'));
		$q .= ', nodePath = ' . (is_null($this->nodePath) ? 'NULL' : '"'.$this->getPathString().'"');
		$q .= ', sortOrder = '. (is_null($this->sortOrder) ? 'NULL' : (int) $this->sortOrder);
		$q .= ', personType = ' . (int) $this->personType;
		if ($this->routeID && !$this->deliverySlot) {
			if (!$db->query('SELECT MAX(deliverySlot) AS "lastSlot" FROM person WHERE routeID = ' . (int) $this->routeID)) {
				$this->setError(E_DATABASE, 'on check of max deliverySlot for route ' . $this->routeID, 'Person::save()');
				$db->rollback($t);
				return false;
			}
			if ($r = $db->getRow(F_RECORD)) $lastSlot = $r->v('lastSlot');
			else $lastSlot = 0;
			$q .= ', deliverySlot = ' . ($lastSlot + 1);
		} else $q .= ', deliverySlot = ' . (is_null($this->deliverySlot) ? 'null' : (int) $this->deliverySlot);
		$q .= ', contactName = \'' . $db->cleanString($this->contactName) . '\'';
		$q .= ', groupName = \'' . $db->cleanString($this->groupName) . '\'';
		$q .= ', email = ' . (is_null($this->email) ? 'null' : '\'' . $db->cleanString($this->email) . '\'');
		$q .= ', password = ' . (is_null($this->password) ? 'null' : '\'' . $db->cleanString(base64_encode($this->password)) . '\'');
		$q .= ', privateKey = ' . ($this->privateKey ? '\'' . $db->cleanString($this->privateKey) . '\'' : 'null');
		$q .= ', phone = \'' . $db->cleanString($this->phone) . '\'';
		$q .= ', routeID = ' . ($this->routeID ? $this->routeID : 'null');
//		$q .= ', customCancelsRecurring = ' . (is_null($this->customCancelsRecurring) ? 'null' : ($this->customCancelsRecurring ? 'true' : 'false'));
		$q .= ', canCustomOrder = ' . (is_null($this->canCustomOrder) ? 'null' : ($this->canCustomOrder ? 'true' : 'false'));
		$q .= ', payTypeIDs = ' . (is_null($this->payTypeIDs) ? 'null' : '"' . implode(',', $this->payTypeIDs) . '"');
		$q .= ', payTypeID = ' . ($this->payTypeID ? $this->payTypeID : 'null');
		$q .= ', compost = ' . ($this->compost ? 'true' : 'false');
		$q .= ', minOrder = ' . (is_null($this->minOrder) ? 'null' : (float) $this->minOrder);
		$q .= ', minOrderDeliver = ' . (is_null($this->minOrderDeliver) ? 'null' : (float) $this->minOrderDeliver);
		$q .= ', bulkDiscount = ' . (is_null($this->bulkDiscount) ? 'null' : (float) $this->bulkDiscount);
		$q .= ', bulkDiscountQuantity = ' . (is_null($this->bulkDiscountQuantity) ? 'null' : (int) $this->bulkDiscountQuantity);
		$q .= ', shipping = ' . (is_null($this->shipping) ? 'null' : (float) $this->shipping);
		$q .= ', shippingType = ' . (is_null($this->shippingType) ? 'null' : (int) $this->shippingType);
		$q .= ', maxStars = ' . (is_null($this->maxStars) ? 'null' : (int) $this->maxStars);
		$q .= ', deposit = ' . (is_null($this->deposit) ? 'null' : (float) $this->deposit);
		$q .= ', credit = ' . (is_null($this->credit) ? 'null' : (float) $this->credit);
		$q .= ', stars = ' . (int) $this->stars;
		$q .= ', recent = ' . ($this->recent ? 'true' : 'false');
		// oop -- for new items, I have to figure out how to insert the delivery order properly
		$q .= ', bins = ' . (int) $this->bins;
		$q .= ', coldpacks = ' . (int) $this->coldpacks;
		$q .= ', bottles = ' . (int) $this->bottles;
		$q .= ', notes = \'' . $db->cleanString($this->notes) . '\'';
		$q .= ', description = ' . ($this->description ? '\'' . $db->cleanString($this->description) . '\'' : 'null');
		$q .= ', website = ' . ($this->website ? '\'' . $db->cleanString($this->website) . '\'' : 'null');
		$q .= ', sessionID = ' . ($this->sessionID ? '\'' . $db->cleanString($this->sessionID) . '\'' : 'null');
		$q .= ', cookieID = ' . ($this->cookieID ? '\'' . $db->cleanString($this->cookieID) . '\'' : 'null');
		if ($this->cc && $this->txnID) {
			$key = getKey();
			//$logger->addEntry('key for encrypting: '.md5($key));
			$cc = Cryptastic::encrypt($this->cc, $key, true);
			$txnID = Cryptastic::encrypt($this->txnID, $key, true);
		}
		$q .= ', cc = ' . ($this->cc ? '\'' . $db->cleanString($cc) . '\'' : 'null');
		$q .= ', txnID = ' . ($this->txnID ? '\'' . $db->cleanString($txnID) . '\'' : 'null');
		$q .= ', pad = ' . ($this->pad ? 'true' : 'false');
		$q .= ' WHERE personID = ' . $this->personID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on attempt to save person ' . $this->personID, 'Person::save()');
			$db->rollback($t);
			return false;
		}
		$addressSuccess = false;
		$error = 0;
		$errorDetail = array ();
		foreach ($this->addresses as $thisAddress) {
			$thisAddress->personID = $this->personID;
			if (!$thisAddress->save()) {
				$error |= $thisAddress->getError();
				$errorDetail[$thisIndex] = array ('error' => $thisAddress->getError(), 'errorDetail' => $thisAddress->getErrorDetail());
			}
		}
		if (count($errorDetail)) {
			$this->setError($error, $errorDetail, 'Person::save()');
			$db->rollback($t);
			return false;
		}
		$this->clearError();
		$db->commit($t);
		global $logger;
		$logger->addEntry('Saved person ' . $this->personID, null, 'Person::save()');
		return true;
	}

	public function delete ($deleteChildren = false, $action = DEL_BOTH) {
		// doesn't delete messages or referrers
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Can\'t delete a person who doesn\'t exist!', 'Person::delete()');
			return false;
		}
		if (!$action) return true;
		global $db;
		$db->start('deletePerson' . $this->personID);
		if ($this->hasAssociatedData()) {
			if (!($action & DEL_TRASH)) {
				$db->rollback('deletePerson' . $this->personID);
				$this->setError(E_HAS_ASSOCIATED_DATA, 'person has journal entries or orders associated with it and was not flagged for trashing.', 'Person::delete()');
				return false;
			} else $hasAssociatedData = true;
		} else $hasAssociatedData = false;
		// don't want to orphan children for now; maybe I can figure out something better. For now, though, it never deletes children.
		if (!$this->deleteFromTree(false)) {
			$db->rollback('deletePerson' . $this->personID);
			return false;
		}
		if (!$this->setRoute(0)) {
			$db->rollback('deletePerson' . $this->personID);
			return false;
		}
		if (!$hasAssociatedData && $action & DEL_PURGE) {
			if (!$db->query('DELETE FROM person WHERE personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on delete of person ' . $this->personID, 'Person::delete()');
				$db->rollback('deletePerson' . $this->personID);
				return false;
			}
			if (!$db->query('DELETE FROM price WHERE personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on delete of prices for person ' . $this->personID, 'Person::delete()');
				$db->rollback('deletePerson' . $this->personID);
				return false;
			}
			if (!$db->query('DELETE orders, orderItem FROM orders LEFT JOIN orderItem ON orderItem.orderID = orders.orderID WHERE orders.personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on clear of standing/supplier orders', 'Person::delete()');
				$db->rollback('deletePerson' . $this->personID);
				return false;
			}
			if (!$db->query('UPDATE item SET supplierID = null WHERE supplierID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on clear of supplier associations for person ' . $this->personID, 'Person::delete()');
				$db->rollback('deletePerson' . $this->personID);
				return false;
			}
		}
		$db->commit('deletePerson'. $this->personID);
		global $logger;
		$logger->addEntry('Deleted person ' . $this->personID, null, 'Person::delete()');
		$this->__construct(null);
		return true;
	}

	public function getLabel ($oneOnly = false) {
		$label = array ();
		if (trim($this->contactName)) $label[] = trim($this->contactName);
		if (trim($this->groupName)) $label[] = trim($this->groupName);
		switch (count($label)) {
			case 0:
				return null;
			case 1:
				return reset($label);
			case 2:
				return ($oneOnly ? $label[0] : implode(', ', $label));
		}
	}

	public function hasAssociatedData () {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Can\'t check a non-existent empty person!', 'Person::hasAssociatedData()');
			return false;
		}
		global $db;
		if (!$db->query('SELECT * FROM journalEntry, orders WHERE journalEntry.personID = ' . $this->personID . ' AND orders.personID = ' . $this->personID . ' AND !(orders.orderType & ' . O_TEMPLATE . ')')) {
			$this->setError(E_DATABASE, 'on query for person ' . $this->personID, 'Person::hasAssociatedData():');
			return false;
		}
		if ($db->getRow()) return true;
		else return false;
	}

	public function hasAddresses ($addressType = AD_ALL) {
		$addressType = (int) $addressType & AD_ALL;
		if (!count($this->addresses)) return false;
		$addresses = 0;
		foreach ($this->addresses as $v) {
			if ($v->addressType & $addressType) $addresses ++;
		}
		return $addresses;
	}

	public function getOrders ($dateStart = null, $dateEnd = null, $orderType = array (O_SALE_EDITABLE, O_BASE), $recursive = false, $orderBy = 'dateToDeliver', $lostOnly = false, $openOnly = false, $completedOnly = false) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getOrders()');
			return false;
		}
		switch ($orderBy) {
			case 'dateCompleted':
			case 'dateDelivered':
			case 'orderID':
				break;
			default:
				$orderBy = 'dateToDeliver';
		}
		if (!is_array($orderType)) {
			$orderType = array ($orderType & O_ALL, O_BASE);
		} else {
			$orderType = array ($orderType[0] & O_ALL, $orderType[1] & O_ALL);
		}
		global $db;
		$dateStart = (int) $this->checkDate($dateStart);
		// $dateStart = roundDate($dateStart);
		$dateEnd = $this->checkDate($dateEnd);
		// if (!$dateEnd) $dateEnd = time();
		// $dateEnd = roundDate($dateEnd) + T_DAY - 1;
		// $dateEnd = $this->roundDate($dateEnd) - 1;
		// TODO: PERFORMANCE: I hafta figure out some way of retrieving the data AND the balance in one statement. Then I should remove the balance-calculating query from JournalEntry::__construct().
		$dateRange = null;
		if ($dateEnd) {
			$dateRange .= ' BETWEEN';
			if ($dateStart < $dateEnd) $dateRange .= ' \'' . $db->cleanDate($dateStart) . '\' AND \'' . $db->cleanDate($dateEnd) . '\'';
			else $dateRange .= ' \'' . $db->cleanDate($dateEnd) . '\' AND \'' . $db->cleanDate($dateStart) . '\'';
		} else $dateRange .= ' BETWEEN \'' . $db->cleanDate($dateStart) . '\' AND \'' . $db->cleanDate($dateStart + T_DAY - 1) . '\'';
		$q = 'SELECT * FROM orders WHERE personID';
		if ($recursive) $q .= ' IN ('.implode(',', $this->getNodePath()).')';
		else $q .= ' = ' . (int) $this->personID;
		if ($dateStart) $q .= ' AND (' . ($orderBy == 'orderID' ? 'dateToDeliver' : $orderBy) . ' ' . $dateRange . ')';
		if ($openOnly) $q .= ' AND ' . (!(($orderType[0] & $orderType[1]) & O_TEMPLATE) ? '(!dateCompleted OR dateCompleted IS NULL)' : null);
		else if ($completedOnly) $q .= ' AND ' . (!(($orderType[0] & $orderType[1]) & O_TEMPLATE) ? 'dateCompleted' : null);
		// $q .= ' AND ((dateCompleted' . $dateRange . ') OR ((!dateCompleted OR dateCompleted IS NULL) AND dateToDeliver AND dateStarted ' . $dateRange . '))';
		$q .= ' AND (orderType & ' . (int) $orderType[1] . ') = ' . (int) $orderType[0];
		if ($lostOnly) $q .= ' AND dateCompleted IS NULL AND (dateToDeliver < NOW() OR dateToDeliver IS NULL)';
		$q .= ' ORDER BY ' . $orderBy . (in_array($orderBy, array('dateToDeliver', 'dateDelivered')) ? ', dateCompleted' : null);
		// logError($q);
		if ($db->query($q, true)) {
			$orderData = array ();
			$orders = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$orderData[$r->v('orderID')] = $r;
			}
			foreach ($orderData as $v) {
				$thisOrder = new Order ($v);
				$orders[$thisOrder->orderID] = $thisOrder;
			}
			$this->clearError();
			return $orders;
		} else {
			$this->setError(E_DATABASE, 'on query for person ' . $this->personID, 'Person::getOrders()');
			return false;
		}
	}

	public function getUnpaidOrders () {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getUnpaidOrders()');
			return false;
		}
		global $db;
		if (!$db->query('SELECT orders.orderID, orders.personID, orders.orderType, SUM(journalEntry.amount) AS paidAmount FROM orders LEFT JOIN journalEntry ON orders.orderID = journalEntry.orderID GROUP BY orders.orderID HAVING (SUM(journalEntry.amount) IS NULL OR SUM(journalEntry.amount) < 0) AND orders.orderType & ' . O_BASE . ' = ' . O_SALE . ' AND orders.personID = ' . $this->personID)) {
			$this->setError(E_DATABASE, 'coudlnt get the list of unbalanced orders', 'Person::getUnpaidOrders()');
			return false;
		}
		$orders = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$orders[$r->v('orderID')] = $r;

		}
		foreach ($orders as $k => $v) {
			$orders[$k] = new Order ((int) $v->v('orderID'));
			$orders[$k]->paidAmount = $v->v('paidAmount');
		}
		reset($orders);
		return $orders;
	}

	public function getOrdersBefore ($orderID) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getOrders()');
			return false;
		}
		$orderID = (int) $orderID;
		if (!$orderID) return false;
		global $db;
		if (!$order = new Order ($orderID)) {
			$this->setError(E_NO_OBJECT, 'Could not bring up order ' . $orderID, 'Person::getOrdersBefore()');
			return false;
		}
		if (!$order->getDateToDeliver()) {
			$this->setError(E_INVALID_DATA, 'No dateToDeliver for order ' . $orderID, 'Person::getOrdersBefore()');
			return false;
		}
		if ($order->personID != $this->personID) {
			$this->setError(E_PERMISSION, 'Order ' . $order->orderID . ' does not belong to person ' . $this->personID, 'Person::getOrdersBefore()');
			return false;
		}
		if (!$db->query('SELECT MAX(dateToDeliver) FROM orders WHERE personID = ' . $this->personID . ' AND orderType & ' . O_DELIVER . ' AND dateCompleted AND dateToDeliver < "' . $db->cleanDate($order->getDateToDeliver()) . '"')) {
			$this->setError(E_DATABASE, 'When looking for previous order', 'Person::getOrdersBefore()');
			return false;
		}
		if (!$prevDate = $db->getRow(F_NUM)) $prevDate = false;
		else $prevDate = $this->checkDate($prevDate[0]);
		if (!$db->query('SELECT * FROM orders WHERE personID = ' . $this->personID . ' AND dateCompleted <= "' . $db->cleanDate($order->getDateToDeliver()) . '" AND !(orderType & ' . O_DELIVER . ')' . ($prevDate ? ' AND dateCompleted > "' . $db->cleanDate($prevDate) . '"' : null))) {
			$this->setError(E_DATABASE, 'When looking for orders in between', 'Person::getOrdersBefore()');
			return false;
		}
		$orders = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$orders[$r->v('orderID')] = $r;
		}
		foreach ($orders as $k => $v) {
			$orders[$k] = new Order ($v);
		}
		return $orders;
	}

	public function hasOpenOrder ($orderType = array(O_SALE, O_BASE), $idOnly = false) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'No personID', 'Person::hasOpenOrder()');
			return false;
		}
		if (!is_array($orderType)) {
			$orderType = array (($orderType & O_ALL), O_BASE);
		} else {
			$orderType = array (($orderType[0] & O_ALL), ($orderType[1] & O_ALL));
		}
		$q = 'SELECT MAX(orderID) AS orderID FROM orders WHERE personID = ' . (int) $this->personID . ' AND (orderType & ' . $orderType[1] . ') = ' . ($orderType[0] & $orderType[1]);
		if (!($orderType[0] & $orderType[1] & O_TEMPLATE)) $q .= ' AND (!dateCompleted OR dateCompleted IS NULL)';
		global $db;
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on check for orders of type ' . $orderType . ' for person', 'Person::hasOpenOrder()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) {
			if ($r->v('orderID')) {
				if (class_exists('Order') && !$idOnly) {
					$openOrder = new Order ((int) $r->v('orderID'));
					return $openOrder;
				} else return $r->v('orderID');
			}
		} else return false;
	}

	public function startOrder ($orderType = O_SALE_EDITABLE_DELIVER, $period = null) {
		if (!$this->isActive()) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'tried to start order for inactive person ' . $this->personID, 'Person::startOrder()');
			return false;
		}
		if (!$this->getRouteID() && $orderType & O_DELIVER) {
			$this->setError(E_NO_OBJECT_ID, 'person ' . $this->personID . ' not in a route', 'Person::startOrder()');
			return false;
		}
		$orderType &= O_ALL;
		global $logger;
		$newOrder = new Order ();
		$newOrder->personID = $this->personID;
		$newOrder->start($orderType, $period);
		$logger->addEntry('Started new order ' . $newOrder->orderID . ' for person ' . $this->personID, null, 'Person::startOrder()');
		return $newOrder;
	}

	public function setPassword ($newPassword) {
		$this->password = $this->encrypt($newPassword);
	}

	public function checkPassword ($password) {
		if ($this->encrypt($password) == $this->password) return true;
		else return false;
	}

	public function encrypt ($stringToEncrypt) {
		global $config;
		return md5(md5($config['encryptionKey'] . $stringToEncrypt));
	}

	public function logout () {
		// echo 'logging out... ';
		global $logger;
		$logger->addEntry('person ' . $this->personID . ' (' . $this->contactName . ') logged out', null, 'Person::logout()');
		sessionDefaults();
	}

	public function checkSession () {
		global $db;
		if (!$db->query('SELECT * FROM person WHERE email = \'' . $db->cleanString($_SESSION['username']) . '\' AND cookieID = \'' . $db->cleanString($_SESSION['cookieID']) . '\' AND sessionID = \'' . $db->cleanString(session_id()) . '\'')) {
			$this->setError(E_DATABASE, 'on check of session using username ' . $_SESSION['username'] . ', cookieID ' . $_SESSION['cookieID'] . ', sessionID ' . session_id(), 'Person::checkSession()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) {
			$this->setSession($r, false, false);
		}
		else $this->logout();
		$this->clearError();
	}

	public function setSession ($sessionInfo, $remember, $init = true) {
		// echo 'setting up session... ';
		// if (!$this->__construct($sessionInfo)) return false; don't think I really need that check
		if (!$this->personID) $this->__construct($sessionInfo);
		if (is_array($sessionInfo)) $sessionInfo = new Record ($sessionInfo);
		if (!is_object($sessionInfo)) {
			$this->setError(E_INVALID_DATA, '$sessionInfo is not an object', 'Person::setSession()');
			return false;
		}
		if (get_class($sessionInfo) != 'Record') {
			$this->setError(E_INVALID_DATA, '$sessionInfo is a ' . get_class($sessionInfo) . ' instead of a Record', 'Person::setSession()');
			return false;
		}
		$_SESSION['personID'] = $this->personID;
		$_SESSION['username'] = $this->email;
		$_SESSION['cookieID'] = $sessionInfo->v('cookieID');
		$_SESSION['loggedIn'] = true;
		unset($_SESSION['failedLoginAttempts']);
		if ($init) {
			global $db;
			if (!$db->query('UPDATE person SET sessionID = \'' . $db->cleanString(session_id()) . '\', cookieID = \'' . $db->cleanString($sessionInfo->v('cookieID')) . '\', lastLogin = NOW() WHERE personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on update of session data for person ' . $this->personID, 'Person::setSession()');
				return false;
			} else $this->lastLogin = time();
		}
		if ($remember) $this->updateCookie($sessionInfo->v('cookieID'), true);
		$this->clearError();
		return true;
	}

	public function updateCookie ($cookieID, $save) {
		// echo 'updating cookie... ';
		global $config;
		$_SESSION['cookieID'] = $cookieID;
		if ($save) {
			$cookie = serialize(array($_SESSION['username'], $cookieID));
			setcookie('localmotiveLogin', $cookie, time() + $config['loginPersistence'], '/');
		}
	}

	public function checkRemembered ($cookie) {
		// echo 'checking remembered cookie thing... ';
		// print_r($cookie);
		list ($username, $cookieID) = @unserialize($cookie);
		if (!$username || !$cookieID) {
			// echo 'missing data!';
			sessionDefaults();
			return;
		}
		global $db;
		if (!$db->query('SELECT * FROM person WHERE email = \'' . $db->cleanString($username) . '\' AND cookieID = \'' . $db->cleanString($cookieID) . '\'')) {
			$this->setError(E_DATABASE, 'on query for username ' . $username . ' and cookieID ' . $cookieID, 'Person::checkRemembered()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) {
			$this->setSession($r, true);
			return true;
		} else return false;
	}

	public function authenticate ($username, $password, $remember = false) {
		// echo 'authenticating... ';
		// we could probably throw some sort of E_WRONG_AUTHENTICATION_CREDENTIALS here
		/*if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Person::authenticate(): no personID yet');
			return false;
		}
		if ((trim($username) == trim($this->email)) && (trim($password) == trim($this->passwordClear))) {
			$this->clearError();
			$this->authenticated = true;
			return true;
		} else {
			$this->clearError();
			$this->authenticated = false;
			return false;
		}*/
		global $db, $config;
		if (isset($_SESSION['failedLoginAttempts'])) {
			if ($_SESSION['failedLoginAttempts'] >= $config['maxLoginAttempts'] && $_SESSION['lastLoginAttempt'] >= time() - $config['blockFailedLoginTime']) {
				$this->logout();
				$this->setError(E_TOO_MANY_FAILED_LOGINS, 'too many failed logins for username ' . $username . ' using password ' . $password, 'Person::authenticate()');
				return false;
			} else if ($_SESSION['lastLoginAttempt'] < time() - $config['blockFailedLoginTime']) {
				$_SESSION['failedLoginAttempts'] = 0;
			}
		}
		$username = trim($username);
		/* if (!ereg('^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$', $username)) {
			$this->setError(E_INVALID_DATA);
			return false;
		} */
		$passwordClear = $password;
		$password = $this->encrypt($password);
		$q = 'SELECT * FROM person WHERE email = \'' . $db->cleanString($username) . '\'';
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on grabbing of person info using username ' . $username . ' and password ' . $passwordClear, 'Person::authenticate()');
			$this->logout();
			return false;
		}
		$r = $db->getRow(F_RECORD);
		if (!$r || ($r->v('password') != base64_encode($password))) {
			if (isset($_SESSION['failedLoginAttempts'])) $_SESSION['failedLoginAttempts'] += 1;
			else $_SESSION['failedLoginAttempts'] = 1;
			$_SESSION['lastLoginAttempt'] = time();
			$this->logout();
			$this->setError($r ? E_LOGIN_CREDENTIALS_INCORRECT : E_NO_OBJECT, 'username ' . $username . ' doesn\'t exist, or password ' . $passwordClear . ' incorrect', 'Person::authenticate()');
			return false;
		}
		$this->__construct($r);
		if (!$this->isActive() || !$this->isInTree()) {
			if (isset($_SESSION['failedLoginAttempts'])) $_SESSION['failedLoginAttempts'] += 1;
			else $_SESSION['failedLoginAttempts'] = 1;
			$_SESSION['lastLoginAttempt'] = time();
			$this->logout();
			$this->setError(E_OBJECT_NOT_ACTIVE, 'person ' . $this->personID . ' (' . $this->contactName . ') not active', 'Person::authenticate()');
			return false;
		}
		$r->r['cookieID'] = md5(time());
		$this->clearError();
		$this->setSession($r, $remember);
		global $logger;
		$logger->addEntry('Logged in person ' . $this->personID . ' (' . $this->contactName . ')', null, 'Person::authenticate()');
		return true;
	}

	public function isAdmin () {
		if (!$this->personID) return false;
		if ($this->personType & (P_ADMIN + P_DEPOT + P_CATEGORY)) return true;
		if ($this->personID == 1) return true;
		return false;
	}

	public function canDeliver () {
		if (!$this->personID) return false;
		$route = $this->getRoute(false);
		if (!$route || !$route->active) return false;
		if (!$this->getAddresses(AD_SHIP)) return false;
		return true;
	}

	public function getMinOrder ($includeThis = true) {
		return $this->getProperty('minOrder', (bool) $includeThis);
	}

	public function getMinOrderDeliver ($includeThis = true) {
		return $this->getProperty('minOrderDeliver', (bool) $includeThis);
	}

	public function getBulkDiscount ($includeThis = true) {
		return $this->getProperty('bulkDiscount', (bool) $includeThis);
	}

	public function getBulkDiscountQuantity ($includeThis = true) {
		return $this->getProperty('bulkDiscountQuantity', (bool) $includeThis);
	}

	public function getShipping ($includeThis = true) {
		return $this->getProperty('shipping', (bool) $includeThis);
	}

	public function getShippingType ($includeThis = true) {
		return $this->getProperty('shippingType', (bool) $includeThis);
	}

	public function getMaxStars ($includeThis = true) {
		return $this->getProperty('maxStars', (bool) $includeThis);
	}

	public function getDeposit ($includeThis = true) {
		return $this->getProperty('deposit', (bool) $includeThis);
	}

	public function getCanCustomOrder ($includeThis = true) {
		return $this->getProperty('canCustomOrder', (bool) $includeThis);
	}

	public function canCustomOrder ($includeThis = true) {
		return $this->getCanCustomOrder((bool) $includeThis);
	}

	public function getCustomCancelsRecurring ($includeThis = true) {
		return $this->getProperty('customCancelsRecurring', (bool) $includeThis);
	}

	public function customCancelsRecurring ($includeThis = true) {
		return $this->getCustomCancelsRecurring((bool) $includeThis);
	}

	public function getCredit ($includeThis = true) {
		return (float) $this->getProperty('credit', (bool) $includeThis);
	}

	public function getPayTypeID ($includeThis = true) {
		$payTypeID = $this->getProperty('payTypeID', (bool) $includeThis);
		if ($payTypeID) return $payTypeID;
		else return null;
	}

	public function getPayTypeIDs ($includeThis = true) {
		$payTypeIDs = $this->getProperty('payTypeIDs', (bool) $includeThis);
		if (is_null($payTypeIDs)) return array ();
		$payTypeIDs = explode(',', $payTypeIDs);
		foreach ($payTypeIDs as $k => $v) {
			if (!$v) unset($payTypeIDs[$k]);
			else $payTypeIDs[$k] = (int) $v;
		}
		return $payTypeIDs;
	}

	public function getPayType ($includeThis = true) {
		if ($payTypeID = $this->getPayTypeID((bool) $includeThis)) return new PayType ($payTypeID);
		else return false;
	}

	public function getPayTypes ($amount = 0, $includeThis = true) {
		$amount = round($amount, 2);
		if (is_array($this->_payTypes) && $amount == 0) return $this->_payTypes;
		if ($payTypeIDs = $this->getPayTypeIDs((bool) $includeThis)) {
			$this->_payTypes = array ();
			foreach ($payTypeIDs as $v) {
				if ($payType = new PayType((int) $v)) {
					if ($payType->isActive()) $this->_payTypes[$v] = $payType;
				}
			}
			$payTypes = $this->_payTypes;
			if ($amount != 0) {
				if (isset($payTypes[PAY_CHEQUE])) unset($payTypes[PAY_CHEQUE]);
				if ($this->getBalance(true) <= $amount || $amount == -1) {
					if (isset($payTypes[PAY_ACCT])) unset($payTypes[PAY_ACCT]);
					if ($this->getPayTypeID() == PAY_ACCT) {
						if ($this->canUsePayType(PAY_CC)) $payTypeID = PAY_CC;
						else if ($this->canUsePayType(PAY_PAYPAL)) $payTypeID = PAY_PAYPAL;
					}
				}
				if (!isset($payTypeID) && ($this->canUsePayType($this->payTypeID) || $this->payTypeID == PAY_ACCT)) $payTypeID = $this->payTypeID;
				if ($payTypeID) $payTypes['default'] = &$payTypes[$payTypeID];
			}
			return $payTypes;
		} else return array ();
	}

	public function canUsePayType ($payTypeID, $includeThis = true) {
		if (!$payTypes = $this->getPayTypes(0, (bool) $includeThis)) return false;
		$payTypeID = (int) $payTypeID;
		if (!$payTypeID) return false;
		if (!in_array($payTypeID, array_keys($payTypes))) return false;
		if (!$payTypes[$payTypeID]->isActive()) return false;
		return true;
	}

	public function getRoute ($recursive = true, $includeThis = true) {
		global $logger;
		$routeID = $this->getRouteID($recursive, (bool) $includeThis);
		if (!$routeID) {
			$this->setError(E_NO_OBJECT, 'no routeID for person ' . $this->personID, 'Person::getRoute()');
			return false;
		}
		if ($route = new Route ((int) $routeID)) return $route;
		else return false;
	}

	public function getRouteID ($recursive = true, $includeThis = true) {
		if ($recursive) return $this->getProperty('routeID', (bool) $includeThis);
		else return $this->routeID;
	}

	public function getAddress ($addressID) {
		$addressID = (int) $addressID;
		if (!isset($this->addresses[$addressID])) {
			$this->setError(E_NO_OBJECT, 'No such addressID for this person', 'Person::removeAddress()');
			return false;
		} else return $this->addresses[$addressID];
	}

	public function getAddresses ($addressType = AD_ALL, $all = false) {
		$addressType = (int) $addressType & AD_ALL;
		$addresses = array ();
		foreach ($this->addresses as $v) {
			$match = 0;
			if ($all) {
				global $addressTypes;
				foreach ($addressTypes as $v2) {
					if ($addressType & $v2) $match |= $v->addressType & $v2;
				}
				if ($match != $addressType) $match = false;
			} else $match = $v->addressType & $addressType;
			if ($match) $addresses[$v->addressID] = $v;
		}
		return $addresses;
	}

	public function addAddress ($address) {
		if (is_object($address)) {
			if (get_class($address) != 'Address') {
				$this->setError(E_INVALID_DATA, 'given object is a ' . get_class($address) . ' rather than an Address', 'Person::addAddress()');
				return false;
			}
			if ($this->personID) $address->personID = $this->personID;
			else $address->personID = -1;
		}
		if (!$address->save()) {
			global $errorCodes;
			$this->setError(E_NO_OBJECT, 'attempt to save address object resulted in ' . $errorCodes[$address->getError()], 'Person::addAddress()');
			return false;
		}
		$this->addresses[$address->addressID] = $address;
	}

	public function removeAddress ($addressID) {
		if (!isset($this->addresses[$addressID])) {
			$this->setError(E_NO_OBJECT, 'No such addressID for this person', 'Person::removeAddress()');
			return false;
		}
		if ($this->addresses[$addressID]->delete()) {
			unset($this->addresses[$addressID]);
			return true;
		} else {
			global $errorCodes, $logger, $db;
			$errorCode = $this->addresses[$addressID]->getError();
			$this->setError($errorCode, 'Attempt to delete address ' . $db->cleanString($addressID) . ' resulted in ' . $errorCodes[$errorCode], 'Person::removeAddress()');
			return false;
		}
	}

	public function removeAddressesOfType ($addressType) {
		if (!($addressType & AD_ALL)) {
			$this->setError(E_INVALID_DATA, $addressType . ' is not a valid addressType', 'Person::removeAddressesOfType()');
			return false;
		}
		foreach ($this->addresses as $v) {
			if ($v->addressType & $addressType) $this->removeAddress($v->addressID);
		}
	}

	public function getDepot () {
		return $this->getParentOfType(P_DEPOT);
	}

	public function getCategory () {
		return $this->getParentOfType(P_CATEGORY);
	}

	public function getDeliverer () {
		return $this->getParentOfType(P_DELIVERER);
	}

	public function getDeliveryDays () {
		// should getNextDeliveryDay be in here instead of in Order?
		$route = $this->getRoute();
		if (!$route->routeID) {
			$this->setError(E_NO_ROUTE, 'no routeID for person ' . $this->personID, 'Person::getDeliveryDays()');
			return false;
		}
		return $route->getRouteDays();
	}

	public function getNextDeliveryDay () {
		$route = $this->getRoute();
		if (!$route->routeID) {
			$this->setError(E_NO_ROUTE, 'no routeID for person ' . $this->personID, 'Person::getNextDeliveryDay()');
			return false;
		}
		return $route->getNextDeliveryDay();
	}

	public function setRoute ($routeID) {
		$routeID = (int) $routeID;
		if (!$this->personID) {
			$this->routeID = $routeID;
			$this->deliverySlot = null;
			return true;
		}
		global $db;
		$t = 'setRoute' . $this->personID;
		$db->start($t);
		if (!$db->query('SELECT routeID, deliverySlot FROM person WHERE personID = ' . (int) $this->personID)) {
			$this->setError(E_DATABASE, 'on checking current values for person ' . $this->personID, 'Person::setRoute()');
			$db->rollback($t);
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'person ' . $this->personID . ' not in database', 'Person::setRoute()');
			$db->rollback($t);
			return false;
		}
		$this->routeID = $r->v('routeID');
		$this->deliverySlot = $r->v('deliverySlot');
		if ($this->routeID != $routeID) {
			if ($this->routeID) {
				if (!$db->query('UPDATE person SET deliverySlot = deliverySlot - 1 WHERE routeID = ' . (int) $this->routeID . ' AND deliverySlot > ' . $this->deliverySlot)) {
					$this->setError(E_DATABASE, 'on closing of gap in old route ' . $this->routeID . ' for person ' . $this->personID, 'Person::setRoute()');
					$db->rollback($t);
					return false;
				}
			}
			if (!$routeID) {
				if (!$db->query('UPDATE person SET deliverySlot = null, routeID = null WHERE personID = ' . (int) $this->personID)) {
					$this->setError(E_DATABASE, 'on removing person ' . $this->personID . ' from route ' . $this->routeID, 'Person::setRoute()');
					$db->rollback($t);
					return false;
				}
				$this->routeID = null;
				$this->deliverySlot = null;
			} else {
				if (!$db->query('SELECT MAX(deliverySlot) AS lastSlot FROM person WHERE routeID = ' . (int) $routeID)) {
					$this->setError(E_DATABASE, 'on finding last delivery slot for new route ' . $routeID . ' for person ' . $this->personID, 'Person::setRoute()');
					$db->rollback($t);
					return false;
				}
				if ($r = $db->getRow(F_RECORD)) $lastSlot = $r->v('lastSlot');
				else $lastSlot = 0;
				if (!$db->query('UPDATE person SET routeID = ' . (int) $routeID . ', deliverySlot = ' . ($lastSlot + 1) . ' WHERE personID = ' . (int) $this->personID)) {
					$this->setError(E_DATABASE, 'on moving person ' . $this->personID . ' to route ' . $routeID . ', slot ' . ($lastSlot + 1), 'Person::setRoute()');
					$db->rollback($t);
					return false;
				}
				$this->routeID = $routeID;
				$this->deliverySlot = $lastSlot + 1;
			}
			$db->commit($t);
			// not gonna bother logging that; the server logs would balloon out
		} else $db->rollback($t);
		return true;
	}

	public function getDeliverySlot () {
		return $this->deliverySlot;
	}

	public function isLastSlot () {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::isLastSlot()');
			return false;
		}
		if (!$this->deliverySlot) {
			$this->setError(E_INVALID_DATA, 'no delivery slot for person ' . $this->personID, 'Person:isLastSlot()');
			return false;
		}
		if ($this->getLastSlot() == $this->deliverySlot) return true;
		return false;
	}

	public function getLastSlot () {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getLastSlot()');
			return false;
		}
		if (!$this->deliverySlot || !$this->routeID) {
			$this->setError(E_INVALID_DATA, 'no delivery slot ' . $this->deliverySlot . ' or route ' . $this->routeID . ' for person ' . $this->personID, 'Person:getLastSlot()');
			return false;
		}
		global $db;
		if (!$db->query('SELECT MAX(deliverySlot) as maxDeliverySlot FROM person WHERE routeID = ' . $this->routeID)) {
			$this->setError(E_DATABASE, 'on query for person ' . $this->personID, 'Person::getLastSlot()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) return (int) $r->v('maxDeliverySlot');
		return false;
	}

	public function setDeliverySlot ($newSlot, $changeMode = MODE_ABSOLUTE) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no person ID', 'Person::setDeliverySlot()');
			return false;
		}
		if (!$this->routeID) {
			$this->setError(E_INVALID_DATA, 'no routeID for person ' . $this->personID, 'Person::setDeliverySlot()');
			return false;
		}
		global $db;
		$db->start('setDeliverySlot' . $this->personID);
		if (!$db->query('SELECT MAX(deliverySlot) AS lastSlot FROM person WHERE routeID = ' . (int) $this->routeID)) {
			$this->setError(E_DATABASE, 'on selection of max delivery slot for route ' . $this->routeID . ' for person ' . $this->personID, 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) $lastSlot = $r->v('lastSlot');
		else $lastSlot = 0;
		if (!$db->query('SELECT deliverySlot, routeID FROM person WHERE personID = ' . (int) $this->personID)) {
			$this->setError(E_DATABASE, 'on check for existence and value of deliverySlot for person ' . $this->personID, 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'person ' . $this->personID . ' doesn\'t exist yet. You should save it before you try to change its deliverySlot.', 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			return false;
		}
		$this->deliverySlot = ($r->v('deliverySlot') ? (int) $r->v('deliverySlot') : null);
		$this->routeID = ($r->v('routeID') ? (int) $r->v('routeID') : null);
		switch ($changeMode) {
			case MODE_ABSOLUTE:
				$newSlot = (int) $newSlot;
				break;
			case MODE_RELATIVE:
				$newSlot = $this->deliverySlot + $newSlot;
				if ($newSlot < 1) $newSlot = null;
		}
		if ($newSlot < 1 || $newSlot > $lastSlot || $newSlot == $this->deliverySlot) {
			$this->setError(E_INVALID_DATA, 'Nothing to move for person ' . $this->personID . ' (deliverySlot = ' . $this->deliverySlot . ', newSlot = ' . $newSlot . ', lastSlot = ' . $lastSlot . ')', 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			// originally returned true; now returns false.
			return false;
		}
		$greater = ($newSlot > $this->deliverySlot ? true : false);
		if (!$db->query('UPDATE person SET deliverySlot = deliverySlot ' . ($greater ? '-' : '+') . ' 1 WHERE deliverySlot BETWEEN ' . ($greater ? $this->deliverySlot : $newSlot) . ' AND ' . ($greater ? $newSlot : $this->deliverySlot) . ' AND routeID = ' . (int) $this->routeID)) {
			$this->setError(E_DATABASE, 'On move of slots in between ' . $this->deliverySlot . ' and ' . $newSlot . ' for person ' . $this->personID, 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			return false;
		}
		if (!$db->query('UPDATE person SET deliverySlot = ' . $newSlot . ' WHERE personID = ' . (int) $this->personID)) {
			$this->setError(E_DATABASE, 'On move of slot for person ' . $this->personID, 'Person::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->personID);
			return false;
		}
		$this->deliverySlot = $newSlot;
		$db->commit('setDeliverySlot' . $this->personID);
		return true;
	}

	public function moveUp () {
		return $this->setDeliverySlot(-1, MODE_RELATIVE);
	}

	public function moveDown () {
		return $this->setDeliverySlot(1, MODE_RELATIVE);
	}

	public function createJournalEntry ($amount, $notes = null, $payTypeID = null, $txnID = null) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Person::createJournalEntry(): no personID');
			return false;
		}
		if (!$this->canUsePayType($payTypeID) && $payTypeID != PAY_ACCT) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'Can\'t use payType ' . $payTypeID . ' for person', 'Person::pay()');
			return false;
		}
		$journalEntry = new JournalEntry;
		$journalEntry->personID = $this->personID;
		$journalEntry->amount = round($amount, 2);
		$journalEntry->notes = $notes;
		$journalEntry->payTypeID = $payTypeID;
		$journalEntry->txnID = $txnID;
		if (!$newBalance = $journalEntry->save()) {
			$this->setError($journalEntry->getError(), 'for person ' . $this->personID . ' journalEntry returned ' . $GLOBALS['errorCodes'][$journalEntry->getError()], ' (' . $journalEntry->getErrorDetail() . ')', 'Person::createJournalEntry()');
			return false;
		}
		// $this->balance = $newBalance;
		$this->clearError();
		return $journalEntry;
	}

	public function pay ($amount, $notes = null, $payTypeID = null, $txnID = null) {
		global $logger;
		$logger->addEntry('txnID in Pay ' . $txnID);
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'Person::pay(): no personID');
			return false;
		}
		if (!$this->canUsePayType($payTypeID)) {
			$this->setError(E_OBJECT_NOT_ACTIVE, 'Can\'t use payType ' . $payTypeID . ' for person', 'Person::pay()');
			return false;
		}
		if ($payTypeID == PAY_ACCT) {
			$this->setError(E_INVALID_DATA, 'Can\'t use account to make a payment', 'Person::pay()');
			return false;
		}
		$amount = round($amount, 2);
		if ($amount <= 0) {
			$this->setError(E_INVALID_DATA, '$amount is a non-positive number', 'Person::pay()');
			return false;
		}
		/*$orders = $this->getUnpaidOrders();
		global $logger;
		while ($amount > 0 && $v = each($orders)) {
			$v = $v['value'];
			if (!$v->addPayment(min(array($amount, abs($v->paidAmount))), $payTypeID, $txnID)) {
				$this->setError($v->getError(), 'order ' . $v->orderID . ' returned error on attempt to pay', 'Person::pay()');
				return false;
			}
			$amount += $v->paidAmount;
		}*/
		if ($amount > 0) $this->createJournalEntry($amount, 'Payment applied to account', $payTypeID, $txnID);
		return true;
	}

	public function getJournalEntries ($dateStart = null, $dateEnd = null) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getJournalEntries()');
			return false;
		}
		global $db;
		$dateStart = (int) $this->checkDate($dateStart);
		// $dateStart = roundDate($dateStart);
		$dateEnd = $this->checkDate($dateEnd);
		if (!$dateEnd) $dateEnd = time();
		// $dateEnd = roundDate($dateEnd) + T_DAY - 1;
		// $dateEnd = $this->roundDate($dateEnd) - 1;
		// TODO: PERFORMANCE: I hafta figure out some way of retrieving the data AND the balance in one statement. Then I should remove the balance-calculating query from JournalEntry::__construct().
		$q = 'SELECT * FROM journalEntry WHERE personID = ' . (int) $this->personID . ' AND dateCreated';
		if ($dateEnd) {
			$q .= ' BETWEEN';
			if ($dateStart < $dateEnd) $q .= ' \'' . $db->cleanDate($dateStart) . '\' AND \'' . $db->cleanDate($dateEnd) . '\'';
			else $q .= ' \'' . $db->cleanDate($dateEnd) . '\' AND \'' . $db->cleanDate($dateStart) . '\'';
		} else $q .= ' BETWEEN \'' . $db->cleanDate($dateStart) . '\' AND \'' . $db->cleanDate($dateStart + T_DAY - 1) . '\'';
		if ($db->query($q)) {
			$journalEntryData = array ();
			$journalEntries = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$journalEntryData[$r->v('journalEntryID')] = $r;
			}
			foreach ($journalEntryData as $thisRow) {
				$thisJournalEntry = new JournalEntry ($thisRow);
				$journalEntries[$thisJournalEntry->journalEntryID] = $thisJournalEntry;
			}
			$this->clearError();
			return $journalEntries;
		} else {
			$this->setError(E_DATABASE, 'on query for person ' . $this->personID, 'Person::getJournalEntries()');
			return false;
		}
	}

	public function getBalance ($includeCredit = false) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getJournalEntries()');
			return false;
		}
		global $db;
		$q = 'SELECT SUM(amount) AS balance FROM journalEntry WHERE personID = ' . $this->personID;
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on calculation of balance', 'Person::getBalance()');
			return false;
		}
		else $credit = 0;
		if (!$r = $db->getRow(F_ASSOC)) $currBal = 0;
		else $currBal = (float) $r['balance'];
		// $this->balance = $currBal;
		if ($includeCredit) $credit = $this->getCredit();
		return $currBal + $credit;
	}

	public function openCustomerAccount ($personInfo = null, $deposit = null) {
		if (!$this->dateCreated) $this->dateCreated = time();
		if ($this->personType & P_CUSTOMER) {
			$this->setError(E_INVALID_DATA, 'person ' . $this->personID . ' already active as a customer', 'Person::openCustomerAccount()');
			return false;
		}
		if (!is_array($personInfo)) {
			if (!$this->personID) {
				$this->setError(E_INVALID_DATA, 'If person hasn\'t already been created, this method should be treated as a very simple constructor, but data given isn\'t an array', 'Person::openCustomerAccount()');
				return false;
			}
		} else if (!$this->__construct($personInfo)) return false;
		global $db, $config;
		$db->start('openCustomer');
		$this->active = true;
		$this->personType |= P_CUSTOMER;
		$this->stars = 0;
		$this->recent = 0;
		if (isset($personInfo['password'])) $this->setPassword($personInfo['password']);
		if (!$this->save()) {
			$db->rollback('openCustomer');
			return false;
		}
		if (!isset($personInfo['parentID']) && !$this->isInTree()) {
			$this->setError(E_INVALID_DATA, 'parentID missing from personInfo and person doesn\'t belong to the tree yet', 'Person::openCustomerAccount()');
			$db->rollback('openCustomer');
			return false;
		}
		global $logger;
		if (isset($personInfo['parentID'])) {
			if (!$this->setParent((int) $personInfo['parentID'])) {
				$db->rollback('openCustomer');
				return false;
			}
		}
		if (is_null($deposit)) $deposit = (float) $this->getDeposit();
		if ($deposit) {
			$mbrFee = new Order ();
			$mbrFee->personID = $this->personID;
			$mbrFee->label = 'Yearly membership fee';
			$mbrFee->start(O_RECURRING, T_YEAR, false);
			$mbrFee->addQuantity($config['depositID'], 1, $deposit);
			if ($mbrFee->save()) {
				if (!$firstMbrFee = $mbrFee->replicate(roundDate(time()), roundDate(time()) + T_DAY - 1)) {
					$this->setError(E_INVALID_DATA, 'Could not create first membership fee from template', 'Person::openCustomerAccount()');
					$db->rollback('openCustomer');
					return false;
				}
			} else {
				$this->setError(E_INVALID_DATA, 'Could not create recurring membership fee template', 'Person::openCustomerAccount()');
				$db->rollback('openCustomer');
				return false;
			}
		}
		$db->commit('openCustomer');
		global $logger;
		$logger->addEntry('Created person ' . $this->personID . ' (' . $this->contactName . ') using password ' . $personInfo['password'], null, 'Person::openCustomerAccount()');
		$this->clearError();
		return true;
	}

	public function closeCustomerAccount ($deposit = null) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::closeCustomerAccount()');
			return false;
		}
		if (!($this->personType & P_CUSTOMER)) {
			$this->setError(E_WRONG_P_TYPE, 'person ' . $this->personID . ' is not a customer', 'Person::closeCustomerAccount()');
			return false;
		}
		global $db, $config, $logger;
		$today = time();
		$db->start('closeCustomer');
		$q = 'SELECT orderID, orderType, dateCompleted, dateDelivered FROM orders WHERE personID = ' . $this->personID . ' AND (orderType & ' . O_OUT . ' AND NOT dateDelivered)';
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on grab of all open or recurring orders for person ' . $this->personID, 'Person::closeCustomerAccount()');
			return false;
		}
		$orders = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$orders[$r->v('orderID')] = $r;
		}
		foreach ($orders as $r) {
			$thisOrder = new Order ((int) $r->v('orderID'));
			if (!$thisOrder->delete()) {
				if (!$thisOrder->cancel()) $this->setError(E_HAS_ASSOCIATED_DATA, 'Could not, for some reason, cancel or delete order ' . $thisOrder->orderID . ' while closing customer account', 'Person::closeCustomerAccount()');
			}
		}
		/* if (is_null($deposit)) $deposit = $this->getDeposit();
		$deposit = round((float) $deposit, 2);
		if ($deposit) {
			if (!$journalEntry = $this->createJournalEntry($deposit, 'Refund of deposit on bins, cold packs' . ($this->compost ? ', compost' : ''))) {
				$this->setError($journalEntry->getError(), 'for person ' . $this->personID . ', refund journalEntry returned error ' . $GLOBALS['errorCodes'][$journalEntry->getError()] . ' (' . $journalEntry->getErrorDetail() . ')', 'Person::closeCustomerAccount()');
				$db->rollback('closeCustomer');
				return false;
			}
		} */
		$this->personType ^= P_CUSTOMER;
		if (!$this->personType) $this->active = false;
		$this->setRoute(0);
		if (!$this->save()) {
			$db->rollback('closeCustomer');
			return false;
		}
		$db->commit('closeCustomer');
		global $logger;
		$logger->addEntry('Closed account for person ' . $this->personID . ' (' . $this->contactName . ')', null, 'Person::closeCustomerAccount()');
		$this->clearError();
	}

	protected function matchCriteria ($object, $criteria) {
		global $logger;
		if (!is_array($criteria) || !count($criteria)) return true;
		if (isRecord($object)) {
			$object = new Item($object);
		}
		if (!isItem($object)) {
			return false;
		}
		$status = true;
		foreach ($criteria as $k => $v) {
			switch ($k) {
				case 'leafNode':
				case 'isLeafNode':
					if (!$object->isLeafNode()) $status = false;
					break;
				case 'personType':
					if (!($object->personType & (int) $v)) $status = false;
			}
		}
		return $status;
	}

	public function getPrice ($item) {
		if (!$this->personID) {
			$this->setError(E_NO_OBJECT_ID, 'no personID', 'Person::getPrice()');
			return false;
		}
		if (!isItem($item) && (int) $item) {
			if (!$item = new Item((int) $item)) {
				$this->setError(E_NO_OBJECT, 'Item ' . (int) $itemID . ' doesn\'t exist', 'Person::getPrice()');
				return false;
			}
		}
		if (!$price = $item->getPrice($this->personID)) {
			$this->setError(E_NO_OBJECT, 'Item ' . (int) $itemID . 'doesn\'t have a price for person ' . $this->personID, 'Person::getPrice()');
			return false;
		}
		return $price;
	}

	private function sortBranch ($a, $b) {
		$a = $this->getNodeForSort($a);
		$b = $this->getNodeForSort($b);
		$fields = $this->getSortFields();
		$sortOrder = 0;
		while (!$sortOrder && ($field = array_shift($fields))) {
			switch ($field['field']) {
				case 'personType':
					$sortOrder = ($a->personType & P_CATEGORY) - ($b->personType & P_CATEGORY);
				case 'isLeafNode':
					$sortOrder = (int) $a->isLeafNode() - (int) $b->isLeafNode();
					break;
				case 'contactName':
				case 'groupName':
				case 'email':
					$sortOrder = strnatcmp($a->$sortField, $b->$sortField);
					break;
				case 'balance':
					$sortOrder = $a->getBalance() - $b->getBalance();
					break;
				case 'city':
					$aaddr = $a->getAddresses();
					$baddr = $b->getAddresses();
					if (count($aaddr) && count($baddr)) {
						$aaddr = array_shift($aaddr);
						$baddr = array_shift($baddr);
						$sortOrder = strnatcmp($aaddr->city, $baddr->city);
					} else {
						$sortOrder = 0;
					}
					break;
				case 'deliverySlot':
					$sortOrder = $a->getDeliverySlot() - $b->getDeliverySlot();
				case 'routeID':
					$sortOrder = $a->getRouteID() - $b->getRouteID();
				case 'route':
					$aroute = $a->getRoute();
					$broute = $b->getRoute();
					$sortOrder = strnatcmp($aroute->label, $broute->label);
				case 'label':
					$sortOrder = strnatcmp($aroute->getLabel(), $broute->getLabel());
				case 'sortOrder':
				default:
					$sortOrder = $a->getSortOrder() - $b->getSortOrder();
			}
			$sortOrder *= $field['dir'];
		}
		return $sortOrder;
	}
}

?>
