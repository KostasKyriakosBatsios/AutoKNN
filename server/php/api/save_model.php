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
    
    if (!isset($data['token']) || !isset($data['file']) || !isset($data['dataset_id']) || !isset($data['folder']) || !isset($data['features'])
        || !isset($data['target']) || !isset($data['k_value']) || !isset($data['distance_value'])
        || !isset($data['best_k_value']) || !isset($data['best_distance_value']) || !isset($data['stratify']) || !isset($data['model_name'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
        exit;
    }
    
    $token = $data['token'];
    $file = $data['file'];
    $dataset_id = $data['dataset_id'];
    $folder = $data['folder'];
    $features = implode(",", $data['features']);
    $target = $data['target'];
    $k_value = implode(",", $data['k_value']);
    $distance_value = implode(",", $data['distance_value']);
    $best_k_value = $data['best_k_value'];
    $best_distance_value = $data['best_distance_value'];
    $p_value = $data['p_value'];
    $best_p_value = $data['best_p_value'];
    $stratify = $data['stratify'] ? 'true' : 'false';
    $model_name = $data['model_name'];

    // Check if p is null
    if ($p_value !== null) {
        $p_value = implode(",", $data['p_value']);
    }

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
    
    // Determine file path based on folder type
    $filePath = '';
    if ($folder === 'public') {
        $filePath = '../../python/public/datasets/' . $file;
    } else {
        $filePath = '../../python/private/' . $hash_user . '/' . 'datasets/' . $file;
    }

    if (!file_exists($filePath)) {
        http_response_code(400);
        echo json_encode(["message" => "File not found"]);
        exit;
    }

    // Check if the model exists in dataset_execution table
    $sql = "SELECT id FROM dataset_execution WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        http_response_code(400);
        echo json_encode(["message" => "Model ID does not exist in dataset_execution"]);
        exit;
    }

    $stmt->close();

    // Check if the name already exists based on the existence in models table and of the id of the user
    $sql = "SELECT *
            FROM models m
            INNER JOIN dataset_execution de ON m.id_of_executed_dataset = de.id
            WHERE name_of_model = ? AND id_of_user = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $model_name, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["message" => "A model with this name already exists. Please choose a different name."]);
        exit;
    }

    // Validate the model name (only allow letters, numbers, underscores, and hyphens)
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $model_name)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Invalid model name. It must start with a letter and contain only letters, numbers, underscores (_), or hyphens (-), with no spaces."
        ]);
        exit;
    }
    
    $savedModelFilePath = '../../python/private/' . $hash_user . '/' . 'models_saved/' . $model_name . '.pkl';

    // Check if the model name already exists
    if (file_exists($savedModelFilePath)) {
        http_response_code(409);
        echo json_encode(["message" => "A model with this name already exists. Please choose a different name."]);
        exit;
    }

    // Execute the Python command with necessary parameters
    $escapedFilePath = escapeshellarg($filePath);
    $escapedFeatures = escapeshellarg($features);
    $escapedTarget = escapeshellarg($target);
    $escapedKValue = escapeshellarg($best_k_value);
    $escapedDistanceValue = escapeshellarg($best_distance_value);
    $escapedPValue = escapeshellarg($best_p_value);
    $escapedSavedModelFilePath = escapeshellarg($savedModelFilePath);
    
    $pythonCmd = "python3 ../../python/save_model.py $escapedFilePath $escapedFeatures $escapedTarget $escapedKValue $escapedDistanceValue $escapedPValue $escapedSavedModelFilePath 2>&1";
    
    // Execute the command
    $output = [];
    $return = 0;
    exec($pythonCmd, $output, $return);
    
    // Now save the model details in models table
    $name_of_class = $target; 
    $sql = "INSERT INTO models (id_of_executed_dataset, name_of_model, features, name_of_class, k, metric_distance, p, stratified_sampling) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("isssisii", $dataset_id, $model_name, $features, $name_of_class, $best_k_value, $best_distance_value, $best_p_value, $stratify);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "message" => "Model saved successfully.",
    ]);
?>