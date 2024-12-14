<?php
    // Include necessary files
    require_once "db_connection.php";
    require_once "functions.php";
    require_once "phpmailer.php";
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['email'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $email = $_GET['email'];

    // Check if the email exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "User not found"]);
        exit;
    }

    $stmt->close();

    // Check if the user is already verified
    $sql = "SELECT * FROM users WHERE email = ? AND email_verification = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "User is already verified"]);
        exit;
    }

    $stmt->close();

    // Retrieve the user ID and the first name
    $sql = "SELECT id, fname FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $result = $result->fetch_assoc();
    $userId = $result['id'];
    $firstName = $result['fname'];
    $stmt->close();

    // Delete from verify account table the user
    $sql = "DELETE FROM verify_account WHERE id_of_user = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Create new verification key
    $verification_key = md5(random_bytes(16));
    $sql = "INSERT INTO verify_account(id_of_user, verification_key) VALUES (?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $userId, $verification_key);
    $stmt->execute();
    $stmt->close();

    // Prepare email for verification
    $subject = "Email verification - AutoKNN Application";
    $app_domain = getDomain();
    $body = "Dear user,<br> To verify your email, click this verification link: <a href='" . $app_domain . "/web_pages/verify_account.html?verification_key=" . $verification_key . "'>Verify Email</a>, or just copy this link: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";
    $alt_body = "To complete your registration, visit: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";

    try {
        send_email($email, $firstName, $subject, $body, $alt_body);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'danger', 'message' => 'Failed to send verification email: ' . $e->getMessage()]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Registration successful. Verification email sent.']);
?>