<?php

class Address extends MarketPrototype {
	public $addressID;
	public $personID;
	public $routeID;
	public $addressType = 0;
	public $careOf;
	public $address1;
	public $address2;
	public $city;
	public $prov;
	public $postalCode;
	public $country;
	public $directions;
	public $phone;

	function __construct ($addressInfo = null) {
		global $logger;
		switch (gettype($addressInfo)) {
			case 'integer':
				global $db;
				$q = 'SELECT * FROM address WHERE addressID = ' . $addressInfo;
				if (!$db->query($q)) {
					$this->setError(E_DATABASE, 'on getting address ' . $addressInfo, 'Address::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'no address ' . $addressInfo, 'Address::__construct()');
					return false;
				}
				$addressInfo = $r;
			case 'array':
				$addressInfo = new Record ($addressInfo);
			case 'object':
				if (get_class($addressInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$addressInfo is a ' . get_class($addressInfo) . ' rather than the expected Record', 'Address::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $addressInfo->v($k);
					if (!is_null($v)) $this->$k = $v;
				}
				if (!$this->validate()) return false;
				break;
			default:
				$this->addressID = null;
				$this->personID = null;
				$this->routeID = null;
				$this->addressType = 0;
				$this->careOf = null;
				$this->address1 = null;
				$this->address2 = null;
				$this->city = null;
				global $config;
				if (isset($config['provDefault'])) $this->prov = $config['provDefault'];
				else $this->prov = null;
				$this->postalCode = null;
				$this->country = null;
				$this->directions = null;
				$this->phone = null;
		}
		$this->clearError();
	}

	function validate () {
		$errorFields = array ();
		$this->addressID = (int) $this->addressID;
		$this->personID = (int) $this->personID;
		if (!$this->personID) $errorFields[] = 'personID';
		$this->routeID = (int) $this->routeID;
		// TODO: add checking to see if route exists if it's a delivery addy
		$this->addressType = (int) $this->addressType;
		if ($this->addressType < 1 || $this->addressType > AD_ALL) $errorFields[] = 'addressType';
		$this->careOf = trim($this->careOf);
		$this->address1 = trim($this->address1);
		if (!$this->address1) $errorFields[] = 'address1';
		$this->address2 = trim($this->address2);
		$this->city = trim($this->city);
		if (!$this->city) $errorFields[] = 'city';
		$this->prov = trim($this->prov);
		global $config;
		if (strlen($this->prov) > $config['provMax'] || !strlen($this->prov)) {
			if (isset($config['provDefault'])) $this->prov = $config['provDefault'];
			else $errorFields[] = 'prov';
		}
		$this->postalCode = trim(strtoupper($this->postalCode));
		global $logger;
		if (isset($config['postalCodeFormat']) && $this->postalCode) {
			if (!preg_match($config['postalCodeFormat'], $this->postalCode)) $errorFields[] = 'postalCode';
		}
		$this->country = trim ($this->country);
		if (!$this->country) $this->country = null;
		$this->directions = trim($this->directions);
		$this->phone = trim($this->phone);
		if (count($errorFields)) {
			// $errorFields[] = 'Address validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'Address::validate()');
			return false;
		}
		return true;
	}

	function save () {
		if (!$this->validate()) return false;
		global $db;
		$t = 'saveAddress' . $this->personID;
		$db->start($t);
		if (!$this->addressID) {
			if (!$db->query('INSERT INTO address (address1) VALUES (\'temp\')', true)) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Address::save()');
				return false;
			}
			$this->addressID = $db->getLastID();
		}
		$q = 'UPDATE address SET ';
		$q .= 'personID = ' . (int) $this->personID;
		$q .= ', routeID = ' . ($this->routeID ? (int) $this->routeID : 'NULL');
		$q .= ', addressType = ' . (int) $this->addressType;
		$q .= ', careOf = \'' . $db->cleanString($this->careOf) . '\'';
		$q .= ', address1 = \'' . $db->cleanString($this->address1) . '\'';
		$q .= ', address2 = ' . ($this->address2 ? '\'' . $db->cleanString($this->address2) . '\'' : 'NULL');
		$q .= ', city = \'' . $db->cleanString($this->city) . '\'';
		$q .= ', prov = ' . ($this->prov ? '\'' . $db->cleanString($this->prov) . '\'' : 'NULL');
		$q .= ', postalCode = ' . ($this->postalCode ? '\'' . $db->cleanString($this->postalCode) . '\'' : 'NULL');
		$q .= ', country = ' . ($this->country ? '\'' . $db->cleanString($this->country) . '\'' : 'NULL');
		$q .= ', directions = ' . ($this->directions ? '\'' . $db->cleanString($this->directions) . '\'' : 'NULL');
		$q .= ', phone = ' . ($this->phone ? '\'' . $db->cleanString($this->phone) . '\'' : 'NULL');
		$q .= ' WHERE addressID = ' . (int) $this->addressID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on update of address ' . $this->addressID . ' for person ' . $this->personID, 'Address::save()');
			$db->rollback($t);
			return false;
		}
		$this->clearError();
		$db->commit($t);
		return true;
	}

	function delete () {
		if (!$this->personID || !$this->addressID) {
			$this->setError(E_NO_OBJECT_ID, 'Address is not set up', 'Address::delete()');
			return false;
		}
		global $db;
		if (!$db->query('DELETE FROM address WHERE addressID = ' . (int) $this->addressID)) {
			$this->setError(E_DATABASE, 'on deletion of address ' . $this->addressID . ' for person ' . $this->personID, 'Address::delete()');
			return false;
		} else return true;
	}
}

?>
