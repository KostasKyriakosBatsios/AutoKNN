<?php
   session_start();
   
   require_once "../db_connection.php";
   
   header('Content-Type: application/json');
   
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       http_response_code(405);
       echo json_encode(["message" => "Only POST requests are allowed"]);
       exit;
   }
   
   // Parse the JSON request
   $data = json_decode(file_get_contents("php://input"), true);
   
   // Validate required parameters
   if (!isset($data['token']) || !isset($data['file']) || !isset($data['folder']) || !isset($data['features'])
       || !isset($data['target']) || !isset($data['k_value']) || !isset($data['distance_value'])
       || !isset($data['stratify'])) {
       http_response_code(400);
       echo json_encode(["message" => "Missing required parameters"]);
       exit;
   }
   
   $token = $data['token'];
   $file = $data['file'];
   $folder = $data['folder'];
   $features = implode(",", $data['features']);
   $target = $data['target'];
   $k_value = implode(",", $data['k_value']);
   $distance_value = implode(",", $data['distance_value']);
   $p_value = $data['p_value'];
   $stratify = $data['stratify'] ? 'true' : 'false';
   
   // Validate the user token
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
   
   // Generate a hash of the user's email
   $hash_user = md5($email);
   
   // Define directories for public/private data
   $publicDir = '../../python/public/datasets';
   $privateDir = '../../python/private/' . $hash_user . '/datasets';
   
   // Determine file path based on folder type
   $filePath = ($folder === 'public') ? $publicDir . '/' . $file : $privateDir . '/' . $file;
   
   if (!file_exists($filePath)) {
       http_response_code(400);
       echo json_encode(["message" => "File not found"]);
       exit;
   }
   
   // Prepare JSON for the parameters
   $parametersArray = json_encode([
       "features" => $features,
       "target" => $target,
       "k" => $k_value,
       "distance" => $distance_value,
       "p" => $p_value,
       "stratified_sampling" => $stratify
   ]);
   
   if ($parametersArray === false) {
       echo json_encode(["message" => "Error encoding JSON", "error" => json_last_error_msg()]);
       exit;
   }
   
   // Insert a new entry into the dataset_execution table
   $query = "INSERT INTO dataset_execution (id_of_user, name_of_dataset, parameters, status) VALUES (?, ?, ?, 'In Progress')";
   $stmt = $mysqli->prepare($query);
   $stmt->bind_param("iss", $user_id, $file, $parametersArray);
   $stmt->execute();
   
   // Get the ID of the inserted dataset
   $dataset_id = $stmt->insert_id;
   
   // Create a directory for results if it doesn't exist
   $resultsDir = '';
   if ($folder === 'public') {
       $resultsDir = '../../python/public/models_json/';
       if (!file_exists($resultsDir)) {
           mkdir($resultsDir, 0777, true);
       }
   } else {
       $resultsDir = '../../python/private/' . $hash_user . '/' . 'models_json/';
       if (!file_exists($resultsDir)) {
           mkdir($resultsDir, 0777, true);
       }
   }
   
   // Path to save the results
   $resultsFilePath = $resultsDir . $dataset_id . '.json';
   
   // Update the dataset_execution entry with the results file path
   $updateQuery = "UPDATE dataset_execution SET results_path = ? WHERE id = ?";
   $updateStmt = $mysqli->prepare($updateQuery);
   $updateStmt->bind_param("si", $resultsFilePath, $dataset_id);
   $updateStmt->execute();
   
   // Escape and format parameters for the Python command
   $escapedFilePath = escapeshellarg($filePath);
   $escapedFeatures = escapeshellarg($features);
   $escapedTarget = escapeshellarg($target);
   $escapedKValue = escapeshellarg($k_value);
   $escapedDistanceValue = escapeshellarg($distance_value);
   $escapedPValue = escapeshellarg($p_value);
   $escapedStratify = escapeshellarg($stratify);
   $escapedResultsFilePath = escapeshellarg($resultsFilePath);
   
   // Create the Python command to execute the kNN model
   $pythonCmd = "python3 ../../python/knn_train_test.py $escapedFilePath $escapedFeatures $escapedTarget $escapedKValue $escapedDistanceValue $escapedPValue $escapedStratify $escapedResultsFilePath 2>&1";
   
   // Execute the Python command and capture the output
   $output = [];
   $return = 0;
   exec($pythonCmd, $output, $return);
   
   // Check if the results file exists
   $resultFileExists = file_exists($resultsFilePath) && filesize($resultsFilePath) > 0;
   
   // Update status based on the results file existence
   $status = $resultFileExists ? 'Completed' : 'Failed';
   $updateStatusQuery = "UPDATE dataset_execution SET status = ? WHERE id = ?";
   $updateStatusStmt = $mysqli->prepare($updateStatusQuery);
   $updateStatusStmt->bind_param("si", $status, $dataset_id);
   $updateStatusStmt->execute();
   
   // Return response based on the execution result
   if ($return === 0) {
       echo json_encode([
           "message" => "Completed execution of algorithm",
           "file" => $file,
           "results_file_exists" => $resultFileExists,
           "results_file_path" => $resultsFilePath,
           "output" => implode("\n", $output),
           "return_code" => $return,
           "status" => $status
       ]);
   } else {
       http_response_code(500);
       echo json_encode([
           "message" => "Failed to execute algorithm",
           "output" => implode("\n", $output),
           "return_code" => $return,
           "status" => $status
       ]);
   }
?>