<?php
    // Return the domain
    function getDomain() {
        $domain = '';
        if (gethostname() == 'nireas') {
            $domain = 'https://kclusterhub.iee.ihu.gr/autoknn';
        } else {
            $domain = 'http://localhost/autoknn';
        }
        
        return $domain;
    }

    // Function to check if the verification key is expired
    function checkVerificationKey($verification_key) {
        global $mysqli;

        // Check if the verification key has expired (10 minutes have passed)
        $sql = "SELECT * FROM verify_account WHERE verification_key = ? AND creation_time < (NOW() - INTERVAL 10 MINUTE)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $verification_key);
        $stmt->execute();
        $result = $stmt->get_result();
        $expiredData = $result->fetch_assoc();
        $stmt->close();

        // If the verification key is expired, retrieve email
        if ($expiredData) {
            $sql = "SELECT u.email FROM users u JOIN verify_account va ON u.id = va.id_of_user WHERE va.verification_key = ? AND va.creation_time < (NOW() - INTERVAL 10 MINUTE)";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                return null;
            }

            $stmt->bind_param("s", $verification_key);
            $stmt->execute();
            $result = $stmt->get_result();
            $email = $result->fetch_assoc()['email'];
            $stmt->close();

            return $email; // Return email only if expired
        }

        // Return null if the verification key is not expired or doesn't exist
        return null;
    }

    // Function to delete user's directories and it's contents
    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
    
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
    
        return rmdir($dir);
    }    
?>