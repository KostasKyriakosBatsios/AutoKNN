$(document).ready(function() {
    // Hide the loading button
    $('#loadLoginBtn').hide();

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

    // Handle form submission, when login button is pressed
    $('#loginBtn').on('click', function(event) {
        event.preventDefault();
        $('#loginBtn').hide();
        $('#loadLoginBtn').show();

        var email = $('#email').val();
        var password = $('#password').val();

        // API call for the login process
        $.ajax({
            url: '../server/php/api/login.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: email,
                password: password
            }),
            success: function(response) {
                if (response.status === "success") {
                    if (response.token) {
                        // Store user details
                        localStorage.setItem('token', response.token);
                        localStorage.setItem('email', email);
                        localStorage.setItem('fname', response.fname);
                        localStorage.setItem('lname', response.lname);
                        localStorage.setItem('allowPublic', response.allowPublic)

                        // Redirect to home page
                        window.location.href = '../index.html';
                    } else {
                        showAlert('danger', response.message, '#alertLogin');
                        $('#loginBtn').show();
                        $('#loadLoginBtn').hide();
                    }
                } else {
                    showAlert('warning', response.message, '#alertLogin');
                    $('#loginBtn').show();
                    $('#loadLoginBtn').hide();      
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertLogin');
                $('#loginBtn').show();
                $('#loadLoginBtn').hide();
            }
        });
    });

    // Allow pressing Enter to trigger the login button click
    $('#password').on('keypress', function(event) {
        if (event.which == 13) {
            $('#loginBtn').click();
        }
    });
});