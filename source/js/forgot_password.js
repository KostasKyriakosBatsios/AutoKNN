$(document).ready(function() {
    // Hide loading buttons and resend email button initially
    $('#loadResetBtn').hide();
    $('#resendBtn').hide();
    $('#loadResendBtn').hide();

    // Function to display the alert message
    function showAlert(type, message, id) {
        var alertMessage = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $(id).html(alertMessage); // Display alert in the datasets section
    }

    let email = '';

    // Handler when the reset button is clicked
    $('#resetBtn').on('click', function() {
        $('#resetBtn').hide();
        $('#loadResetBtn').show();
        email = $('#email').val().trim();

        // AJAX call for resetting the password
        $.ajax({
            url: '../server/php/forgot_password.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: email
            }),
            success: function(response) {
                // Reset form
                showAlert('success', response.message, '#alertReset');
                showAlert('warning', 'If a reset password email has not been sent to your email address, please click on the resend mail button', '#alertResend');
                $('#loadResetBtn').hide();
                $('#resendBtn').show();
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertReset');
                $('#resetBtn').show();
                $('#loadResetBtn').hide();
            }
        });
    });

    // Handle resend button click
    $('#resendBtn').on('click', function() {
        // Show loading button and hide resend button
        $('#resendBtn').hide();
        $('#loadResendBtn').show();

        // AJAX call to resend a verification mail
        $.ajax({
           url: '../server/php/resend_email.php',
           data: {
               email: email
           },
           method: 'GET',
           success: function(response) {
                $('#loadResendBtn').hide();
                $('#resendBtn').show();
                showAlert(response.status, response.message, '#alertResend');
           },
           error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#loadResendBtn').hide();
                $('#resendBtn').show();

                // Display specific error message from the server response
                const response = JSON.parse(xhr.responseText);
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertResend');
           }
        });
    });
});