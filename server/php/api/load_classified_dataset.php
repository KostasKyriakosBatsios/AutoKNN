<?php
    session_start();
    
    require_once "../db_connection.php";

    header('Content-Type: application/json');
    
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

    // Define the classified dataset directory
    $classifiedFilePath = '../../python/private/' . $hash_user . '/' . 'classified_datasets/' . $file;

    // Check if file exists
    if (!file_exists($classifiedFilePath)) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Classified dataset not found"]);
        exit;
    }

    // Read the dataset file
    $fileContents = file($classifiedFilePath);
    if ($fileContents === false) {
        http_response_code(500);
        echo json_encode(["status" => "danger", "message" => "Error reading file"]);
        exit;
    }

    // Process the file contents (assumed to be CSV)
    $data = [];
    $header = str_getcsv(array_shift($fileContents));
    foreach ($fileContents as $line) {
        $data[] = str_getcsv($line);
    }

    // Close database connection
    $mysqli->close();

    // Return dataset as JSON
    echo json_encode([
        "status" => "success",
        "header" => $header,
        "data" => $data
    ]);
?>