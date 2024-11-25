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

    if (!isset($deleteParams['token']) || !isset($deleteParams['file']) || !isset($deleteParams['folder'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $deleteParams['token'];
    $file = $deleteParams['file'];
    $folder = $deleteParams['folder'];

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

    // Define public and private directories
    $publicDir = '../../python/public/datasets';
    $privateDir = '../../python/private/' . $hash_user . '/datasets';

    // Initialize file path
    $filePath = '';
    if ($folder === 'public') {
        $filePath = $publicDir . '/' . $file;
        if ($allowPublic == 0) {
            http_response_code(403);
            echo json_encode(["status" => "danger", "message" => "You do not have permission to delete this public dataset"]);
            exit;
        }
    } elseif ($folder === 'private') {
        $filePath = $privateDir . '/' . $file;
    } else {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Invalid dataset type"]);
        exit;
    }

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo json_encode(["status" => "success", "message" => "Successfully deleted the '" . $file . "' dataset from '" . $folder . "' folder"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "danger", "message" => "Failed to delete the '" . $file . "' dataset from '" . $folder . "' folder"]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "Dataset not found"]);
    }
?>