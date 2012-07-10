<?php

class Price extends MarketPrototype {
	public $personID;
	public $itemID;
	public $price = 0;
	public $tax = 0;
	public $multiple = 1;

	function __construct ($priceInfo, $priceInfo2 = null) {
		switch (gettype($priceInfo)) {
			case 'integer':
				if (is_int($priceInfo2) && $priceInfo2 && $priceInfo) {
					global $db;
					if (!$db->query('SELECT * FROM price WHERE personID = ' . $priceInfo . ' AND itemID = ' . $priceInfo2)) {
						$this->setError(E_DATABASE, 'on query for person ' . $priceInfo . ' and item ' . $priceInfo2, 'Price::__construct()');
						return false;
					}
					if (!$r = $db->getRow(F_ASSOC)) {
						$this->setError(E_NO_OBJECT, 'price for person '. $priceInfo . ' and item ' . $priceInfo2 . ' doesn\'t exist', 'Price::__construct()');
						return false;
					}
				} else {
					$this->setError(E_INVALID_DATA, 'either person ' . $priceInfo . ' or item ' . $priceInfo2 . ' is not an integer', 'Price::__construct()');
					return false;
				}
				$priceInfo = $r;
			case 'array':
				$priceInfo = new Record ($priceInfo);
			case 'object':
				if (get_class($priceInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$priceInfo is a ' . get_class($priceInfo) . ' rather than the expected Record', 'Price::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $priceInfo->v($k);
					if (!is_null($v)) $this->$k = $v;
				}
				if (!$this->validate()) return false;
				break;
			default:
				$this->personID = null;
				$this->itemID = null;
				$this->price = 0;
				$this->tax = 0;
				$this->multiple = 1;
		}
		$this->clearError();
	}

	function validate () {
		$errorFields = array ();
		$this->personID = (int) $this->personID;
		if (!$this->personID) $errorFields[] = 'personID';
		$this->itemID = (int) $this->itemID;
		if (!$this->itemID) $errorFields[] = 'itemID';
		$this->price = round((float) $this->price, 2);
		$this->tax = (int) $this->tax;
		$this->multiple = ((int) $this->multiple) ? (int) $this->multiple : 1;
		if (count($errorFields)) {
			$errorFields[] = 'Price validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'Price::validate()');
			return false;
		}
		return true;
	}

	function save () {
		if (!$this->validate()) return false;
		global $db;
		if (!$db->query('DELETE FROM price WHERE personID = ' . $this->personID . ' AND itemID = ' . $this->itemID)) {
			$this->setError(E_DATABASE, 'on deletion of old price', 'Price::save()');
			return false;
		}
		$q = 'INSERT INTO price (personID, itemID, price, tax, multiple) values (' . $this->personID . ', ' . $this->itemID . ', ' . $this->price . ', ' . $this->tax . ', ' . $this->multiple . ')';
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on update of price for person ' . $this->personID . ' and item ' . $this->itemID, 'Price::save()');
			return false;
		}
		$this->clearError();
		global $logger;
		return true;
	}

	function delete () {
		if (!$this->personID || !$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'Price is not set up', 'Price::delete()');
			return false;
		}
		global $db;
		if (!$db->query('DELETE FROM price WHERE personID = ' . (int) $this->personID . ' AND itemID = ' . (int) $this->itemID)) {
			$this->setError(E_DATABASE, 'on deletion of price for person ' . $this->personID . ' and item ' . $this->itemID, 'Price::delete()');
			return false;
		} else return true;
	}
}

/*class Adjuster extends MarketPrototype { // don't think this one is the right one
	public $adjusterID;
	public $adjType;
	public $dateStart;
	public $dateEnd;
	public $value;

	public function __construct ($adjusterInfo = null) {
		switch (gettype($adjusterInfo)) {
			case 'integer':
				global $db;
				if (!$db->query('SELECT * FROM adjuster WHERE adjusterID = ' . $adjusterInfo)) {
					$this->setError(E_DATABASE, 'on query for adjuster ' . $adjusterInfo, 'Adjuster::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'Adjuster ' . $adjusterInfo . ' doesn\'t exist', 'Adjuster::__construct()');
					return false;
				}
				$adjusterInfo = $r;
			case 'array':
				$adjusterInfo = new Record ($adjusterInfo);
			case 'object':
				if (get_class($adjusterInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$adjusterInfo is a ' . get_class($adjusterInfo) . ' rather than the expected Record', 'Adjuster::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $adjusterInfo->v($k);
					if (!is_null($v)) switch ($k) {
						case 'dateStart':
						case 'dateEnd':
							$this->$k = ($v ? null : strtotime($v));
							break;
						case 'value':
							if ((float) $v == $v) $this->$k = (float) $v;
							else if ($v) {
								$v = explode(',', $v);
								$v2 = array ();
								foreach ($v as $v3) {
									list($k3, $v3) = explode(':', $v3);
									$v2[$k3] = $v3;
								}
								$v = $v2;
							} else $v = null;
						default:
							$this->$k = $v;
					}
				}
				if (!$this->validate()) return false;
				break;
			default:
				$this->adjusterID = null;
				$this->adjType = null;
				$this->dateStart = null;
				$this->dateEnd = null;
				$this->value = null;
		}
		$this->clearError();
	}

	public function validate () {
		$errorFields = array ();
		$this->adjusterID = (int) $this->adjusterID ? (int) $this->adjusterID : false;
		$this->adjType = N_ALL & (int) $this->adjType;
		if (!$this->adjType) $errorFields[] = 'adjType';
		$this->dateStart = $this->checkDate($this->dateStart);
		if (!$this->dateStart && !is_null($this->dateStart)) $errorFields[] = 'dateStart';
		$this->dateEnd = $this->checkDate($this->dateEnd);
		if (!$this->dateEnd && !is_null($this->dateEnd)) $errorFields[] = 'dateEnd';
		switch (gettype($this->value)) {
			case 'integer':
			case 'float':
				$this->value = round ($this->value, 2);
				if (!$this->value) $errorFields[] = 'value';
				break;
			case 'array':
				$value = array ();
				foreach ($this->value as $k => $v) {
					// HERE'S WHERE I LEFT OFF
					$k = (float) abs(round($k, ($this->adjType & N_)
		if (count($errorFields)) {
			$errorFields[] = 'Price validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'Price::validate()');
			return false;
		}
		return true;
	}
}*/

/* class Adjuster extends MarketPrototype {
	public $adjusterID;
	public $adjusterType;
	public $calcType;
	public $amount;
	private $orders = array ();
	private $orderItems = array ();
	private $items = array ();
	private $people = array ();

	public function __construct ($adjusterInfo) {
		switch (gettype($adjusterInfo)) {
			case 'integer':
				global $db;
				if (!$db->query('SELECT * FROM adjuster WHERE adjusterID = ' . $adjusterInfo)) {
					$this->setError(E_DATABASE, 'on query for adjuster ' . $adjusterInfo, 'Adjuster::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'adjuster '. $priceInfo . ' doesn\'t exist', 'Adjuster::__construct()');
					return false;
				}
				$adjusterInfo = $r;
			case 'array':
				$adjusterInfo = new Record ($adjusterInfo);
			case 'object':
				if (get_class($adjusterInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$adjusterInfo is a ' . get_class($adjusterInfo) . ' rather than the expected Record', 'adjuster::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $adjusterInfo->v($k);
					if (!is_null($v)) $this->$k = $v;
				}
				if (!$this->validate()) return false;
				break;
			default:
				$this->adjusterID = null;
				$this->adjusterType = null;
				$this->calcType = null;
				$this->amount = 0;
		}
		$this->orders = array ();
		$this->orderItems = array ();
		$this->items = array ();
		$this->people = array ();
		$this->clearError();
		return true;
	}

	public function validate () {
		$errorFields = array ();
		if (!(int) $this->adjusterID) $this->adjusterID = null;
		$this->adjusterType &= ADJ_ALL;
		if (!$this->adjusterType) $errorFields[] = 'adjusterType';
		$this->calcType &= N_ALL;
		if (!$this->calcType) $errorFields[] = 'calcType';
		switch (gettype($this->amount)) {
			case 'string':
				$amounts = explode(',', $this->amount);
				$this->amount = array ();
				foreach ($amounts as $v) {
					$v = explode(':', $v);
					if (count($v) != 2) {
						if (!in_array('amount', $errorFields)) $errorFields[] = 'amount';
					} else $this->amount[(float) $v[0]] = (float) $v[1];
				}
			case 'array':
} */

?>
