"use strict";

$(document).ready(function() {
	$('#register-form').submit(validate_form);
});

function validate_form() {
	if ($('#password').val() !== $('#password-2').val()) {
		alert('Passwords do not match!');
		return false;
	}
	return true;
}