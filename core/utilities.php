<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function getOrdinal($number) {
    if (!is_numeric($number)) {
        return $number; // Return as is if not a number
    }
    
    // Handle 11, 12, and 13 specially as they all use 'th'
    if (in_array(($number % 100), array(11, 12, 13))) {
        return $number . 'th';
    }

    // Determine the suffix based on the last digit
    switch ($number % 10) {
        case 1:
            return $number . 'st';
        case 2:
            return $number . 'nd';
        case 3:
            return $number . 'rd';
        default:
            return $number . 'th';
    }
}

/**
 * Escape HTML for output.
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function generateTemporaryPassword(int $length = 12): string {
    $length = max(8, $length);
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $alphabetLength = strlen($alphabet);
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $alphabetLength - 1)];
    }

    return $password;
}

function sendWelcomeEmail(string $toEmail, string $studentName, string $username, string $temporaryPassword): array {
    if (!class_exists(PHPMailer::class)) {
        return [
            'success' => false,
            'message' => 'PHPMailer is not installed. Run composer install to enable email sending.'
        ];
    }

    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        return [
            'success' => false,
            'message' => 'SMTP settings are not configured.'
        ];
    }

    $mailer = new PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = SMTP_USERNAME;
        $mailer->Password = SMTP_PASSWORD;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $mailer->CharSet = 'UTF-8';

        $fromEmail = defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME;
        $fromName = defined('SMTP_FROM_NAME') && SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'Student Portal';

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($toEmail, $studentName);
        $mailer->isHTML(true);
        $mailer->Subject = 'Your Student Portal Account';

        $loginUrl = defined('APP_URL') ? APP_URL . '/login' : '/login';
        $safeName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

        $mailer->Body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937;'>
                <h2 style='margin-bottom: 16px;'>Welcome to the Student Portal</h2>
                <p>Hello {$safeName},</p>
                <p>Your student account has been created. Use the credentials below to sign in for the first time:</p>
                <div style='background: #f8fafc; border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px; margin: 16px 0;'>
                    <p style='margin: 0 0 8px 0;'><strong>Username:</strong> {$safeUsername}</p>
                    <p style='margin: 0;'><strong>Temporary Password:</strong> {$safePassword}</p>
                </div>
                <p>Sign in here: <a href='{$safeLoginUrl}'>{$safeLoginUrl}</a></p>
                <p>After logging in, you will be asked to change your password.</p>
                <p>If you did not expect this email, please contact the registrar or system administrator.</p>
            </div>
        ";

        $mailer->AltBody = "Hello {$studentName},\n\nYour student account has been created.\nUsername: {$username}\nTemporary Password: {$temporaryPassword}\nLogin: {$loginUrl}\n\nYou will be asked to change your password after signing in.";

        $mailer->send();

        return [
            'success' => true,
            'message' => 'Welcome email sent successfully.'
        ];
    } catch (PHPMailerException $e) {
        return [
            'success' => false,
            'message' => 'Email sending failed: ' . $e->getMessage()
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'message' => 'Email sending failed: ' . $e->getMessage()
        ];
    }
}
?>
