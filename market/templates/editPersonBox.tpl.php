<div id="editPersonBox" title="Edit person" style="display: none;">
	<p class="errorBox" id="editPersonErrors" style="display: none;"></p>
	<form id="editPersonForm" action="managePeople.php">
		<input type="hidden" name="personID"/>
		<input type="hidden" name="parentID"/>
		<input type="hidden" name="action" value="editPerson"/>
		<input type="hidden" name="viewBy" value="<? if (isset($_REQUEST['viewBy'])) echo $_REQUEST['viewBy']; ?>"/>
		<? if (isset($node)) { ?><input type="hidden" name="nodeID" value="<? echo $node->personID; ?>"/><? } ?>
		<fieldset>
			<legend>Name and organisation</legend>
			<ul class="form">
				<li class="fCustomer fSupplier fDepot">
					<label for="contactName">Contact name</label>
					<input type="text" name="contactName"/>
				</li>
				<li class="fCustomer fSupplier fDepot fCategory">
					<label for="groupName">Group</label>
					<input type="text" name="groupName"/>
				</li>
				<li>
					<label for="dateCreated">Joined on</label>
					<span class="widget" id="dateCreated"></span>
				</li>
				<li>
					<label for="active">Active</label>
					<input type="checkbox" name="active" label="active"/>
				</li>
			</ul>
		</fieldset>
		<fieldset>
			<legend>Account type</legend>
			<ul class="checkboxes">
				<li><input type="checkbox" name="customer" id="customer"/> <label for="customer">customer</label></li>
				<li><input type="checkbox" name="member" id="member"/> <label for="member">co-op member</label></li>
				<li><input type="checkbox" name="depot" id="depot"/> <label for="depot">depot</label> <span class="fDepot"><input type="checkbox" name="private" id="private"/> <label for="private">private</label></span></li>
				<li><input type="checkbox" name="supplier" id="supplier"/> <label for="supplier">supplier</label></li>
				<li><input type="checkbox" name="category" id="category"/> <label for="category">category</label></li>
				<li><input type="checkbox" name="csa" id="csa"/> <label for="csa">CSA subscriber</label></li>
				<li><input type="checkbox" name="volunteer" id="volunteer"/> <label for="volunteer">volunteer</label></li>
				<li><input type="checkbox" name="deliverer" id="deliverer"/> <label for="deliverer">deliverer</label></li>
			</ul>
		</fieldset>
		<fieldset>
			<legend>Contact</legend>
			<ul class="form">
				<li class="fCustomer fSupplier fDepot">
					<label for="phone">Phone</label>
					<input type="text" name="phone"/>
				</li>
				<li class="fCustomer fSupplier fDepot">
					<label for="email">E-mail</label>
					<input type="text" name="email" id="email"/>
					<span class="hint"><span>will be login</span> <span id="emailDuplicate" class="errorBox" style="display: none;">An account with that e-mail address has already been created.</span></span>
				</li>
				<li class="fCustomer fSupplier fDepot">
					<label for="password">Password</label>
					<input type="text" name="password"/>
				</li>
				<li class="fPrivate">
					<label for="privateKey">Passkey</label>
					<input type="text" name="privateKey" id="privateKey"/>
					<span class="hint">to make a depot private</span>
				</li>
				<li class="fSupplier fCategory fDepot">
					<label for="website">Website</label>
					<input type="text" name="website"/>
					<span class="hint">for suppliers</span>
				</li>
				<li class="fCustomer fSupplier fDepot">
					<label for="routeID">Route</label>
					<select name="routeID" id="routeID">
					<? if (!isset($routes)) $routes = getRoutes();
					foreach ($routes as $v) {
						echo '<option value="' . $v->routeID . '">' . htmlEscape($v->label) . '</option>';
					} ?>
					</select>
				</li>
			</ul>
			<fieldset class="fCustomer fSupplier fDepot">
				<legend>Addresses <a href="javascript:addAddress()"><img src="img/naddr.png" class="icon" alt="+"/> New address</a> <input type="hidden" id="newAddyQty" value="0"/></legend>
				<ul id="addresses" class="addresses"></ul>
			</fieldset>
		</fieldset>
		<fieldset>
			<legend>Account/order management (leave blank for default)</legend>
			<ul class="form">
				<li class="fCategory">
					<label for="payTypeIDs">Allowed payment types</label>
					<span class="widget">
						<? if (!isset($payTypes)) $payTypes = getPayTypes(); ?>
						<label id="payTypeIDInherit"><input type="checkbox" name="payTypeIDs[inherit]"> inherit (<?
						$labels = array();
						foreach ($payTypes as $v) {
							if ($v->isActive()) $labels[] = '<span id="payTypeLabel' . $v->payTypeID . '">' . htmlEscape($v->label) . '</span>';
						}
						echo implode(', ', $labels);
						?>)</label><br/>
						<? foreach ($payTypes as $v) { ?>
							<label><input type="checkbox" name="payTypeIDs[<?= $v->payTypeID ?>]"<?= ($v->isActive() ? null : ' disabled="disabled"') ?> class="payTypeIDs"> <?= htmlEscape($v->label) ?></label><br/>
						<? } ?>
					</span>
				</li>
				<li class="fCustomer fSupplier fCategory">
					<label for="payTypeID">Default payment type</label>
					<select name="payTypeID">
						<option value="" id="payTypeIDParent">inherit</option>
						<?
						foreach ($payTypes as $v) {
							echo "\t\t\t\t\t\t<option value=\"" . $v->payTypeID . '"' . ($v->isActive() ? null : ' disabled="disabled"') . '>' . htmlEscape($v->label) . "</option>\n";
						} ?>
					</select>
				</li>
				<li class="fCustomer" id="storedCC">
					<label>Stored credit card</label>
					<span class="widget">
						<span id="storedCCnum"></span>
						<label><input type="checkbox" name="forgetCC" id="forgetCC"/> forget</label><br/>
						<label><input type="checkbox" name="pad" id="pad"/> automatic billing (requires customer auth)</label>
					</span>
				</li>
				<li class="fCustomer">
					<label for="stars">Stars</label>
					<span class="widget">
						<input type="text" name="stars" class="figTiny"/> &nbsp;
						<input type="checkbox" name="recent"/>
						<label for="recent" class="noform">Ordered last period</label>
					</span>
				</li>
				<li class="fCustomer fCategory">
					<label for="maxStars">Maximum stars</label>
					<span class="widget">
						<input type="text" name="maxStars" class="figTiny"/>
						<span id="maxStarsParent"></span>
					</span>
				</li>
				<li class="fCustomer fCategory">
					<span class="label">Minimum orders</span>
					<span class="widget">
						<label>depot: $<input type="text" name="minOrder" class="figSmall"/></label>
						<label>deliver: $<input type="text" name="minOrderDeliver" class="figSmall"/></label>
					</span>
				</li>
				<li class="fCustomer fCategory">
					<span class="label">Bulk discount</span>
					<span class="widget">
						<input type="text" name="bulkDiscountQuantity" class="figSmall"/>
						<label for="bulkDiscountQuantity" class="noform">units</label> at <input type="text" name="bulkDiscount" class="figTiny"/>
						<label for="bulkDiscount" class="noform">% off</label>
						<span id="bulkDiscountParent"></span>
					</span>
				</li>
				<li class="fCategory fCustomer">
					<label for="deposit">Deposit amount</label>
					<span class="widget">
						$<input type="text" name="deposit" class="figSmall"/>
						<span id="depositParent"></span>
					</span>
				</li>
				<li class="fCategory fCustomer">
					<label for="deposit">Line of credit</label>
					<span class="widget">
						$<input type="text" name="credit" class="fig"/>
						<span id="creditParent"></span>
					</span>
				</li>
				<!--<li><label for="customCancelsRecurring">Custom cancels recurring order</label> <input type="checkbox" name="customCancelsRecurring" id="customCancelsRecurring"/></li/>-->
				<li class="fCustomer fCategory">
					<label for="canCustomOrder">Can custom-order</label>
					<span class="widget">
						<label class="noform"><input type="radio" name="canCustomOrder" id="canCustomOrderY" value="1"/> yes</label>
						<label class="noform"><input type="radio" name="canCustomOrder" id="canCustomOrderN" value="0"/> no</label>
						<span id="canCustomOrderControl">
							<label class="noform"><input type="radio" name="canCustomOrder" id="canCustomOrderI" value="-1"/> <span id="canCustomOrderParent">inherit</span></label>
						</span>
					</span>
				</li>
				<!--<li><label for="compost">Compost pickup</label> <input type="checkbox" name="compost"/>-->
			</ul>
		</fieldset>
		<fieldset>
			<legend>Other details</legend>
			<ul class="form">
				<li class="fCategory fSeller">
					<label for="description">Description</label>
					<textarea rows="3" name="description"></textarea>
					<span class="hint">for suppliers and categories, will appear publicly</span>
				</li>
				<li>
					<label for="notes">Notes</label>
					<textarea rows="3" name="notes"></textarea>
					<span class="hint">private, for admins, accounting, and delivery people only</span>
				</li>
				<li>
					<span class="label">&nbsp;</span>
					<input type="submit" value="Save"/>
				</li>
			</ul>
		</fieldset>
	</form>
</div>
