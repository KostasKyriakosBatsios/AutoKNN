$(document).ready(function() {
    // Hide the loading button initially
    $('#loadEditNamePassBtn').hide();
    
    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');

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
        window.location.href = '../index.html'; // Redirect to home page
    });

    // Function to display alert message
    function showAlert(message, type) {
        var alertMessage = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#alertEditNamePass').html(alertMessage); // Make sure this container exists in your HTML
    }

    // Hanlde the radio buttons
    $('#changeOptions input[type="radio"]').on('change', function() {
        var selectedOption = $('#changeOptions input[type="radio"]:checked').val();

        // Disable all inputs by default
        $('#fname, #lname, #currentPassword, #newPassword, #confirmPassword').prop('disabled', true);
        $('#confirmBtn').prop('disabled', true);

        if (selectedOption === 'username') {
            $('#currentPassword, #newPassword, #confirmPassword').val('');
            $('#fname, #lname').prop('disabled', false);
            validateForm();
        } else if (selectedOption === 'password') {
            $('#fname, #lname').val('');
            $('#currentPassword, #newPassword, #confirmPassword').prop('disabled', false);
            validateForm();
        } else if (selectedOption === 'both') {
            $('#fname, #lname, #currentPassword, #newPassword, #confirmPassword').prop('disabled', false);
            validateForm();
        }
    });

    // Function to validate form fields and enable/disable confirm button
    function validateForm() {
        var selectedOption = $('#changeOptions input[type="radio"]:checked').val();
        var isValid = false;

        if (selectedOption === 'username') {
            isValid = $('#fname').val().trim() !== '' && $('#lname').val().trim() !== '';
        } else if (selectedOption === 'password') {
            isValid = $('#currentPassword').val().trim() !== '' && 
                      $('#newPassword').val().trim() !== '' && 
                      $('#confirmPassword').val().trim() !== '';
        } else if (selectedOption === 'both') {
            isValid = $('#fname').val().trim() !== '' && 
                      $('#lname').val().trim() !== '' &&
                      $('#currentPassword').val().trim() !== '' && 
                      $('#newPassword').val().trim() !== '' && 
                      $('#confirmPassword').val().trim() !== '';
        }

        $('#confirmBtn').prop('disabled', !isValid);
    }

    // Monitor inputs to validate form dynamically
    $('#fname, #lname, #currentPassword, #newPassword, #confirmPassword').on('input', validateForm);

    // Handle form submission
    $('#confirmBtn').on('click', function(event) {
        event.preventDefault();

        var formData = {
            token: token,
            first_name: $('#fname').val(),
            last_name: $('#lname').val(),
            current_password: $('#currentPassword').val(),
            new_password: $('#newPassword').val(),
            confirm_password: $('#confirmPassword').val()
        };

        var selectedOption = $('input[name="changeOption"]:checked').val();

        $('#confirmBtn').hide();
        $('#loadEditNamePassBtn').show();

        // API call for changing the username and/or password
        $.ajax({
            url: '../server/php/api/edit_name_password.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#confirmBtn').show();
                $('#loadEditNamePassBtn').hide();

                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    
                    if (selectedOption === 'username' || selectedOption === 'both') {
                        // Update session storage and displayed username
                        localStorage.setItem('fname', formData.first_name);
                        localStorage.setItem('lname', formData.last_name);
                        $('#username').text(formData.first_name + ' ' + formData.last_name);
                    }

                    $('#fname, #lname, #currentPassword, #newPassword, #confirmPassword').val('');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                // Handle error during the kNN execution
                console.log('Error starting the algorithm:', error);
                console.log('XHR object:', xhr);
                console.log('Status:', status);
                $('#confirmBtn').show();
                $('#loadEditNamePassBtn').hide();
                showAlert('An error occurred while processing your request.', 'danger');
            }
        });
    });

    // Allow pressing Enter to trigger the login button click
    $('#confirmPassword').on('keypress', function(event) {
        if (event.which == 13) {
            $('#confirmBtn').click();
        }
    });

    // Allow pressing Enter to trigger the login button click
    $('#lname').on('keypress', function(event) {
        if (event.which == 13) {
            $('#confirmBtn').click();
        }
    });
});