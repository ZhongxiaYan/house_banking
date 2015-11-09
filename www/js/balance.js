"use strict";

$(document).ready(function() {
	// hide the rows that do not expand except when clicked
	$('.hidden-row').children().hide().end()
					.prev('.expandable-row').css({
						'cursor': 'pointer',
						'background-color': '#b8d1f3'
					});

	// clicking a visible expandable row will expand a hidden row under it
    $('.expandable-row').click(function() {
        $(this).nextUntil('.expandable-row').children().slideToggle();
    });

    $('.deposit-hidden-row').children().hide().end()
					.prev('.deposit-expandable-row').css('cursor', 'pointer');
    $('.deposit-expandable-row').click(function() {
    	var tr = $(this).next('.deposit-hidden-row').children().eq(0);
        $(this).next('.deposit-hidden-row').children().slideToggle();
    });

    $('.deposit-edit-button').click(make_deposit_form); // create a form to edit a deposit when clicked

    $('.deposit-delete-button').click(function(e) { // deletes the deposit
    	if (!confirm('Are you sure you want to delete this deposit? (This cannot be undone).')) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    $('#deposit-amount').blur(function() { // keeps amount with 2 decimal places
    	var amount = $(this).val();
    	$(this).val(Math.ceil(100 * amount) / 100);
    });
    

    $('.transaction-hidden-row').children().hide().end()
					.prev('.transaction-expandable-row').css('cursor', 'pointer');
    $('.transaction-expandable-row').click(function() {
        $(this).next('.transaction-hidden-row').children().slideToggle();
    });
	
	// adjust display based on who is selected to have paid and total amount of payment
    $('#trans-paid-by').change(choose_paid_user);
    $('#trans-total-amount').blur(split_total_amount);

    $('.transaction-edit-button').click(make_transaction_form); // makes a form to edit the transaction when clicked
    $('.transaction-delete-button').click(function(e) {
    	// single transaction warning
    	if ($(this).parent().siblings('input').val() === 's' && !confirm('Delete this transaction? (This cannot be undone).')) {
            e.preventDefault();
            return false;
        // repeated transaction warning
        } else if ($(this).parent().siblings('input').val() === 'r' && !confirm('Delete every instance of this repeated transaction? (This cannot be undone)')) {
        	e.preventDefault();
            return false;
        }
        return true;
    });

    // toggle transaction form between single or repeated transaction
    $('.trans-repeat-toggle input').change(function() {
    	if ($(this).is(':checked')) { // repeated
    		// show hidden fields
    		$(this).parent('.form-group').siblings('.trans-repeat-info').each(function() {
    			$(this).css('display', 'inline');
    		});
    		$(this).parent('.form-group').siblings('.trans-start-date').children('label').text('Start date:');
    	} else {
    		// hide fields for repeated transactions
    		$(this).parent('.form-group').siblings('.trans-repeat-info').each(function() {
    			$(this).css('display', 'none');
    		});
    		$(this).parent('.form-group').siblings('.trans-start-date').children('label').text('Date:');
    	}
    }).trigger('change');

});

// gets user selected by trans-paid-by field
function choose_paid_user() {
	var form = $(this).closest('form');
	var selected_id = $(this).find('option:selected').val();
	var total_amount = form.find('#trans-total-amount').val();
	adjust_display_compensation(form.find('.user-amount'), total_amount, selected_id);
}

// shows subtraction of the total amount for the user that paid the amount
function adjust_display_compensation(user_amounts, total_amount, selected_id) {
	var old_user_name = adjust_display_compensation.old_user_name;
	var new_user_name;
	if (!total_amount) {
		total_amount = 0;
	}
	user_amounts.each(function() {
		var name = $(this).attr('name');
		if (name === old_user_name) {
			$(this).parent().next('span').remove();
		}
		if (name === 'user_' + selected_id + '_amount') {
			// show subtraction of total amount from this user
			$(this).parent().after('<span id="adjustment-amount"> - ' + total_amount + ' </span>');
			new_user_name = name;
		}
	});
	adjust_display_compensation.old_user_name = new_user_name;
}

// for the closest form, split the total amount evenly among all people in house
function split_total_amount() {
	var form = $(this).closest('form');
	var selected_id = form.find('#trans-paid-by option:selected').val();
	var total_amount = parseFloat($(this).val());
	total_amount = Math.ceil(100 * total_amount) / 100;
	$(this).val(total_amount);
	var amounts = form.find('.user-amount');
	adjust_display_compensation(amounts, total_amount, selected_id);
	var old_user_amount = this.user_amount;
	var new_user_amount = Math.ceil(100 * total_amount / amounts.length) / 100;
	amounts.each(function() {
		if (!old_user_amount) {
			$(this).val(new_user_amount);
		} else if (total_amount) {
			if (!$(this).val() || parseFloat($(this).val()) === old_user_amount) {
				$(this).val(new_user_amount);
			}
		}
	});
	this.user_amount = new_user_amount;
}

// clones the deposit form at the top of the page to allow deposit editting
function make_deposit_form() {
	var deposit_form = $('#deposit-form').clone();

	// stores information in the table into a map
	var deposit_info = new Object;
	$(this).closest('tr').prev().children('td').each(function() {
		deposit_info[$(this).attr('type')] = $(this).text();
	});

	// set the default values of all the fields to be existing info
	deposit_form.find(':input').each(function() {
		var field_name = $(this).attr('name');
		if (deposit_info.hasOwnProperty(field_name)) { // field should be changed
			$(this).val(deposit_info[field_name]);
		}
	});

	// changes form submission information
	deposit_form.attr('action', 'balance.php?submission=deposit_edit');
	deposit_form.append('<input type="hidden" name="deposit-id" value="' + $(this).val() + '">');
	
	// append form to the row entry, set up listeners
	$(this).parent().parent().append(deposit_form);
	deposit_form.find('#deposit-amount').blur(function() {
    	$(this).val(Math.ceil(100 * parseFloat($(this).val())) / 100);
    });

	$(this).unbind().click(cancel_deposit_form);
	$(this).text('Cancel');
}

// removes the deposit form added by make_deposit_form and revert back to previous state
function cancel_deposit_form() {
	console.log($(this).closest('td').children('form:last-child'));
	$(this).closest('form').children('form:last-child').remove();
	$(this).unbind().click(make_deposit_form);
	$(this).text('Edit');
}

// creates a new transaction form to allow user to edit existing information
function make_transaction_form() {
	var trans_form = $('#trans-form').clone();

	// hidden input field containing information
	var repeat_info_carrier = $(this).parent().siblings('input');
	trans_form.attr('action', 'balance.php?submission=transaction_edit');

	// stores information in the table into a map
	var trans_info = new Object;
	$(this).closest('tr').prev().children('td').each(function() {
		var type = $(this).attr('type');
		if (type === 'trans-paid-by') { // for trans-paid-by cell, use the user-id attribute
			trans_info[type] = $(this).attr('user-id');
		} else {
			trans_info[type] = $(this).text();
		}
	});
	$(this).closest('td').find('div').each(function() {
		var user_id = $(this).attr('user-id');
		if (typeof user_id !== typeof undefined && user_id !== false) {
			trans_info['user_' + $(this).attr('user-id') + '_amount'] = parseFloat($(this).text().split(': ')[1]);
		}
	});

	// deal with transactions that repeat. Take info stored in hidden field
	trans_form.find('.trans-repeat-toggle').remove(); // remove the toggle and add a hidden field signaling repeat if needed
	if (repeat_info_carrier.val() === 'r') { // for repeated events
		trans_form.append('<input type="hidden" name="trans-repeat">');
		trans_form.children('.trans-start-date').children('label').text('Start Date:');
		trans_form.children('.trans-repeat-info').each(function() {
			$(this).css('display', 'inline');
		});
		trans_info['trans-repeat'] = 'yes';
		trans_info['trans-date'] = repeat_info_carrier.attr('trans-date'); // start date
		trans_info['trans-end-date'] = repeat_info_carrier.attr('trans-end-date');
		trans_info['trans-interval-num'] = repeat_info_carrier.attr('trans-interval-num');
		trans_info['trans-interval-unit'] = repeat_info_carrier.attr('trans-interval-unit');
	} else {
		trans_form.children('.trans-start-date').children('label').text('Date:');
		trans_form.children('.trans-repeat-info').remove();
	}

	// find selected payer and calculate how much he's supposed to pay
	var selected_payer;
	trans_form.find('#trans-paid-by').each(function() {
		$(this).children().each(function(index) {
			if (typeof selected_payer === 'undefined' && index === 0) {
				selected_payer = $(this);
			}
			if ($(this).val() === trans_info['trans-paid-by']) { // check if user-id of this person is same as the payer's
				selected_payer = $(this);
				trans_info['user_' + $(this).val() + '_amount'] = trans_info['user_' + $(this).val() + '_amount'] + parseFloat(trans_info['trans-total-amount']);
			}
		});
	});
	console.log(trans_info['trans-interval-unit']);
	// find selected unit
	var selected_unit;
	trans_form.find('#trans-interval-unit').each(function() {
		$(this).children().each(function(index) {
			if (typeof selected_unit === 'undefined' && index === 0) {
				selected_unit = $(this);
			}
			if ($(this).val() === trans_info['trans-interval-unit']) {
				selected_unit = $(this);
			}
		});
	});
	trans_form.append('<input type="hidden" name="trans-id">');
	trans_info['trans-id'] = repeat_info_carrier.attr('trans-id');

	// set the default values of all the fields to be existing info
	trans_form.find(':input').each(function() {
		var field_name = $(this).attr('name');
		if (trans_info.hasOwnProperty(field_name)) { // field should be changed
			$(this).val(trans_info[field_name]);
		}
	});

	// append form to the row entry, set up listeners
	$(this).closest('td').append(trans_form);
	selected_payer.attr('selected', 'selected');
	selected_unit.attr('selected', 'selected');
	trans_form.find('#trans-paid-by').change(choose_paid_user).change();

	$(this).unbind().click(cancel_transaction_form);
	$(this).text('Cancel');

}

// removes the transaction form created by make_transaction_form adn revert back to previous state
function cancel_transaction_form() {
	$(this).closest('form').next('form:last-child').remove();
	$(this).unbind().click(make_transaction_form);
	$(this).text('Edit');
}