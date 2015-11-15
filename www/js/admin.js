"use strict";

$(document).ready(function() {
	$('.all').change(function() {
		var checked = this.checked;
		$(this).closest('tr').siblings().find('input').prop('checked', checked);
	});
});
