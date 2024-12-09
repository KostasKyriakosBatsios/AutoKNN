<?php
    session_start();

    require_once "../db_connection.php";

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Only POST requests are allowed"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['token']) || !isset($data['features']) || !isset($data['class']) || !isset($data['file']) || !isset($data['model'])) {
        http_response_code(400);
        echo json_encode(["message" => "Missing required parameters"]);
        exit;
    }

    $token = $data['token'];
    $features = implode(",", $data['features']);
    $class = $data['class'];
    $file = $data['file'];
    $model = $data['model'];

    $query = "SELECT id, email FROM users WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token"]);
        exit;
    }
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $email = $user['email'];
    $stmt->close();

    $hash_user = md5($email);

    // Get the paths of the unclassified file and the model
    $filePath = '../../python/private/' . $hash_user . '/' . 'unclassified_datasets/' . $file;
    $modelPath = '../../python/private/' . $hash_user . '/' . 'models_saved/' . $model;

    if (!file_exists($filePath) || !file_exists($modelPath)) {
        http_response_code(400);
        echo json_encode(["message" => "File and/or model not found"]);
        exit;
    }

    $file = substr($file, 0, -4);
    $nameOfClassifiedFile = $file . '_classified.csv';
    $savedResultsFilePath = '../../python/private/' . $hash_user . '/' . 'classified_datasets/' . $nameOfClassifiedFile;

    // Determine Features for Classification
    $fields = [];  // Initialize an array to store non-numeric fields
    $num_fields = [];  // Array to store numeric fields
    $csv_array = [];  // To hold the CSV data
    $row = 0;

    if (($csvFile = fopen($filePath, 'r')) !== FALSE) {
        while (($row_data = fgetcsv($csvFile, 2048, ",")) !== FALSE) {
            $csv_array[$row] = $row_data;
            $row++;
        }
        fclose($csvFile);

        $header = $csv_array[0];  // The first row contains column headers
        $numColumns = count($header);

        // Inspect each column to determine if it's numeric or non-numeric
        for ($col = 0; $col < $numColumns; $col++) {
            $columnData = array_column($csv_array, $col);
            array_shift($columnData);  // Remove the header row

            $numericCount = count(array_filter($columnData, 'is_numeric'));
            $totalCount = count($columnData);

            if ($numericCount == $totalCount) {
                $num_fields[] = $header[$col];  // Numeric field
            } else {
                $fields[] = $header[$col];  // Non-numeric field
            }
        }
    }

    // Validate Selected Features
    $selectedFeatures = explode(",", $features);  // Features selected from the model

    $invalidFeatures = array_diff($selectedFeatures, $num_fields);  // Check for any mismatch
    if (!empty($invalidFeatures)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Model features do not match dataset features",
            "invalid_features" => $invalidFeatures
        ]);
        exit;
    }
    $validatedFeatures = implode(",", $selectedFeatures);

    // Class Validation
    $classFound = in_array($class, $fields);  // Check if class exists in non-numeric fields
    if (!$classFound) {
        $class = 'None';  // Default to 'None' if class is not found
    }

    // Escape and format individual parameters
    $escapedFilePath = escapeshellarg($filePath);
    $escapedModelPath = escapeshellarg($modelPath);
    $escapedFeatures = escapeshellarg($validatedFeatures);
    $escapedClass = escapeshellarg($class);
    $escapedSavedResultsFilePath = escapeshellarg($savedResultsFilePath);

    // Create the command string
    $pythonCmd = "python3 ../../python/classify_data.py $escapedFilePath $escapedModelPath $escapedFeatures $escapedClass $escapedSavedResultsFilePath 2>&1";
    
    // Execute the command
    $output = [];
    $return = 0;
    exec($pythonCmd, $output, $return);
    
    // Check the output
    $jsonOutput = implode("\n", $output);
    if ($return !== 0) {
        // Command failed, return error details
        echo json_encode([
            "error" => "Python script execution failed",
            "output" => $jsonOutput,
            "return_code" => $return
        ]);
        exit;
    }
    
    // Decode the JSON output
    $results = json_decode($jsonOutput, true);
    
    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "error" => "Invalid JSON from Python script",
            "json_error" => json_last_error_msg(),
            "output" => $jsonOutput,
            "return_code" => $return
        ]);
        exit;
    }
    
    // Send the results back
    echo json_encode([
        "message" => "Algorithm is executing",
        "file" => $file,
        "model" => $model,
        "results" => $results,
        "classified_file" => $savedResultsFilePath,
        "command" => $pythonCmd,
        "return_code" => $return
    ]);
?>