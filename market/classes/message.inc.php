<?php

class Message extends MarketPrototype {
	public $messageID;
	public $dateStart;
	public $dateEnd;
	public $messageType;
	public $personID;
	public $messageTitle;
	public $messageBody;
	
	public function __construct ($messageInfo) {
		switch (gettype($messageInfo)) {
			case 'integer':
				global $db;
				if (!$db->query('SELECT * FROM message WHERE messageID = ' . $messageInfo)) {
					$this->setError(E_DATABASE, 'on grab of message ' . $messageInfo, 'Message::__construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'no such message ' . $messageInfo, 'Message::__construct()');
					return false;
				}
				$messageInfo = $r;
			case 'array':
				$messageInfo = new Record ($messageInfo);
			case 'object':
				if (get_class($messageInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$messageInfo is a ' . get_class($messageInfo) . ' rather than the expected Record', 'Message::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $messageInfo->v($k);
					if (!is_null($v)) {
						switch ($k) {
							case 'dateStart':
							case 'dateEnd':
								$this->$k = ($v ? null : strtotime($v));
								break;
							default:
								else $this->$k = $v;
						}
					}
				}
				if (!$this->validate()) return false;
			default:
				$this->messageID = null;
				$this->dateStart = null;
				$this->dateEnd = null;
				$this->messageType = null;
				$this->personID = null;
				$this->messageTitle = null;
				$this->messageBody = null;
		}
		$this->clearError();
		return true;
	}
	
	public function validate () {
		$errorFields = array ();
		$this->messageID = (int) $this->messageID;
		$this->dateStart = $this->checkDate($this->dateStart);
		if (!$this->dateStart && !is_null($this->dateStart)) $errorFields[] = 'dateStart';
		$this->dateEnd = $this->checkDate($this->dateEnd);
		if (!$this->dateEnd && !is_null($this->dateEnd)) $errorFields[] = 'dateEnd';
		if (array_key_exists($this->messageType, $messageTypes)) $this->messageType = $messageTypes[$this->messageType];
		else $errorFields[] = 'messageType';
		$this->messageTitle = trim($this->messageTitle);
		if ($this->messageType != NOTICE_CLOSURE && !$this->messageTitle) $errorFields[] = 'messageTitle';
		$this->personID = (int) $this->personID;
		if ($this->messageType == NOTICE_PERSON && !$this->personID) $errorFields[] = 'personID';
		$messageBody = trim($messageBody);
		if (count($errorFields)) {
			$this->setError(E_INVALID_DATA, $errorFields, 'Message::validate()');
			return false;
		}
	}
	
	public function save () {
		if (!$this->validate()) return false;
		global $db;
		if (!$this->messageID) {
			if (!$db->query('INSERT INTO message (dateStart) VALUES (' . ($this->dateStart ? '\'' . $db->cleanDate($this->dateStart) . '\'' : 'NOW()') . ')')) {
				$this->setError(E_DATABASE, 'on creation of new message record', 'Message::save()');
				return false;
			}
			$this->messageID = $db->getLastID();
		}
		$q = 'UPDATE message SET messageID = ' . $this->messageID ? $this->messageID : 'null';
		$q .= ', dateStart = ' . ($this->dateStart ? '\'' . $db->cleanDate($this->dateStart) . '\'' : 'NOW()');
		$q .= ', dateEnd = ' . ($this->dateEnd ? '\'' . $db->cleanDate($this->dateEnd) . '\'' : 'null');
		$q .= ', messageType = ' . (int) $this->messageType;
		$q .= ', personID = ' . ($this->personID ? (int) $this->personID : 'null');
		$q .= ', messageTitle = \'' . $db->cleanString($this->messageTitle) . '\'';
		$q .= ', messageBody = \'' . $db->cleanString($this->messageBody) . '\'';
		$q .= ' WHERE messageID = ' . $this->messageID;
		if (!$db->query($q)) {
			$this->setError(E_DATABASE, 'on save' . ($this->messageID ? ' of message ' . $this->messageID : null), 'Message::save()');
			return false;
		}
		global $logger;
		$logger->addEntry('Saved message ' . $this->messageID, null, 'Message::save()');
		$this->clearError();
		return true;
	}
	
	public function isActive () {
		if (!$this->messageID) {
			$this->setError(E_NO_OBJECT_ID, 'no messageID', 'Message::isActive()');
			return false;
		}
		// might have conflict here with cascading errors
		$this->clearError();
		$today = time();
		$dateStart = ($this->dateStart ? strtotime($this->dateStart) : $today - 1);
		$dateEnd = ($this->dateEnd ? strtotime($this->dateEnd) : $today + 1);
		if ($today >= $dateStart && $today <= $dateEnd) return true;
		else return false;
	}	
}

?>
