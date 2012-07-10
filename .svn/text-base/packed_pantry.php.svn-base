<?php
$pageTitle = 'Localmotive - Packed Pantry';
$pageArea = 'packed_pantry';
$pageSubArea = 'packed_pantry';
$selected_category = 'packedpantry';
$calnavbar_title = '';
include ('header.tpl.php');
include ('packed_pantry_navbar.inc.php');

// if submitted, get the event info
$selected_date = $_REQUEST['selected_date'];
$selected_event = $_REQUEST['selected_event'];
include ('calnavbar.inc.php');
?>
<div class="intro">
	<p>Remember the days when you could go down to the basement in February and find a bit of that long-lost summer, in a jar? This summer, Localmotive will help you to recapture those days.</p>
</div>
<p>Perhaps you don't have time, or you don't want to bother, but you do want to eat local in the winter. Localmotive is proud to present two great options: <strong><a href="packed_pantry.php">Packed Pantry</a></strong> or <strong><a href="canning_workshops.php">Collective Canning Workshops</a></strong>!
 
<h2>Packed Pantry</h2>
<p>By doing large batches of canning and preserving, Localmotive will put away all of your favorite soups, fruits, jams, jellies, juices, pickles, and more! At the end of the harvest season, we will divide up our stock of preserves amongst all members, giving each member a variety pack of canning. We source directly from certified organic farmers from the Okanagan Valley, ensuring that you get the safest, most nutritious foods at good prices. By supporting these local growers, you do your part in protecting the environment and health, while supporting the local growers against price-deflating and unsustainably imported goods.</p>

<h2>Membership</h2>
<p><strong>Packed Pantry</strong> membership is limited to only 80 clients for the 2007 season, a total of 4000 jars. Each member will receive 25 or 50 jars of assorted canning. Delivery is included in your order for areas from Vernon to Osoyoos. Sign up below to secure your membership now.</p>

<?
$signin_ok = true;
// sign in the customer
if (isset($_REQUEST['mode'])) {

	$customerName = $_REQUEST["customerName"];
	$customerAddress = $_REQUEST["customerAddress"];
	$customerCity = $_REQUEST["customerCity"];
	$customerPostalCode = $_REQUEST["customerPostalCode"];
	$customerPhone = $_REQUEST["customerPhone"];
	$customerEmail = $_REQUEST["customerEmail"];
	$numberOfJars = $_REQUEST["numberOfJars"];
	
	if ($customerName!="" && $customerAddress!="" && $customerCity!="" && $customerPostalCode!="" && 
	    $customerPhone!="" && $customerEmail!=""  && $paymentType!="") {
	
	/////////////////////////////////////////////////////////////////////////
	// record the change in the calendar
	$filelist = "calendar_list.csv";
	$filecontents2 = file($filelist);
	for ($i=0;$i<sizeof($filecontents2);$i++) {

		$theline = $filecontents2[$i];
		// parse the line
		$eventdate = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$eventname = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$category = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$details = substr( $theline, 0, strpos($theline, "#") );
		$theline = substr( $theline, strpos($theline, "#")+1 );
		$tickets = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$purchased = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$price = substr( $theline, 0, strpos($theline, ",") );
		$theline = substr( $theline, strpos($theline, ",")+1 );
		$active = $theline;
		
		if (($eventdate == $selected_date) && ($eventname == $selected_event)) {
			// change the number of tickets
			$content = $eventdate . "," . $eventname . "," . $category . "," . $details . "#" . $tickets . "," . ($purchased+$numberOfJars) . "," . $price . "," . $active;
			// set the line to the new value 
			$filecontents2[$i] = $content;
			// and then rewrite the rest of the filecontents into a new file
    	    $newfilecontents = fopen($filelist, "w+");
   	    	for ($a=0;$a<sizeof($filecontents2);$a++) {
	       		$linesize = strlen($filecontents2[$a] . "\n");
				$writeresult = fwrite($newfilecontents, $filecontents2[$a], $linesize);
	        } // end for
			fclose($newfilecontents);
			break;
	    } // end if
	} // end for

	/////////////////////
	// define the payment
	switch ($numberOfJars) {
		case 25: $payment=212.50; break;
		case 30: $payment=247.50; break;
		case 35: $payment=280.00; break;
		case 40: $payment=310.00; break;
		case 45: $payment=337.50; break;
		case 50: $payment=400; break;
	} // end switch

	$newline = "\n";
	$submit_content = "Customer Name: " . $customerName . $newline . 
					  "Customer Address: " . $customerAddress . $newline . 
					  "Customer City: " . $customerCity . $newline . 
					  "Customer PostalCode: " . $customerPostalCode . $newline . 
					  "Customer  Phone: " . $_customerPhone . $newline . 
					  "Customer Email: " . $customerEmail . $newline . 
					  "Number Of Jars: " . $numberOfJars . $newline .
					  "Payment: $" . abs($payment) . $newline;
						

	//////////////////
	// sent the emails
	$homeemail = "orders@localmotive.ca";
	$emailheader = "From: " . $homeemail . "\r\nReply-To: " . $homeemail;
	$subject = $customerName . " has subscribed to " . $eventname;
	$theemail = "orders@localmotive.ca";
	//$theemail = "ntucakov@gmail.com";
 	mail($theemail, $subject, $submit_content, $emailheader);

	$customer_message = "Hi, thanks for signing up for the '" . $eventname . "' packed pantry." . $newline . "Please make sure make the payment of $" . abs($payment);

	$adminmail = "orders@localmotive.ca";
	$emailheader = "From: " . $adminmail . "\r\nReply-To: " . $adminmail;
	$subject = "Localmotive sign-up details!";
 	mail($customerEmail, $subject, $customer_message, $emailheader);

	$customerName="";$customerAddress="";$customerCity="";$customerPostalCode="";$customerPhone="";$customerEmail="";
	$signin_ok = true;
	
	echo '<span class="redfont"><br><br>Submission was sucessfully<br><br>';
	if ($paymentType=='visa') {
	echo 'Please submit the payment of <strong>$' . abs($payment) . '</strong><br><br></span>';
	//////////
	// payment
	?>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="feedme@localmotive.ca">
	<input type="hidden" name="item_name" value="Localmotive Organic">
	<input type="hidden" name="item_number" value="1">
	<input type="hidden" name="amount" value="<? echo abs($payment)+abs(round($payment*0.025,2)); ?>">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="no_note" value="1">
	<input type="hidden" name="currency_code" value="CAD">
	<input type="hidden" name="lc" value="CA">
	<input type="hidden" name="bn" value="PP-BuyNowBF">
	<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but02.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
(note: a 2.5% service charge of $ <? echo abs(round($payment*0.025,2)); ?> will be added to this payment) 
	</form>
<? 
	}
	else echo "Please make sure you make the payment of <strong>$" . abs($payment) . "</strong> by cheque.<br><br></span>";
	
	} // end if
	else $signin_ok = false;
}

// THE EVENT INFO!!!
include ('display_event.inc.php'); 
?>

<h3>Sign up</h3>
<? if (!$signin_ok) echo '<span class="redfont">Error: please fill-out all the fields<br></span>'; ?>
<form name="pantry_signup" method="POST" action="packed_pantry.php?selected_date=<? echo $selected_date ?>&selected_event=<? echo $selected_event ?>">
<table class="formLayout">
	<tr>
		<th>Name</th>
		<td><input type="text" value="<? echo $customerName ?>" size="35" maxlength="255" name="customerName"/></td>
	</tr>
	<tr>
		<th>Address</th>
		<td><input type="text" value="<? echo $customerAddress ?>" size="35" maxlength="255" name="customerAddress"/></td>
	</tr>
	<tr>
		<th>City</th>
		<td><input type="text" value="<? echo $customerCity ?>" size="35" maxlength="255" name="customerCity"/></td>
	</tr>
	<tr>
		<th>Postal code</th>
		<td><input type="text" value="<? echo $customerPostalCode ?>" size="7" maxlength="7" name="customerPostalCode"/></td>
	</tr>
	<tr>
		<th>Phone</th>
		<td><input type="text" value="<? echo $customerPhone ?>" size="14" maxlength="14" name="customerPhone"/></td>
	</tr>
	<tr>
		<th>E-mail</th>
		<td><input type="text" value="<? echo $customerEmail ?>" size="35" maxlength="255" name="customerEmail"></td>
	</tr>
	<tr>
      <th>Payment</th>
      <td><input name="paymentType" type="radio" value="visa" <? if ($paymentType=="visa") echo 'checked="checked"'; ?> />
    Visa
      <input name="paymentType" type="radio" value="cheque" <? if ($paymentType=="cheque") echo 'checked="checked"'; ?> />
    Cheque</td>
    </tr>
	<tr>
		<th>Number of jars</th>
		<td>
			<select name="numberOfJars">
				<option value="25" <? if ($numberOfJars == "25") echo 'selected'; ?>>25 jars - $212.50 ($8.50 per jar)</option>
				<option value="30" <? if ($numberOfJars == "30") echo 'selected'; ?>>30 jars - $247.50 ($8.25 per jar)</option>
				<option value="35" <? if ($numberOfJars == "35") echo 'selected'; ?>>35 jars - $280.00 ( $8.00 per jar)</option>
				<option value="40" <? if ($numberOfJars == "40") echo 'selected'; ?>>40 jars - $310.00 ( $7.75 per jar)</option>
				<option value="45" <? if ($numberOfJars == "45") echo 'selected'; ?>>45 jars - $337.50 ($7.50 per jar)</option>
				<option value="50" <? if ($numberOfJars == "50") echo 'selected'; ?>>50 jars - $350.00 ( $7.00 per jar)</option>
			</select>
		</td>
	</tr>
	<tr>
		<th>&nbsp;</th>
		<td><? if (($tickets-$purchased)<50) echo '<span class="redfont">full</span><br>'; ?><input type="submit" <? if (($tickets-$purchased)<50) echo disabled; ?> name="submit" value="Sign up!"/></td>
	</tr>
</table>
<input type=hidden name=mode value=submit>
</form>

<?php include ('footer.tpl.php'); ?>