-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 10, 2012 at 10:14 AM
-- Server version: 5.5.24
-- PHP Version: 5.3.10-1ubuntu3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `localmotive`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE IF NOT EXISTS `address` (
  `addressID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `personID` int(11) NOT NULL,
  `routeID` int(10) unsigned DEFAULT NULL,
  `addressType` tinyint(4) NOT NULL,
  `careOf` varchar(255) DEFAULT NULL,
  `address1` varchar(255) NOT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `prov` varchar(50) DEFAULT NULL,
  `postalCode` varchar(50) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `directions` text,
  `phone` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`addressID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`addressID`, `personID`, `routeID`, `addressType`, `careOf`, `address1`, `address2`, `city`, `prov`, `postalCode`, `country`, `directions`, `phone`) VALUES
(1, -1, NULL, 1, '', 'asefasefasefasef', NULL, 'Penticton', 'BC', 'V2A 6Y2', NULL, NULL, NULL),
(2, 2, NULL, 1, '', 'fasefaseasef', NULL, 'Penticton', 'BC', 'V2A 6Y2', NULL, NULL, NULL),
(3, 3, NULL, 1, '', 'asefasefasefasef', NULL, 'Penticton', 'BC', 'V2A 6Y2', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `configID` varchar(50) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`configID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`configID`, `value`) VALUES
('beta', '1'),
('blockFailedLoginTime', '300'),
('bottleDeposit', '1.5'),
('defaultPaymentType', '2'),
('depositID', '11'),
('docRoot', ''),
('email', 'alex@localmotive.ca'),
('errorReporting', '33554367'),
('gMapsKey', 'ABQIAAAAFFDAmn3ZJ6l6touLHqHU2xSyordhPYUxkmXD_TGOneu-aIw4uxSktGb-0lAULA_n0El-Ul5iVCIVsQ'),
('hst', '12'),
('humanDateFormat', '%d/%m/%y %T'),
('locale', 'en_CA.utf8'),
('loginPersistence', '31104000'),
('marketOpen', 'true'),
('maxLoginAttempts', '8'),
('postalCodeFormat', '/^(?!.*[DFIOQU])[A-VXY][0-9][A-Z]\\ {0,1}[0-9][A-Z][0-9]$/'),
('provDefault', 'BC'),
('provMax', '2'),
('pst', '7'),
('rootSecureUrl', 'https://localmotive.local'),
('rootUrl', 'http://localmotive.local'),
('timeZone', '-8'),
('webmaster', 'webguy@localmotive.ca');

-- --------------------------------------------------------

--
-- Table structure for table `deliveryDay`
--

CREATE TABLE IF NOT EXISTS `deliveryDay` (
  `deliveryDayID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `cutoffDay` tinyint(1) NOT NULL DEFAULT '0',
  `dateStart` datetime NOT NULL,
  `period` int(11) NOT NULL DEFAULT '604800',
  `lockToWeekday` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`deliveryDayID`),
  UNIQUE KEY `deliveryDayID` (`deliveryDayID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `deliveryDay`
--

INSERT INTO `deliveryDay` (`deliveryDayID`, `label`, `active`, `cutoffDay`, `dateStart`, `period`, `lockToWeekday`) VALUES
(1, 'Milk run', 1, 0, '2012-03-14 00:00:00', 604800, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `itemID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dateCreated` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `nodePath` varchar(500) DEFAULT NULL,
  `sortOrder` smallint(6) DEFAULT NULL,
  `itemType` smallint(6) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `isKit` tinyint(1) DEFAULT '0',
  `label` varchar(255) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `distance` smallint(6) DEFAULT NULL,
  `description` text,
  `image` tinyint(1) DEFAULT '0',
  `quantity` int(11) DEFAULT NULL,
  `reorderQuantity` int(11) DEFAULT NULL,
  `runningOutQuantity` int(11) DEFAULT NULL,
  `canOrderPastZero` tinyint(1) DEFAULT NULL,
  `cutoffDay` smallint(6) DEFAULT NULL,
  `trackInventory` tinyint(1) DEFAULT NULL,
  `supplierID` int(11) DEFAULT NULL,
  `specialPacking` tinyint(1) DEFAULT NULL,
  `availableToRecurring` tinyint(1) DEFAULT NULL,
  `csaRequired` tinyint(1) DEFAULT NULL,
  `organic` tinyint(1) DEFAULT NULL,
  `canBePermanent` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`itemID`),
  UNIQUE KEY `itemID` (`itemID`),
  KEY `item_supplierID_fkey` (`supplierID`),
  KEY `sku` (`sku`),
  KEY `nodePath` (`nodePath`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`itemID`, `dateCreated`, `active`, `nodePath`, `sortOrder`, `itemType`, `sku`, `isKit`, `label`, `location`, `distance`, `description`, `image`, `quantity`, `reorderQuantity`, `runningOutQuantity`, `canOrderPastZero`, `cutoffDay`, `trackInventory`, `supplierID`, `specialPacking`, `availableToRecurring`, `csaRequired`, `organic`, `canBePermanent`) VALUES
(1, '2008-02-11 10:44:01', 1, '/1', 1, 1, NULL, 0, 'Market', '', NULL, 'The containing category for everything', 0, 0, NULL, NULL, 1, 0, 1, NULL, 0, 1, 0, 1, 1),
(2, '2009-04-08 22:41:58', 1, '/1/2', 1, 1, NULL, 0, 'Dairy', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, NULL, NULL, NULL, 1),
(3, '2009-04-08 22:42:11', 1, '/1/3', 2, 1, NULL, 0, 'Meats', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(4, '2009-04-08 22:42:28', 1, '/1/4', 3, 1, NULL, 0, 'Bulk Goods', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(5, '2009-04-08 22:42:51', 1, '/1/5', 4, 1, NULL, 0, 'Produce', '', NULL, '', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '2009-04-08 22:43:01', 1, '/1/6', 5, 1, NULL, 0, 'Baked Goods', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 1),
(7, '2009-04-08 22:43:09', 1, '/1/7', 6, 1, NULL, 0, 'Extras', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(8, '2009-04-10 20:24:19', 1, '/1/8', 7, 0, NULL, 0, 'Deposit', '', NULL, 'Stub for deposit amount, available to everyone - DO NOT DELETE', 0, 0, NULL, NULL, 1, 0, 0, NULL, 0, NULL, NULL, 0, NULL),
(9, '2012-03-13 13:03:44', 1, '/1/5/9', 1, 0, NULL, 0, 'One single cherry', 'a basket', NULL, 'ateasetatestasetasetaset', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '2012-03-13 13:26:49', 1, '/1/2/10', 3, 0, NULL, 0, 'Milk, 2L homogenised', 'Jerseyland Farms', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, '2012-03-19 15:17:45', 1, '/1/2/24', 1, 1, NULL, 0, 'Cheese', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, '2012-03-19 15:39:29', 1, '/1/2/25', 2, 1, NULL, 0, 'Ice cream', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, '2012-03-19 15:41:10', 1, '/1/2/26', 4, 1, NULL, 0, 'Yogurt', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, '2012-03-19 15:41:29', 1, '/1/2/25/27', 1, 0, NULL, 0, 'Chocolate ripple', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, '2012-03-19 15:58:35', 1, '/1/2/25/28', 2, 0, NULL, 0, 'Gorilla', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, '2012-03-20 17:22:27', 1, '/1/2/25/29', 3, 1, NULL, 0, 'Soy-based', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, '2012-03-20 17:22:49', 1, '/1/2/25/29/30', 1, 0, NULL, 0, 'BLAHHHHH!', '', NULL, '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `journalEntry`
--

CREATE TABLE IF NOT EXISTS `journalEntry` (
  `journalEntryID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `personID` int(11) NOT NULL,
  `orderID` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `dateCreated` datetime DEFAULT NULL,
  `notes` text,
  `payTypeID` int(11) DEFAULT NULL,
  `txnID` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`journalEntryID`),
  KEY `journalEntry_orderID_fkey` (`orderID`),
  KEY `journalEntry_personID_fkey` (`personID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `journalEntry`
--

INSERT INTO `journalEntry` (`journalEntryID`, `personID`, `orderID`, `amount`, `dateCreated`, `notes`, `payTypeID`, `txnID`) VALUES
(1, 3, 4, -35.10, '2012-06-25 13:24:12', 'Total from order # 4', 4, NULL),
(2, 3, 4, 35.10, '2012-06-25 13:24:13', 'Payment on order # 4', 3, 'w6Gp8U2jo3kDEc+eZvNc9RLEztGyGvekoTcy8UhxHtHL9j+06yTqp3F2tAyjxQmdEG/JZSaecD4R5ZkwgXxU/uzzgQLzTEZqzchdxURiy4+Ziy/y');

-- --------------------------------------------------------

--
-- Table structure for table `kitItem`
--

CREATE TABLE IF NOT EXISTS `kitItem` (
  `kitID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  PRIMARY KEY (`kitID`,`itemID`),
  KEY `kitItem_itemID_fkey` (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `logEntry`
--

CREATE TABLE IF NOT EXISTS `logEntry` (
  `logEntryID` int(11) NOT NULL,
  `dateCreated` datetime NOT NULL,
  `personID` int(11) DEFAULT NULL,
  `errorCode` int(11) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `requestVars` text,
  `sessionVars` text,
  `entryText` text,
  PRIMARY KEY (`logEntryID`),
  KEY `logEntry_personID_fkey` (`personID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `orderItem`
--

CREATE TABLE IF NOT EXISTS `orderItem` (
  `orderID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `quantityOrdered` smallint(6) DEFAULT NULL,
  `quantityDelivered` smallint(6) DEFAULT NULL,
  `permanent` tinyint(1) NOT NULL DEFAULT '0',
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `discount` decimal(4,2) DEFAULT NULL,
  `tax` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`orderID`,`itemID`),
  KEY `orderItem_itemID_fkey` (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `orderItem`
--

INSERT INTO `orderItem` (`orderID`, `itemID`, `quantityOrdered`, `quantityDelivered`, `permanent`, `unitPrice`, `discount`, `tax`) VALUES
(4, 9, 1, NULL, 0, 15.00, 0.00, 0),
(4, 27, 1, NULL, 0, 5.00, 0.00, 0),
(4, 30, 1, NULL, 0, 7.00, 0.00, 0),
(5, 9, 4, NULL, 0, NULL, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `orderID` int(11) NOT NULL AUTO_INCREMENT,
  `personID` int(11) NOT NULL,
  `addressID` int(11) unsigned DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `csa` tinyint(1) NOT NULL DEFAULT '0',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `orderType` smallint(6) NOT NULL,
  `recurringOrderID` int(11) unsigned DEFAULT NULL,
  `period` int(11) DEFAULT '1',
  `lockToRoute` tinyint(1) NOT NULL DEFAULT '1',
  `dateStarted` datetime DEFAULT NULL,
  `dateCheckedOut` datetime DEFAULT NULL,
  `dateCompleted` datetime DEFAULT NULL,
  `dateToDeliver` date DEFAULT NULL,
  `dateResume` datetime DEFAULT NULL,
  `dateDelivered` datetime DEFAULT NULL,
  `dateCanceled` datetime DEFAULT NULL,
  `payTypeID` int(11) DEFAULT NULL,
  `hst` decimal(4,2) DEFAULT NULL,
  `pst` decimal(4,2) DEFAULT NULL,
  `surcharge` decimal(4,2) unsigned DEFAULT NULL,
  `surchargeType` tinyint(4) unsigned DEFAULT NULL,
  `shipping` decimal(4,2) DEFAULT NULL,
  `shippingType` tinyint(4) DEFAULT NULL,
  `discount` decimal(4,2) unsigned DEFAULT NULL,
  `discountType` tinyint(4) unsigned DEFAULT NULL,
  `stars` smallint(6) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`orderID`),
  KEY `orders_personID_fkey` (`personID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderID`, `personID`, `addressID`, `label`, `csa`, `editable`, `orderType`, `recurringOrderID`, `period`, `lockToRoute`, `dateStarted`, `dateCheckedOut`, `dateCompleted`, `dateToDeliver`, `dateResume`, `dateDelivered`, `dateCanceled`, `payTypeID`, `hst`, `pst`, `surcharge`, `surchargeType`, `shipping`, `shippingType`, `discount`, `discountType`, `stars`, `balance`, `notes`) VALUES
(4, 3, 0, NULL, 0, 1, 13, NULL, NULL, 1, '2012-03-20 13:35:27', NULL, '2012-06-25 20:24:12', '2012-06-27', NULL, NULL, NULL, NULL, 12.00, 7.00, NULL, NULL, 30.00, 1, NULL, NULL, -1, NULL, ''),
(5, 3, 0, NULL, 0, 1, 13, NULL, NULL, 1, '2012-06-25 13:37:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12.00, 7.00, NULL, NULL, 30.00, 1, NULL, NULL, NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `payType`
--

CREATE TABLE IF NOT EXISTS `payType` (
  `payTypeID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `labelShort` varchar(5) DEFAULT NULL,
  `labelLong` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `surcharge` float DEFAULT NULL,
  `surchargeType` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`payTypeID`),
  UNIQUE KEY `paymentTypeID` (`payTypeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `payType`
--

INSERT INTO `payType` (`payTypeID`, `label`, `labelShort`, `labelLong`, `active`, `surcharge`, `surchargeType`) VALUES
(1, 'Cheque/cash', 'Ch', 'Pay by cheque or cash', 1, NULL, NULL),
(2, 'PayPal', 'PP', 'Pay by PayPal', 1, 3, 1),
(3, 'Credit Card', 'CC', 'Pay by credit card', 1, 0, 1),
(4, 'Account', 'Ac', 'Charge to account', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE IF NOT EXISTS `person` (
  `personID` int(11) NOT NULL AUTO_INCREMENT,
  `dateCreated` datetime DEFAULT NULL,
  `lastLogin` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `nodePath` varchar(500) DEFAULT NULL,
  `sortOrder` smallint(6) DEFAULT NULL,
  `personType` smallint(6) DEFAULT NULL,
  `deliverySlot` smallint(6) DEFAULT NULL,
  `contactName` varchar(255) DEFAULT NULL,
  `groupName` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `privateKey` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `routeID` int(11) DEFAULT NULL,
  `customCancelsRecurring` tinyint(1) DEFAULT '0',
  `canCustomOrder` tinyint(1) DEFAULT NULL,
  `payTypeIDs` varchar(20) DEFAULT NULL,
  `payTypeID` int(10) unsigned DEFAULT NULL,
  `compost` tinyint(1) NOT NULL DEFAULT '0',
  `minOrder` decimal(10,2) DEFAULT NULL,
  `minOrderDeliver` decimal(10,2) unsigned DEFAULT NULL,
  `bulkDiscount` decimal(4,2) DEFAULT NULL,
  `bulkDiscountQuantity` smallint(6) DEFAULT NULL,
  `shipping` decimal(4,2) DEFAULT NULL,
  `shippingType` tinyint(4) DEFAULT NULL,
  `maxStars` smallint(6) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  `credit` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stars` smallint(6) NOT NULL DEFAULT '0',
  `recent` tinyint(1) NOT NULL DEFAULT '0',
  `bins` smallint(6) DEFAULT NULL,
  `coldpacks` smallint(6) DEFAULT NULL,
  `bottles` smallint(6) DEFAULT NULL,
  `notes` text,
  `description` text,
  `image` tinyint(1) NOT NULL DEFAULT '0',
  `website` varchar(255) DEFAULT NULL,
  `sessionID` varchar(255) DEFAULT NULL,
  `cookieID` varchar(255) DEFAULT NULL,
  `cc` varchar(255) DEFAULT NULL,
  `txnID` varchar(255) DEFAULT NULL,
  `pad` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`personID`),
  UNIQUE KEY `person_email_key` (`email`),
  KEY `person_routeID_fkey` (`routeID`),
  KEY `nodePath` (`nodePath`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `person`
--

INSERT INTO `person` (`personID`, `dateCreated`, `lastLogin`, `active`, `nodePath`, `sortOrder`, `personType`, `deliverySlot`, `contactName`, `groupName`, `email`, `password`, `privateKey`, `phone`, `routeID`, `customCancelsRecurring`, `canCustomOrder`, `payTypeIDs`, `payTypeID`, `compost`, `minOrder`, `minOrderDeliver`, `bulkDiscount`, `bulkDiscountQuantity`, `shipping`, `shippingType`, `maxStars`, `deposit`, `credit`, `balance`, `stars`, `recent`, `bins`, `coldpacks`, `bottles`, `notes`, `description`, `image`, `website`, `sessionID`, `cookieID`, `cc`, `txnID`, `pad`) VALUES
(1, '2012-03-22 13:37:04', '2012-07-04 16:18:14', 1, '/1', NULL, 20, NULL, '', 'Localmotive', 'alex@localmotive.ca', 'OWYzMTQ2YWFiOGM1MjI1OTFmNzNkYWFiOWU3ODdkNWM=', NULL, '', NULL, 0, 1, '1,3,2', NULL, 0, NULL, NULL, NULL, NULL, 30.00, 1, NULL, NULL, NULL, 0.00, 0, 1, 0, 0, 0, '', NULL, 0, NULL, 'e5ud9gvlot9oatislou9hrue60', 'a72f36eb54f915b16dc604d40d44ca43', NULL, NULL, 0),
(3, '2012-06-25 13:24:12', '2012-06-25 13:20:57', 1, '/1/3', 1, 1, 1, 'Steve Bobs', '', 'steve@example.com', 'ZDgzY2NiMWU4NzEwNjY1NDRiNzQ0Y2QxYWFiODMzZDI=', NULL, '333-333-3333', 1, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 1, 1, 0, 0, '', NULL, 0, NULL, 'm10n4540c25da1tdgjrrt1aia6', '208090cbae0a7abbc95e30f3e9e5aa7b', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `price`
--

CREATE TABLE IF NOT EXISTS `price` (
  `personID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` smallint(6) DEFAULT NULL,
  `multiple` smallint(6) DEFAULT '1',
  PRIMARY KEY (`personID`,`itemID`),
  KEY `price_itemID_fkey` (`itemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `price`
--

INSERT INTO `price` (`personID`, `itemID`, `price`, `tax`, `multiple`) VALUES
(1, 9, 15.00, 0, 1),
(1, 10, 2.00, 0, 1),
(1, 27, 5.00, 2, 1),
(1, 28, 5.00, 2, 1),
(1, 30, 7.00, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `recurring_order_items`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`e20967f_local`@`localhost` SQL SECURITY DEFINER VIEW `localmotive`.`recurring_order_items` AS select `localmotive`.`orderItem`.`orderID` AS `orderID`,`localmotive`.`item`.`itemID` AS `itemID`,`localmotive`.`item`.`label` AS `label`,count(`localmotive`.`orderItem`.`orderID`) AS `no_ordered` from (`localmotive`.`orderItem` join `localmotive`.`item`) where (`localmotive`.`orderItem`.`orderID` in (select `localmotive`.`orders`.`orderID` AS `orderID` from `localmotive`.`orders` where (((`localmotive`.`orders`.`orderType` & 3) = 3) and ((not(`localmotive`.`orders`.`dateCompleted`)) or isnull(`localmotive`.`orders`.`dateCompleted`) or (`localmotive`.`orders`.`dateCompleted` > now())) and `localmotive`.`orders`.`personID` in (select `localmotive`.`person`.`personID` AS `personID` from `localmotive`.`person` where (`localmotive`.`person`.`lft` between (select `localmotive`.`person`.`lft` AS `lft` from `localmotive`.`person` where (`localmotive`.`person`.`personID` = 2)) and (select `localmotive`.`person`.`rgt` AS `rgt` from `localmotive`.`person` where (`localmotive`.`person`.`personID` = 2)))))) and (`localmotive`.`orderItem`.`itemID` = `localmotive`.`item`.`itemID`)) group by `localmotive`.`item`.`itemID`;
-- in use (#1449 - The user specified as a definer ('e20967f_local'@'localhost') does not exist)

-- --------------------------------------------------------

--
-- Table structure for table `referrer`
--

CREATE TABLE IF NOT EXISTS `referrer` (
  `referrerID` int(11) NOT NULL,
  `referreeID` int(11) NOT NULL,
  `datereferred` date DEFAULT NULL,
  PRIMARY KEY (`referrerID`,`referreeID`),
  KEY `referrer_referreeID_fkey` (`referreeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `route`
--

CREATE TABLE IF NOT EXISTS `route` (
  `routeID` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`routeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `route`
--

INSERT INTO `route` (`routeID`, `label`, `active`) VALUES
(1, 'Penticton', 1);

-- --------------------------------------------------------

--
-- Table structure for table `routeDay`
--

CREATE TABLE IF NOT EXISTS `routeDay` (
  `routeID` int(11) NOT NULL,
  `deliveryDayID` int(11) NOT NULL,
  `deliverySlot` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`routeID`,`deliveryDayID`),
  KEY `routeDay_deliveryDayID_fkey` (`deliveryDayID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `routeDay`
--

INSERT INTO `routeDay` (`routeID`, `deliveryDayID`, `deliverySlot`) VALUES
(1, 1, 1);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
