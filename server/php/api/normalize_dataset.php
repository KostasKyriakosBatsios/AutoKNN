<?php
    session_start();
    
    require_once '../db_connection.php';
    
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }
    
    // Read the JSON input from the request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if decoding was successful and if required parameters are present
    if (!isset($input['token']) || !isset($input['file']) || !isset($input['folder'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing or invalid required parameters"]);
        exit;
    }
    
    $token = $input['token'];
    $file = $input['file'];
    $folder = $input['folder'];
    
    // Fetch user's email based on the session token
    $sql = "SELECT email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token"]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $email = $user['email'];
    
    // Generate hash for user email
    $hash_user = md5($email);
    
    // Define public and private directories
    $publicDir = '../../python/public/datasets';
    $privateDir = '../../python/private/' . $hash_user . '/datasets';
    
    // Define output directories for normalized datasets
    $publicOutputDir = '../../python/public/normalized';
    $privateOutputDir = '../../python/private/' . $hash_user . '/normalized';
    
    // Initialize file path and output folder
    $filePath = '';
    $outputFolder = '';
    if ($folder === 'public') {
        $filePath = $publicDir . '/' . $file;
        $outputFolder = $publicOutputDir;
    } elseif ($folder === 'private') {
        $filePath = $privateDir . '/' . $file;
        $outputFolder = $privateOutputDir;
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Invalid dataset type"]);
        exit;
    }
    
    if (!file_exists($filePath)) {
        echo json_encode(["message" => "File not found"]);
        exit;
    }
    
    // Escape the file path and output folder for use in the shell command
    $escapedFilePath = escapeshellarg($filePath);
    $escapedOutputFolder = escapeshellarg($outputFolder);
    
    $pythonCmd = "python3 ../../python/normalization.py $escapedFilePath $escapedOutputFolder 2>&1";
    $output = [];
    $return = 0;
    exec($pythonCmd, $output, $return);
    
    // Join the output array into a single string
    $fullOutput = implode("\n", $output);
    
    // Parse the output for normalized data
    if ($return === 0) {
        // Construct the path for the normalized dataset
        $normalizedDataPath = $outputFolder . '/' . basename($file, '.csv') . '_normalized.csv';
        if (file_exists($normalizedDataPath)) {
            $df = fopen($normalizedDataPath, 'r');
            $header = fgetcsv($df);
            $data = [];
            while (($row = fgetcsv($df)) !== false) {
                $data[] = $row;
            }
            fclose($df);
        
            echo json_encode([
                'message' => 'Normalization successful',
                'header' => $header,
                'data' => $data,
                'normalizedFileUrl' => $normalizedDataPath
            ]);
        } else {
            echo json_encode(['message' => 'Normalized file not found']);
        }
    } else {
        echo json_encode([
            'message' => 'Normalization failed',
            'output' => $fullOutput
        ]);
    }
?>