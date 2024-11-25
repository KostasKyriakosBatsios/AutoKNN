<?php
    // Start the session
    session_start();

    // Include the database connection file
    require_once "../db_connection.php";

    // Retrieve POST data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only POST requests are allowed"]);
        exit;
    }
    
    if (!isset($_POST['token']) || !isset($_POST['first_name']) || !isset($_POST['last_name']) || !isset($_POST['current_password']) || !isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $_POST['token'];
    $firstName = $_POST['first_name'] ?? null;
    $lastName = $_POST['last_name'] ?? null;
    $currentPassword = $_POST['current_password'] ?? null;
    $newPassword = $_POST['new_password'] ?? null;
    $confirmPassword = $_POST['confirm_password'] ?? null;

    // Fetch user details based on token
    $sql = "SELECT id, fname, lname, pass FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "danger", "message" => "Failed to prepare statement"]);
        exit;
    }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode(["status" => "danger", "message" => "Invalid token"]);
        exit;
    }

    $userId = $user['id'];
    $currentFirstName = $user['fname'];
    $currentLastName = $user['lname'];
    $hashedCurrentPassword = $user['pass'];

    // Validate current password if provided
    if ($currentPassword && !password_verify($currentPassword, $hashedCurrentPassword)) {
        echo json_encode(["status" => "danger", "message" => "Current password is incorrect"]);
        exit;
    }

    // Check if the new password is the same as the current password
    if ($newPassword && password_verify($newPassword, $hashedCurrentPassword)) {
        echo json_encode(["status" => "danger", "message" => "New password cannot be the same as the current password"]);
        exit;
    }

    // Prepare update query based on provided fields
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    // Check if the new first and last name are the same as the current first and last name
    if ($firstName && $lastName) {
        if ($firstName !== $currentFirstName || $lastName !== $currentLastName) {
            $updateFields[] = "fname = ?, lname = ?";
            $params[] = $firstName;
            $params[] = $lastName;
            $paramTypes .= "ss";
        } else {
            echo json_encode(["status" => "danger", "message" => "First name and last name cannot be the same as before"]);
            exit;
        }
    }

    // Check if the new and confirm passwords are matching
    if ($newPassword) {
        if ($newPassword !== $confirmPassword) {
            echo json_encode(["status" => "danger", "message" => "New password and confirm password do not match"]);
            exit;
        }
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateFields[] = "pass = ?";
        $params[] = $newPasswordHash;
        $paramTypes .= "s";
    }

    if (empty($updateFields)) {
        echo json_encode(["status" => "danger", "message" => "No changes were made"]);
        exit;
    }

    $updateSql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $params[] = $userId;
    $paramTypes .= "i";

    // Prepare and execute the update statement
    $stmt = $mysqli->prepare($updateSql);
    if (!$stmt) {
        echo json_encode(["status" => "danger", "message" => "Failed to prepare update statement"]);
        exit;
    }
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "User details updated successfully"]);
    } else {
        echo json_encode(["status" => "danger", "message" => "No changes were made"]);
    }

    $stmt->close();
    $mysqli->close();
?>