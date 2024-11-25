<?php
    // Start session to access session variables
    session_start();
    
    // Include database connection
    require_once "../db_connection.php";

    header('Content-Type: application/json');

    // Ensure the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only POST requests are allowed"]);
        exit;
    }

    // Retrieve the POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if required parameters are present
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $email = $data['email'];
    $password = $data['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "danger", "message" => "Email and Password are required."]);
        exit();
    }

    // Query the database for the user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['pass'])) {
            // Check if the token isn't null
            if (!is_null($user['token']) && !empty($user['token'])) {
                /* Check if the user is verified
                $sql = "SELECT * FROM users WHERE email = ? AND email_verification = 1";
                $stmt2 = $mysqli->prepare($sql);
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $result = $stmt2->get_result();
                if ($result->num_rows === 0) {
                    echo json_encode(["status" => "danger", "message" => "Email is not verified."]);
                    exit;
                }
                $stmt2->close(); */

                echo json_encode([
                    "status" => "success",
                    "token" => $user['token'],
                    "fname" => $user['fname'],
                    "lname" => $user['lname'],
                    "allowPublic" => $user['allowPublic']
                ]);
            } else {
                echo json_encode(["status" => "danger", "message" => "Token is null."]);
                exit;
            }
        } else {
            echo json_encode(["status" => "danger", "message" => "Incorrect password."]);
            exit;
        }
    } else {
        echo json_encode(["status" => "danger", "message" => "Email does not exist."]);
        exit;
    }

    // Close the statement
    $stmt->close();
?>