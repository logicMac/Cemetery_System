<?php
/**
 * Mailer helper — wraps PHPMailer with SMTP settings stored in the database
 * (mail_settings table, editable from Admin → Settings).
 * Falls back to the constants in config/mail_config.php when the table
 * is missing or the row is empty.
 *
 * send_mail(PDO $pdo, $to, $toName, $subject, $htmlBody, $textBody = '')
 */

require_once __DIR__ . '/../config/mail_config.php';

/**
 * Load mail settings from the DB, falling back to config constants.
 */
function mail_settings(PDO $pdo) {
    $defaults = [
        'smtp_host' => MAIL_HOST,
        'smtp_port' => MAIL_PORT,
        'smtp_encryption' => MAIL_ENCRYPTION,
        'smtp_username' => MAIL_USERNAME,
        'smtp_password' => MAIL_PASSWORD,
        'from_name' => MAIL_FROM_NAME,
        'admin_notify_email' => ADMIN_NOTIFY_EMAIL,
        'is_enabled' => MAIL_ENABLED ? 1 : 0,
    ];

    try {
        $row = $pdo->query("SELECT * FROM mail_settings WHERE id = 1 LIMIT 1")->fetch();
        if ($row) {
            foreach ($defaults as $k => $v) {
                if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                    $defaults[$k] = $row[$k];
                } elseif ($k === 'is_enabled') {
                    $defaults[$k] = (int)$row[$k];
                }
            }
            // DB row always wins for the on/off switch once the table exists
            $defaults['is_enabled'] = (int)$row['is_enabled'];
            // Only fall back to config credentials when DB ones are blank
            if ($row['smtp_username'] === '' || $row['smtp_username'] === null) {
                $defaults['smtp_username'] = MAIL_USERNAME;
                $defaults['smtp_password'] = MAIL_PASSWORD;
            }
        }
    } catch (Throwable $e) {
        // mail_settings table not migrated yet — use config constants
    }

    return $defaults;
}

function send_mail(PDO $pdo, $to, $toName, $subject, $htmlBody, $textBody = '') {
    $cfg = mail_settings($pdo);

    if (!$cfg['is_enabled']) {
        return false;
    }
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Mailer: invalid recipient ' . var_export($to, true));
        return false;
    }
    if (empty($cfg['smtp_username']) || empty($cfg['smtp_password'])
        || $cfg['smtp_username'] === 'your-gmail@gmail.com') {
        error_log('Mailer: SMTP credentials not configured');
        return false;
    }

    // Load PHPMailer lazily — a missing library must not fatal the page
    $base = __DIR__ . '/../lib/PHPMailer/src/';
    if (!file_exists($base . 'PHPMailer.php') || !file_exists($base . 'SMTP.php') || !file_exists($base . 'Exception.php')) {
        error_log('Mailer: PHPMailer library missing (lib/PHPMailer not deployed)');
        return false;
    }
    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_username'];
        $mail->Password   = $cfg['smtp_password'];
        $mail->SMTPSecure = $cfg['smtp_encryption'];
        $mail->Port       = (int)$cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $fromEmail = $cfg['smtp_username'];
        $mail->setFrom($fromEmail, $cfg['from_name']);
        $mail->addAddress($to, $toName ?: $to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Mailer error to ' . $to . ': ' . $mail->ErrorInfo);
        return false;
    }
}
