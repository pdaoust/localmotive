<?php
$pageTitle = 'Localmotive - Packed Pantry';
$pageArea = 'packed_pantry';
$pageSubArea = 'canning_workshops';
$selected_category = 'canning';
$calnavbar_title = 'All Workshops:';
include ('header.tpl.php');
include ('packed_pantry_navbar.inc.php');

// if submitted, get the event info
$selected_date = $_REQUEST['selected_date'];
$selected_event = $_REQUEST['selected_event'];
include ('calnavbar.inc.php');
?>
<div class="intro">
	<p align="center">Collective Canning Workshops</p>
</div>
<p> Hosted at our beautiful outdoor workspace at the Oasis Farm, come with your friends and family for a great day of fun filled preserving. Workshops are designed to give you the information you need to have a preserving filled future, for a variety of kinds of canning. Participants receive detailed step by step instruction on the canning process with supervision by our preservation gurus. This is a great way to learn more about preservation techniques, and how to keep your diet local in the winter.
<p>Each participant will help to can a batch of in season fruit or vegetables, and will receive a variety pack of canning at the end of the summer. Our collective efforts are combined at the end of the season, when we take a few jars from each workshop group, and send them to participants in a variety pack. This way, each participant receives a sampling of each workshop, and a great selection to carry them through the winter. Each participant must purchase a min. of 25 jars of canning, and can order up to 50 jars each. Workshop fee is included in the price of the canning. 
<p>Participants do not need to purchase any canning equipment for the workshops, and receive a discount price on the canning thanks to their help in the canning process during the workshop. Please pack your own lunch. Transportation to and from Penticton is included in the workshop fee. We have limited spaces for each scheduled day, up to 10 participants.

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
	$numberOfTix = $_REQUEST["numberOfTix"];
	
	if ($customerName!="" && $customerAddress!="" && $customerCity!="" && $customerPostalCode!="" && 
	    $customerPhone!="" && $customerEmail!="" && $paymentType!="") {
	
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
			$content = $eventdate . "," . $eventname . "," . $category . "," . $details . "#" . $tickets . "," . ($purchased+$numberOfTix) . "," . $price . "," . $active;
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
	$payment = $numberOfTix * $price;

	$newline = "\n";
	$submit_content = "Customer Name: " . $customerName . $newline . 
					  "Customer Address: " . $customerAddress . $newline . 
					  "Customer City: " . $customerCity . $newline . 
					  "Customer PostalCode: " . $customerPostalCode . $newline . 
					  "Customer  Phone: " . $_customerPhone . $newline . 
					  "Customer Email: " . $customerEmail . $newline . 
					  "Number Of Tix: " . $numberOfTix . $newline .
					  "Payment: $" . abs($payment) . $newline;
						

	//////////////////
	// sent the emails
	$homeemail = "orders@localmotive.ca";
	$emailheader = "From: " . $homeemail . "\r\nReply-To: " . $homeemail;
	$subject = $customerName . " has subscribed to " . $eventname;
	$theemail = "orders@localmotive.ca";
	//$theemail = "ntucakov@gmail.com";
 	mail($theemail, $subject, $submit_content, $emailheader);

	$customer_message = "Hi, thanks for signing up for the '" . $eventname . "' canning workshop." . $newline . "Please make sure make the payment of $" . abs($payment);

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
?>


<?php 
$php_name = "canning_workshops.php";
include ('display_event.inc.php'); 
?>
<h3>Sign up</h3>
<? if (!$signin_ok) echo '<span class="redfont">Error: please fill-out all the fields<br></span>'; ?>
<form name="pantry_signup" method="POST" action="canning_workshops.php?selected_date=<? echo $selected_date ?>&selected_event=<? echo $selected_event ?>">
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
		<th>Number of Tix</th>
		<td>
		  <span class="formEntry"> <span class="formField">
            <select name="numberOfTix">
              <option value="1" <? if ($numberOfTix == "1") echo 'selected'; ?>>1</option>
              <option value="2" <? if ($numberOfTix == "2") echo 'selected'; ?>>2</option>
              <option value="3" <? if ($numberOfTix == "3") echo 'selected'; ?>>3</option>
              <option value="4" <? if ($numberOfTix == "4") echo 'selected'; ?>>4</option>
              <option value="5" <? if ($numberOfTix == "5") echo 'selected'; ?>>5</option>
            </select>
            </span></span>		</td>
	</tr>
	<tr>
		<th>&nbsp;</th>
		<td><? if (($tickets-$purchased)==0) echo '<span class="redfont">full</span><br>'; ?><input type="submit" <? if (($tickets-$purchased)==0) echo disabled; ?> name="submit" value="Sign up!"/></td>
	</tr>
</table>
<input type=hidden name=mode value=submit>
</form>
<?php include ('footer.tpl.php'); ?>