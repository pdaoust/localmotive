$(function () {
	<? if ($payAction == 'order') { ?>activateLabelSens();<? } ?>
	bindEvents();
	if (document.location.hash && (marketItem = $(document.location.hash))) {
		intervalID = setInterval(function () {
			$(marketItem).toggleClass('highlight');
		}, 500);
		setTimeout('clearInterval(' + intervalID + ')', 3000);
		setTimeout(function () {
			$(marketItem).removeClass('highlight');
		}, 3500);
	}
	changePayForm();
});

<? if ($payAction == 'order') { ?>
function deleteItem (itemID) {
	listCell = $('#listLabel' + itemID);
	oldHtml = listCell.html();
	listCell.text('deleting...');
	$.ajax({
		data: {
			ajax: 1,
			action: 'deleteItem',
			itemID: itemID,
			referrer: '<?= htmlEscape($referrer) ?>'<?= $tour ? ', tour: 1, svcID: ' . $customer->personID: null ?>
		},
		url: 'order.php',
		dataType: 'json',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			listCell.addClass('notice').text('couldn\'t delete');
			setTimeout(function () {
				listCell.fadeOut(500, function () {
					$(this).removeClass('notice').html(oldHtml).fadeIn(1);
				});
			}, 3000);
		},
		success: function (r) {
			$('#shoppingListCol').html(r.shoppingListCol);
			if (Number(r.success)) $('#qtyOrd' + itemID).text('');
			bindEvents();
		},
		type: 'POST'
	});
}<? } ?>
function changePayForm () {
	console.log('payForm triggered');
	switch (Number($('#payTypeID').val())) {
		case <?= PAY_ACCT ?>:
			console.log('account');
			$('ul.form li:not(.pay_acct)').slideUp('slow');
			$('.pay_acct').slideDown('slow');
			break;
		case <?= PAY_CC ?>:
			console.log('cc');
			$('ul.form li:not(.pay_cc)').slideUp('slow');
			switch($('#cardAction').val()) {
				case 'useStoredCC':
					$('input.ccFormField').attr('disabled', true);
					$('span.ccFormField, li.ccFormField').hide();
					$('.pay_cc:not(.ccFormField), #storedCCnum').slideDown('slow');
					break;
				case 'useNewCC':
					$('input.ccFormField').removeAttr('disabled');
					$('#CardNum').keyup();
					$('#rememberCC').change();
					$('#pad').change();
					$('span.ccFormField, li.ccFormField, .pay_cc').slideDown('slow');
					$('#storedCCnum').hide();
			}
			break;
		case <?= PAY_PAYPAL ?>:
			console.log('pp');
			$('ul.form li:not(.pay_paypal)').slideUp('slow');
			$('.pay_paypal').slideDown('slow');
			break;
		default:
			console.log($('#payTypeID').val());
	}
}

function changeCardAction () {
	console.log('card action triggered');
	switch($('#cardAction').val()) {
		case 'useStoredCC':
			$('input.ccFormField').attr('disabled', true);
			$('span.ccFormField, li.ccFormField').slideUp('slow');
			$('#storedCCnum').slideDown('slow');
			$('#pad').attr('disabled', false);
			break;
		case 'useNewCC':
			$('input.ccFormField').removeAttr('disabled');
			$('#CardNum').keyup();
			$('#rememberCC').change();
			$('#pad').change();
			$('span.ccFormField, li.ccFormField').slideDown('slow');
			$('#storedCCnum').slideUp('slow');
	}
}

<? if ($payAction == 'order') { ?>function changeQty (listItem) {
	itemID = $(listItem).attr('data-itemID');
	qty = $(listItem).val();
	listCell = $('#priceQty' + itemID);
	oldHtml = listCell.html();
	listCell.text('changing...');
	$.ajax({
		data: {
			ajax: 1,
			action: 'changeQty',
			itemID: itemID,
			qty: qty,
			referrer: '<?= htmlEscape($referrer) ?>'<?= $tour ? ', tour: 1, svcID: ' . $customer->personID : null ?>
		},
		url: 'order.php',
		dataType: 'json',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			listCell.addClass('notice').text('couldn\'t change');
			setTimeout(function () {
				listCell.fadeOut(500, function () {
					$(this).removeClass('notice').html(oldHtml).fadeIn(1);
				});
			}, 3000);
		},
		success: function (r) {
			$('#shoppingListCol').html(r.shoppingListCol);
			$('#qtyOrd' + r.itemID).text(r.qty ? (r.qty + ' ordered') : '');
			bindEvents();
		},
		type: 'POST'
	});
}

function changePrice (listItem) {
	itemID = $(listItem).attr('data-itemID');
	qty = $(listItem).val();
	listCell = $('#priceQty' + itemID);
	oldHtml = listCell.html();
	listCell.text('changing...');
	$.ajax({
		data: {
			ajax: 1,
			action: 'changePrice',
			itemID: $(listItem).attr('data-itemID'),
			price: $(listItem).val(),
			referrer: '<?= htmlEscape($referrer) ?>'<?= $tour ? ', tour: 1, svcID: ' . $customer->personID : null ?>
		},
		dataType: 'json',
		url: 'order.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			listCell.addClass('notice').text('couldn\'t change');
			setTimeout(function () {
				listCell.fadeOut(500, function () {
					$(this).removeClass('notice').html(oldHtml).fadeIn(1);
				});
			}, 3000);
		},
		success: function (r) {
			$('#shoppingListCol').html(r.shoppingListCol);
			bindEvents();
		},
		type: 'POST'
	});
}

function activateLabelSens() {
	$('#orderLabel').click(function () {
		$(this)
			.html('<input type="text" id="orderLabelInput" maxlength="255" value="' + $('#orderLabel').text() + '"/>')
			.unbind('click');
		$('#orderLabelInput')
			.keyup(function (e) {
				if (e.which != null) keycode = e.which; else keycode = e.keycode;
				if (keycode == 13 || keycode == 9) {
					changeLabel (this);
				}
			})
			.blur(function () {
				changeLabel(this);
			});
	});
}

function updateNotices (r) {
	if (r.short) {
		$('.short').show();
		$('.canOrder').hide();
		$('.shortAmt').text(r.short);
	} else {
		$('.short').hide();
		$('.canOrder').show();
	}
	if (r.permShort) {
		$('#permNotice').show();
	} else {
		$('#permNotice').hide();
	}
	$('#permTotal').text(r.permTotal);
	$('#permShort').text(r.permShort);
}

function changeLabel (label) {
	$.ajax({
		data: {
			action: 'changeLabel',
			label: $(label).val()<?= $tour ? ', tour: 1, svcID: ' . $customer->personID : null ?>
		},
		url: 'order.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			console.log('failed to change label');
		},
		success: function (r) {
			$('#orderLabel').text(r ? r : 'no description');
			activateLabelSens();
		},
		type: 'POST'
	});
}

function makePerm (itemID) {
	listCell = $('#listLabel' + itemID);
	oldHtml = listCell.html();
	listCell.text('changing permanency...');
	$.ajax({
		data: {
			action: 'makePerm',
			ajax: 1,
			itemID: itemID,
			referrer: '<?= htmlEscape($referrer) ?>'<?= $tour ? ', tour: 1, svcID: ' . $customer->personID : null ?>
		},
		dataType: 'json',
		url: 'order.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			listCell.addClass('notice').text('couldn\'t change permanency');
			setTimeout(function () {
				listCell.fadeOut(500, function () {
					$(this).removeClass('notice').html(oldHtml).fadeIn(1);
				});
			}, 3000);
		},
		success: function (r) {
			$('#shoppingListCol').html(r.shoppingListCol);
			if (r.errorMsg) {
				listCell = $('#listLabel' + itemID);
				listCell.addClass('notice').text(r.errorMsg);
				setTimeout(function () {
					listCell.fadeOut(500, function () {
						$(this).removeClass('notice').html(oldHtml).fadeIn(1);
					});
				}, 3000);
			}
			bindEvents();
		},
		type: 'POST'
	});
}<? } ?>

function bindEvents () {
	console.log('rebinding');
	<? if ($payAction == 'order') { ?>
	if (orderSettings = $('#orderSettings')) {
		orderSettings.ajaxForm({
			'type': 'POST',
			'timeout': <?= (int) $config['ajaxTimeout'] ?>,
			'error': function () {
				console.log('failed to submit form ');
			},
		});
		dateFields = $('.dateField');
		dateFields.datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true});
		dateFields.unbind('keyup');
		dateFields.change(function () {
			orderSettings.ajaxSubmit({
				data: {
					ajax: 1,
					<?= $tour ? 'tour: 1, svcID: ' . $customer->personID . ",\n" : null ?>
					action: 'changeDates'
				},
				error: function () {
					console.log('couldnt change date fields');
				},
				success: function (r) {
					$('#orderActions').html(r);
					bindEvents();
				}
			});
		});
		periodFields = $('#mult, #period');
		periodFields.unbind('change').change(function () {
			mult = Number($('#mult').val());
			if (mult < 1) $mult = 1;
			period = $('#period').val();
			orderSettings.ajaxSubmit({
				data: {
					ajax: 1,
					<?= $tour ? 'tour: 1, svcID: ' . $customer->personID . ",\n" : null ?>
					action: 'changePeriod',
					mult: mult,
					period: period
				},
				error: function () {
					console.log('couldnt set period');
				},
				success: function (r) {
					$('#shoppingListCol').html(r);
					bindEvents();
				}
			});
		});
		$('#mult').numeric('');
		$('.orderFlag').unbind('change').change(function () {
			orderSettings.ajaxSubmit({
				data: {
					ajax: 1,
					<?= $tour ? 'tour: 1, svcID: ' . $customer->personID . ",\n" : null ?>
					action: 'setOption',
					option: $(this).attr('id'),
					value: Number($(this).attr('checked'))
				},
				success: function (r) {
					if (r) {
						$('#shoppingListCol').html(r);
						bindEvents();
					}
					else console.log('failed to change option ' + $(this).attr('id'));
				}
			});
		});
	}
	$('.listQty').unbind('change').numeric('').change(function () {
		changeQty (this);
	});
	<? if ($customer->isIn($user, false)) { ?>
		$('.listPrice').unbind('change').numeric('.').change(function () {
			changePrice (this);
		});
	<? } } ?>
	$('#payTypeID').unbind('change').change(changePayForm);
	$('#cardAction').unbind('change').change(changeCardAction);
	$('#CardNum').keyup(function () {
		if ($(this).val().length) {
			$('#rememberCC').removeAttr('disabled');
		} else {
			console.log('turning off remember');
			$('#rememberCC').removeAttr('checked').attr('disabled', true);
		}
	}).keyup();
	$('#rememberCC').change(function () {
		console.log('remember triggered');
		if ($(this).attr('checked')) {
			$('#pad').removeAttr('disabled');
			// $('#CardNum').keyup();
		} else if ($('#cardAction').val() == 'useNewCC') {
			$('#pad').attr('disabled', true);
		}
	}).change();
	$('#pad').change(function () {
		console.log('pad triggered');
		if ($(this).attr('checked') && !$(this).attr('disabled')) {
			if ($('#cardAction').val() == 'useNewCC') $('#rememberCC').attr('checked', true);
		} else {
			$('#rememberCC').removeAttr('checked');
		}
	});
	$('#removeHold').click(function () {
		$('#dateHold').val('').change();
	});
	$('#removeResume').click(function () {
		$('#dateResume').val('').change();
	});
}
