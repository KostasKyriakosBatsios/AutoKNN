<?php
    session_start();
    
    require_once "../db_connection.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["status" => "danger", "message" => "Only GET requests are allowed"]);
        exit;
    }

    if (!isset($_GET['token']) || !isset($_GET['file']) || !isset($_GET['folder'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Missing required parameters"]);
        exit;
    }

    $token = $_GET['token'];
    $file = $_GET['file'];
    $folder = $_GET['folder'];

    // Validate folder type
    if (!in_array($folder, ['public', 'private'])) {
        http_response_code(400);
        echo json_encode(["status" => "danger", "message" => "Invalid folder specified"]);
        exit;
    }

    // Fetch user email based on the session token
    $sql = "SELECT email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["status" => "danger", "message" => "Invalid token"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Generate hash for user email
    $hash_user = md5($email);

    // Define public and private directories
    $publicDir = '../../python/public/datasets';
    $privateDir = '../../python/private/' . $hash_user . '/datasets';

    // Determine file path based on folder
    $filePath = '';
    if ($folder === 'public' && file_exists($publicDir . '/' . $file)) {
        $filePath = $publicDir . '/' . $file;
    } elseif ($folder === 'private' && file_exists($privateDir . '/' . $file)) {
        $filePath = $privateDir . '/' . $file;
    } else {
        http_response_code(404);
        echo json_encode(["status" => "danger", "message" => "Dataset not found"]);
        exit;
    }

    // Read the dataset file
    $fileContents = file($filePath);
    if ($fileContents === false) {
        http_response_code(500);
        echo json_encode(["status" => "danger", "message" => "Error reading file"]);
        exit;
    }

    // Process the file contents (assumed to be CSV for this example), and add a counter variable, that counts the rows of data added to data[]
    $counter = 0;
    $data = [];
    $header = str_getcsv(array_shift($fileContents));
    foreach ($fileContents as $line) {
        $data[] = str_getcsv($line);
        $counter++;
    }

    // Close database connection
    $mysqli->close();

    echo json_encode([
        "status" => "success",
        "header" => $header,
        "data" => $data,
        "counter" => $counter
    ]);
?>