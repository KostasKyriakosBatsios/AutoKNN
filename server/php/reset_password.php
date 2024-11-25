<?php
    require_once "db_connection.php";
    require_once "functions.php";

    header('Content-Type: application/json');
   
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }
   
    // Parse the JSON request
    $data = json_decode(file_get_contents("php://input"), true);
   
    // Validate required parameters
    if (!isset($data['verification_key']) || !isset($data['password']) || !isset($data['confirmPassword'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
        exit;
    }
   
    $verification_key = $data['verification_key'];
    $password = $data['password'];
    $confirmPassword = $data['confirmPassword'];

    // Check if the verification key is expired
    $email = checkVerificationKey($verification_key);
    if (!$email) {
        http_response_code(400);
        echo json_encode(['status' => 'danger', 'message' => 'Invalid verification key.']);
        exit;
    }

    // Check for uppercase, lowercase, number, special character and the legth of the password
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password) || strlen($password) < 8) {
        echo json_encode(['status' => 'warning', 'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.']);
        exit;
    }

    // Check if the password and confirm password are empty
    if (empty($password) || empty($confirmPassword)) {
        echo json_encode(['status' => 'warning', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if the passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(['status' => 'warning', 'message' => 'Passwords do not match.']);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update the user's password in the database
    $sql = "UPDATE users u JOIN verify_account va ON u.id = va.id SET u.pass = ? WHERE va.verification_key = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $hashedPassword, $verification_key);
    $stmt->execute();

    // Delete verification key
    $deleteQuery = "DELETE FROM verify_account WHERE verification_key = ?";
    $stmt = $mysqli->prepare($deleteQuery);
    $stmt->bind_param('s', $verification_key);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Password reset successfully.']);
?>