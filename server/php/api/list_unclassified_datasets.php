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

    // Generate hash for user email
    $hash_user = md5($email);

    // Define models' directory
    $unclassifiedDatasetsDir = '../../python/private/' . $hash_user . '/unclassified_datasets';

    // Function to scan directory and return dataset names
    function scanDatasets($directory) {
        $unclassifiedDatasets = [];
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    error_log("Found File: " . $file); // Log found files
                    $unclassifiedDatasets[] = [
                        'name' => $file,
                        'path' => $directory . '/' . $file
                    ];
                }
            }
        }
        return $unclassifiedDatasets;
    }

    // Fetch datasets from public and private directories
    $unclassifiedDatasets = scanDatasets($unclassifiedDatasetsDir);

    // Close database connection
    $mysqli->close();

    echo json_encode([
        "status" => "success", 
        "unclassifiedDatasets" => $unclassifiedDatasets
    ]);
?>