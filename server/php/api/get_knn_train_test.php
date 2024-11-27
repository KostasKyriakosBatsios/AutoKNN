<?php
    require_once "../db_connection.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Only GET requests are allowed"]);
        exit;
    }

    // Validate required parameters
    if (!isset($_GET['token']) || !isset($_GET['file']) || !isset($_GET['features'])
    || !isset($_GET['target']) || !isset($_GET['k_value']) || !isset($_GET['distance_value'])
    || !isset($_GET['stratify'])) {
       http_response_code(400);
       echo json_encode(["message" => "Missing required parameters"]);
       exit;
    }

    // Retrieve parameters
    $file = $_GET['file'];
    $features = implode(",", $_GET['features']);
    $selectedClass = $_GET['selectedClass'];
    $k_value = implode(",", $_GET['k_value']);
    $metricDistance_value = implode(",", $_GET['distance_value']);
    $p = $_GET['p'];
    $stratifiedSampling = $_GET['stratify'] ? 'true' : 'false';

    $hash_user = md5($email);
    $directories = [
        '../../python/public/models_json/',
        '../../python/private/' . $hash_user . '/' . 'models_json/'
    ];

    // Helper to match parameters
    function parametersMatch($storedData, $requestData) {
        return $storedData['dataset'] === $requestData['file'] &&
            $storedData['features'] === $requestData['features'] &&
            $storedData['class'] === $requestData['selectedClass'] &&
            $storedData['k_values'] === $requestData['k_value'] &&
            $storedData['distance_values'] === $requestData['metricDistance_value'] &&
            $storedData['p_value'] === $requestData['p'] &&
            $storedData['stratified_sampling'] === $requestData['stratifiedSampling'];
    }

    $requestData = compact('file', 'features', 'selectedClass', 'k_value', 'metricDistance_value', 'p', 'stratifiedSampling');

    // Search JSON files
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            foreach (glob($dir . "*.json") as $jsonFilePath) {
                $storedData = json_decode(file_get_contents($jsonFilePath), true);

                if ($storedData && parametersMatch($storedData, $requestData)) {
                    // Return the file's contents directly
                    $results = file_get_contents($jsonFilePath);
                    echo $results;
                    exit;
                }
            }
        }
    }

    // No match found
    http_response_code(404);
    echo json_encode([
        "message" => "No matching file found."
    ]);
?>