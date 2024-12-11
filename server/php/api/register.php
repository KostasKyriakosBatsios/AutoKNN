<?php
    session_start();
    require_once "../db_connection.php";
    require_once "../functions.php";
    require_once "../phpmailer.php";

    header('Content-Type: application/json');

    // Ensure the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }

    // Parse the JSON request
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required parameters
    if (!isset($data["fname"]) || !isset($data["lname"]) || !isset($data["email"]) || !isset($data["password"]) || !isset($data["confirmPassword"])) {
        header("HTTP/1.1 400 Bad Request");
       echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
       exit;
    }

    // Sanitize and validate inputs
    $fname = $data["fname"];
    $lname = $data["lname"];
    $email = $data["email"];
    $password = $data["password"];
    $confirmPassword = $data["confirmPassword"];

    // Check if fields are empty
    if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirmPassword)) {
        echo json_encode(["status" => "warning", "message" => "All fields are required."]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "warning", "message" => "Invalid email format."]);
        exit;
    }

    // Ensure passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(["status" => "warning", "message" => "Passwords do not match."]);
        exit;
    }

    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "warning", "message" => "Email already exists."]);
        exit;
    }
    
    $stmt->close();

    // Hash the password and generate a random token
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(50));

    // Insert the user into the database
    $sql = "INSERT INTO users (fname, lname, email, pass, token) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssss", $fname, $lname, $email, $hashedPassword, $token);
    $stmt->execute();
    $stmt->close();

    // Create user directories
    $hash_user = md5($email);
    $directories = ["datasets", "models_json", "models_saved", "unclassified_datasets", "classified_datasets"];
    foreach ($directories as $dir) {
        $path = "../../python/private/$hash_user/$dir";
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            echo json_encode(["status" => "danger", "message" => "Failed to create directory: $path"]);
            exit;
        }
    }

    // Retrieve the user ID
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userId = $result->fetch_assoc()['id'];
    $stmt->close();

    // Create verification key
    $verification_key = md5(random_bytes(16));
    $sql = "INSERT INTO verify_account(id, verification_key) VALUES (?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $userId, $verification_key);
    $stmt->execute();
    $stmt->close();

    // Prepare email for verification
    $subject = "Email verification - AutoKNN Application";
    $app_domain = getDomain();
    $body = "Dear user,<br> Welcome to AutoKNN Application. To complete your registration, click this verification link (valid for 10'. If expired, you can press resend mail on the link): <a href='" . $app_domain . "/web_pages/verify_account.html?verification_key=" . $verification_key . "'>Verify Email</a>, or just copy this link: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";
    $alt_body = "To complete your registration, visit: $app_domain/web_pages/verify_account.html?verification_key=$verification_key";

    try {
        send_email($email, $fname, $subject, $body, $alt_body);
    } catch (Exception $e) {
        echo json_encode(["status" => "danger", "message" => "Failed to send verification email: " . $e->getMessage()]);
    }

    echo json_encode(["status" => "success", "message" => "Registration successful. Verification email sent."]);
?>