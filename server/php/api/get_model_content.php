<?php
    session_start();

    require_once "../db_connection.php";

    header('Content-Type: application/json');

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

    // Define models' file path
    $modelFilePath = '../../python/private/' . $hash_user . '/' . 'models_saved/' . $model;

    // Check if the file exists
    if (!file_exists($modelFilePath)) {
        echo json_encode(['error' => 'Model file not found.']);
        exit;
    }

    // Execute the Python script to get the features
    $escapedModelFilePath = escapeshellarg($modelFilePath);
    $pythonCmd = "python3 ../../python/get_model_content.py $escapedModelFilePath 2>&1";

    // Execute the Python command and capture the output
    $output = [];
    $return = 0;
    exec($pythonCmd, $output, $return);

    // Combine the output into a single JSON response
    $outputJson = implode("\n", $output); // Combine output lines into a single string

    // Decode JSON output
    $response = json_decode($outputJson, true); // Convert JSON to associative array

    // Extract the base name of the model file without the extension
    $baseFileName = pathinfo($model, PATHINFO_FILENAME);

    // Extract the class from the database based on id of the executed dataset and the base model file name
    $sql = "SELECT id FROM dataset_execution WHERE id_of_user = (
        SELECT id FROM users WHERE token = ?
    )";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(500);
        echo json_encode(["status" => "danger", "message" => "Failed to retrieve dataset execution ID"]);    
        exit;    
    }

    $datasetExecution = $result->fetch_assoc();
    $datasetId = $datasetExecution['id'];
    $stmt->close();

    $sql = "SELECT name_of_class FROM models WHERE name_of_model = ? AND id_of_executed_dataset = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $baseFileName, $datasetId);  // Use base file name
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "danger", "message" => "Class not found for the specified model.", "model_name" => $baseFileName]);
        exit;
    }

    $modelClass = $result->fetch_assoc();
    $class = $modelClass['name_of_class'];

    // Return both features and class to the frontend
    echo json_encode([
        "status" => "success",
        "features" => $response['features'] ?? [],  // Ensure features are present
        "class" => $class ?? '' // Use the class retrieved from the database
    ]);
?>