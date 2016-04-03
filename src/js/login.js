"use strict";

$(document).ready(function() {
    var recovery_form = $('#recovery-form');
    $('#show-recovery').click(function() {
        recovery_form.removeAttr('hidden');
    });
    $('#generate-recovery-code').click(function() {
        $.post('login.php?submission=generate_code', recovery_form.serialize(), function(return_data) {
            console.log(return_data);
            var return_obj = JSON.parse(return_data);
            if (return_obj['success'] === '1') {
                alert('Recovery email sent!');
                recovery_form.children().removeAttr('hidden');
                $("#recovery-submit").show();
            } else {
                alert('Failed to send recovery. Please check that your Email address is correct.');
            }
        }, 'html');
    });
});
