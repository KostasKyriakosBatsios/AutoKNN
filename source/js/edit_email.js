$(document).ready(function() {
    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');
    var email = localStorage.getItem('email');

    if (token) {
        // User is logged in
        $('#loginBtn').hide();
        $('#registerBtn').hide();
        $('#profileNav').show();
        $('#username').text(fname + ' ' + lname);
        $('#usernameDisplay').text(fname + ' ' + lname);
    } else {
        // User is not logged in
        $('#loginNav').show();
        $('#registerNav').show();
        $('#profileNav').hide();
    }

    // Logout functionality
    $('#logoutBtn').on('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('fname');
        localStorage.removeItem('lname');
        window.location.href = '../index.html'; // Redirect to home page
    });

    // Hide loading buttons and resend email button initially
    $('#loadEditConfirmBtn').hide();
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

    let newEmail = '';

    // Handler when the user presses the confirm button
    $('#confirmBtn').on('click', function() {
        $('#confirmBtn').hide();
        $('#loadEditConfirmBtn').show();

        // Get the new mail the user wrote
        newEmail = $('#email').val().trim();

        // Check if it matches the one used to register initially
        if (newEmail === email) {
            // Show alert
            showAlert('danger', 'The email you entered is the same as the one used to register. Please try again.', '#alertEditEmail');
            $('#loadEditConfirmBtn').hide();
            $('#confirmBtn').show();
            return;
        }

        // API call for editing the email
        $.ajax({
            url: '../server/php/api/edit_email.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: newEmail,
                token: token
            }),
            success: function(response) {
                // Handle successful edit
                if (response.status === 'success') {
                    // Set the new email in local storage
                    localStorage.setItem('email', newEmail);
                    showAlert('success', response.message, '#alertEditEmail');
                    showAlert('warning', 'If a verification email has not been sent to your email address, please click on the resend mail button', '#alertResend');
                    $('#loadEditConfirmBtn').hide();
                    $('#resendBtn').show();
                } else {
                    // Show error alert
                    showAlert('danger', response.message, '#alertEditEmail');
                    $('#loadEditConfirmBtn').hide();
                    $('#confirmBtn').show();
                }
            },
            error: function() {
                // Show error alert
                showAlert('danger', 'An error occurred. Please try again.', '#alertEditEmail');
                $('#loadEditConfirmBtn').hide();
                $('confirmBtn').show();
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