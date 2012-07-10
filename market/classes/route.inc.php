<?php

class Route extends MarketPrototype {
	public $routeID;
	public $label;
	public $active = true;
	public $routeDays = array ();

	public function __construct ($routeInfo = null) {
		switch (gettype($routeInfo)) {
			case 'integer':
				if (!$routeInfo) return;
				global $db;
				if (!$db->query('SELECT * FROM route WHERE routeID = ' . $routeInfo)) {
					$this->setError(E_DATABASE, 'on query for route ' . $routeInfo, 'Route::__construct()');
					return false;
				}
				if ($r = $db->getRow(F_RECORD)) {
					$this->routeID = $routeInfo;
					$this->label = $r->v('label');
					$this->active = $r->v('active');
					if (!$db->query('SELECT * FROM routeDay, deliveryDay WHERE routeDay.routeID = ' . $routeInfo . ' AND routeDay.deliveryDayID = deliveryDay.deliveryDayID')) {
						$this->setError(E_DATABASE, 'on gettage of delivery days for route ' . $routeInfo, 'Route::__construct()');
						return false;
					}
					$this->routeDays = array ();
					$routeDaysData = array ();
					while ($r = $db->getRow(F_RECORD)) {
						$routeDaysData[] = $r;
					}
					foreach ($routeDaysData as $thisRouteDayData) {
						if ($thisRouteDay = new RouteDay ($thisRouteDayData)) $this->routeDays[$thisRouteDayData->v('deliveryDayID')] = $thisRouteDay;
					}
					// $this->validate();
				} else {
					$this->setError(E_NO_OBJECT, 'no route ' . $routeInfo, 'Route::__construct()');
					return false;
				}
				break;
			case 'array':
				$routeInfo = new Record ($routeInfo);
			case 'object':
				if (get_class($routeInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$routeInfo is a ' . get_class($routeInfo) . ' rather than the expected Record', 'Route::__construct()');
					return false;
				}
				foreach ($this as $k => $v) {
					$v = $routeInfo->v($k);
					if (!is_null($v)) $this->$k = $v;
					if ($k == 'active') $this->$k = $routeInfo->b($k);
				}
				if (!$this->validate()) return false;
				break;
			case 'null':
			default:
				$this->routeID = null;
				$this->label = null;
				$this->active = true;
				$this->routeDays = array ();
		}
		$this->clearError();
		return true;
	}

	public function validate () {
		$errorFields = array ();
		$this->routeID = (int) $this->routeID;
		if (!$this->label) $errorFields[] = 'label';
		$this->active = $this->active ? true : false;
		foreach ($this->routeDays as $thisDay => $thisRouteDay) {
			if (get_class($thisRouteDay) != 'RouteDay' || (get_class($thisRouteDay) == 'RouteDay' && !$thisRouteDay->validate())) {
				// unset($this->routeDays[$thisIndex]); // don't know if I should unset this or not
				$errorFields[] = 'routeDay' . $thisDay;
			}
		}
		if (count($errorFields)) {
			$errorFields[] = 'Route verify';
			$this->setError(E_INVALID_DATA, $errorFields, 'Route::validate()');
			return false;
		}
		return true;
	}

	public function getRouteDays () {
		return $this->routeDays;
	}

	public function addRouteDay ($deliveryDayID) {
		$deliveryDayID = (int) $deliveryDayID;
		if (isset($this->routeDays[$deliveryDayID])) return false;
		global $db;
		if (!$db->query('SELECT deliveryDayID FROM deliveryDay WHERE deliveryDayID = ' . $deliveryDayID)) {
			$this->setError(E_DATABASE, 'on check for existence of deliveryDayID ' . $deliveryDayID . ' on route ' . $this->routeID, 'Route::addRouteDay()');
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, $deliveryDayID . ' is not set up as a delivery day (route ' . $this->routeID . ')', 'Route::addRouteDay()');
			return false;
		} else {
			$routeDayData = array ('routeID' => $this->routeID, 'deliveryDayID' => $deliveryDayID);
			$this->routeDays[$deliveryDayID] = new RouteDay ($routeDayData);
			$this->routeDays[$deliveryDayID]->save();
		}
		$this->clearError();
		return true;
	}

	public function deleteRouteDay ($deliveryDayID) {
		$deliveryDayID = (int) $deliveryDayID;
		if (isset($this->routeDays[$deliveryDayID])) {
			$this->routeDays[$deliveryDayID]->delete();
			unset($this->routeDays[$deliveryDayID]);
			return true;
		} else return false;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		$t = 'saveRoute' . $this->routeID;
		$db->start($t);
		if (!$this->routeID) {
			if (!$db->query('INSERT INTO route (label) VALUES (\'' . $db->cleanString($this->label) . '\')')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Route::save()');
				$db->rollback($t);
				return false;
			}
			$this->routeID = $db->getLastID();
		}
		$q = 'UPDATE route SET ';
		$q .= 'label = \'' . $db->cleanString($this->label) . '\'';
		$q .= ', active = ' . ($this->active ? 'true' : 'false');
		$q .= ' WHERE routeID = ' . $this->routeID;
		if (!$db->query($q, true)) {
			$this->setError(E_DATABASE, 'on save of route ' . $this->routeID, 'Route::save()');
			$db->rollback($t);
			return false;
		}
		$db->commit($t);
		global $logger;
		$logger->addEntry('Saved route ' . $this->routeID . ' (' . $this->label . ')', null, 'Route::save()');
		$this->clearError();
		return true;
	}

	public function delete () {
		if (!$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'No routeID', 'Route::delete()');
			return false;
		}
		global $db;
		$db->start('deleteRoute' . $this->routeID);
		foreach ($this->routeDays as $thisRouteDay) {
			$thisRouteDay->delete();
		}
		if (!$db->query('DELETE FROM route WHERE routeID = ' . (int) $this->routeID)) {
			$this->setError(E_DATABASE, 'on deletion of route ' . $this->routeID, 'Route::delete()');
			$db->rollback('deleteRoute' . $this->routeID);
			return false;
		}
		$this->routeDays = array ();
		if (!$db->query('UPDATE person SET routeID = null, deliverySlot = null WHERE routeID = ' . (int) $this->routeID)) {
			$this->setError(E_DATABASE, 'on update of associated people for route ' . $this->routeID, 'Route::delete()');
			$db->rollback('deleteRoute' . $this->routeID);
			return false;
		}
		$db->commit('deleteRoute' . $this->routeID);
		global $logger;
		$logger->addEntry('Deleted route ' . $this->routeID, null, 'Route::delete()');
		$this->__construct(null);
		$this->clearError();
		return true;
	}

	function getPeople () {
		global $db;
		if (!$db->query('SELECT * FROM person WHERE ' . ($this->routeID ? 'routeID = ' . (int) $this->routeID : '(routeID IS NULL OR routeID = 0)') . ' AND (personType & ' . (P_SUPPLIER + P_DEPOT + P_CUSTOMER) . ') > 0 ORDER BY deliverySlot', true)) {
			$this->setError(E_DATABASE, 'on query for route ' . $this->routeID, 'Route::getPeople()');
			return false;
		}
		$people = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$people[$r->v('personID')] = $r;
		}
		foreach ($people as $k => $v) {
			$people[$k] = new Person($v);
			if (!$this->routeID) {
				if ($people[$k]->getRouteID()) unset($people[$k]);
			}
		}
		$this->clearError();
		return $people;
	}

	function getNextDeliveryDay ($day = null, $accountForCutoff = true, $dateStart = null, $period = null) {
		global $logger;
		$day = $this->checkDate($day);
		if (!$day) $day = time();
		$day = $this->roundDate($day);
		global $config, $dayNames, $db;
		if ($dateStart) {
			$dateStart = $this->checkDate($dateStart);
			$dateStart = $this->roundDate($dateStart);
			if (!$dateStart) {
				$this->setError(E_INVALID_DATA, $dateStart . ' is not a valid timestamp or date string (route ' . $this->routeID . ')', 'Route::getNextDeliveryDay()');
				return false;
			}
			$dateStart = $this->getNearestDeliveryDay ($dateStart, true);
			// if ($day < $dateStart) $day = $dateStart;
			$period = (int) $period;
			if ($period > 0) {
				$period = round((int) $period / T_DAY) * T_DAY;
				if ($period < T_DAY) {
					$this->setError(E_INVALID_DATA, 'Period ' . $period . ' is smaller than a day', 'Route::getNextDeliveryDay()');
					return false;
				}
				$intervals = ($day - $dateStart) / $period;
				if ($intervals < 0) $intervals = 0;
				$intervals = ceil($intervals);
				$roundedDay = $dateStart + $intervals * $period;
				list ($newDay, $cutoff) = $this->getNearestDeliveryDay($roundedDay, false, true);
				if ($accountForCutoff) {
					if ($day + $cutoff * T_DAY > $newDay) {
						$newDay += $period;
						$newDay = $this->getNearestDeliveryDay($newDay, false);
					}
				}
				return $newDay;
				/* $roundedDayPrev = $roundedDay - $period;
				list ($newDayPrev, $cutoff) = $this->getNearestDeliveryDay($roundedDayPrev, true, true);
				if ($day == $dateStart && $cutoff) return $newDay;
				else return ($newDayPrev - ($accountForCutoff ? $cutoff : 0) * T_DAY >= $day) ? $newDayPrev : $newDay; */
			}
		}
		return $this->getNearestDeliveryDay($day, $accountForCutoff);
	}

	// $d is calculated delta between
	protected function getNearestDeliveryDay ($day, $accountForCutoff, $returnCutoff = false) {
		$earliest = null;
		$cutoffDay = 0;
		foreach ($this->routeDays as $v) {
			$nextDay = $v->getNextDeliveryDay($day, $accountForCutoff);
			if (is_null($earliest) || $earliest > $nextDay) {
				$earliest = $nextDay;
				$cutoffDay = $v->cutoffDay;
			}
		}
		return ($returnCutoff ? array ($earliest, $cutoffDay) : $earliest);
	}

	// TODO: CLEANUP: maybe I need to do some more data sanitisation in this function; it assumes a lot
	public function getLastDeliveryDay ($day = null, $accountForCutoff = true, $dateStart = null, $period = null, $weekday = null) {
		if (!$day) $day = time();
		else if (is_string($day)) $day = strtotime($day);
		if (!is_int($day) || !$day) return false;
		$day = $this->roundDate($day);
		if (is_string($dateStart)) $dateStart = strtotime($dateStart);
		$dateStart = $this->roundDate($dateStart);
		if (!$dateStart) {
			$this->setError(E_INVALID_DATA, $dateStart . ' doesn\'t appear to be a valid date string or timestamp (route ' . $this->routeID . ')', 'Route::getLastDeliveryDay()');
			return false;
		}
		$nextDeliveryDay = $this->getNextDeliveryDay($day, $accountForCutoff, $dateStart, $period, $weekday);
		if (!$nextDeliveryDay) return false;
		if ($nextDeliveryDay == $day) return $nextDeliveryDay;
		$period = ((int) $period ? abs((int) $period) : 1);
		$subtraction = $nextDeliveryDay - ($period * T_WEEK);
		if ($subtraction < $dateStart) return false;
		else return $subtraction;
	}

	public function getRouteDay ($day, $returnAll = false) {
		// returns first instance of a routeDay that corresponds to that
		// date, unless $returnAll is true, in which case it'll check
		// them all
		if (!$day) $day = time ();
		if (!$day = myCheckDate($day)) {
			$this->setError(E_INVALID_DATA, '$day is not a valid date', 'Route::getRouteDay()');
			return false;
		}
		$day = roundDate($day);
		if ($returnAll) $routeDays = array ();
		foreach ($this->routeDays as $thisDay) {
			if ($thisDay->getNextDeliveryDay($day, false) == $day) {
				if ($returnAll) $routeDays[$thisDay->deliveryDayID] = $thisDay;
				else return $thisDay;
			}
		}
		if ($returnAll) return $routeDays;
		else return false;
	}

	public function getCutoffDay ($day) {
		if (!$day) $day = $this->getNextDeliveryDay();
		if ($day = $this->getRouteDay($day)) return $day->cutoffDay;
		else return false;
	}

	public function getSchedule ($includeStart = false) {
		if (!count($this->routeDays)) {
			$this->setError(E_NO_OBJECT, 'This route is not attached to any days', 'Route::getSchedule()');
			return false;
		}
		$schedule = array ();
		foreach ($this->routeDays as $v) {
			$schedule[$v->deliveryDayID] = $v->getSchedule ($includeStart);
		}
		return $schedule;
	}
}

?>
