$(document).ready(function() {
    // Hide loading button initially
    $('#loadDeleteBtn').hide();

    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');
    var loggedInEmail = localStorage.getItem('email'); // Ensure this is set when the user logs in


    if (token) {
        // User is logged in
        $('#loginBtn').hide();
        $('#registerBtn').hide();
        $('#profileNav').show();
        $('#username').text(fname + ' ' + lname);
    } else {
        // User is not logged in
        $('#loginBtn').show();
        $('#registerBtn').show();
        $('#profileNav').hide();
    }

    // Logout functionality
    $('#logoutBtn').on('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('fname');
        localStorage.removeItem('lname');
        localStorage.removeItem('email');
        window.location.href = '../index.html'; // Redirect to home page
    });

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

    // Event listeners to check fields
    $('#email, #password, #confirmPassword').on('input', function() {
        var email = $('#email').val().trim();
        var password = $('#password').val().trim();
        var confirmPassword = $('#confirmPassword').val().trim();
        
        if (email && password && confirmPassword) {
            $('#confirmBtn').prop('disabled', false);
        } else {
            $('#confirmBtn').prop('disabled', true);
        }
    });

    // Handle the confirm button when pressed
    $('#confirmBtn').on('click', function() {
        var email = $('#email').val().trim();
        var password = $('#password').val().trim();
        var confirmPassword = $('#confirmPassword').val().trim();

        if (password !== confirmPassword) {
            showAlert('danger', 'Passwords do not match. Please try again.', '#alertDelete');
            $('#loadDeleteBtn').hide();
            $('#confirmBtn').show();
            $('#deleteModal').modal('hide'); // Hide the modal if passwords do not match
            return;
        }

        if (email !== loggedInEmail) {
            showAlert('danger', 'The email address does not match the logged-in account.', '#alertDelete');
            $('#loadDeleteBtn').hide();
            $('#confirmBtn').show();
            $('#deleteModal').modal('hide'); // Hide the modal if the email does not match
            return;
        }

        $('#loadDeleteBtn').show();
        $('#confirmBtn').hide();
        $('#deleteModal').modal('show'); // Show the modal
    });

    // Handle account deletion confirmation
    $('#confirmDeleteBtn').on('click', function() {
        var email = $('#email').val().trim();
        var password = $('#password').val().trim();
        var confirmPassword = $('#confirmPassword').val().trim();

        // API call for deleting the account
        $.ajax({
            url: '../server/php/api/delete_account.php',
            type: 'DELETE',
            contentType: 'application/json',
            data: JSON.stringify({
                email: email,
                password: password,
                confirmPassword: confirmPassword,
                token: token,
            }),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', 'Account deleted successfully. You will be redirected shortly.', '#alertDelete');
                    setTimeout(function() {
                        $('#loadDeleteBtn').hide();
                        $('#confirmBtn').show();
                        $('#deleteModal').modal('hide');
                        localStorage.removeItem('token');
                        localStorage.removeItem('fname');
                        localStorage.removeItem('lname');
                        window.location.href = '../index.html'; // Redirect to home page
                    }, 2000); // Delay for user to see the alert
                } else {
                    showAlert('danger', response.message, '#alertDelete');
                    $('#loadDeleteBtn').hide();
                    $('#confirmBtn').show();
                    $('#deleteModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);

                // Display specific error message from the server response
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'An unexpected error occurred.';

                showAlert('danger', message, '#alertDelete');
                $('#loadDeleteBtn').hide();
                $('#confirmBtn').show();
                $('#deleteModal').modal('hide');
            }
        });
    });
});