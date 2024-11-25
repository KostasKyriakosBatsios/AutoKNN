$(document).ready(function() {
    // Hide buttons initially
    $('#loginBtn, #loadLoginBtn, #resendBtn, #loadResendBtn').hide();

    // Function to display alert messages
    function showAlert(type, message, id) {
        const alertMessage = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        $(id).html(alertMessage);
    }

    // Extract verification key from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const verificationKey = urlParams.get('verification_key');
    let email = '';

    // AJAX call for the verification process
    $.ajax({
        url: '../server/php/verify_account.php',
        method: 'GET',
        data: { 
            verification_key: verificationKey 
        },
        success: function() {
            showAlert('success', 'Account verified successfully.', '#alertVerify');
            $('#loginBtn').show();
        },
        error: function(jqXHR) {
            let response;
            try {
                response = JSON.parse(jqXHR.responseText);
            } catch (e) {
                response = { message: 'An unexpected error occurred.' };
            }

            showAlert('danger', response.message || 'An error occurred while verifying the account. Press resend email button to send a new verification email.', '#alertVerify');
            $('#resendBtn').show();

            // Set email if provided in the error response
            if (response.email) {
                email = response.email;
            }

            console.log('email from response:', email);
        }
    });

    // Handle resend button click
    $('#resendBtn').on('click', function() {
        $('#resendBtn').hide();
        $('#loadResendBtn').show();

        // AJAX call to resend the verification email
        $.ajax({
            url: '../server/php/resend_email.php',
            data: { 
                email: email
            },
            method: 'GET',
            success: function(response) {
                $('#loadResendBtn').hide();
                showAlert(response.status, response.message, '#alertResend');
                $('#resendBtn').show();
            },
            error: function() {
                $('#loadResendBtn').hide();
                $('#resendBtn').show();
                showAlert('danger', 'Failed to resend verification email. Please try again later.', '#alertVerify');
            }
        });
    });

    // Redirect to login on login button click
    $('#loginBtn').on('click', function() {
        $('#loginBtn').hide();
        $('#loadLoginBtn').show();
        window.location.href = 'login.html';
    });
});