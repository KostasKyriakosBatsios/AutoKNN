<?php
    require_once "db_connection.php";
    require_once "functions.php";
    require_once "phpmailer.php";

    header('Content-Type: application/json');

    // Ensure the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }

    // Retrieve the POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if required parameters are present
    if (!isset($data['email'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
        exit;
    }

    $email = $data['email'];

    // Validate email
    if (empty($email)) {
        echo json_encode(["status" => "danger", "message" => "Email and Password are required."]);
        exit();
    }

    // Check if the email exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(["status" => "danger", "message" => "Email does not exist."]);
        exit();
    }

    // Check if the email is typed properly
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "danger", "message" => "Invalid email address."]);
        exit();
    }

    // Extract id and fname
    $sql = "SELECT id, fname FROM users WHERE email=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $id = $user['id'];
    $fname = $user['fname'];
    $stmt->close();

    // Generate verification key
    $verification_key = md5(random_bytes(16));
    $verifQuery = "INSERT INTO verify_account (id, verification_key) VALUES (?, ?)";
    $stmt = $mysqli->prepare($verifQuery);
    $stmt->bind_param("is", $id, $verification_key);
    $stmt->execute();
    $stmt->close();

    // Preparing the mail to send the verification link
    $subject = "Reset your password - AutoKNN Application";
    $app_domain = getDomain();
    $body = "Dear user,<br> This is a request to reset your password. To complete your request, click this reset link to reset your password: <a href='" . $app_domain . "/web_pages/reset_password.html?verification_key=" . $verification_key . "'>Reset password</a>, or just copy and paste this link into your browser: $app_domain/web_pages/reset_password.html?verification_key=$verification_key";
    $alt_body = "Dear user,<br> This is a request to reset your password. To complete your request, copy and paste this link into your browser: $app_domain/web_pages/reset_password.html?verification_key=$verification_key";
    
    try {
        send_email($email, $fname, $subject, $body, $alt_body);
    } catch (Exception $e) {
        echo json_encode(['status' => 'danger', 'message' => 'Failed to send reset password email: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Reseting password was successful.']);
?>