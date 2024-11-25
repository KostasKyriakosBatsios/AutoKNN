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
            success: function(data) {
                // Reset form
                showAlert('success', data.message, '#alertReset');
                showAlert('warning', 'If a verification email has not been sent to your email address, please click on the resend mail button', '#alertResend');
                $('#loadResetBtn').hide();
                $('#resendBtn').show();
            },
            error: function() {
                showAlert('danger', 'An error occurred. Please try again.', '#alertReset');
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
           error: function(error) {
                console.log('AJAX Error:', error); // Debug: log AJAX error
                $('#loadResendBtn').hide();
                $('#resendBtn').show();
                showAlert('danger', 'An error occurred. Please try again.', '#alertResend');
           }
        });
    });
});