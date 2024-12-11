<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require __DIR__.'/../../vendor/autoload.php';
    require "credentials_config.php";
    
    function send_email($recipient_email, $recipient_name, $subject, $body, $alt_body) {
        global $username, $password;

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;
            $mail->SMTPOptions = array('ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ));                               
            $mail->Username   = $username;                     
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
            $mail->Port       = 587;                                    
        
            // Recipients
            $mail->setFrom($username, 'AutoKNN');
            $mail->addAddress($recipient_email, $recipient_name);     
        
            // Content
            $mail->isHTML(true);                                  
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $alt_body;
        
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail Error: ' . $mail->ErrorInfo);
            return false;
        }
    }
?>