<?php
// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$_SESSION['last_activity'] = time();

$visitor_name = htmlspecialchars($_SESSION['visitor_name'] ?? 'Visitor', ENT_QUOTES, 'UTF-8');
$visitor_email = htmlspecialchars($_SESSION['visitor_email'] ?? '', ENT_QUOTES, 'UTF-8');
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst(str_replace('-', ' ', $current_page)); ?> - Visitor Portal</title>

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css?v=5">
    <link rel="stylesheet" href="../assets/css/admin.css?v=8">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css?v=3">

    <style>
        /* Visitor portal layout fixes */
        html, body, .admin-layout { min-height: 100vh; }
        .admin-main { position: relative; z-index: 1; }
        .admin-sidebar { z-index: 200; }
        .admin-header {
            z-index: 10000 !important;
            background: #ffffff !important;
            height: 80px !important;
            min-height: 80px !important;
            padding: 10px 32px !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
