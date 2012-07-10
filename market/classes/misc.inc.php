<?php

class PayType extends MarketPrototype {
	public $payTypeID;
	public $label;
	public $labelShort;
	public $labelLong;
	private $active = true;
	public $surcharge;
	public $surchargeType;

	public function __construct ($payTypeInfo = null) {
		switch (gettype($payTypeInfo)) {
			case 'integer':
				if (!$payTypeInfo) return;
				global $db;
				if (!$db->query('SELECT * FROM payType WHERE payTypeID = ' . $payTypeInfo)) {
					$this->setError(E_DATABASE, 'on query for payType ' . $payTypeInfo, 'PayType::__construct()');
					return false;
				}
				if ($r = $db->getRow(F_RECORD)) {
					$this->payTypeID = $payTypeInfo;
					$this->label = $r->v('label');
					$this->labelShort = $r->v('labelShort');
					$this->labelLong = $r->v('labelLong');
					$this->active = (bool) $r->v('active');
					$this->surcharge = $r->v('surcharge');
					$this->surchargeType = $r->v('surchargeType');
				} else {
					$this->setError(E_NO_OBJECT, 'no payment type ' . $payTypeInfo, 'PayType::__construct()');
					return false;
				}
				break;
			case 'array':
				$payTypeInfo = new Record ($payTypeInfo);
			case 'object':
				if (get_class($payTypeInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$payTypeInfo is a ' . get_class($payTypeInfo) . ' rather than the expected Record', 'PayType::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $payTypeInfo->v($k);
					if (!is_null($v)) $this->$k = $v;
				}
				if (!$this->validate()) return false;
				break;
			case 'null':
			default:
				$this->payTypeID = null;
				$this->label = null;
				$this->labelShort = null;
				$this->labelLong = null;
				$this->active = true;
				$this->surcharge = null;
				$this->surchargeType = null;
		}
		$this->clearError();
		return true;
	}

	public function validate () {
		$errorFields = array ();
		$this->payTypeID = (int) $this->payTypeID;
		if (!$this->label) $errorFields[] = 'label';
		$this->labelShort = trim($this->labelShort);
		if (!$this->labelShort) $this->labelShort = null;
		$this->labelLong = trim($this->labelLong);
		if (!$this->labelLong) $this->labelLong = null;
		$this->active = $this->active ? true : false;
		$this->surcharge = (float) $this->surcharge;
		if (!is_null($this->surchargeType)) {
			switch ($this->surchargeType) {
				case 1:
				case 2:
				case 3:
					break;
				default:
					$this->surchargeType = null;
			}
		}
		if (count($errorFields)) {
			$errorFields[] = 'PayType verify';
			$this->setError(E_INVALID_DATA, $errorFields, 'PayType::validate()');
			return false;
		}
		return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		if (!$this->payTypeID) {
			if (!$db->query('INSERT INTO payType (label) VALUES (\'' . $db->cleanString($this->label) . '\')')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'PayType::save()');
				return false;
			}
			$this->payTypeID = $db->getLastID();
		}
		$q = 'UPDATE payType SET ';
		$q .= 'label = \'' . $db->cleanString($this->label) . '\'';
		$q .= 'labelShort = ' . ($this->labelShort ? '\'' . $db->cleanString($this->labelShort) . '\'' : null);
		$q .= 'labelLong = ' . ($this->labelLong ? '\'' . $db->cleanString($this->labelLong) . '\'' : null);
		$q .= ', active = ' . ($this->active ? 'true' : 'false');
		$q .= ', surcharge = ' . (is_null($this->surcharge) ? 'null' : $this->surcharge);
		$q .= ', surchargeType = ' . (is_null($this->surchargeType) ? 'null' : $this->surchargeType);
		$q .= ' WHERE payTypeID = ' . $this->payTypeID;
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on save of route ' . $this->payTypeID, 'PayType::save()');
			return false;
		}
		global $logger;
		$logger->addEntry('Saved payType ' . $this->payTypeID . ' (' . $this->label . ')', null, 'PayType::save()');
		$this->clearError();
		return true;
	}

	public function delete () {
		if (!$this->payTypeID) {
			$this->setError(E_NO_OBJECT_ID, 'No payTypeID', 'PayType::delete()');
			return false;
		}
		global $db;
		$db->start('deletePayType' . $this->payTypeID);
		if (!$db->query('DELETE FROM payType WHERE payTypeID = ' . (int) $this->payTypeID)) {
			$this->setError(E_DATABASE, 'on deletion of payType ' . $this->payTypeID, 'PayType::delete()');
			$db->rollback('deletePayType' . $this->payTypeID);
			return false;
		}
		$this->routeDays = array ();
		if (!$db->query('UPDATE person SET payTypeID = null WHERE payTypeID = ' . (int) $this->payTypeID)) {
			$this->setError(E_DATABASE, 'on update of associated people for payType ' . $this->label, 'PayType::delete()');
			$db->rollback('deletePayType' . $this->payTypeID);
			return false;
		}
		$db->commit('deletePayType' . $this->payTypeID);
		global $logger;
		$logger->addEntry('Deleted payType ' . $this->payTypeID, null, 'PayType::delete()');
		$this->__construct(null);
		$this->clearError();
	}

	public function isActive () {
		return (bool) $this->active;
	}

	public function setActive ($v) {
		$this->active = (bool) $v;
		return $this->save();
	}

	public function getPeople () {
		global $db;
		if (!$db->query('SELECT * FROM person WHERE ' . ($this->payTypeID ? 'payTypeID = ' . (int) $this->payTypeID : '(payTypeID IS NULL OR payTypeID = 0)'))) {
			$this->setError(E_DATABASE, 'on query for route ' . $this->payTypeID, 'PayType::getPeople()');
			return false;
		}
		$people = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$thisPerson = new Person ($r);
			$people[] = $thisPerson;
		}
		$this->clearError();
		return $people;
	}

	public function getSurcharge ($amount) {
		if (!$this->surcharge) return 0;
		if (!$this->surchargeType) return 0;
		$amount = (int) $amount;
		if (!$amount) return 0;
		if ($this->surchargeType & N_TIER) {
			foreach ($this->surcharge as $k => $v) {
				if ($amount <= $k) {
					$surcharge = $v;
					break;
				}
			}
		} else $surcharge = $this->surcharge;
		if ($this->surchargeType & N_FLAT) return $surcharge;
		else if ($this->surchargeType & N_PERCENT) return round($amount * $surcharge / 100, 2);
		else return 0;
	}

	public function getSurchargeTier ($amount) {
		if (!$this->surcharge) return false;
		if (!$this->surchargeType) return false;
		if ($this->surchargeType & N_FLAT) return false;
		$amount = (int) $amount;
		if (!$amount) return 0;
		if ($this->surchargeType & N_TIER) {
			foreach ($this->surcharge as $k => $v) {
				if ($amount <= $k) {
					$surcharge = $k;
					break;
				}
			}
		} else $surcharge = $this->surcharge;
		return $surcharge;
	}
}

class Cryptastic {
 
	/** Encryption Procedure
	 *
	 *  @param mixed msg message/data
	 *  @param string k encryption key
	 *  @param boolean base64 base64 encode result
	 *
	 *  @return string iv+ciphertext+mac or
	 * boolean false on error
	*/
	public function encrypt( $msg, $k, $base64 = false ) {
 
		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;
 
		$msg = serialize($msg);                         # serialize
		$iv = mcrypt_create_iv(32, MCRYPT_RAND);        # create iv
 
		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
			return false;
 
		$msg = mcrypt_generic($td, $msg);               # encrypt
		$msg = $iv . $msg;                              # prepend iv
		$mac = self::pbkdf2($msg, $k, 1000, 32);       # create mac
		$msg .= $mac;                                   # append mac
 
		mcrypt_generic_deinit($td);                     # clear buffers
		mcrypt_module_close($td);                       # close cipher module
 
		if ( $base64 ) $msg = base64_encode($msg);      # base64 encode?
 
		return $msg;                                    # return iv+ciphertext+mac
	}
 
	/** Decryption Procedure
	 *
	 *  @param string msg output from encrypt()
	 *  @param string k encryption key
	 *  @param boolean base64 base64 decode msg
	 *
	 *  @return string original message/data or
	 * boolean false on error
	*/
	public function decrypt( $msg, $k, $base64 = false ) {
 
		if ( $base64 ) $msg = base64_decode($msg);          # base64 decode?
 
		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;
 
		$iv = substr($msg, 0, 32);                          # extract iv
		$mo = strlen($msg) - 32;                            # mac offset
		$em = substr($msg, $mo);                            # extract mac
		$msg = substr($msg, 32, strlen($msg)-64);           # extract ciphertext
		$mac = self::pbkdf2($iv . $msg, $k, 1000, 32);     # create mac
 
		if ( $em !== $mac )                                 # authenticate mac
			return false;
 
		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )      # initialize buffers
			return false;
 
		$msg = mdecrypt_generic($td, $msg);                 # decrypt
		$msg = unserialize($msg);                           # unserialize
 
		mcrypt_generic_deinit($td);                         # clear buffers
		mcrypt_module_close($td);                           # close cipher module
 
		return $msg;                                        # return original msg
	}
 
	/** PBKDF2 Implementation (as described in RFC 2898);
	 *
	 *  @param string p password
	 *  @param string s salt
	 *  @param int c iteration count (use 1000 or higher)
	 *  @param int kl derived key length
	 *  @param string a hash algorithm
	 *
	 *  @return string derived key
	*/
	public function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {
 
		$hl = strlen(hash($a, null, true)); # Hash length
		$kb = ceil($kl / $hl);              # Key blocks to compute
		$dk = '';                           # Derived key
 
		# Create key
		for ( $block = 1; $block <= $kb; $block ++ ) {
 
			# Initial hash for this block
			$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
 
			# Perform block iterations
			for ( $i = 1; $i < $c; $i ++ )
 
				# XOR each iterate
				$ib ^= ($b = hash_hmac($a, $b, $p, true));
 
			$dk .= $ib; # Append iterated block
		}
 
		# Return derived key of correct length
		return substr($dk, 0, $kl);
	}
}

?>
