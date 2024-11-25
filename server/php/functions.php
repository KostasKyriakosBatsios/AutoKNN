<?php
    // Return the domain
    function getDomain() {
        $domain = '';
        if (gethostname() == 'kclusterhub') {
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
        $sql = "SELECT * FROM verify_account WHERE verification_key = ? AND creation_time <= (NOW() - INTERVAL 10 MINUTE)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $verification_key);
        $stmt->execute();
        $result = $stmt->get_result();
        $expiredData = $result->fetch_assoc();
        $stmt->close();

        // If expired, retrieve email
        if ($expiredData) {
            $sql = "SELECT u.email FROM users u JOIN verify_account va ON u.id = va.id WHERE va.verification_key = ?";
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

        // Return null if not expired or doesn't exist
        return null;
    }

?>