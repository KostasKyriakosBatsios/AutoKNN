<?php
    session_start();
    
    require_once '../db_connection.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['token']) || !isset($_GET['file'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
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
        echo json_encode(["message" => "Invalid token"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Generate hash for user email
    $hash_user = md5($email);

    // Define file path
    $filePath = '../../python/private/' . $hash_user . '/' . 'classified_datasets/' . $file;

    // Check if the file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(["message" => "Model not found"]);
        exit;
    }

    // Serve the file for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($filePath));
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);

    echo json_encode(["status" => "success", "message" => "Download was successful."]);

    exit();
?>