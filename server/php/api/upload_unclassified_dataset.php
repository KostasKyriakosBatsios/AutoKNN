<?php
    require_once "../db_connection.php";

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only POST requests are allowed"]);
        exit;
    }

    if (!isset($_FILES['file']) || !isset($_POST['token'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $_POST['token'];
    $file = $_FILES['file'];

    // Query to find the user based on the token
    $query = "SELECT id, email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($userId, $email);
    $stmt->fetch();
    $stmt->close();

    if (!$userId) {
        http_response_code(401);
        echo json_encode(["status" => "danger", "message" => "Invalid token"]);
        exit;
    }

    // Generate the user's hash using the md5 function on their email
    $userHash = md5($email);

    // Determine the upload path based on the user's selection
    $uploadPath = '../../python/private/' . $userHash . '/unclassified_datasets';

    // Ensure the upload path exists
    if (!is_dir($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            http_response_code(500);
            echo json_encode(["status" => "danger", "message" => "Failed to create directories."]);
            exit;
        }
    }

    // Validate the file type (must be a .csv)
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Invalid file type. Only CSV files are allowed."]);
        exit;
    }

    // Validate the file size (must be less than 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "File size exceeds 10MB limit."]);
        exit;
    }

    // Generate a unique filename to avoid overwriting
    $filename = basename($file['name']);
    $targetFile = $uploadPath . DIRECTORY_SEPARATOR . $filename;

    // Check if the file already exists in the target directory
    if (file_exists($targetFile)) {
        http_response_code(409);
        echo json_encode(["status" => "danger", "message" => "This file already exists in the folder"]);
        exit;
    }

    // Move the uploaded file to the target directory
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "File uploaded successfully.", "file: '" => $filename . "'"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "danger", "message" => "Failed to upload the file."]);
    }
?>