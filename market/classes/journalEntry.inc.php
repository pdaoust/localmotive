<?php

class JournalEntry extends MarketPrototype {
	public $journalEntryID;
	public $personID;
	public $orderID;
	public $amount = 0;
	public $balance = null;
	public $dateCreated;
	public $notes;
	public $payTypeID;
	public $txnID;

	public function __construct ($journalEntryInfo = null) {
		global $logger;
		switch (gettype($journalEntryInfo)) {
			case 'integer':
				global $logger;
				global $db;
				if (!$db->query('SELECT * FROM journalEntry WHERE journalEntryID = ' . $journalEntryInfo)) {
					$this->setError(E_DATABASE, 'on main query', 'JournalEntry::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'no journal entry ' . $journalEntryInfo, 'JournalEntry::construct()');
					return false;
				}
				$journalEntryInfo = $r;
			case 'array':
				$journalEntryInfo = new Record ($journalEntryInfo);
			case 'object':
				if (get_class($journalEntryInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$journalEntryInfo is a ' . get_class($journalEntryInfo) . ' rather than the expected Record', 'JournalEntry::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $journalEntryInfo->v($k);
					if (!is_null($v)) {
						switch ($k) {
							case 'dateCreated':
								$this->$k = ($v ? strtotime($v) : null);
								break;
							case 'txnID':
								if ($journalEntryInfo->e('journalEntryID')) {
									$this->$k = Cryptastic::decrypt($v, getKey(), true);
									$logger->addEntry('try 1: '.$this->$k);
									if (!preg_match('/^[0-9a-zA-Z\*]+$/', $this->$k)) $this->$k = Cryptastic::decrypt($v, getKey());
									$logger->addEntry('try 2: '.$this->$k);
									if (!preg_match('/^[0-9a-zA-Z\*]+$/', $this->$k)) $this->$k = Cryptastic::decrypt($v, getKey(false));
									if (!preg_match('/^[0-9a-zA-Z\*]+$/', $this->$k)) $this->$k = null;
									$logger->addEntry('try 3: '.$this->$k);
								} else $this->$k = $v;
								break;
							default:
								$this->$k = $v;
						}
					}
				}
				if (!$this->validate()) return false;
				$this->calculateBalance();
				break;
			default:
				$this->journalEntryID = null;
				$this->personID = null;
				$this->orderID = null;
				$this->amount = 0;
				$this->balance = null;
				$this->dateCreated = null;
				$this->notes = null;
				$this->payTypeID = null;
				$this->txnID = null;
		}
		$this->clearError();
	}

	public function calculateBalance () {
		if (!$this->journalEntryID || !$this->personID) {
			return 0;
			// does not throw error anymore, cuz we want empty journal entries to be used as starting balances
			// $this->setError(E_NO_OBJECT_ID, 'JournalEntry::calculateBalance(): no journalEntryID or personID');
			// return false;
		}
		if ($this->balance) return $this->balance;
		global $db;
		$q = 'SELECT SUM(amount) AS balance FROM journalEntry WHERE journalEntryID <= ' . $this->journalEntryID . ' AND personID = ' . $this->personID;
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on calculation of balance for journalEntry ' . $this->journalEntryID, 'JournalEntry::calculateBalance()');
			return false;
		}
		// this may be erroneous, because as long as this is a valid journalEntry, there should be a result set
		if ($r = $db->getRow(F_RECORD)) $this->balance = $r->v('balance');
		else $this->balance = 0;
		return $this->balance;
	}

	public function validate () {
		global $logger;
		$logger->addEntry('txnID in validate' . $this->txnID);
		$errorFields = array ();
		$this->journalEntryID = $this->journalEntryID ? (int) $this->journalEntryID : null;
		$this->personID = (int) $this->personID;
		if (!$this->personID) $errorFields[] = 'personID';
		$this->orderID = $this->orderID ? (int) $this->orderID : null;
		if (round((float) $this->amount, 2)) $this->amount = round((float) $this->amount, 2);
		else {
			$this->amount = null;
			$errorFields[] = 'amount';
		}
		$this->dateCreated = $this->checkDate($this->dateCreated);
		if (!$this->dateCreated && !is_null($this->dateCreated)) $errorFields[] = 'dateCreated';
		$this->notes = trim($this->notes);
		$this->payTypeID = (int) $this->payTypeID;
		$payTypes = getPayTypes();
		if ($this->payTypeID) {
			if (!array_key_exists($this->payTypeID, $payTypes)) $errorFields[] = 'payTypeID';
			if (!$payTypes[$this->payTypeID]->isActive()) $errorFields[] = 'payTypeID';
			} else $logger->addEntry('payTypeID ' . $this->payTypeID . ' is active');
		if (!$this->payTypeID) $errorFields[] = 'payTypeID';
		$this->txnID = trim($this->txnID);
		$logger->addEntry('txnID after validate ' . $this->txnID);
		if (count($errorFields)) {
			$errorFields[] = 'JournalEntry validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'JournalEntry::validate()');
			return false;
		}
		return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		if (!$this->journalEntryID) {
			$db->start('journalEntryNew');
			$q = 'INSERT INTO journalEntry SET ';
			$q .= 'personID = ' . $this->personID . ', ';
			$q .= 'orderID = ' . ($this->orderID ? $this->orderID : 'null') . ', ';
			$q .= 'amount = ' . $this->amount . ', ';
			$q .= 'dateCreated = NOW(), ';
			$q .= 'notes = \'' . $db->cleanString($this->notes) . '\', ';
			$q .= 'payTypeID = ' . $this->payTypeID . ', ';
			$q .= 'txnID = ' . ($this->txnID ? '\'' . $db->cleanString(Cryptastic::encrypt($this->txnID, getKey(), true)) . '\'' : 'null');
			if (!$db->query($q, true)) {
				$this->setError(E_DATABASE, 'on insertion of new journalEntry for person ' . $this->personID, 'JournalEntry::save()');
				$db->rollback('journalEntryNew');
				return false;
			}
			$this->journalEntryID = $db->getLastID();
			/* if (!$db->query('UPDATE person SET balance = balance + ' . $this->amount . ' WHERE personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'on update of balance of person ' . $this->personID, 'JournalEntry::save()');
				$db->rollback('journalEntryNew');
				return false;
			} */
			if (!$db->query('SELECT SUM(amount) AS balance FROM journalEntry WHERE personID = ' . $this->personID)) {
				$this->setError(E_DATABASE, 'while getting person\'s new balance', 'JournalEntry::save()');
				$db->rollback('journalEntryNew');
				return false;
			}
			if ($r = $db->getRow(F_RECORD)) {
				$newBalance = $r->v('balance');
				$this->balance = $newBalance;
				/*if ($this->orderID) {
					if (!$db->query('UPDATE orders SET balance = balance + ' . $this->amount . ' WHERE orderID = ' . $this->orderID)) {
						$this->setError(E_DATABASE, 'on update of balance for order ' . $this->orderID, 'JournalEntry::save()');
						$db->rollback('journalEntryNew');
						return false;
					}
					if (!$db->query('SELECT balance FROM orders WHERE orderID = ' . $this->orderID)) {
						$this->setError(E_DATABASE, 'while getting new balance for order ' . $this->orderID, 'JournalEntry::save()');
						$db->rollback('journalEntryNew');
						return false;
					}
					if (!$r = $db->getRow(F_RECORD)) {
						$this->setError(E_NO_OBJECT, 'no order with orderID' . $this->orderID, 'JournalEntry::save()');
						$db->rollback('journalEntryNew');
						return false;
					}
					$newBalance = array ($newBalance, $r->v('balance'));
				}*/
				$db->commit('journalEntryNew');
				$this->clearError();
				global $logger;
				return $newBalance;
			} else {
				$this->setError(E_NO_OBJECT, 'no person with that ID', 'JournalEntry::save()');
				$db->rollback('journalEntryNew');
				return false;
			}
		} else {
			if (!$db->query('UPDATE journalEntry SET notes = \'' . $db->cleanString(trim($this->notes)) . '\' WHERE journalEntryID = ' . $this->journalEntryID)) {
				$this->setError(E_DATABASE, 'on update of notes for journalEntry ' . $this->journalEntryID, 'JournalEntry::save()');
				$db->rollback('journalEntry' . $this->journalEntryID);
				return false;
			}
		}
		$db->commit('journalEntry' . $this->journalEntryID);
		global $logger;
		$logger->addEntry('Saved notes on journalEntry ' . $this->journalEntryID, null, 'JournalEntry::save()');
		$this->clearError();
		return true;
	}
}
?>
