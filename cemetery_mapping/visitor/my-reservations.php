<?php
session_start();
require_once '../config/database.php';

// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    header('Location: ../index.php');
    exit;
}

$visitor_name = $_SESSION['visitor_name'] ?? 'Visitor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Cemetery Mapping</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #000000;
            color: white;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        /* Navigation */
        .nav-bar {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(20px);
            padding: 16px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo h2 {
            margin: 0;
            font-size: 1.3rem;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-logo p {
            color: var(--zinc-400);
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .nav-links {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .nav-links a, .nav-links span {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .nav-links a.active {
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            box-shadow: 0 4px 16px rgba(0, 230, 118, 0.3);
        }

        .nav-links a:not(.active):hover {
            background: rgba(255, 255, 255, 0.06);
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Page Hero */
        .page-hero {
            background: linear-gradient(135deg, rgba(0, 230, 118, 0.08) 0%, rgba(5, 150, 105, 0.08) 100%);
            border: 1px solid rgba(0, 230, 118, 0.15);
            border-radius: 24px;
            padding: 48px 40px;
            margin-bottom: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 180%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 230, 118, 0.08) 0%, transparent 60%);
        }

        .page-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }

        .page-hero p {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 1;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 230, 118, 0.3);
            box-shadow: 0 8px 30px rgba(0, 230, 118, 0.15);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 24px; height: 24px; }

        .stat-icon.blue { background: rgba(0, 230, 118, 0.15); color: #00c853; }
        .stat-icon.amber { background: rgba(201, 168, 108, 0.15); color: #c9a86c; }
        .stat-icon.green { background: rgba(0, 200, 83, 0.15); color: #5a9b6f; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }

        .stat-info { flex: 1; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Reservation Cards */
        .reservations-container { display: grid; gap: 20px; }

        .reservation-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 28px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00c853 0%, #059669 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .reservation-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 230, 118, 0.25);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .reservation-card:hover::before { opacity: 1; }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .reservation-id {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reservation-id-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(0, 230, 118, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00c853;
            flex-shrink: 0;
        }

        .reservation-id-icon svg { width: 18px; height: 18px; }

        .badges-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge svg { width: 14px; height: 14px; }

        .status-pending {
            background: rgba(201, 168, 108, 0.12);
            color: #c9a86c;
            border: 1px solid rgba(201, 168, 108, 0.25);
        }

        .status-approved {
            background: rgba(0, 200, 83, 0.12);
            color: #5a9b6f;
            border: 1px solid rgba(0, 200, 83, 0.25);
        }

        .status-rejected {
            background: rgba(181, 90, 90, 0.12);
            color: #b55a5a;
            border: 1px solid rgba(181, 90, 90, 0.25);
        }

        .payment-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-unpaid { background: rgba(181, 90, 90, 0.12); color: #b55a5a; }
        .payment-partial { background: rgba(201, 168, 108, 0.12); color: #c9a86c; }
        .payment-paid { background: rgba(0, 200, 83, 0.12); color: #5a9b6f; }

        /* Payment Progress Bar */
        .payment-progress {
            margin-bottom: 20px;
        }

        .payment-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .payment-progress-label {
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .payment-progress-label svg { width: 14px; height: 14px; }

        .payment-progress-value {
            font-weight: 600;
        }

        .payment-progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 4px;
            overflow: hidden;
        }

        .payment-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00c853 0%, #059669 100%);
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .payment-progress-fill.complete {
            background: linear-gradient(90deg, #5a9b6f 0%, #059669 100%);
        }

        /* Reservation Details */
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-label svg { width: 14px; height: 14px; }

        .detail-value {
            font-size: 1.05rem;
            font-weight: 500;
        }

        .detail-value.highlight {
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        /* Alert Boxes */
        .alert-box {
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .alert-danger {
            background: rgba(181, 90, 90, 0.08);
            border: 1px solid rgba(181, 90, 90, 0.2);
            color: #b55a5a;
        }

        .alert-box svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }

        /* Payments Section */
        .payments-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .payments-section h3 {
            font-size: 1rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.8);
        }

        .payments-section h3 svg { width: 18px; height: 18px; color: #00c853; }

        .payment-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .payment-item:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(0, 230, 118, 0.2);
        }

        .payment-amount-circle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(0, 200, 83, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .payment-amount-circle svg { width: 20px; height: 20px; color: #5a9b6f; }

        .payment-info { flex: 1; }

        .payment-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: #5a9b6f;
        }

        .payment-meta {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 4px;
        }

        .payment-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .payment-meta-item svg { width: 13px; height: 13px; }

        .payment-meta-item a { color: #00c853; text-decoration: none; }
        .payment-meta-item a:hover { text-decoration: underline; }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 230, 118, 0.35);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
        }

        .empty-state-icon-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: rgba(0, 230, 118, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state-icon-circle svg {
            width: 36px;
            height: 36px;
            color: #00c853;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-state p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.45);
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #15151f;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 36px;
            max-width: 520px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-header-icon svg { width: 20px; height: 20px; color: white; }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00c853;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 0 3px rgba(0, 230, 118, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.82rem;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 36px;
            height: 36px;
            border: 3px solid rgba(0, 230, 118, 0.15);
            border-top-color: #00c853;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 2000;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .toast.show { transform: translateX(0); }
        .toast.success { background: rgba(0, 200, 83, 0.15); border: 1px solid rgba(0, 200, 83, 0.3); color: #5a9b6f; }
        .toast.error { background: rgba(181, 90, 90, 0.15); border: 1px solid rgba(181, 90, 90, 0.3); color: #b55a5a; }
        .toast svg { width: 20px; height: 20px; }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .nav-content { padding: 0 20px; }
            .main-container { padding: 30px 15px; }
            .page-hero { padding: 40px 30px; }
            .page-hero h1 { font-size: 2.2rem; }
        }

        @media (max-width: 768px) {
            .nav-bar { padding: 12px 15px; }
            .nav-content { flex-direction: column; gap: 12px; }
            .nav-logo h2 { font-size: 1.1rem; }
            .nav-links { flex-direction: column; gap: 8px; width: 100%; }
            .nav-links a, .nav-links span { width: 100%; justify-content: center; font-size: 0.85rem; }
            .main-container { padding: 20px 10px; }
            .page-hero { padding: 30px 20px; margin-bottom: 24px; border-radius: 16px; }
            .page-hero h1 { font-size: 1.8rem; }
            .page-hero p { font-size: 0.9rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-card { padding: 16px; gap: 12px; }
            .stat-icon { width: 40px; height: 40px; }
            .stat-icon svg { width: 20px; height: 20px; }
            .stat-value { font-size: 1.6rem; }
            .stat-label { font-size: 0.72rem; }
            .reservation-card { padding: 20px 15px; border-radius: 16px; }
            .reservation-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .reservation-id { font-size: 1.1rem; }
            .reservation-details { grid-template-columns: 1fr; gap: 14px; }
            .detail-value { font-size: 1rem; }
            .payment-item { flex-direction: column; align-items: flex-start; gap: 10px; }
            .btn { padding: 10px 18px; font-size: 0.85rem; }
            .modal-content { padding: 28px 20px; }
            .modal-header h2 { font-size: 1.3rem; }
            .empty-state { padding: 60px 20px; border-radius: 16px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .page-hero h1 { font-size: 1.5rem; }
            .reservation-card { padding: 16px 12px; }
            .modal-content { padding: 24px 16px; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-bar">
        <div class="nav-content">
            <div class="nav-logo">
                <h2>Matinao Memorial</h2>
                <p>Cemetery Management System</p>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Map
                </a>
                <a href="my-reservations.php" class="active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    My Reservations
                </a>
                <span style="color: var(--zinc-400);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <?php echo htmlspecialchars($visitor_name); ?>
                </span>
                <a href="logout.php" style="background: rgba(181, 90, 90, 0.12); color: #b55a5a;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <!-- Page Hero -->
        <div class="page-hero">
            <h1>My Reservations</h1>
            <p>Track your plot reservations, payments, and approval status</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" id="statsGrid" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="totalReservations">0</div>
                    <div class="stat-label">Total Reservations</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="pendingCount">0</div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="approvedCount">0</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="totalPaid">₱0.00</div>
                    <div class="stat-label">Total Paid</div>
                </div>
            </div>
        </div>

        <!-- Reservations Container -->
        <div class="reservations-container">
            <div id="reservationsContainer">
                <div class="loading">
                    <p style="color: rgba(255,255,255,0.4); margin-bottom: 16px;">Loading your reservations...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <h2>Submit Payment</h2>
            </div>
            <form id="paymentForm" enctype="multipart/form-data">
                <input type="hidden" id="reservation_id" name="reservation_id">

                <div class="form-group">
                    <label>Amount to Pay *</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00">
                    <small id="balanceInfo"></small>
                </div>

                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reference Number</label>
                    <input type="text" name="reference_number" placeholder="Transaction/Reference Number">
                    <small>Enter the transaction or reference number if applicable</small>
                </div>

                <div class="form-group">
                    <label>Proof of Payment (Image/PDF)</label>
                    <input type="file" name="proof_of_payment" accept="image/*,.pdf" style="padding: 10px;">
                    <small>Upload screenshot or receipt of payment</small>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Additional notes or comments..."></textarea>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Payment</button>
                    <button type="button" onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentReservation = null;

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const icon = type === 'success'
                ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            toast.innerHTML = icon + '<span>' + message + '</span>';
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        async function loadReservations() {
            try {
                const response = await fetch('../api/get_my_reservations.php');
                const data = await response.json();

                if (data.success) {
                    displayReservations(data.reservations);
                } else {
                    document.getElementById('reservationsContainer').innerHTML =
                        '<p style="text-align: center; color: rgba(255,255,255,0.4);">Error loading reservations</p>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function displayReservations(reservations) {
            const container = document.getElementById('reservationsContainer');

            if (reservations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon-circle">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <h3>No Reservations Yet</h3>
                        <p>You haven't made any plot reservations. Start by browsing available plots on the map.</p>
                        <a href="dashboard.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            Browse Available Plots
                        </a>
                    </div>
                `;
                return;
            }

            const stats = {
                total: reservations.length,
                pending: reservations.filter(r => r.status === 'pending').length,
                approved: reservations.filter(r => r.status === 'approved').length,
                totalPaid: reservations.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0)
            };

            document.getElementById('statsGrid').style.display = 'grid';
            document.getElementById('totalReservations').textContent = stats.total;
            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('approvedCount').textContent = stats.approved;
            document.getElementById('totalPaid').textContent = '₱' + stats.totalPaid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            container.innerHTML = reservations.map(res => {
                const total = parseFloat(res.total_amount);
                const paid = parseFloat(res.amount_paid);
                const balance = total - paid;
                const progressPercent = total > 0 ? Math.min(100, (paid / total) * 100) : 0;
                const isComplete = balance <= 0;

                const statusIcon = {
                    pending: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    approved: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    rejected: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                };

                return `
                <div class="reservation-card">
                    <div class="reservation-header">
                        <div class="reservation-id">
                            <div class="reservation-id-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                            </div>
                            Reservation #${res.id}
                        </div>
                        <div class="badges-group">
                            <span class="status-badge status-${res.status}">${statusIcon[res.status] || ''}${res.status}</span>
                            <span class="payment-badge payment-${res.payment_status}">${res.payment_status}</span>
                        </div>
                    </div>

                    <div class="payment-progress">
                        <div class="payment-progress-header">
                            <span class="payment-progress-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Payment Progress
                            </span>
                            <span class="payment-progress-value" style="color: ${isComplete ? '#5a9b6f' : '#c9a86c'};">
                                ₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} / ₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="payment-progress-bar">
                            <div class="payment-progress-fill ${isComplete ? 'complete' : ''}" style="width: ${progressPercent}%;"></div>
                        </div>
                    </div>

                    <div class="reservation-details">
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Plot Number
                            </span>
                            <span class="detail-value">${res.plot_number || 'N/A'}</span>
                        </div>
                        ${res.compartment_number ? `
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Compartment
                            </span>
                            <span class="detail-value">${res.compartment_number}</span>
                        </div>
                        ` : ''}
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                Type
                            </span>
                            <span class="detail-value">${res.reservation_type.charAt(0).toUpperCase() + res.reservation_type.slice(1)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Total Amount
                            </span>
                            <span class="detail-value highlight">₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Amount Paid
                            </span>
                            <span class="detail-value" style="color: #5a9b6f;">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Balance
                            </span>
                            <span class="detail-value" style="color: ${balance > 0 ? '#c9a86c' : '#5a9b6f'};">
                                ₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Reservation Date
                            </span>
                            <span class="detail-value">${new Date(res.reservation_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>

                    ${res.intended_for ? `
                        <div class="detail-item" style="margin: 14px 0;">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Intended For
                            </span>
                            <span class="detail-value">${res.intended_for}</span>
                        </div>
                    ` : ''}

                    ${res.purpose ? `
                        <div class="detail-item" style="margin: 14px 0;">
                            <span class="detail-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Purpose
                            </span>
                            <span class="detail-value">${res.purpose}</span>
                        </div>
                    ` : ''}

                    ${res.rejection_reason ? `
                        <div class="alert-box alert-danger">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <strong>Rejection Reason:</strong>
                                <p style="margin: 6px 0 0 0;">${res.rejection_reason}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${res.status === 'approved' && res.payment_status !== 'paid' ? `
                        <div style="margin-top: 18px;">
                            <button onclick="openPaymentModal(${res.id}, ${res.total_amount}, ${res.amount_paid})" class="btn btn-primary">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Submit Payment
                            </button>
                        </div>
                    ` : ''}

                    ${res.payments && res.payments.length > 0 ? `
                        <div class="payments-section">
                            <h3>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Payment History
                            </h3>
                            ${res.payments.map(payment => `
                                <div class="payment-item">
                                    <div class="payment-amount-circle">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                                    </div>
                                    <div class="payment-info">
                                        <div class="payment-amount">₱${parseFloat(payment.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                        <div class="payment-meta">
                                            <span class="payment-meta-item">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                ${new Date(payment.payment_date).toLocaleString('en-PH')}
                                            </span>
                                            <span class="payment-meta-item">${payment.payment_method.replace('_', ' ').toUpperCase()}</span>
                                            ${payment.reference_number ? `
                                            <span class="payment-meta-item">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                                Ref: ${payment.reference_number}
                                            </span>
                                            ` : ''}
                                            ${payment.proof_of_payment ? `
                                            <span class="payment-meta-item">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <a href="../uploads/payments/${payment.proof_of_payment}" target="_blank">View Proof</a>
                                            </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <span class="payment-badge payment-${payment.verification_status}">${payment.verification_status}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                `;
            }).join('');
        }

        function openPaymentModal(reservationId, totalAmount, amountPaid) {
            currentReservation = { id: reservationId, totalAmount, amountPaid };
            const balance = totalAmount - amountPaid;

            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('balanceInfo').textContent = `Remaining balance: ₱${balance.toFixed(2)}`;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            document.getElementById('paymentForm').reset();
        }

        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const response = await fetch('../api/submit_payment.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Payment submitted successfully', 'success');
                    closePaymentModal();
                    loadReservations();
                } else {
                    showToast(data.message || 'Error submitting payment', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error submitting payment', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Payment';
            }
        });

        // Close modal on outside click
        document.getElementById('paymentModal').addEventListener('click', (e) => {
            if (e.target.id === 'paymentModal') closePaymentModal();
        });

        loadReservations();
    </script>
</body>
</html>
