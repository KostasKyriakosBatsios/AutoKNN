<?php
    session_start();

    require_once '../db_connection.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== "DELETE") {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only DELETE requests are allowed"]);
        exit;
    }

    // Parse the DELETE request body
    $deleteParams = json_decode(file_get_contents("php://input"), true);

    if (!isset($deleteParams['token']) || !isset($deleteParams['model'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $deleteParams['token'];
    $model = $deleteParams['model'];

    // Fetch user's email and allowPublic based on the session token
    $sql = "SELECT email, allowPublic FROM users WHERE token = ?";
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

    // Define model's file path
    $modelFilePath = '../../python/private/' . $hash_user . '/' . 'models_saved/' . $model;

    if (file_exists($modelFilePath)) {
        if (unlink($modelFilePath)) {
            echo json_encode(["status" => "success", "message" => "Model deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "danger", "message" => "Failed to delete the model"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "Dataset not found"]);
    }
?>