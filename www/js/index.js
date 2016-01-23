"use strict";

$(document).ready(function() {
	update_height_width();
	window.sessionStorage.table = JSON.stringify(get_table_data());
	window.sessionStorage.server_table = '';
	var table = $('#editable-table');
	$('#table-width').change(function() { // desired width changed
		var tr = table.children('tbody').children('tr');
		var width = tr.eq(0).children().length;
		var targ_width = $(this).val();
		tr.each(function() { // adjust each row's width until matches the desired width
			var tr_width = width;
			while (targ_width > tr_width) { // need to add some columns
				$(this).append('<td contenteditable></td>');
				tr_width++;
			}
			if (targ_width < tr_width) { // need to delete some columns
				$(this).children('td').slice(targ_width - tr_width).remove();
			}
		});
	});
	$('#table-height').change(function() { // desired height changed
		var new_row = get_new_row(table);
		var height = table.children('tbody').children('tr').length;
		var targ_height = $(this).val();
		table.children('tbody').each(function() { // adjust table height until matches the desired height
			while (targ_height > height) { // need to add some rows
				$(this).append(new_row.clone());
				height++;
			}
			if (targ_height < height) { // need to delete some rows
				$(this).children('tr').slice(targ_height - height).remove();
			}
		});
	});
	$('#append-top').click(function() {
		table.children('tbody').prepend(get_new_row(table));
		update_height_width();
	});
	$('#append-bottom').click(function() {
		table.children('tbody').append(get_new_row(table));
		update_height_width();
	});
	$('#append-left').click(function() {
		table.children('tbody').children('tr').each(function() {
			$(this).prepend('<td contenteditable></td>');
		});
		update_height_width();
	});
	$('#append-right').click(function() {
		table.children('tbody').children('tr').each(function() {
			$(this).append('<td contenteditable></td>');
		});
		update_height_width();
	});
	$('#interactive-resize').click(start_resize);
	$('#restore').click(restore_data);
	$('#save').click(send_data);
	var timer_id = window.setInterval(check_server_data, 5000);
	$(window).blur(function() {
        window.clearInterval(timer_id);
        timer_id = 0;
	});
	$(window).focus(function() {
    	window.clearInterval(timer_id);
        timer_id = window.setInterval(check_server_data, 5000);
	});
});

function get_new_row(table) {
	var new_row = null;
	var height = table.children('tbody').children('tr').length;
	if (height > 0) { // clone existing row if one exists
		var new_row = table.children('tbody').children('tr').eq(0).clone();
		new_row.children('td').text('');
	} else { // create row from scratch if previous height is 0
		var width = $('#table-width').val();
		if (isNaN(width)) { // backup in case someone screws with the textbox
			var tr = table.children('tbody').children('tr').eq(0).children().length;
		}
		var new_row_str = '<tr>';
		for (var i = 0; i < width; i++) {
			new_row_str += '<td contenteditable></td>';
		}
		new_row_str += '</tr>';
		new_row = $(new_row_str);
	}
	return new_row;
}

function start_resize() {
	$(this).text('Done');
	$('#restore').unbind().click(cancel_resize).text('Cancel');
	$('#editable-table').find('td').addClass('editting-cell').click(resize_select).mouseenter(function() {
		var selected = $('.selected-cell');
		if (selected.length == 1) {
			var column = $(this).index();
			var row = $(this).parent().index();
			var selected_column = selected.index();
			var selected_row = selected.parent().index();
			$('#editable-table').find('td').removeClass('editting-cell-covered').end()
								.find('tr').slice(Math.min(row, selected_row), Math.max(row, selected_row) + 1)
								.each(function() {
									$(this).children('td').slice(Math.min(column, selected_column), Math.max(column, selected_column) + 1)
										   .addClass('editting-cell-covered');
								});
		}
	}).end().attr('title', 'Select first corner');
	$(this).unbind().click(done_resize);
}

// find the selected cells and crop the table according to the corners defined by the selected cells
function done_resize() {
	var selected = false;
	var min_x = Infinity;
	var min_y = Infinity;
	var max_x = 0;
	var max_y = 0;
	$('.selected-cell').each(function() {
		var x = $(this).index();
		var y = $(this).parent('tr').index();
		min_x = Math.min(min_x, x);
		max_x = Math.max(max_x, x);
		min_y = Math.min(min_y, y);
		max_y = Math.max(max_y, y);
		selected = true;
	});
	if (selected) { // crop the unwanted parts
		resize(min_x, max_x, min_y, max_y);
	}
	cancel_resize();
}

function resize(min_x, max_x, min_y, max_y) {
	var tbody = $('#editable-table').children('tbody');
	tbody.children('tr').slice(max_y + 1).remove();
	tbody.children('tr').slice(0, min_y).remove();
	tbody.children('tr').each(function() {
		$(this).children('td').slice(max_x + 1).remove();
		$(this).children('td').slice(0, min_x).remove();
	});
	$('#table-width').val(max_x - min_x + 1).change();
	$('#table-height').val(max_y - min_y + 1).change();
}

function cancel_resize() {
	$('#editable-table').find('td').removeClass('editting-cell selected-cell editting-cell-covered').unbind();
	$('#restore').unbind().click(restore_data).text('Restore');
	$('#interactive-resize').unbind().click(start_resize).text('Interactive Crop');
	$('#editable-table').removeAttr('title');
}

function resize_select() {
	var num_cells = $('.selected-cell').length;
	if (num_cells < 2) {
		$(this).addClass('selected-cell').unbind('click').click(resize_deselect);
		if (num_cells === 0) {
			$('#editable-table').attr('title', 'Select second corner or click "Done"');
		} else {
			$('#editable-table').attr('title', 'Click "Done" to crop');
		}
	} else {
		$('#editable-table').attr('title', 'Click "Done" to crop');
	}
}

function resize_deselect() {
	$(this).removeClass('selected-cell editting-cell-covered').unbind('click').click(resize_select);
	var num_cells = $('.selected-cell').length;
	if (num_cells === 0) {
		$('#editable-table').attr('title', 'Select first corner');
	} else if (num_cells === 1) {
		$('#editable-table').attr('title', 'Select second corner or click "Done"');
	}
}

function update_height_width() {
	$('#table-height').val($('#editable-table').children('tbody').children('tr').length).change();
	$('#table-width').val($('#editable-table').children('tbody').children('tr').eq(0).children('td').length).change();
}

function send_data() {
	var send_data = {};
	var curr_table_data = get_table_data();
	send_data['after'] = curr_table_data;
	send_data['before'] = JSON.parse(window.sessionStorage.table);
	send_data['action'] = 'alter_table';
	send_data['session_token'] = $('#editable-table').attr('session_token');
	var request = $.post("index.php?submission=alter_table", send_data, function(return_data) {
		// console.log(return_data);
		var return_obj = JSON.parse(return_data);
		if (return_obj['success'] === '1') {
			alert('Done!');
			window.sessionStorage.table = JSON.stringify(curr_table_data);
		} else {
			alert('Data changed by another user, please click restore or refresh.');
			window.sessionStorage.server_table = JSON.stringify(return_obj['reference']);
		}
	}, 'html');
	request.error(function(jqXHR, textStatus, errorThrown) {
		alert('Error data not saved: ' + textStatus, errorThrown);
		console.error('Error: ' + textStatus, errorThrown);
	});
}

function get_table_data() {
	var cell_data = {};
	$('#editable-table').find('tr').each(function(row_index) {
		var row_data = {};
		$(this).children('td').each(function(column_index) {
			var text = $(this).text();
			if (text !== '') {
				row_data[column_index.toString()] = text;
			}
		});
		if (!jQuery.isEmptyObject(row_data)) {
			cell_data[row_index.toString()] = row_data;
		}
	});
	return cell_data;
}

function restore_data(e) {
	if (window.sessionStorage.server_table === '' && !confirm('Restore to original table?')) {
    	e.preventDefault();
        return;
    } else if (window.sessionStorage.server_table !== '' && !confirm('Restore to up-to-date table?')) {
    	e.preventDefault();
    	return;
    }
	if (window.sessionStorage.server_table !== '') {
		window.sessionStorage.table = window.sessionStorage.server_table;
		window.sessionStorage.server_table = '';
	}
	var curr_table = JSON.parse(window.sessionStorage.table);
	set_table_data(curr_table);
	check_server_data.user_alerted = false;
}

function set_table_data(curr_table) {
	var max_row = 0;
	var max_column = 0;
	// find dimension of new table
	for (var row in curr_table) {
		max_row = Math.max(max_row, parseInt(row));
		for (var column in curr_table[row]) {
			max_column = Math.max(max_column, parseInt(column));
		}
	}
	resize(0, max_column, 0, max_row);

	// set data for resized table
	$('#editable-table').find('tr').each(function(row_index) {
		var row_data = curr_table[row_index.toString()];
		if (typeof row_data === typeof undefined && row_data !== false) {
			$(this).children('td').text('');
		} else {
			$(this).children('td').each(function(column_index) {
				var text = row_data[column_index.toString()];
				if (typeof text !== typeof undefined && text !== false) {
					$(this).text(text);
				} else {
					$(this).text('');
				}
			});
		}
			
	});
}

function check_server_data() {
	var send_data = {
		action : 'check_table',
		session_token : $('#editable-table').attr('session_token')
	};
	if (!('user_alerted' in check_server_data)) {
		check_server_data.user_alerted = false;
	}

	var request = $.post("index.php?submission=check_table", send_data, function(return_data) {
		var return_obj = JSON.parse(return_data);
		var reference_table = return_obj['reference'];
		var curr_saved_table = JSON.parse(window.sessionStorage.table);
		if (compare_table_data(reference_table, curr_saved_table)) {
			window.sessionStorage.server_table = '';
		} else {
			window.sessionStorage.server_table = JSON.stringify(reference_table);
			if (!check_server_data.user_alerted) {
				alert('Data changed on server! Please click "Restore" or refesh the page');
				check_server_data.user_alerted = true;
			}	
		}
	}, 'html');
	request.error(function(jqXHR, textStatus, errorThrown) {
		alert('Could not connect to server error: ' + textStatus, errorThrown);
		console.error('Error: ' + textStatus, errorThrown);
	});
}

function compare_table_data(table1, table2) {
	if (Object.keys(table1).length !== Object.keys(table2).length) {
		return false;
	}
	for (var row in table1) {
		var table1row;
		var table2row;
		if (!(row in table2) || Object.keys(table1row = table1[row]).length !== Object.keys(table2row = table2[row]).length) {
			return false;
		}
		for (var column in table1row) {
			if (!(column in table2row) || table2row[column] !== table1row[column]) {
				return false;
			}
		}
	}
	return true;
}