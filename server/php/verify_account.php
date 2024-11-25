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
        exit;
    }

    // Change value of verified user from 0 to 1
    $updateUserVerifiedQuery = "UPDATE users u JOIN verify_account va ON u.id = va.id SET u.email_verification = 1 WHERE va.verification_key = ?";
    $stmt = $mysqli->prepare($updateUserVerifiedQuery);
    $stmt->bind_param('s', $verification_key);
    $stmt->execute();

    // Delete verification key
    $deleteQuery = "DELETE FROM verify_account WHERE verification_key = ?";
    $stmt = $mysqli->prepare($deleteQuery);
    $stmt->bind_param('s', $verification_key);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Account verified successfully']);
?>