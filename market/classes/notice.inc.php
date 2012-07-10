<?php

class Notice extends MarketPrototype {
	public $noticeID;
	public $dateStart;
	public $dateEnd;
	public $noticeType;
	public $personID;
	public $noticeTitle;
	public $noticeBody;
	
	public function __construct ($noticeInfo) {
		switch (gettype($noticeInfo)) {
			case 'integer':
				global $db;
				if (!$db->query('SELECT * FROM notice WHERE noticeID = ' . $noticeInfo)) {
					$this->setError(E_DATABASE);
					return false;
				}
				if ($db->getRow($result)) {
					$this->noticeID = $noticeData['noticeID'];
					$this->dateStart = $noticeData['dateStart'];
					$this->dateEnd = $noticeData['dateEnd'];
					$this->noticeType = $this->validNoticeTypes[$noticeData['noticeType']];
					$this->personID = $noticeData['personID'];
					$this->noticeTitle = $noticeData['noticeTitle'];
					$this->noticeBody = $noticeData['noticeBody'];
				} else {
					$this->setError(E_NO_OBJECT, 'Notice::__construct(): no such notice ' . $noticeInfo);
					return false;
				}
			case 'array':
				foreach ($this as $thisKey => $thisValue) {
					if (array_key_exists($thisKey, $noticeInfo)) $this->$thisKey = $noticeInfo[$thisKey];
				}
				if (!$this->validate()) return false;
			default:
				$this->noticeID = null;
				$this->dateStart = null;
				$this->dateEnd = null;
				$this->noticeType = null;
				$this->personID = null;
				$this->noticeTitle = null;
				$this->noticeBody = null;
		}
		$this->clearError();
	}
	
	public function validate () {
		$errorFields = array ();
		$this->noticeID = (int) $this->noticeID;
		$this->dateStart = $this->checkDate($this->dateStart);
		if (!$this->dateStart && !is_null($this->dateStart)) $errorFields[] = 'dateStart';
		$this->dateEnd = $this->checkDate($this->dateEnd);
		if (!$this->dateEnd && !is_null($this->dateEnd)) $errorFields[] = 'dateEnd';
		if (array_key_exists($this->noticeType, $this->validNoticeTypes)) $this->noticeType = $this->validNoticeTypes[$this->noticeType];
		else $errorFields[] = 'noticeType';
		$this->noticeTitle = trim($this->noticeTitle);
		if ($this->noticeType != NOTICE_CLOSURE && !$this->noticeTitle) $errorFields[] = 'noticeTitle';
		$this->personID = (int) $this->personID;
		if ($this->noticeType == NOTICE_PERSON && !$this->personID) $errorFields[] = 'personID';
		$noticeBody = trim($noticeBody);
		if (count($errorFields)) {
			$this->setError(E_INVALID_DATA, $errorFields);
			return false;
		}
	}
	
	public function save () {
		if (!$this->validate()) return false;
		global $db;
		$q = 'REPLACE INTO notice SET noticeID = ' . $this->noticeID ? $this->noticeID : 'null';
		$q .= ', dateStart = ' . $this->dateStart ? '"' . $this->dateStart . '"' : 'null';
		$q .= ', dateEnd = ' . $this->dateEnd ? '"' . $this->dateEnd . '"' : 'null';
		$q .= ', noticeType = ' . $this->noticeType;
		$q .= ', personID = ' . $this->personID ? $this->personID : 'null';
		$q .= ', noticeTitle = "' . $db->cleanString($this->noticeTitle) . '"';
		$q .= ', noticeBody = "' . $db->cleanString($this->noticeBody) . '"';
		if ($db->query($q)) {
			$newNoticeID = mysql_insert_id($db);
			if ($newNoticeID) $this->noticeID = $newNoticeID;
		} else {
			$this->setError(E_DATABASE);
			return false;
		}
		$this->clearError();
	}
	
	public function isActive () {
		if (!$this->noticeID) {
			$this->setError(E_NO_OBJECT_ID, 'Notice::isActive(): no noticeID');
			return false;
		}
		// might have conflict here with cascading errors
		$this->clearError();
		$today = time();
		$dateStart = $this->dateStart ? strtotime($this->dateStart) : $today - 1;
		$dateEnd = $this->dateEnd ? strtotime($this->dateEnd) : $today + 1;
		if ($today >= $dateStart && $today <= $dateEnd) return true;
		else return false;
	}	
}

?>