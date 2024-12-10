<?php
    require_once "db_upass.php";
    
    $host = $REMOTE_HOST;
    $db = $DB_SCHEMA;
    $user = $DB_USER;
    $pass = $DB_PASS;

    $mysqli = new mysqli($host, $user, $pass, $db);

/*  $host = "localhost";
    $userlocal = $DB_USER_LOCAL;
    $pass = "";
    $db = "autoknn_db";
    $mysqli = new mysqli($host, $userlocal, $pass, $db); */

    // Checking if the connection to MySQL has failed.
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
?>