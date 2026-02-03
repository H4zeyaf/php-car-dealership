<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer manually
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

function sendEmail($to, $subject, $body, $isHTML = true) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mhbautomobiles.tanger@gmail.com';
        $mail->Password   = 'sgtt geph luma hkew';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Email Settings
        $mail->setFrom('mhbautomobiles.tanger@gmail.com', 'MHB Automobiles');
        $mail->addAddress($to);
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Template for appointment confirmation
function sendAppointmentConfirmation($clientEmail, $clientName, $carMake, $carModel, $bookingDate) {
    $subject = "Confirmation de rendez-vous - MHB Automobiles";
    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: #000; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 30px; margin-top: 20px; border-radius: 8px; }
                .highlight { color: #e63946; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MHB Automobiles</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour " . htmlspecialchars($clientName) . ",</h2>
                    <p>Votre rendez-vous a été <span class='highlight'>confirmé avec succès</span>!</p>
                    
                    <h3>Détails du rendez-vous:</h3>
                    <ul>
                        <li><strong>Véhicule:</strong> " . htmlspecialchars($carMake . ' ' . $carModel) . "</li>
                        <li><strong>Date:</strong> " . htmlspecialchars($bookingDate) . "</li>
                    </ul>
                    
                    <p>Nous vous attendons avec impatience pour vous faire découvrir ce véhicule exceptionnel.</p>
                    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                    
                    <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MHB Automobiles</strong></p>
                </div>
                <div class='footer'>
                    <p>MHB Automobiles - Votre passion, notre métier</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return sendEmail($clientEmail, $subject, $body);
}

// Template for contact form notification
function sendContactNotification($adminEmail, $name, $email, $message) {
    $subject = "Nouveau message de contact - " . $name;
    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: #e63946; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 30px; margin-top: 20px; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouveau message de contact</h2>
                </div>
                <div class='content'>
                    <p><strong>Nom:</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Message:</strong></p>
                    <p style='background: #f5f5f5; padding: 15px; border-left: 4px solid #e63946;'>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return sendEmail($adminEmail, $subject, $body);
}

// Template for newsletter welcome
function sendNewsletterWelcome($subscriberEmail) {
    $subject = "Bienvenue au Club Fidélité MHB";
    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: #000; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 30px; margin-top: 20px; border-radius: 8px; }
                .highlight { color: #e63946; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MHB Automobiles</h1>
                </div>
                <div class='content'>
                    <h2>Merci de vous inscrire!</h2>
                    <p>Vous faites maintenant partie du <span class='highlight'>Club Fidélité MHB</span>.</p>
                    <p>Vous recevrez en exclusivité:</p>
                    <ul>
                        <li>Nos dernières promotions</li>
                        <li>Les nouveautés du showroom</li>
                        <li>Des offres spéciales réservées aux membres</li>
                    </ul>
                    <p>À très bientôt!</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return sendEmail($subscriberEmail, $subject, $body);
}
?>
