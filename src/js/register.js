"use strict";

$(document).ready(function() {
	$('#register-form').submit(validate_form);
});

function validate_form() {
	if ($('#email').val().search(/\w+@\w+\.\w+/) === -1) {
		alert('Email is invalid!');
		return false;
	}
	var password = $('#password').val();
	if (password.length < 6) {
		alert('Password has to be at least 6 characters long!');
		return false;
	} else if (password !== $('#password-2').val()) {
		alert('Passwords do not match!');
		return false;
	}
	return true;
}