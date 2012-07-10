<?php

// this file includes DeliverDay and RouteDay

class DeliveryDay extends MarketPrototype {
	public $deliveryDayID;
	public $dateStart;
	public $period = T_WEEK;
	public $active = true;
	public $label;
	public $cutoffDay;
	public $nextDay;
	public $lockToWeekday;

	public function __construct ($deliveryDayInfo = null) {
		switch (gettype($deliveryDayInfo)) {
			case 'integer':
				if (!$deliveryDayInfo) return;
				global $db;
				if (!$db->query('SELECT * FROM deliveryDay WHERE deliveryDayID = ' . $deliveryDayInfo)) {
					$this->setError(E_DATABSE, 'on select query', 'DeliveryDay::construct()');
					return false;
				}
				if (!$r = $db->getRow(F_ASSOC)) {
					$this->setError(E_NO_OBJECT, 'no deliveryDay ' . $deliveryDayInfo, 'DeliveryDay::__construct()');
					return false;
				} else $deliveryDayInfo = $r;
			case 'array':
				$deliveryDayInfo = new Record ($deliveryDayInfo);
			case 'object':
				if (get_class($deliveryDayInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$deliveryDayInfo is a ' . get_class($deliveryDayInfo) . ' rather than the expected Record', 'DeliveryDay::__construct()');
					return false;
				}
				if (!$this->constructFromRecord($deliveryDayInfo)) return false;
				if (!$this->validate()) return false;
				break;
			case 'null':
			default:
				$this->deliveryDayID = null;
				$this->dateStart = null;
				$this->period = T_WEEK;
				$this->active = true;
				$this->label = null;
				$this->cutoffDay = null;
				$this->lockToWeekday = null;
		}
		$this->clearError();
		//global $logger;
		//$logger->addEntry(print_r($this, true));
		return true;
	}

	public function constructFromDeliveryDayID ($deliveryDayID) {
		$deliveryDayID = (int) $deliveryDayID;
		if (!$deliveryDayID) {
			$this->__construct(null);
			return false;
		}
		global $db;
		if (!$db->query('SELECT * FROM deliveryDay WHERE deliveryDayID = ' . $deliveryDayID)) {
			$this->setError(E_DATABASE, 'on grab of deliveryDay ' . $deliveryDayID . ' from database', 'DeliveryDay::constructFromDeliveryDayID()');
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'deliveryDay ' . $deliveryDayID . ' doesn\'t exist', 'DeliveryDay::constructFromDeliveryDayID()');
			return false;
		}
		return $this->constructFromRecord($r);
	}

	public function constructFromArray ($deliveryDayInfo) {
		if (is_array($deliveryDayInfo)) $deliveryDayInfo = new Record ($deliveryDayInfo);
		else {
			$this->setError(E_INVALID_DATA, '$deliveryDayInfo is ' . gettype($deliveryDayInfo) . ' rather than the expected Array or Record', 'DeliveryDay::constructFromArray()');
			return false;
		}
		return $this->constructFromRecord($deliveryDayInfo);
	}

	public function constructFromRecord ($deliveryDayInfo) {
		if (!is_object($deliveryDayInfo)) {
			$this->setError(E_INVALID_DATA, '$deliveryDayInfo is ' . gettype($deliveryDayInfo) . ' rather than the expected Array or Record', 'DeliveryDay::constructFromRecord()');
			return false;
		}
		if (get_class($deliveryDayInfo) != 'Record') {
			$this->setError(E_INVALID_DATA, '$deliveryDayInfo is a ' . get_class($deliveryDayInfo) . ' rather than the expected Record', 'DeliveryDay::constructFromRecord()');
			return false;
		}
		foreach ($this as $k => $v) {
			$v = $deliveryDayInfo->v($k);
			if (!is_null($v)) {
				switch ($k) {
					case 'dateStart':
						if ($v) $this->$k = strtotime($v);
						break;
					case 'active':
					case 'lockToWeekday':
						$this->$k = $deliveryDayInfo->b($k);
						break;
					default:
						$this->$k = $v;
				}
			}
		}
		if (!$this->validate()) return false;
		return true;
	}

	public function validate () {
		$errorFields = array ();
		$this->deliveryDayID = (int) $this->deliveryDayID;
		$this->dateStart = $this->checkDate($this->dateStart);
		if (!$this->dateStart && !is_null($this->dateStart)) $errorFields[] = 'dateStart';
		$this->period = round((int) $this->period / T_DAY) * T_DAY;
		if ($this->period < T_DAY) {
			$errorFields[] = 'period';
			$this->period = null;
		}
		$this->active = (bool) $this->active;
		$this->label = trim ($this->label);
		$this->cutoffDay = (int) $this->cutoffDay;
		$this->lockToWeekday = ($this->period < 0 ? (bool) $this->lockToWeekday : null);
		if ($this->cutoffDay < 0) $errorFields[] = 'cutoffDay';
		if (count($errorFields)) {
			$errorFields[] = 'deliveryDay validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'DeliveryDay::validate()');
			return false;
		} else return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		$t = 'saveDeliveryDay' . $this->deliveryDayID;
		$db->start($t);
		if (!$this->deliveryDayID) {
			if (!$db->query('INSERT INTO deliveryDay (label) VALUES (\'temp\')')) {
				$this->setError(E_DATABASE, 'on creation of new record', 'Person::save()');
				return false;
			}
			$this->deliveryDayID = $db->getLastID();
		}
		//global $logger;
		//$logger->addEntry(print_r($this, true));
		$q = 'UPDATE deliveryDay SET ';
		$q .= 'dateStart = \'' . $db->cleanDate($this->dateStart) . '\'';
		$q .= ', period = ' . (int) $this->period;
		$q .= ', active = ' . ($this->active ? 'true' : 'false');
		$q .= ', label = \'' . $db->cleanString($this->label) . '\'';
		$q .= ', cutoffDay = ' . (int) $this->cutoffDay;
		$q .= ', lockToWeekday = ' . (is_null($this->lockToWeekday) ? 'null' : (int) $this->lockToWeekday);
		$q .= ' WHERE deliveryDayID = ' . $this->deliveryDayID;
		if ($db->query($q, true)) {
			global $logger;
			$logger->addEntry('Saved deliveryDay ' . $this->deliveryDayID, null, 'DeliveryDay::save()');
			$db->commit($t);
			$this->clearError();
			return true;
		} else {
			$this->setError(E_DATABASE, 'on query', 'DeliveryDay::save()');
			$db->rollback($t);
			return false;
		}
	}

	public function delete () {
		if (!$this->deliveryDayID) return false;
		global $db;
		$db->start('deleteDeliveryDay' . $this->deliveryDayID);
		if (!$db->query('DELETE FROM deliveryDay WHERE deliveryDayID = ' . (int) $this->deliveryDayID)) {
			$db->rollback('deletedeliveryDay' . $this->deliveryDayID);
			$this->setError(E_DATABASE, 'on deliveryDay deletion', 'DeliveryDay::delete()');
			return false;
		}
		if (!$db->query('DELETE FROM routeDay WHERE deliveryDayID = ' . (int) $this->deliveryDayID)) {
			$db->rollback('deleteDeliveryDay' . $this->deliveryDayID);
			$this->setError(E_DATABASE, 'on routeDay deletion', 'DeliveryDay::delete()');
			return false;
		}
		$db->commit('deleteDeliveryDay' . $this->deliveryDayID);
		global $logger;
		$logger->addEntry('Deleted deliveryDay ' . $this->deliveryDayID, null, 'DeliveryDay::delete()');
		$this->__construct(null);
		$this->clearError();
		return true;
	}

	public function getRoutes ($sortBy = 'deliverySlot') {
		if (!$this->deliveryDayID) {
			$this->setError(E_NO_OBJECT_ID, 'no deliveryDay ID', 'deliveryDay::getRoutes()');
			return false;
		}
		global $db;
		$q = 'SELECT * FROM route, routeDay WHERE route.routeID = routeDay.routeID AND routeDay.deliveryDayID = ' . $this->deliveryDayID;
		switch ($sortBy) {
			case 'label':
				$q .= ' ORDER BY route.label';
				break;
			case 'deliverySlot':
			default:
				$q .= ' ORDER BY routeDay.deliverySlot';
		}
		if ($db->query($q)) {
			$routes = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$routes[] = new Route ($r);
			}
			$this->clearError();
			return $routes;
		} else {
			$this->setError(E_DATABASE, 'on query', 'DeliveryDay::getRoutes()');
			return false;
		}
	}

	public function getRouteIDs ($sortBy = 'deliverySlot') {
		if (!$this->deliveryDayID) {
			$this->setError(E_NO_OBJECT_ID, 'no deliveryDay ID', 'deliveryDay::getRouteIDs()');
			return false;
		}
		global $db;
		$q = 'SELECT route.routeID AS routeID FROM route, routeDay WHERE route.routeID = routeDay.routeID AND routeDay.deliveryDayID = ' . $this->deliveryDayID;
		switch ($sortBy) {
			case 'label':
				$q .= ' ORDER BY route.label';
				break;
			case 'deliverySlot':
			default:
				$q .= ' ORDER BY routeDay.deliverySlot';
		}
		if ($db->query($q)) {
			$routes = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$routes[] = $r->v('routeID');
			}
			$this->clearError();
			return $routes;
		} else {
			$this->setError(E_DATABASE, 'on query', 'DeliveryDay::getRouteIDs()');
			return false;
		}
	}

	public function getNextDeliveryDay ($day = null, $cutoff = true) {
		if (!$this->deliveryDayID) {
			$this->setError(E_NO_OBJECT_ID, 'no deliveryDay ID', 'DeliveryDay::getNextDeliveryDay()');
			return false;
		}
		global $logger;
		$day = $this->checkDate($day);
		if (!$day) $day = time();
		$day = roundDate($day);
		if (!$day) {
			$this->setError(E_INVALID_DATA, '$day ' . Date::human($day) . ' is not a valid day', 'DeliveryDay::getNextDeliveryDay');
			return false;
		}
		$day = roundDate($day, T_DAY, true);
		$day += ($cutoff ? ($this->cutoffDay * T_DAY) : 0);
		$dateStart = roundDate($this->dateStart, T_DAY, true);
		if ($day < $dateStart) $day = $dateStart;
		if ($this->period > 0) {
			// doesn't check $this->period for sanity -- should it?
			$intervals = ceil(($day - $dateStart) / $this->period);
			$newDay = $dateStart + $intervals * $this->period;
			// The dates have been adjusted to make sure math works regardless of DST;
			// now we have to put it back
			if (date('I', $newDay)) $newDay -= T_HOUR;
		} else if ($this->period < 0) {
			if ($this->lockToWeekday) {
				$weekday = strftime('%A', $dateStart);
				$monthDay = (int) strftime('%e', $dateStart);
				$monthDay = ceil($monthDay / 7);
			}
			$newDay = $dateStart;
			while ($newDay < $day) {
				$newDay = Date::addMonths(abs($this->period), $newDay);
				if ($this->lockToWeekday) $newDay = strtotime($monthDay . ' ' . $weekday . strftime('%b %Y', $newDay));
			}
		} else return false;
		return roundDate($newDay);
	}

	public function getSchedule ($includeStart = false) {
		return Date::human($this->dateStart, $this->period, $includeStart);
	}
}

// RouteDay now inherits from DeliveryDay, for easy access to the DeliveryDay's properties. Methods for DeliveryDay should become useless.

class RouteDay extends DeliveryDay {
	public $routeID;
	public $deliveryDayID;
	public $deliverySlot;
	private $route;

	public function __construct ($routeDayInfo = null, $routeDayInfo2 = null) {
		global $logger;
		switch (gettype($routeDayInfo)) {
			case 'integer':
				if (!$routeDayInfo) return;
				global $db;
				$db->query('SELECT * FROM routeDay WHERE routeID = ' . $routeDayInfo . ' AND deliveryDayID = ' . $routeDayInfo2);
				if ($r = $db->getRow(F_RECORD)) {
					$this->routeID = $r->v('routeID');
					$this->deliveryDayID = $r->v('deliveryDayID');
					$this->deliverySlot = $r->v('deliverySlot');
				} else {
					$this->setError(E_DATABASE, 'on grab of routeDays with routeID ' . $routeDayInfo . ' and deliveryDayID ' . $routeDayInfo2 . ' from database', 'RouteDay::__construct()');
					return false;
				}
				if (!$this->constructFromDeliveryDayID($routeDayInfo2)) return false;
				break;
			case 'array':
				$routeDayInfo = new Record ($routeDayInfo);
			case 'object':
				if (get_class($routeDayInfo) != 'Record') {
					$this->setError(E_INVALID_DATA, '$routeDayInfo is a ' . get_class($routeDayInfo) . ' rather than the expected Record', 'RouteDay::__construct()');
					return false;
				}
				// TODO: may or not have a security issue with being able to set $this->deliveryDay through an array
				if ($routeDayInfo->v('label')) {
					$s = $this->constructFromRecord($routeDayInfo);
					if (!$s) return false;
				} else {
					foreach ($this as $k => $v) {
						$v = $routeDayInfo->v($k);
						if (isset($v)) $this->$k = $v;
					}
					unset($k);
					if ($routeDayInfo->v('deliveryDayID')) $k = 'deliveryDayID';
					else {
						$this->setError(E_NO_OBJECT_ID, 'array does not have a deliveryDayID in it!', 'OrderItem::__construct()');
						return false;
					}
					if (!$this->constructFromDeliveryDayID($routeDayInfo->v($k))) return false;
				}
				if (!$this->validate()) return false;
				break;
			case 'null':
			default:
				$this->routeID = null;
				$this->deliveryDayID = null;
				$this->deliverySlot = null;
				$this->deliveryDay = null;
				$this->route = null;
		}
		$this->clearError();
		global $logger;
		return true;
	}

	public function validate () {
		$errorFields = array ();
		$this->routeID = (int) $this->routeID;
		if (!$this->routeID) $errorFields[] = 'routeID';
		$this->deliveryDayID = (int) $this->deliveryDayID;
		if (!$this->deliveryDayID) $errorFields[] = 'deliveryDayID';
		$this->deliverySlot = (int) $this->deliverySlot;
		if (count($errorFields)) {
			$errorFields[] = 'routeDay validate';
			$this->setError(E_INVALID_DATA, $errorFields, 'RouteDay::validate()');
			return false;
		} else return true;
	}

	public function save () {
		if (!$this->validate()) return false;
		global $db;
		$db->start('saveRouteDay');
		if (!$db->query('DELETE FROM routeDay WHERE routeID = ' . $this->routeID . ' AND deliveryDayID = ' . $this->deliveryDayID, true)) {
			$this->setError(E_DATABASE, 'on deletion of old routeDay record', 'RouteDay::save()');
			return false;
		}
		$q = 'INSERT INTO routeDay (routeID, deliveryDayID, deliverySlot) VALUES (' . $this->routeID . ', ' . $this->deliveryDayID;
		if (!$this->deliverySlot) {
			if (!$db->query('SELECT MAX(deliverySlot) AS lastSlot FROM routeDay WHERE deliveryDayID = ' . $this->deliveryDayID)) {
				$this->setError(E_DATABASE, 'can\'t find maximum delivery slot', 'RouteDay::save()');
				$db->rollback('saveRouteDay');
				return false;
			}
			if ($r = $db->getRow(F_RECORD)) $lastSlot = (int) $r->v('lastSlot');
			else $lastSlot = 0;
			$q .= ', ' . ($lastSlot + 1) . ')';
		} else $q .= ', ' . $this->deliverySlot . ')';
		if ($db->query($q, true)) {
			$this->clearError();
			$db->commit('saveRouteDay');
			return true;
		} else {
			$this->setError(E_DATABASE, 'on save', 'RouteDay::save()');
			$db->rollback('saveRouteDay');
			return false;
		}
	}

	public function delete () {
		if (!$this->deliveryDayID || !$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'either deliveryDayID ' . $this->deliveryDayID . ' or routeID ' . $this->routeID . ' is missing', 'RouteDay::delete()');
			return false;
		}
		global $db;
		// call to setDeliverySlot() to close up gap before deleting
		$db->start('deleteRouteDay' . $this->routeID);
		if (!$db->query('SELECT deliverySlot FROM routeDay WHERE routeID = ' . $this->routeID . ' AND deliveryDayID = ' . $this->deliveryDayID)) {
			$this->setError(E_DATABASE, 'on check and update of deliverySlot', 'RouteDay::delete()');
			$db->rollback('deleteRouteDay' . $this->routeID);
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'this routeDay doesn\'t appear in the database', 'RouteDay::delete()');
			$db->rollback('deleteRouteDay' . $this->routeID);
			return false;
		}
		$this->deliverySlot = $r->v('deliverySlot');
		if (!$db->query('DELETE FROM routeDay WHERE routeID = ' . $this->routeID . ' AND deliveryDayID = ' . $this->deliveryDayID)) {
			$this->setError(E_DATABASE, 'on delete', 'RouteDay::delete()');
			$db->rollback('deleteRouteDay' . $this->routeID);
			return false;
		}
		if (!$db->query('UPDATE routeDay SET deliverySlot = deliverySlot - 1 WHERE deliverySlot > ' . $this->deliverySlot . ' AND deliveryDayID = ' . $this->deliveryDayID)) {
			$this->setError(E_DATABASE, 'on closing of deliverySlot gap', 'RouteDay::delete()');
			$db->rollback('deleteRouteDay' . $this->routeID);
			return false;
		}
		$db->commit('deleteRouteDay' . $this->routeID);
		global $logger;
		$logger->addEntry('Deleted (routeID ' . $this->routeID . ', deliveryDayID ' . $this->deliveryDayID . ')', null, 'RouteDay::delete()');
		$this->__construct(null);
		return true;
	}

	public function getRoute () {
		if (!$this->deliveryDayID || !$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'either deliveryDayID ' . $this->deliveryDayID . ' or routeID ' . $this->routeID . ' is missing', 'RouteDay::getRoute()');
			return false;
		}
		if (get_class($this->route) == 'Route') return $this->route;
		if (!$route = new Route ((int) $this->routeID)) {
			$this->setError(E_NO_OBJECT, 'route creation returned this error: ' . $GLOBALS['errorCodes'][$route->getError()] . ' (' . $route->getErrorDetail() . ')', 'RouteDay::getRoute()');
			return false;
		}
		$this->route = $route;
		return $route;
	}

	public function getDeliverySlot () {
		return $this->deliverySlot;
	}

	public function isLastSlot () {
		if (!$this->deliveryDayID || !$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'either deliveryDayID ' . $this->deliveryDayID . ' or routeID ' . $this->routeID . ' is missing', 'RouteDay::isLastSlot()');
			return false;
		}
		global $db;
		if ($this->getLastSlot() == $this->deliverySlot) return true;
		return false;
	}

	public function getLastSlot () {
		if (!$this->deliveryDayID || !$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'either deliveryDayID ' . $this->deliveryDayID . ' or routeID ' . $this->routeID . ' is missing', 'RouteDay::getLastSlot()');
			return false;
		}
		global $db;
		if (!$db->query('SELECT MAX(deliverySlot) as maxDeliverySlot FROM routeDay WHERE deliveryDayID = ' . $this->deliveryDayID)) {
			$this->setError(E_DATABASE, 'on query', 'RouteDay::getLastSlot()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) return (int) $r->v('maxDeliverySlot');
		return false;
	}

	public function setDeliverySlot ($newSlot, $changeMode = MODE_ABSOLUTE) {
		global $logger;
		if (!$this->deliveryDayID || !$this->routeID) {
			$this->setError(E_NO_OBJECT_ID, 'either deliveryDayID ' . $this->deliveryDayID . ' or routeID ' . $this->routeID . ' is missing', 'RouteDay::setDeliverySlot()');
			return false;
		}
		global $db;
		$db->start('setDeliverySlot' . $this->routeID);
		if (!$db->query('SELECT MAX(deliverySlot) AS lastSlot FROM routeDay WHERE deliveryDayID = ' . (int) $this->deliveryDayID, true)) {
			$this->setError(E_DATABASE, 'on selection of max delivery slot', 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) $lastSlot = $r->v('lastSlot');
		else $lastSlot = 0;
		if (!$db->query('SELECT deliverySlot FROM routeDay WHERE deliveryDayID = ' . (int) $this->deliveryDayID . ' AND routeID = ' . (int) $this->routeID, true)) {
			$this->setError(E_DATABASE, 'on check for existence and value of current routeDay\'s deliverySlot', 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			return false;
		}
		if (!$r = $db->getRow(F_RECORD)) {
			$this->setError(E_NO_OBJECT, 'this routeDay (routeID ' . $this->routeID . ', deliveryDayID ' . $this->deliveryDayID . ') doesn\'t exist yet. You should save it before you try to change its deliverySlot.', 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			return false;
		}
		$this->deliverySlot = (int) $r->v('deliverySlot');
		switch ($changeMode) {
			case MODE_ABSOLUTE:
				$newSlot = (int) $newSlot;
				break;
			case MODE_RELATIVE:
				$newSlot = $this->deliverySlot + (int) $newSlot;
		}
		if ($newSlot < 1 || $newSlot > $lastSlot || $newSlot == $this->deliverySlot) {
			$this->setError(E_INVALID_DATA, 'Nothing to move (deliverySlot = ' . $this->deliverySlot . ', newSlot = ' . $newSlot . ', lastSlot = ' . $lastSlot . ')', 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			// originally returned true; now returns false. Does not throw error though.
			return false;
		}
		$greater = ($newSlot > $this->deliverySlot ? true : false);
		if (!$db->query('UPDATE routeDay SET deliverySlot = deliverySlot ' . ($greater ? '-' : '+') . ' 1 WHERE deliverySlot BETWEEN ' . ($greater ? $this->deliverySlot : $newSlot) . ' AND ' . ($greater ? $newSlot : $this->deliverySlot) . ' AND deliveryDayID = ' . (int) $this->deliveryDayID, true)) {
			$this->setError(E_DATABASE, 'On move of slots in between this (' . $this->deliverySlot . ') and other (' . $newSlot . ')', 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			return false;
		}
		if (!$db->query('UPDATE routeDay SET deliverySlot = ' . $newSlot . ' WHERE routeID = ' . $this->routeID . ' AND deliveryDayID = ' . $this->deliveryDayID, true)) {
			$this->setError(E_DATABASE, 'On move of current routeDay\'s slot from ' . $this->deliverySlot . ' to ' . $newSlot, 'RouteDay::setDeliverySlot()');
			$db->rollback('setDeliverySlot' . $this->routeID);
			return false;
		}
		$oldSlot = $this->deliverySlot;
		$this->deliverySlot = $newSlot;
		$db->commit('setDeliverySlot' . $this->routeID);
		global $logger;
		return true;
	}

	public function moveUp () {
		return $this->setDeliverySlot(-1, MODE_RELATIVE);
	}

	public function moveDown () {
		return $this->setDeliverySlot(1, MODE_RELATIVE);
	}
}

?>
