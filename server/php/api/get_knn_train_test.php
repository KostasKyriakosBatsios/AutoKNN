<?php
    session_start();

    require_once "../db_connection.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Only GET requests are allowed"]);
        exit;
    }

    // Validate required parameters
    if (!isset($_GET['token']) || !isset($_GET['file'])  || !isset($_GET['features'])
       || !isset($_GET['target']) || !isset($_GET['k_value']) || !isset($_GET['distance_value'])
       || !isset($_GET['stratify'])) {
       http_response_code(400);
       echo json_encode(["message" => "Missing required parameters"]);
       exit;
    }

    // Retrieve parameters
    $token = $_GET['token'];
    $file = $_GET['file'];
    $features = is_string($_GET['features']) ? json_decode($_GET['features'], true) : $_GET['features'];
    $selectedClass = $_GET['target'];
    $k_value = is_string($_GET['k_value']) ? json_decode($_GET['k_value'], true) : $_GET['k_value'];
    $metricDistance_value = is_string($_GET['distance_value']) ? json_decode($_GET['distance_value'], true) : $_GET['distance_value'];
    $p = $_GET['p_value'];
    $stratifiedSampling = filter_var($_GET['stratify'], FILTER_VALIDATE_BOOLEAN);

    // Check if p is null
    if ($p === null || $p === "null") {
        $p = null;
    } else {
        $p = intval($p);
    }

    // Validate token
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

    $email = $result->fetch_assoc()['email'];
    $stmt->close();

    $hash_user = md5($email);
    $directories = ['../../python/public/models_json/', '../../python/private/' . $hash_user . '/' . 'models_json/'];

    // Helper function to check if the parameteres match
    function parametersMatch($storedData, $requestData) {
        return isset(
            $storedData['dataset'], $storedData['features'], $storedData['class'],
            $storedData['k_values'], $storedData['distance_values'],
            $storedData['p_value'], $storedData['stratified_sampling']
        ) &&
            basename($storedData['dataset']) === $requestData['dataset'] &&
            $storedData['features'] === $requestData['features'] &&
            $storedData['class'] === $requestData['class'] &&
            array_map('intval', $storedData['k_values']) === $requestData['k_values'] &&
            $storedData['distance_values'] === $requestData['distance_values'] &&
            $storedData['p_value'] === $requestData['p_value'] &&
            $storedData['stratified_sampling'] === $requestData['stratified_sampling'];
    }      

    // Prepare request data
    $requestData = [
        'dataset' => basename($file),
        'features' => $features,
        'class' => $selectedClass,
        'k_values' => array_map('intval', $k_value),
        'distance_values' => $metricDistance_value,
        'p_value' => $p,
        'stratified_sampling' => $stratifiedSampling,
    ];

    // Search for JSON files
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            echo json_encode(["message" => "Directory not found: $directory"]);
            continue;
        }
    
        $files = glob($directory . '*.json');
        if (empty($files)) {
            echo json_encode(["message" => "No JSON files found."]);
            continue;
        }
    
        foreach ($files as $filePath) {
            $storedData = json_decode(file_get_contents($filePath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(["message" => "Error decoding JSON file: $filePath", "error" => json_last_error_msg()]);
                continue;
            }
        
            if (parametersMatch($storedData, $requestData)) {
                // Return the stored data as a results variable
                $results = file_get_contents($filePath);
                echo $results;
                exit;
            }
        }
    }

    // No match found
    http_response_code(404);
    echo json_encode(["message" => "No matching file found."]);
?>