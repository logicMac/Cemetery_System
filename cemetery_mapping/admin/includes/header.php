<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

$admin_username = htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8');
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst(str_replace('-', ' ', $current_page)); ?> - Admin Panel</title>

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    
    <!-- Leaflet CSS (for map pages) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.css" />
    
    <!-- Leaflet Rotate CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.css" />
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin.css?v=8">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css?v=2">
</head>
<body>
    <div class="admin-layout">
