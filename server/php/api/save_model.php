<?php
    session_start();

    require_once "../db_connection.php";
    
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['token']) || !isset($data['file']) || !isset($data['folder']) || !isset($data['features'])
        || !isset($data['target']) || !isset($data['k_value']) || !isset($data['distance_value'])
        || !isset($data['stratify']) || !isset($data['dataset_id']) || !isset($data['model_name'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
        exit;
    }
    
    $token = $data['token'];
    $file = $data['file'];
    $folder = $data['folder'];
    $features = implode(",", $data['features']);
    $target = $data['target'];
    $k_value = $data['k_value'];
    $distance_value = $data['distance_value'];
    $p_value = $data['p_value'];
    $stratify = $data['stratify'] ? 1 : 0;
    $dataset_id = $data['dataset_id'];
    $model_name = $data['model_name'];

    $query = "SELECT id, email FROM users WHERE token = ?";
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
    $user_id = $user['id'];
    $email = $user['email'];
    $hash_user = md5($email);
    
    $publicDir = '../../python/public/datasets';
    $privateDir = '../../python/private/' . $hash_user . '/datasets';
    
    $filePath = ($folder === 'public') ? $publicDir . '/' . $file : $privateDir . '/' . $file;
    
    if (!file_exists($filePath)) {
        http_response_code(400);
        echo json_encode(["message" => "File not found"]);
        exit;
    }
    
    // Check if the model exists in dataset_execution table
    $checkExecutionQuery = "SELECT id FROM dataset_execution WHERE id = ?";
    $stmt = $mysqli->prepare($checkExecutionQuery);
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        http_response_code(400);
        echo json_encode(["message" => "Model ID does not exist in dataset_execution"]);
        exit;
    }
    
    // Validate the model name (only allow letters, numbers, underscores, and hyphens)
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $model_name)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid model name. It must start with a letter and can only contain letters, numbers, underscores, and hyphens."]);
        exit;
    }

    // Check where the results file is saved (public or private folder)
    $resultsDir = '';
    if ($folder === 'public') {
        $resultsDir = '../../python/public/models_json/';
    } else {
        $resultsDir = '../../python/private/' . $hash_user . '/' . 'models_json/';
    }

    $resultsFilePath = $resultsDir . $dataset_id . '.json';
    
    $savedModelFilePath = '../../python/private/' . $hash_user . '/' . 'models_saved/' . $model_name . '.pkl';

    // Check if the model name already exists
    if (file_exists($savedModelFilePath)) {
        http_response_code(409); // Conflict status code
        echo json_encode(["message" => "A model with this name already exists. Please choose a different name."]);
        exit;
    }

    // Execute the Python command with necessary parameters
    $escapedFilePath = escapeshellarg($filePath);
    $escapedFeatures = escapeshellarg($features);
    $escapedTarget = escapeshellarg($target);
    $escapedKValue = escapeshellarg($k_value);
    $escapedDistanceValue = escapeshellarg($distance_value);
    $escapedPValue = escapeshellarg($p_value);
    $escapedSavedModelFilePath = escapeshellarg($savedModelFilePath);
    
    $pythonCmd = "python3 ../../python/save_model.py $escapedFilePath $escapedFeatures $escapedTarget $escapedKValue $escapedDistanceValue $escapedPValue $escapedSavedModelFilePath 2>&1";
    
    // Execute the command
    $output = [];
    $return = 0;
    exec($pythonCmd, $output, $return);
    
    // Now save the model details in models table
    $name_of_class = $target; 
    $modelClassesQuery = "INSERT INTO models (id_of_executed_dataset, name_of_model, features, name_of_class, k, metric_distance, p, stratified_sampling) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($modelClassesQuery);
    $stmt->bind_param("isssisii", $dataset_id, $model_name, $features, $name_of_class, $k_value, $distance_value, $p_value, $stratify);
    $stmt->execute();

    echo json_encode([
        "dataset_id" => $dataset_id,
        "file" => $file,
        "command" => $pythonCmd,
        "output" => implode("\n", $output),
        "return_code" => $return,
        "saved_model_file" => $savedModelFilePath 
    ]);
?>