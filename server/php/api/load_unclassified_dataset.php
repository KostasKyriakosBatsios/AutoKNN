<?php
    session_start();
    
    require_once "../db_connection.php";

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['token']) || !isset($_GET['file'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $_GET['token'];
    $file = $_GET['file'];

    // Fetch user email based on the session token
    $sql = "SELECT email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["status" => "danger", "message" => "Invalid token"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Generate hash for user email
    $hash_user = md5($email);

    // Define unclassified dataset directory
    $unclassifiedFilePath = '../../python/private/' . $hash_user . '/' . 'unclassified_datasets/' . $file;

    // Read the dataset file
    $fileContents = file($unclassifiedFilePath);
    if ($fileContents === false) {
        http_response_code(500);
        echo json_encode(["status" => "danger", "message" => "Error reading file"]);
        exit;
    }

    // Process the file contents (assumed to be CSV for this example)
    $data = [];
    $header = str_getcsv(array_shift($fileContents));
    foreach ($fileContents as $line) {
        $data[] = str_getcsv($line);
    }

    // Close database connection
    $mysqli->close();

    // Return dataset as JSON
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "header" => $header,
        "data"=> $data
    ]);
?>