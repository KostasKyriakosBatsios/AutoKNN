$(document).ready(function() {
    // Hide the loading buttons and resend mail button initially
    $('#loadRegisterBtn').hide();
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
        $(id).html(alertMessage);
    }

    // Initialize form parameters
    let fname = '';
    let lname = '';
    let email = '';
    let password = '';
    let confirmPassword = '';

    // Handle form submission
    $('#registerBtn').on('click', function() {
        // Show loading button and hide register button
        $('#registerBtn').hide();
        $('#loadRegisterBtn').show();
    
        fname = $('#fname').val().trim();
        lname = $('#lname').val().trim();
        email = $('#email').val().trim();
        password = $('#password').val().trim();
        confirmPassword = $('#confirmPassword').val().trim();
    
        // Validate inputs
        if (!fname || !lname || !email || !password || !confirmPassword) {
            showAlert('warning', 'All fields are required.', '#alertRegister');
            $('#loadRegisterBtn').hide();
            $('#registerBtn').show();
            return;
        }
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showAlert('warning', 'Invalid email format.', '#alertRegister');
            $('#loadRegisterBtn').hide();
            $('#registerBtn').show();
            return;
        }
        if (password !== confirmPassword) {
            showAlert('warning', 'Passwords do not match.', '#alertRegister');
            $('#loadRegisterBtn').hide();
            $('#registerBtn').show();
            return;
        }
    
        // API call for registering a user
        $.ajax({
            url: '../server/php/api/register.php',
            type: 'POST',
            data: JSON.stringify({
                fname: fname,
                lname: lname,
                email: email,
                password: password,
                confirmPassword: confirmPassword
            }),
            contentType: 'application/json',
            success: function(response) {
                $('#loadRegisterBtn').hide();
                $('#resendBtn').show();
                showAlert(response.status, response.message, '#alertRegister');
                if (response.status === 'success') {
                    showAlert('warning', 'If a verification email has not been sent to your email address, please click on the resend mail button.', '#alertResend');
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#loadRegisterBtn').hide();
                $('#registerBtn').show();
                showAlert('danger', 'An error occurred. Please try again.', '#alertRegister');
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
                showAlert(response.status, response.message, '#alertRegister');
                showAlert('warning', 'If a verification email has not been sent to your email address, please click on the resend mail button', '#alertResend');
           },
           error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#loadResendBtn').hide();
                $('#resendBtn').show();

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertRegister');
                showAlert('danger', message, '#alertResend');
           }
        });
    });

    // Allow pressing Enter to trigger the login button click
    $('#confirmPassword').on('keypress', function(event) {
        if (event.which == 13) {
            $('#registerBtn').click();
        }
    });
});