$(document).ready(function() {
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
});