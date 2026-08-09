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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0a0f;
            color: white;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }
        
        /* Navigation */
        .nav-bar {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            padding: 16px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-links a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .nav-links a:not(.active):hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Page Header */
        .page-hero {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 50px 40px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: float 20s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        
        .page-hero h1 {
            font-size: 2.8rem;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }
        
        .page-hero p {
            font-size: 1.15rem;
            color: var(--zinc-400);
            position: relative;
            z-index: 1;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--zinc-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Reservation Cards */
        .reservations-container {
            display: grid;
            gap: 24px;
        }
        
        .reservation-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .reservation-card:hover {
            transform: translateY(-4px);
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
        }
        
        .reservation-card:hover::before {
            opacity: 1;
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .reservation-id {
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .badges-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        
        .status-approved {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .payment-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .payment-unpaid {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        
        .payment-partial {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }
        
        .payment-paid {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--zinc-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .detail-value.highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        /* Alert Boxes */
        .alert-box {
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        /* Payments Section */
        .payments-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .payments-section h3 {
            font-size: 1.1rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .payment-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            color: var(--zinc-300);
        }
        
        .empty-state p {
            font-size: 1rem;
            color: var(--zinc-400);
            margin-bottom: 24px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--zinc-300);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--zinc-400);
            font-size: 0.85rem;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
        }
        
        .loading::after {
            content: '';
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(102, 126, 234, 0.2);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        
        /* Mobile Responsive Styles */
        @media (max-width: 1024px) {
            .nav-content {
                padding: 0 20px;
            }
            
            .main-container {
                padding: 30px 15px;
            }
            
            .page-hero {
                padding: 40px 30px;
            }
            
            .page-hero h1 {
                font-size: 2.4rem;
            }
        }
        
        @media (max-width: 768px) {
            /* Navigation */
            .nav-bar {
                padding: 12px 15px;
            }
            
            .nav-content {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .nav-logo h2 {
                font-size: 1.1rem;
            }
            
            .nav-logo p {
                font-size: 0.75rem;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }
            
            .nav-links a,
            .nav-links span {
                width: 100%;
                padding: 10px 12px;
                font-size: 0.9rem;
                justify-content: center;
            }
            
            .nav-links a svg,
            .nav-links span svg {
                width: 18px;
                height: 18px;
            }
            
            /* Main Container */
            .main-container {
                padding: 20px 10px;
            }
            
            /* Page Hero */
            .page-hero {
                padding: 30px 20px;
                margin-bottom: 30px;
                border-radius: 16px;
            }
            
            .page-hero h1 {
                font-size: 2rem;
                margin-bottom: 10px;
            }
            
            .page-hero p {
                font-size: 0.95rem;
            }
            
            /* Stats Grid */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                padding: 18px;
            }
            
            .stat-value {
                font-size: 2rem;
                margin-bottom: 6px;
            }
            
            .stat-label {
                font-size: 0.75rem;
            }
            
            /* Reservation Cards */
            .reservation-card {
                padding: 20px 15px;
                border-radius: 16px;
            }
            
            .reservation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 20px;
                padding-bottom: 16px;
            }
            
            .reservation-id {
                font-size: 1.1rem;
            }
            
            .badges-group {
                width: 100%;
                justify-content: flex-start;
            }
            
            .status-badge {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            .payment-badge {
                padding: 5px 10px;
                font-size: 0.7rem;
            }
            
            /* Reservation Details */
            .reservation-details {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-bottom: 16px;
            }
            
            .detail-label {
                font-size: 0.75rem;
            }
            
            .detail-label svg {
                width: 14px;
                height: 14px;
            }
            
            .detail-value {
                font-size: 1rem;
            }
            
            /* Payments Section */
            .payments-section {
                margin-top: 20px;
                padding-top: 20px;
            }
            
            .payments-section h3 {
                font-size: 1rem;
                margin-bottom: 14px;
            }
            
            .payment-item {
                padding: 14px;
                margin-bottom: 10px;
            }
            
            /* Buttons */
            .btn {
                padding: 10px 18px;
                font-size: 0.85rem;
            }
            
            /* Alert Boxes */
            .alert-box {
                padding: 14px;
                margin: 14px 0;
                font-size: 0.85rem;
            }
            
            /* Empty State */
            .empty-state {
                padding: 60px 20px;
            }
            
            .empty-state-icon {
                font-size: 3rem;
                margin-bottom: 16px;
            }
            
            .empty-state h3 {
                font-size: 1.3rem;
                margin-bottom: 10px;
            }
            
            .empty-state p {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            
            /* Modal */
            .modal-content {
                padding: 30px 20px;
                max-width: 95%;
                width: 95%;
            }
            
            .modal-content h2 {
                font-size: 1.4rem;
                margin-bottom: 20px;
            }
            
            .modal-content h2 svg {
                width: 28px;
                height: 28px;
            }
            
            /* Form Groups */
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 12px 14px;
                font-size: 14px;
                border-radius: 8px;
            }
            
            .form-group small {
                font-size: 0.75rem;
                margin-top: 4px;
            }
        }
        
        @media (max-width: 480px) {
            /* Extra small devices */
            .nav-bar {
                padding: 10px 12px;
            }
            
            .nav-logo h2 {
                font-size: 1rem;
            }
            
            .nav-links a,
            .nav-links span {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            
            .page-hero {
                padding: 25px 15px;
            }
            
            .page-hero h1 {
                font-size: 1.6rem;
            }
            
            .page-hero p {
                font-size: 0.85rem;
            }
            
            /* Stats Grid - Single column on very small screens */
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
            
            .stat-label {
                font-size: 0.7rem;
            }
            
            /* Reservation Cards */
            .reservation-card {
                padding: 16px 12px;
            }
            
            .reservation-id {
                font-size: 1rem;
            }
            
            .reservation-id svg {
                width: 20px;
                height: 20px;
            }
            
            .status-badge {
                padding: 5px 10px;
                font-size: 0.7rem;
            }
            
            .payment-badge {
                padding: 4px 8px;
                font-size: 0.65rem;
            }
            
            .detail-value {
                font-size: 0.95rem;
            }
            
            /* Buttons */
            .btn {
                padding: 9px 16px;
                font-size: 0.8rem;
            }
            
            .btn svg {
                width: 16px;
                height: 16px;
            }
            
            /* Modal */
            .modal-content {
                padding: 25px 15px;
            }
            
            .modal-content h2 {
                font-size: 1.2rem;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 10px 12px;
            }
            
            /* Empty State */
            .empty-state {
                padding: 40px 15px;
            }
            
            .empty-state svg {
                width: 60px;
                height: 60px;
            }
            
            .empty-state h3 {
                font-size: 1.2rem;
            }
            
            .empty-state p {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 360px) {
            /* Very small devices */
            .page-hero h1 {
                font-size: 1.4rem;
            }
            
            .page-hero p {
                font-size: 0.8rem;
            }
            
            .reservation-card {
                padding: 14px 10px;
            }
            
            .modal-content {
                padding: 20px 12px;
            }
        }
        
        /* Landscape orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            .nav-content {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .nav-links {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .page-hero {
                padding: 20px 15px;
            }
            
            .page-hero h1 {
                font-size: 1.4rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .modal-content {
                max-height: 85vh;
                overflow-y: auto;
            }
        }
        
        /* Touch-friendly tap targets */
        @media (hover: none) and (pointer: coarse) {
            .nav-links a,
            .btn,
            button {
                min-height: 44px;
            }
            
            .reservation-card {
                cursor: default;
            }
            
            /* Better touch feedback */
            .btn:active,
            .nav-links a:active {
                transform: scale(0.98);
            }
        }
        
        /* Prevent zoom on input focus (iOS) */
        @supports (-webkit-touch-callout: none) {
            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }
        
        @media (max-width: 768px) {
            .page-hero h1 {
                font-size: 2rem;
            }
            
            .nav-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .reservation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
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
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Map
                </a>
                <a href="my-reservations.php" class="active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    My Reservations
                </a>
                <span style="color: var(--zinc-400); display: flex; align-items: center; gap: 8px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <?php echo htmlspecialchars($visitor_name); ?>
                </span>
                <a href="logout.php" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; gap: 8px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
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

        <!-- Stats Grid (will be populated dynamically) -->
        <div class="stats-grid" id="statsGrid" style="display: none;">
            <div class="stat-card">
                <div class="stat-value" id="totalReservations">0</div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="pendingCount">0</div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="approvedCount">0</div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalPaid">₱0.00</div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>

        <!-- Reservations Container -->
        <div class="reservations-container">
            <div id="reservationsContainer">
                <div class="loading">
                    <p style="color: var(--zinc-400); margin-bottom: 20px;">Loading your reservations...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 24px; font-size: 1.8rem; display: flex; align-items: center; gap: 12px;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 32px; height: 32px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Submit Payment
            </h2>
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
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Submit Payment
                    </button>
                    <button type="button" onclick="closePaymentModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentReservation = null;
        
        async function loadReservations() {
            try {
                const response = await fetch('../api/get_my_reservations.php');
                const data = await response.json();
                
                if (data.success) {
                    displayReservations(data.reservations);
                } else {
                    document.getElementById('reservationsContainer').innerHTML = 
                        '<p style="text-align: center; color: var(--zinc-400);">Error loading reservations</p>';
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
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 80px; height: 80px; margin: 0 auto 20px; opacity: 0.5;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <h3>No Reservations Yet</h3>
                        <p>You haven't made any plot reservations. Start by browsing available plots on the map.</p>
                        <a href="dashboard.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            Browse Available Plots
                        </a>
                    </div>
                `;
                return;
            }
            
            // Calculate stats
            const stats = {
                total: reservations.length,
                pending: reservations.filter(r => r.status === 'pending').length,
                approved: reservations.filter(r => r.status === 'approved').length,
                totalPaid: reservations.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0)
            };
            
            // Show and update stats
            document.getElementById('statsGrid').style.display = 'grid';
            document.getElementById('totalReservations').textContent = stats.total;
            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('approvedCount').textContent = stats.approved;
            document.getElementById('totalPaid').textContent = '₱' + stats.totalPaid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            container.innerHTML = reservations.map(res => `
                <div class="reservation-card">
                    <div class="reservation-header">
                        <div class="reservation-id" style="display: flex; align-items: center; gap: 8px;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                            Reservation #${res.id}
                        </div>
                        <div class="badges-group">
                            <span class="status-badge status-${res.status}">${res.status}</span>
                            <span class="payment-badge payment-${res.payment_status}">${res.payment_status}</span>
                        </div>
                    </div>
                    
                    <div class="reservation-details">
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Plot Number
                            </span>
                            <span class="detail-value">${res.plot_number || 'N/A'}</span>
                        </div>
                        ${res.compartment_number ? `
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Compartment
                            </span>
                            <span class="detail-value">${res.compartment_number}</span>
                        </div>
                        ` : ''}
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Type
                            </span>
                            <span class="detail-value">${res.reservation_type.charAt(0).toUpperCase() + res.reservation_type.slice(1)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Total Amount
                            </span>
                            <span class="detail-value highlight">₱${parseFloat(res.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Amount Paid
                            </span>
                            <span class="detail-value" style="color: #22c55e;">₱${parseFloat(res.amount_paid).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Balance
                            </span>
                            <span class="detail-value" style="color: ${parseFloat(res.total_amount) - parseFloat(res.amount_paid) > 0 ? '#fbbf24' : '#22c55e'}">
                                ₱${(parseFloat(res.total_amount) - parseFloat(res.amount_paid)).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Reservation Date
                            </span>
                            <span class="detail-value">${new Date(res.reservation_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>
                    
                    ${res.intended_for ? `
                        <div class="detail-item" style="margin: 16px 0;">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Intended For
                            </span>
                            <span class="detail-value">${res.intended_for}</span>
                        </div>
                    ` : ''}
                    
                    ${res.purpose ? `
                        <div class="detail-item" style="margin: 16px 0;">
                            <span class="detail-label" style="display: flex; align-items: center; gap: 6px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Purpose
                            </span>
                            <span class="detail-value">${res.purpose}</span>
                        </div>
                    ` : ''}
                    
                    ${res.rejection_reason ? `
                        <div class="alert-box alert-danger" style="display: flex; align-items: start; gap: 10px;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <strong>Rejection Reason:</strong>
                                <p style="margin: 8px 0 0 0;">${res.rejection_reason}</p>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${res.status === 'approved' && res.payment_status !== 'paid' ? `
                        <div style="margin-top: 20px;">
                            <button onclick="openPaymentModal(${res.id}, ${res.total_amount}, ${res.amount_paid})" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Submit Payment
                            </button>
                        </div>
                    ` : ''}
                    
                    ${res.payments && res.payments.length > 0 ? `
                        <div class="payments-section">
                            <h3 style="display: flex; align-items: center; gap: 8px;">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Payment History
                            </h3>
                            ${res.payments.map(payment => `
                                <div class="payment-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div>
                                            <strong style="font-size: 1.1rem; color: #22c55e;">₱${parseFloat(payment.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                            <span style="color: var(--zinc-400); margin-left: 12px;">${payment.payment_method.replace('_', ' ').toUpperCase()}</span>
                                        </div>
                                        <span class="payment-badge payment-${payment.verification_status}">${payment.verification_status}</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--zinc-400); display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            ${new Date(payment.payment_date).toLocaleString('en-PH')}
                                        </div>
                                        ${payment.reference_number ? `
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                            </svg>
                                            Ref: ${payment.reference_number}
                                        </div>
                                        ` : ''}
                                        ${payment.proof_of_payment ? `
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                            <a href="../uploads/payments/${payment.proof_of_payment}" target="_blank" style="color: #667eea;">View Proof</a>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
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
            
            try {
                const response = await fetch('../api/submit_payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closePaymentModal();
                    loadReservations();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error submitting payment');
            }
        });
        
        // Load reservations on page load
        loadReservations();
    </script>
</body>
</html>
