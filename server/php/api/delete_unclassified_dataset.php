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

    if (!isset($deleteParams['token']) || !isset($deleteParams['file'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $deleteParams['token'];
    $file = $deleteParams['file'];

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
    $allowPublic = $user['allowPublic'];

    // Generate hash for user email
    $hash_user = md5($email);

    // Define file path
    $filePath = '../../python/private/' . $hash_user . '/' . 'unclassified_datasets/' . $file;

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo json_encode(["status" => "success", "message" => "'" . ucfirst($file) . "' dataset deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "danger", "message" => "Failed to delete the " . $file . " dataset"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "Dataset not found"]);
    }
?>