<?php
    session_start();
    
    require_once "../db_connection.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['token'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    // Fetch user email based on the session token
    $token = $_GET['token'];

    
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
    $stmt->close();

    // Generate hash for user email
    $hash_user = md5($email);

    // Define models' directory
    $modelsDir = '../../python/private/' . $hash_user . '/models_saved';

    // Function to scan directory and return dataset names
    function scanDatasets($directory) {
        $models = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    error_log("Found File: " . $file); // Log found files
                    $models[] = [
                        'name' => $file,
                        'path' => $directory . '/' . $file
                    ];
                }
            }
        }
        return $models;
    }

    // Fetch datasets from public and private directories
    $models = scanDatasets($modelsDir);

    echo json_encode([
        "status" => "success", 
        "models" => $models
    ]);
?>