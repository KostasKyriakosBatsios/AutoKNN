$(document).ready(function() {
    // Initialize buttons as hiden
    $('#loadConfirmBtn').hide();
    $('#loginBtn').hide();
    $('#loadLoginBtn').hide();

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

    // Getting the verification key from the URL
    let urlParams = new URLSearchParams(window.location.search);
    let verificationKey = urlParams.get('verification_key');

    // Handler when the user presses the confirm button
    $('#confirmBtn').on('click', function() {
        $('#confirmBtn').hide();
        $('#loadConfirmBtn').show();

        var password = $('#password').val().trim();
        var confirmPassword = $('#confirmPassword').val().trim();

        // AJAX call to reset the password
        $.ajax({
            url: '../server/php/reset_password.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                verification_key: verificationKey,
                password: password,
                confirmPassword: confirmPassword
            }),
            success: function() {
                showAlert('success', 'Password reset successful', '#alertResetPassword');
                $('#loadConfirmBtn').hide();
                $('#loginBtn').show();
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertResetPassword');
                $('#loadConfirmBtn').hide();
                $('#confirmBtn').show();
                if (message === 'Invalid verification key') {
                    $('#confirmBtn').show().prop('disabled', true);
                    showAlert('warning', 'To get a new verification key, please click on the forgot password link below the confirm button.', '#alertResend');
                }
            }
        });
    });

    // Handler when the user presses the login button
    $('#loginBtn').on('click', function() {
        $('#loginBtn').hide();
        $('#loadLoginBtn').show();
        window.location.href = 'login.html';
    });
});