<?php
    require_once "db_connection.php";
    require_once "functions.php";

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status'=>'danger', 'message' => 'Only GET requests are allowed']);
        exit;
    }

    if (!isset($_GET['verification_key'])) {
        http_response_code(400);
        echo json_encode(['status'=> 'danger', 'message' => 'Missing required parameters']);
        exit;
    }

    $verification_key = $_GET['verification_key'];
    
    // Check if the verification key has expired
    $email = checkVerificationKey($verification_key);
    if ($email) {
        http_response_code(400);
        echo json_encode(['status'=> 'danger', 'message' => 'Verification key has expired', 'email' => $email]);

        // Delete user's directories
        $hash_user = md5($email);
        $userDir = "../../python/private/$hash_user";
        
        // Delete the user's directory
        if (!deleteDirectory($userDir)) {
            echo json_encode(["status" => "danger", "message" => "Failed to delete user directories"]);
            $stmt->close();
            exit();
        }

        // Delete user from database, so the user can register with this email in the future
        $sql = "DELETE FROM users WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        exit;
    }

    // Change value of verified user from 0 to 1
    $sql = "UPDATE users u JOIN verify_account va ON u.id = va.id_of_user SET u.email_verification = 1 WHERE va.verification_key = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $verification_key);
    $stmt->execute();

    // Delete verification key
    $deleteQuery = "DELETE FROM verify_account WHERE verification_key = ?";
    $stmt = $mysqli->prepare($deleteQuery);
    $stmt->bind_param('s', $verification_key);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Account verified successfully']);
?>