<?

// VARIABLE CONVENTIONS USED THROUGHOUT CODE
//
// $i = iterator
// $q = query
// $r = row (from query result)
// $s = success/failure of function/expression
// $t = token

define ('MODE_ABSOLUTE', 0);
define ('MODE_RELATIVE', 1);
define ('DAY_MON', 1);
define ('DAY_TUE', 2);
define ('DAY_WED', 3);
define ('DAY_THU', 4);
define ('DAY_FRI', 5);
define ('DAY_SAT', 6);
define ('DAY_SUN', 7);
define ('SORT_FORWARD', 1);
define ('SORT_REVERSE', 0);
/*
 * old order types; not used any more
define ('O_SALE', 1);
define ('O_PURCHASE', 2);
define ('O_RECURRING', 3);
define ('O_SUPPLIER', 4);
define ('O_ONCE', 3);*/
define ('O_IN', 0);
define ('O_OUT', 1);			// the default; a sale (0 is a purchase)
define ('O_TEMPLATE', 2);		// non-completable recurring order
define ('O_DELIVER', 4);		// locked to delivery route
define ('O_EDITABLE', 8);		// editable by customer; system orders should be marked non-editable
define ('O_CSA', 16);			// CSA order; must have at least one CSA item on it
define ('O_SYSTEM', 32);			// system order; should not be editable
// bit masks for order types
define ('O_DIR', 1);
define ('O_OLD', 3);
define ('O_BASE', 3);
define ('O_BASE_EDITABLE', 11);
// convenience types, used in conjunction with O_OLD
define ('O_SALE', 1);
define ('O_SALE_EDITABLE', 9);
define ('O_SALE_EDITABLE_DELIVER', 13);
define ('O_RECURRING', 3);
define ('O_RECURRING_EDITABLE', 11);
define ('O_PURCHASE', 0);
define ('O_SUPPLIER', 2);
define ('O_NO_STARS', 24);
define ('O_ALL', 63);

define ('PAY_CHEQUE', 1);
define ('PAY_PAYPAL', 2);
define ('PAY_CC', 3);
define ('PAY_ACCT', 4); // paying with account credit
define ('F_ASSOC', 1);
define ('F_NUM', 2);
define ('F_RECORD', 3);
define ('STARS_NO_CALCULATE', -1);
define ('M_PERSON', 1);
define ('M_ANNOUNCEMENT', 2);
define ('M_BACKEND', 3);
define ('M_CLOSURE', 4);
define ('P_CUSTOMER', 1);
define ('P_SUPPLIER', 2);
define ('P_CATEGORY', 4);
define ('P_DEPOT', 8);
define ('P_DELIVERER', 16);
define ('P_PRIVATE', 32);
define ('P_CSA', 64);
define ('P_MEMBER', 128);
define ('P_VOLUNTEER', 256);
define ('P_ADMIN', 512);
define ('P_PACKER', 1024);
define ('P_SLEEPING', 2048);
define ('P_ALL', 4095);
define ('P_CANORDER', 3);
// item types
define ('I_CATEGORY', 1);
define ('I_ITEM', 0);
define ('I_ALL', 1);
// inheritance types - totally reusing 'I' here. Oh well.
define ('I_CHILD', 1);
define ('I_PARENT', 2);
define ('I_CHILD_NULL', 3);
define ('I_PARENT_NULL', 4);
define ('I_PARENT_TRUE', 5);
define ('LOG_NONE', 0);
define ('LOG_DB', 1);
define ('LOG_OUTPUT', 2);
define ('LOG_FILE', 3);
define ('LOG_CONSOLE', 4);
define ('A_IGNORE', 0);
define ('A_DENY', 1);
define ('A_DEFER', 2);
define ('A_KEEP', 3);
define ('AD_SHIP', 1);
define ('AD_MAIL', 2);
define ('AD_PAY', 4);
define ('AD_ALL', 7);
define ('D_LFT', -1);
define ('D_DESC', -1);
define ('D_RGT', 1);
define ('D_ASC', 1);
define ('DEL_TRASH', 1);
define ('DEL_PURGE', 2);
define ('DEL_BOTH', 3);
define ('TAX_PST', 1);
define ('TAX_HST', 2);
define ('TAX_ALL', 3);
define ('DB_MYSQL', 1);
define ('DB_PGSQL', 2);
define ('N_PERCENT', 1);
define ('N_FLAT', 0);
define ('N_CALCTYPE', 1);
define ('N_TIER', 2);
define ('N_NET', 4);
define ('N_GROSS', 0);
define ('N_APPLYTO', 4);
define ('N_QTY', 8);
define ('N_PRICE', 0);
define ('N_CALCON', 8);
define ('N_TIER_PERCENT', 3);
define ('N_TIER_FLAT', 2);
define ('N_NOTIER', 5);
define ('N_ALL', 15);
define ('ADJ_TAX', 1);
define ('ADJ_MARKUP', 2);
define ('ADJ_SURCHARGE', 3);
define ('ADJ_DISCOUNT', 4);
define ('ADJ_HANDLING', 5);
define ('ADJ_ALL', 7);

define ('T_MINUTE', 60);
define ('T_HOUR', 3600);
define ('T_DAY', 86400);
define ('T_WEEK', 604800);
// special case; months and years cannot be consistently defined as periods
// instead, they're defined by negative numbers, and of course a year is 12 months.
define ('T_MONTH', -1);
define ('T_YEAR', -12);

// various date formats; if I internationalise, I may have to roll them into the config database
// default value for datepicker inputs
define ('TF_PICKER_VAL', '%e %B %Y');
// jQuery date format for datepicker instantiators
define ('TF_PICKER_JQ', 'd MM yy');
// format for Javascript Date objects
define ('TF_JSDATE', '%Y, %m - 1, %d');
// and for AJAX
define ('TF_JSDATE_AJAX', '"year": %Y, "month": %-m - 1, "day": %e');
// format for log timestamps
define ('TF_LOG', '%d/%m/%y %T');
// format for customers
define ('TF_HUMAN', '%A, %d %b %Y');
define ('TF_HTML5', '%F');
define ('NF_MONEY', '%+n');
define ('NF_MONEY_ACCT', '%+n');
define ('NF_MONEY_NOCURR', '%!+n');
define ('DF_NODEPATH_SIBLINGS', '^%s/[[:digit:]]+$');

define ('E_DATABASE', 1);
define ('E_INVALID_DATA', 2);
define ('E_NO_OBJECT_ID', 4);
define ('E_NO_OBJECT', 8);
define ('E_OBJECT_NOT_ACTIVE', 16);
define ('E_NOT_WITHIN_DELIVERY_CUTOFF', 32);
define ('E_WRONG_ORDER_TYPE', 64);
define ('E_ORDER_COMPLETED', 128);
define ('E_ORDER_DELIVERED', 256);
define ('E_ORDER_CANCELED', 512);
define ('E_ORDER_EMPTY', 1024);
define ('E_ORDER_TOO_SMALL', 2048);
define ('E_ORDER_NOT_COMPLETED', 4096);
define ('E_RECURRING_ALREADY_ORDERED', 8192);
define ('E_CUSTOM_ALREADY_ORDERED', 16384);
define ('E_NO_MORE_RECURRING', 32768);
define ('E_NOT_AVAILABLE_TO_CUSTOMER', 65536);
define ('E_INCORRECT_MULTIPLE', 131072);
define ('E_TOO_MANY_FAILED_LOGINS', 262144);
define ('E_LOGIN_CREDENTIALS_INCORRECT', 524288);
define ('E_NO_ROUTE', 1048576);
define ('E_WRONG_P_TYPE', 2097152);
define ('E_NO_RECURRING_FOR_NEXT_DELIVERY_DAY', 4194304);
define ('E_HAS_ASSOCIATED_DATA', 8388608);
define ('E_IMAGE_FAILED', 16777216);
define ('E_PERMISSION', 33554432);

$ajax = false;

$errorCodes = array (
	1 => 'E_DATABASE',
	2 => 'E_INVALID_DATA',
	4 => 'E_NO_OBJECT_ID',
	8 => 'E_NO_OBJECT',
	16 => 'E_OBJECT_NOT_ACTIVE',
	32 => 'E_NOT_WITHIN_DELIVERY_CUTOFF',
	64 => 'E_WRONG_ORDER_TYPE',
	128 => 'E_ORDER_COMPLETED',
	256 => 'E_ORDER_DELIVERED',
	512 => 'E_ORDER_CANCELED',
	1024 => 'E_ORDER_EMPTY',
	2048 => 'E_ORDER_TOO_SMALL',
	4096 => 'E_ORDER_NOT_COMPLETED',
	8192 => 'E_RECURRING_ALREADY_ORDERED',
	16384 => 'E_CUSTOM_ALREADY_ORDERED',
	32768 => 'E_NO_MORE_RECURRING',
	65536 => 'E_NOT_AVAILABLE_TO_CUSTOMER',
	131072 => 'E_INCORRECT_MULTIPLE',
	262144 => 'E_TOO_MANY_FAILED_LOGINS',
	524288 => 'E_LOGIN_CREDENTIALS_INCORRECT',
	1048576 => 'E_NO_ROUTE',
	2097152 => 'E_WRONG_P_TYPE',
	4194304 => 'E_NO_RECURRING_FOR_NEXT_DELIVERY_DAY',
	8388608 => 'E_HAS_ASSOCIATED_DATA',
	16777216 => 'E_IMAGE_FAILED',
	33554432 => 'E_PERMISSION'
);

/* $orderTypes = array (
	0 => null,
	1 => O_SALE,
	2 => O_PURCHASE,
	3 => O_RECURRING,
	4 => O_SUPPLIER,
	'sale' => O_SALE,
	'purchase' => O_PURCHASE,
	'recurring' => O_RECURRING,
	'supplier' => O_SUPPLIER
); */
$orderTypeNames = array (
	1 => 'sale',
	2 => 'purchase',
	3 => 'recurring',
	4 => 'supplier'
);

$days = array (
	1 => DAY_MON,
	2 => DAY_TUE,
	3 => DAY_WED,
	4 => DAY_THU,
	5 => DAY_FRI,
	6 => DAY_SAT,
	7 => DAY_SUN,
	'sunday' => DAY_SUN, 'sun' => DAY_SUN,
	'monday' => DAY_MON, 'mon' => DAY_MON,
	'tuesday' => DAY_TUE, 'tue' => DAY_TUE,
	'wednesday' => DAY_WED, 'wed' => DAY_WED,
	'thursday' => DAY_THU, 'thu' => DAY_THU,
	'friday' => DAY_FRI, 'fri' => DAY_FRI,
	'saturday' => DAY_SAT, 'sat' => DAY_SAT
);
$dayNames = array (
	1 => 'Monday',
	2 => 'Tuesday',
	3 => 'Wednesday',
	4 => 'Thursday',
	5 => 'Friday',
	6 => 'Saturday',
	7 => 'Sunday'
);

$monthNames = array (
	1 => 'Jan',
	2 => 'Feb',
	3 => 'Mar',
	4 => 'Apr',
	5 => 'May',
	6 => 'Jun',
	7 => 'Jul',
	8 => 'Aug',
	9 => 'Sep',
	10 => 'Oct',
	11 => 'Nov',
	12 => 'Dec'
);

$messageTypes = array (
	0 => null,
	1 => M_PERSON,
	2 => M_ANNOUNCEMENT,
	3 => M_BACKEND,
	4 => M_CLOSURE,
	'person' => M_PERSON,
	'announcement' => M_ANNOUNCEMENT,
	'backend' => M_BACKEND,
	'closure' => M_CLOSURE
);

$personTypes = array (
	0 => null,
	1 => P_CUSTOMER,
	2 => P_SUPPLIER,
	4 => P_CATEGORY,
	8 => P_DEPOT,
	16 => P_DELIVERER,
	32 => P_PRIVATE,
	64 => P_CSA,
	128 => P_MEMBER,
	256 => P_VOLUNTEER,
	512 => P_ADMIN,
	1024 => P_PACKER,
	2048 => P_SLEEPING,
	'customer' => P_CUSTOMER,
	'supplier' => P_SUPPLIER,
	'category' => P_CATEGORY,
	'depot' => P_DEPOT,
	'deliverer' => P_DELIVERER,
	'private' => P_PRIVATE,
	'csa' => P_CSA,
	'member' => P_MEMBER,
	'volunteer' => P_VOLUNTEER,
	'admin' => P_ADMIN,
	'packer' => P_PACKER,
	'sleeping' => P_SLEEPING
);

$personTypeNames = array (
	P_CUSTOMER => 'customer',
	P_SUPPLIER => 'supplier',
	P_CATEGORY => 'category',
	P_DEPOT => 'depot',
	P_DELIVERER => 'deliverer',
	P_PRIVATE => 'private',
	P_CSA => 'csa',
	P_MEMBER => 'member',
	P_VOLUNTEER => 'volunteer',
	P_ADMIN => 'admin',
	P_PACKER => 'packer',
	P_SLEEPING => 'sleeping'
);

$addressTypes = array (
	0 => null,
	1 => AD_SHIP,
	2 => AD_MAIL,
	4 => AD_PAY
);

$timeNames = array (
	0 => '',
	T_DAY => 'day',
	T_WEEK => 'week',
	T_MONTH => 'month',
	T_YEAR => 'year'
);

$imageMimetypes = array ('image/jpeg', 'image/gif', 'image/png');

$payTypeIDs = array (PAY_CHEQUE, PAY_PAYPAL, PAY_CC, PAY_ACCT);

?>
