"use strict";

$(document).ready(function() {
	$('.all').change(function() {
		console.log($(this).closest('tr').siblings().find('input'));
		var checked = this.checked;
		$(this).closest('tr').siblings().find('input').prop('checked', checked);
	});
});
