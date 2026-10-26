<?php
/**
 * Mail Configuration (Gmail SMTP)
 *
 * Fill in your Gmail address and an App Password
 * (Google Account > Security > 2-Step Verification > App passwords).
 * Do NOT use your normal Gmail password — it will not work.
 */

// SMTP server settings
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls'); // PHPMailer::ENCRYPTION_STARTTLS

// Credentials — EDIT THESE
define('MAIL_USERNAME', 'your-gmail@gmail.com');   // Gmail address that sends the mail
define('MAIL_PASSWORD', 'your-app-password');      // 16-char Gmail App Password

// From / reply-to identity
define('MAIL_FROM_EMAIL', MAIL_USERNAME);
define('MAIL_FROM_NAME', 'Matinao Memorial Cemetery');

// Fallback admin recipient when no admin_users.email is set
define('ADMIN_NOTIFY_EMAIL', '');

// Set to false to disable all outgoing mail (e.g. on local dev without internet)
define('MAIL_ENABLED', true);
