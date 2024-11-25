<?php
    require_once "../db_connection.php";
    require_once "../phpmailer.php";
    require_once "../functions.php";

    header('Content-Type: application/json');
   
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only POST requests are allowed"]);
        exit;
    }
   
    // Parse the JSON request
    $data = json_decode(file_get_contents("php://input"), true);
   
    // Validate required parameters
    if (!isset($data['token']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }
   
    $token = $data['token'];
    $email = $data['email'];

    // Validate email
    if (empty($email)) {
        echo json_encode(["status" => "danger", "message" => "Email is required."]);
        exit();
    }

    // Check if token is valid, if so, get the "old" email
    $sql = "SELECT email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["status" => "danger", "message" => "Invalid token"]);
        exit();
    }

    $user = $result->fetch_assoc();
    $previous_email = $user['email'];

    // Check if the email is typed properly
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "danger", "message" => "Invalid email address."]);
        exit();
    }

    // Check if it exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "danger", "message" => "Email already exists."]);
        exit();
    }

    // Check if the domain has valid MX records
    list(, $email_domain) = explode("@", $email);
    if (!checkdnsrr($email_domain, "MX")) {
        echo json_encode(["status" => "warning", "message" => "Invalid email domain."]);
        exit;
    }
    
    // Update the email and verification status based on token
    $sql = "UPDATE users SET email = ?, email_verification = 0 WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();

    // Extract id and fname
    $sql = "SELECT id, fname FROM users WHERE email=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $id = $user['id'];
    $fname = $user['fname'];

    // Generate verification key
    $verification_key = md5(random_bytes(16));
    $verifQuery = "INSERT INTO verify_account (id, verification_key) VALUES (?, ?)";
    $stmt = $mysqli->prepare($verifQuery);
    $stmt->bind_param("is", $id, $verification_key);
    $stmt->execute();

    $previous_hash_user = md5($previous_email);
    $new_hash_user = md5($email);

    // Attempt to change the hash inside the private folder
    try {
        $user_directory = rename("../../python/private/" . $previous_hash_user, "../../python/private/" . $new_hash_user);
    } catch (Exception $e) {
        echo json_encode(["status" => "danger", "message" => "Failed to change email: " . $e->getMessage()]);
        exit;
    }

    // Check if the change was successful
    if (!$user_directory) {
        echo json_encode(["status" => "danger", "message" => "Failed to change email"]);
        exit;
    }

    // Preparing the mail to send the verification link
    $subject = "New email verification - AutoKNN Application";
    $app_domain = getDomain();
    $body = "Dear user,<br> This is a request to change your email. To complete your change, click this verification link to verify your new email address: <a href='" . $app_domain . "/web_pages/verify_account.html?verification_key=" . $verification_key . "'>Verify new email</a>, or just copy and paste this link into your browser: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";
    $alt_body = "Dear user,<br> This is a request to change your email. To complete your change, copy and paste this link into your browser: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";
    
    try {
        send_email($email, $fname, $subject, $body, $alt_body);
    } catch (Exception $e) {
        echo json_encode(["status" => "danger", "message" => "Failed to send verification email: " . $e->getMessage()]);
        exit;
    }

    echo json_encode(["status" => "success", "message" => "Change of email was successful."]);
?>