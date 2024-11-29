<?php
    require_once "../db_connection.php";

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Only GET requests are allowed"]);
        exit;
    }

    // Validate required parameters
    if (!isset($_GET['token']) || !isset($_GET['file']) || !isset($_GET['features']) || !isset($_GET['target'])
    || !isset($_GET['k_value']) || !isset($_GET['distance_value']) || !isset($_GET['p_value'])
    || !isset($_GET['stratify'])) {
       http_response_code(400);
       echo json_encode(["message" => "Missing required parameters"]);
       exit;
    }

    // Retrieve parameters
    $token = $_GET['token'];
    $file = $_GET['file'];
    $features = implode(",", $_GET['features']);
    $selectedClass = $_GET['target'];
    $k_value = implode(",", $_GET['k_value']);
    $metricDistance_value = implode(",", $_GET['distance_value']);
    $p = $_GET['p_value'];
    $stratifiedSampling = $_GET['stratify'] ? 'true' : 'false';

    // Get the email based on the token, and create the hash_user
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

    $directories = [
        '../../python/public/models_json/',
        '../../python/private/' . $hash_user . '/' . 'models_json/'
    ];

    // Helper function to match parameters
    function parametersMatch($storedData, $requestData) {
        return isset($storedData['dataset'], $storedData['features'], $storedData['class'], $storedData['k_values'], $storedData['distance_values'], $storedData['p_value'], $storedData['stratified_sampling']) &&
            $storedData['dataset'] === $requestData['file'] &&
            $storedData['features'] === $requestData['features'] &&
            $storedData['class'] === $requestData['selectedClass'] &&
            $storedData['k_values'] === $requestData['k_value'] &&
            $storedData['distance_values'] === $requestData['metricDistance_value'] &&
            $storedData['p_value'] === $requestData['p'] &&
            $storedData['stratified_sampling'] === $requestData['stratifiedSampling'];
    }

    // Prepare request data for matching
    $requestData = compact('file', 'features', 'selectedClass', 'k_value', 'metricDistance_value', 'p', 'stratifiedSampling');

    // Search for JSON files in each directory
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            error_log("Directory not found: $directory");
            continue;
        }

        $files = glob($directory . '*.json');
        if (empty($files)) {
            error_log("No JSON files found in directory: $directory");
            continue;
        }

        foreach ($files as $filePath) {
            $storedData = json_decode(file_get_contents($filePath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error decoding JSON file: $filePath");
                continue;
            }

            if (parametersMatch($storedData, $requestData)) {
                echo json_encode($storedData);
                exit;
            }
        }
    }

    // No match found
    http_response_code(404);
    echo json_encode([
        "message" => "No matching file found."
    ]);
?>