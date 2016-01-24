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


    $('.transaction-hidden-row').children().hide().end()
                    .prev('.transaction-expandable-row').css('cursor', 'pointer');
    $('.transaction-expandable-row').click(function() {
        $(this).next('.transaction-hidden-row').children().slideToggle();
    });
    

    $('.transaction-edit-button').click(make_transaction_form); // makes a form to edit the transaction when clicked
    $('.transaction-delete-button').click(function(e) {
        // single transaction warning
        if ($(this).parent().siblings('input').val() === '0' && !confirm('Delete this transaction? (This cannot be undone).')) {
            e.preventDefault();
            return false;
        // repeated transaction warning
        } else if ($(this).parent().siblings('input').val() === '1' && !confirm('Delete every instance of this repeated transaction? (This cannot be undone)')) {
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

    $('#trans-form').find('#trans-start-date').change(function() {
        console.log($('#trans-form').find('#trans-end-date'));
        console.log($(this).val());
        $('#trans-form').find('#trans-end-date').attr('min', $(this).val());
    });

    // user interface for assigning paid and owed amounts

    // sources of dragging
    var owe_money_dest = $('.owed');
    $('.drag-src').on('dragstart', drag_start_action);
    $('.drag-src').each(function() {
                      convert_src_to_user($(this).attr('user-id'), owe_money_dest, 'owed');
                      set_user_state(owe_money_dest, $(this), owe_money_dest.attr('state'), 'owed');
                      recalculate_user_amount(owe_money_dest);
                  });

    // destination of dragging
    $('.drag-dest').on('dragover', drag_over).end()
                   .on('drop', drag_drop);

    $('.split-even').click(split_evenly_action);

    $('.split-prop').click(split_proportionally_action);

    $('.split-custom').click(split_custom_action);
});

// converts a drag-src draggable object to drag-user
function convert_src_to_user(user_id, dest, origin_str) {
    var src = $('.drag-src.user-' + user_id).clone();
    src.on('dragstart', drag_start_action);
    src.addClass('drag-user');
    src.removeClass('drag-src');

    var close_button = $('<a class="close-button"></a>');
    src.append(close_button);
    close_button.click(function() {
        var drag_dest = $(this).closest('.drag-dest');
        $(this).parent().remove();
        recalculate_user_amount(drag_dest);
    });

    // displays amount
    var amount_div = $('<div class="amount-div" amount="0">$0</div>');
    src.append(amount_div);
    src.attr('origin', origin_str);
    dest.append(src);
    return src;
}

// set input state of users in the container
function set_container_state(container, state) {
    var users = container.find('.drag-user');
    var prev_state = container.attr('state');
    set_user_state(container, users, state, prev_state);
    container.attr('state', state);
    
    var input = container.children('input');
    switch (state) {
        case 'custom':
            input.remove();
            break;
        case 'prop':
        case 'even':
            if (input.length === 0) {
                var input = $('<input type="number" class="form-control input-sm" step="0.01" placeholder="Enter total">');
                // add total inputs before buttons
                var buttons = container.children('.btn-group');
                buttons.after(input);
                input.on('change keyup', function() {
                    recalculate_user_amount(container);
                });
            }
    }
    recalculate_user_amount(container);
}

function set_user_state(container, users, state, prev_state) {
    users.find('input').remove(); // remove input no matter what
    switch (state) {
        case 'input':
        case 'custom':
        case 'prop':
            append_user_input(container, users, state);
            break;
        case null:
        case 'even':
        default:
            break;
    }
}

// append user input to each .drag-user
function append_user_input(container, user, state) {
    var note = null;
    switch (state) {
        case 'custom':
            note = 'Enter amount';
            break;
        case 'prop':
            note = 'Enter decimal weight';
            break;
    }
    var num_input = $('<input type="number" class="form-control input-sm" step="0.01" placeholder="' + note + '">');    
    user.each(function() {
        var new_input = num_input.clone();
        new_input.on('change keyup', function() {
            recalculate_user_amount(container);
        });
        $(this).append(new_input);
    });
}

function sum_user_inputs(container) {
    var sum = 0;
    container.children('.drag-user').children('input').each(function() {
        var input = $(this).val();
        if (input) {
            sum += parseFloat($(this).val());
        }
    })
    return sum;
}

// takes any children of the container
function recalculate_user_amount(container) {
    var state = container.attr('state');
    var drag_users = container.children('.drag-user');
    switch (state) {
        case 'custom':
            drag_users.children('input').each(function() {
                var new_amt = $(this).val();
                if (new_amt) {
                    new_amt = (parseFloat(new_amt)).toFixed(2);
                } else {
                    new_amt = 0;
                }
                var drag_user = $(this).parent();
                var amount_div = drag_user.find('.amount-div');
                amount_div.text('$' + new_amt);
                amount_div.attr('amount', new_amt);                
            });
            break;
        case 'prop':
            var total = container.children('input').val();
            var total_proportion = sum_user_inputs(container);
            drag_users.children('input').each(function() {
                var input = $(this).val();
                var new_amt = 0;
                if (input && total_proportion > 0) {
                    new_amt = (total * parseFloat(input) / total_proportion).toFixed(2);
                }
                var drag_user = $(this).parent();
                var amount_div = drag_user.find('.amount-div');
                amount_div.text('$' + new_amt);
                amount_div.attr('amount', new_amt);
            });
            break;
        case 'even':
            var total = container.children('input').val();
            if (!total) {
                total = 0;
            }
            var average = 0;
            if (drag_users.length > 0) {
                average = (parseFloat(total) / drag_users.length).toFixed(2);
            }
            drag_users.each(function() {
                var amount_div = $(this).find('.amount-div');
                amount_div.text('$' + average);
                amount_div.attr('amount', average);                
            });
            break;
        default:
            drag_users.each(function() {
                var amount_div = $(this).find('.amount-div');
                amount_div.text('$0');
                amount_div.attr('amount', '0');
            });
    }
    update_user_net(container.closest('form'));
}

function update_user_net(form) {
    var final_amounts = form.children('.user-final-amt');
    final_amounts.children('input').each(function() {
        var user_id = $(this).attr('user-id');
        var user_total = 0;
        form.find('.user-' + user_id).each(function() {
            var amount_div = $(this).find('.amount-div');
            // there is an amount in the user
            if (amount_div.length === 1) {
                // in the .paid container
                if ($(this).closest('.paid').length === 1) {
                    user_total -= parseFloat(amount_div.attr('amount'));
                } else if ($(this).closest('.owed').length === 1) {
                    user_total += parseFloat(amount_div.attr('amount'));
                }
            }
        });
        $(this).val((user_total).toFixed(2));
    });
}



function split_evenly_action() {
    var container = $(this).closest('.drag-dest');
    set_container_state(container, 'even');
    
    $(this).siblings().removeAttr('disabled');
    $(this).attr('disabled', 'disabled');
}

function split_proportionally_action() {
    var container = $(this).closest('.drag-dest');
    set_container_state(container, 'prop');

    $(this).siblings().removeAttr('disabled');
    $(this).attr('disabled', 'disabled');
}

function split_custom_action() {
    var container = $(this).closest('.drag-dest');
    set_container_state(container, 'custom');
    $(this).siblings().removeAttr('disabled')
    $(this).attr('disabled', 'disabled');
}



function drag_start_action(evt) {
    var src = $(evt.target);
    var user_id = src.attr('user-id');
    var origin = src.attr('origin');
    evt.originalEvent.dataTransfer.setData('user_id', user_id);
    evt.originalEvent.dataTransfer.setData('origin', origin);
}

function drag_over(evt) {
    evt.preventDefault();
}

function drag_drop(evt) {
    evt.preventDefault();
    evt.stopPropagation();
    var user_id = evt.originalEvent.dataTransfer.getData('user_id');
    var origin = evt.originalEvent.dataTransfer.getData('origin');
    var target = $(evt.target);
    var dest = null;

    // if inside children, move to the parent .drag-dest container
    if (target.hasClass('.drag-dest')) {
        dest = target;
    } else {
        dest = target.closest('.drag-dest');
    }

    if (dest.find('.user-' + user_id).length === 0) { // make sure element doesn't exist already
        var curr_class = null;
        if (target.hasClass('paid')) {
            curr_class = 'paid';
        } else if (target.hasClass('owed')) {
            curr_class = 'owed';
        }
        var src = null;
        if (origin !== 'source') { // coming from some origin, don't clone, just remove the other one
            src = dest.closest('form').find('.drag-user.user-' + user_id);
            var origin_container = src.closest('.drag-dest');
            src.attr('origin', curr_class);
            dest.append(src);
            recalculate_user_amount(origin_container);
        } else { // coming from source, add an amount display and a close button
            src = convert_src_to_user(user_id, dest, curr_class);
        }
        
        // always remove input box if whether there is one or not and then update to existing state
        set_user_state(dest, src, dest.attr('state'), 'input');
        recalculate_user_amount(dest);
    }
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
    deposit_form.attr('action', deposit_form.attr('action').replace('add', 'edit'));
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
    $(this).closest('form').children('form:last-child').remove();
    $(this).unbind().click(make_deposit_form);
    $(this).text('Edit');
}

// creates a new transaction form to allow user to edit existing information
function make_transaction_form() {
    var trans_form = $('#trans-form').clone();

    // hidden input field containing information
    var repeat_info_carrier = $(this).parent().siblings('input');
    trans_form.attr('action', trans_form.attr('action').replace('add', 'edit'));

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
    if (repeat_info_carrier.val() === '1') { // for repeated events
        trans_form.append('<input type="hidden" name="trans-is-repeated">');
        trans_form.children('.trans-start-date').children('label').text('Start Date:');
        trans_form.children('.trans-repeat-info').each(function() {
            $(this).css('display', 'inline');
        });
        trans_info['trans-is-repeated'] = '1';
        trans_info['trans-date'] = repeat_info_carrier.attr('trans-date'); // start date
        trans_info['trans-end-date'] = repeat_info_carrier.attr('trans-end-date');
        trans_info['trans-interval-num'] = repeat_info_carrier.attr('trans-interval-num');
        trans_info['trans-interval-unit'] = repeat_info_carrier.attr('trans-interval-unit');
    } else {
        trans_form.children('.trans-start-date').children('label').text('Date:');
        trans_form.children('.trans-repeat-info').remove();
    }

    // find selected payer and calculate how much he's supposed to pay
    var selected_payer = null;
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
    
    // find selected unit
    var selected_unit = null;
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
    if (selected_payer !== null) {
        selected_payer.attr('selected', 'selected');
    }
    if (selected_unit !== null) {
        selected_unit.attr('selected', 'selected');
    }

    $(this).unbind().click(cancel_transaction_form);
    $(this).text('Cancel');

}

// removes the transaction form created by make_transaction_form adn revert back to previous state
function cancel_transaction_form() {
    $(this).closest('form').next('form:last-child').remove();
    $(this).unbind().click(make_transaction_form);
    $(this).text('Edit');
}