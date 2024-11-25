$(document).ready(function() {
    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');

    // If no token is found, redirect to the login page
    if (!token) {
        window.location.href = './login.html';
        return; // Prevent further execution of the script
    }

    // Fetch user data from session storage
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');

    // User is logged in
    $('#loginBtn').hide();
    $('#registerBtn').hide();
    $('#profileNav').show();
    $('#username').text(fname + ' ' + lname);

    // Logout functionality
    $('#logoutBtn').on('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('fname');
        localStorage.removeItem('lname');
        localStorage.removeItem('allowPublic');
        window.location.href = '../index.html'; // Redirect to home page
    });

    // Put the user's token on the input
    $('#token').val(token);

    // Copy the token to the clipboard when the copy button is clicked
    $('#copyToken').on('click', function() {
        navigator.clipboard.writeText(token);

        // Change the button's text and icon to indicate success
        var copyButton = $('#copyToken');
        copyButton.html('<i><ion-icon name="checkmark-outline"></ion-icon></i><span>Copied</span>');

        // Revert the button's text and icon after 2 seconds
        setTimeout(function() {
            copyButton.html('<i><ion-icon name="clipboard-outline"></ion-icon></i><span>Copy</span>');
        }, 2000);
    });

    // Optional: Disable the input field
    $('#token').prop('disabled', true);
});