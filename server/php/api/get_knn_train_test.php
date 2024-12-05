<?php
    session_start();

    require_once "../db_connection.php";
    
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['dataset_id']) || !isset($_GET['token']) || !isset($_GET['folder'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameter"]);
        exit;
    }
    
    $dataset_id = intval($_GET['dataset_id']);
    $token = $_GET['token'];
    $folder = $_GET['folder'];

    $query = "SELECT email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $email = $user['email'];

    $hash_user = md5($email);

    // Path to the results file
    $resultsDir = '';
    if ($folder === 'public') {
        $resultsDir = '../../python/public/models_json/';
    } else {
        $resultsDir = '../../python/private/' . $hash_user . '/' . 'models_json/';
    }

    $resultsFilePath = $resultsDir . $dataset_id . '.json';

    if (!file_exists($resultsFilePath)) {
        http_response_code(404);
        echo json_encode(["message" => "Results file not found"]);
        exit;
    }

    $results = file_get_contents($resultsFilePath);
    echo $results;
?>