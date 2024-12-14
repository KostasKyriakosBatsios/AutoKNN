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

    // Extract the base name of the model file without the extension
    $baseFileName = pathinfo($model, PATHINFO_FILENAME);

    // Extract the class of the chosen model
    $sql = "SELECT name_of_class, features FROM models WHERE name_of_model = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $baseFileName);  // Use base file name
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "danger", "message" => "Class not found for the specified model.", "model_name" => $baseFileName]);
        exit;
    }

    $results = $result->fetch_assoc();
    $class = $results['name_of_class'];

    // Use features from the databasev and convert features to an array
    $features = $results['features']; 
    $featuresArray = explode(",", $features);
    $stmt->close();

    // Return both features and class to the frontend
    echo json_encode([
        "status" => "success",
        "features" => $featuresArray ?? [],  // Ensure features are present
        "class" => $class ?? '' // Use the class retrieved from the database
    ]);
?>