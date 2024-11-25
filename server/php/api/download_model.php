<?php
    session_start();
    
    require_once '../db_connection.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['token']) || !isset($_GET['model'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $_GET['token'];
    $model = $_GET['model'];
    
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

    // Define model's file path
    $modelFilePath = '../../python/private/' . $hash_user . '/' . 'models/' . $model;

    // Check if the file exists
    if (!file_exists($modelFilePath)) {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "Model not found"]);
        exit;
    }

    // Serve the file for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($modelFilePath));
    header('Content-Length: ' . filesize($modelFilePath));
    
    readfile($modelFilePath);

    echo json_encode(["status" => "success", "message" => "Download was successful."]);

    exit();
?>