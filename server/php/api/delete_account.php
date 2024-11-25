<?php
    session_start();
    
    require_once "../db_connection.php";
    
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== "DELETE") {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only DELETE requests are allowed"]);
        exit;
    }

    // Parse the DELETE request body
    $deleteParams = json_decode(file_get_contents("php://input"), true);

    if (!isset($deleteParams['email']) || !isset($deleteParams['password']) || !isset($deleteParams['confirmPassword']) || !isset($deleteParams['token'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $email = $deleteParams['email'];
    $password = $deleteParams['password'];
    $confirmPassword = $deleteParams['confirmPassword'];
    $token = $deleteParams['token'];
    
    // Validate input data
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        echo json_encode(["status" => "danger", "message" => "All fields are required"]);
        exit();
    }
    
    if ($password !== $confirmPassword) {
        echo json_encode(["status" => "danger", "message" => "Passwords do not match"]);
        exit();
    }
    
    // Function to delete a directory and its contents
    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
    
    // Prepare and execute a statement to retrieve user details
    if ($stmt = $mysqli->prepare("SELECT id, pass FROM users WHERE email = ?")) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows === 0) {
            echo json_encode(["status" => "danger", "message" => "Invalid email"]);
            $stmt->close();
            exit();
        }
    
        // Bind the result to variables
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();
    
        // Verify the provided password
        if (!password_verify($password, $hashedPassword)) {
            echo json_encode(["status" => "danger", "message" => "Incorrect password"]);
            $stmt->close();
            exit();
        }
    
        // Hash the email to find the user's directory
        $hash_user = md5($email);
        $userDir = "../../python/private/$hash_user";
    
        // Delete the user's directory
        if (!deleteDirectory($userDir)) {
            echo json_encode(["status" => "danger", "message" => "Failed to delete user directories"]);
            $stmt->close();
            exit();
        }
    
        // Prepare and execute a statement to delete the user
        if ($stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?")) {
            $stmt->bind_param('i', $userId);
            if ($stmt->execute()) {
                // Logout user and destroy the session
                session_destroy();
                echo json_encode(["status" => "success", "message" => "Account deleted successfully"]);
            } else {
                echo json_encode(["status" => "danger", "message" => "Failed to delete account"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "danger", "message" => "Failed to prepare delete statement"]);
        }
    } else {
        echo json_encode(["status" => "danger", "message" => "Failed to prepare select statement"]);
    }
    
    // Close the database connection
    $mysqli->close();
?>