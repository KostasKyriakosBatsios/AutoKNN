$(document).ready(function() {
    // Check if the user is logged in by looking for the token
    var token = localStorage.getItem('token');
    var fname = localStorage.getItem('fname');
    var lname = localStorage.getItem('lname');

    // Hide the span with id text2 and profile card initially
    $('#text2').hide();
    $('#profileCard').hide();

    // Initialize welcome message
    $('#welcome').text('Welcome to AutoKNN');

    if (token) {
        // User is logged in
        $('#loginBtn').hide();
        $('#registerBtn').hide();
        $('#profileNav').show();
        $('#username').text(fname + ' ' + lname);
        $('#usernameDisplay').text(fname + ' ' + lname);
        $('#text1').hide();
        $('#text2').show();
        $('#welcome').text('Welcome to AutoKNN, ' + fname + ' ' + lname);
        $('#profileCard').show();
    } else {
        // User is not logged in
        $('#loginNav').show();
        $('#registerNav').show();
        $('#profileNav').hide();
        $('#text1').show();
        $('#text2').hide();
        $('#welcome').text('Welcome to AutoKNN');
        $('#profileCard').hide();
    }

    // Logout functionality
    $('#logoutBtn').on('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('fname');
        localStorage.removeItem('lname');
        window.location.href = 'index.html';
        $('#text1').show();
        $('#text2').hide();
        $('#welcome').text('Welcome to AutoKNN');
    });

    // Handler when the about button is clicked
    $('#aboutBtn').on('click', function() {
        window.location.href = './web_pages/about.html'; // Redirect to about page
    });

    // Handler when the rate button is clicked
    $('#rateBtn').on('click', function() {
        window.open('https://forms.gle/yobQyc7v4heNRV7z9');
    });
});