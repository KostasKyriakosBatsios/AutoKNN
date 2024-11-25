<?php
    require_once "db_upass.php";
    
    $host = "localhost";
    $db = "autoknn_db";

    $user = $DB_USER;
    $pass = $DB_PASS;

    $mysqli = new mysqli($host, $user, $pass, $db);

    // Checking if the connection to MySQL has failed.
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
?>