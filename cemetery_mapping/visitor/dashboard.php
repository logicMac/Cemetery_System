<?php
session_start();

// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    header('Location: ../login.php?role=visitor');
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?role=visitor&timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

$visitor_name = htmlspecialchars($_SESSION['visitor_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cemetery Map - Matinao Memorial</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.css" />
    
    <!-- Leaflet Rotate CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.css" />
    
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <!-- Marker Cluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
        #map { position: absolute; top: 0; bottom: 0; width: 100%; }
        .leaflet-popup-content-wrapper { background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(0, 230, 118, 0.15); }
        .leaflet-popup-content { color: white; margin: 16px; }
        .leaflet-popup-tip { background: rgba(0, 0, 0, 0.9); }
        
        /* Animated user location marker */
        .user-location-marker { 
            width: 20px; 
            height: 20px; 
            background: #5a87a8; 
            border: 3px solid white; 
            border-radius: 50%; 
            box-shadow: 0 0 10px rgba(90, 135, 168, 0.5); 
            animation: pulse 2s infinite; 
        }
        
        @keyframes pulse { 
            0%, 100% { 
                opacity: 1; 
                transform: scale(1);
            } 
            50% { 
                opacity: 0.7; 
                transform: scale(1.1);
            } 
        }
        
        /* Animated destination marker */
        .destination-marker {
            width: 40px;
            height: 40px;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* Custom route line with animation */
        .leaflet-routing-container {
            position: fixed !important;
            top: 280px !important;
            left: 20px !important;
            bottom: auto !important;
            right: auto !important;
            max-width: 280px !important;
            max-height: 300px !important;
            overflow-y: auto !important;
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 230, 118, 0.15) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5) !important;
            z-index: 999 !important;
        }
        
        .leaflet-routing-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .leaflet-routing-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .leaflet-routing-container::-webkit-scrollbar-thumb {
            background: rgba(0, 230, 118, 0.5);
            border-radius: 3px;
        }
        
        .leaflet-routing-container::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 230, 118, 0.7);
        }
        
        /* Hide routing geocoder inputs */
        .leaflet-routing-geocoders {
            display: none !important;
        }
        
        .leaflet-routing-container h2,
        .leaflet-routing-container h3 {
            color: white !important;
            font-size: 1rem !important;
            margin: 0 0 12px 0 !important;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .leaflet-routing-alt {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(0, 230, 118, 0.15) !important;
            border-radius: 12px !important;
            padding: 12px !important;
            margin: 8px 0 !important;
            color: white !important;
        }
        
        .leaflet-routing-icon {
            filter: invert(1) !important;
        }
        
        .leaflet-routing-geocoder {
            display: none !important;
        }
        
        /* Minimize routing panel button */
        .leaflet-routing-collapse-btn {
            background: rgba(0, 230, 118, 0.3) !important;
            border-radius: 8px !important;
            color: white !important;
            padding: 4px 8px !important;
            cursor: pointer !important;
        }
        
        .leaflet-routing-collapse-btn:hover {
            background: rgba(0, 230, 118, 0.5) !important;
        }
        
        /* Widget Organization - Prevent Overlaps */
        /* TOP BAR - Navigation buttons centered */
        .top-bar {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 230, 118, 0.15);
            border-radius: 12px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }
        
        .top-bar.collapsed {
            padding: 8px 12px;
        }
        
        .top-bar.collapsed > *:not(.toggle-nav-btn) {
            display: none;
        }
        
        .toggle-nav-btn {
            background: rgba(0, 230, 118, 0.2);
            border: 1px solid rgba(0, 230, 118, 0.3);
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .toggle-nav-btn:hover {
            background: rgba(0, 230, 118, 0.3);
        }
        
        /* SEARCH BAR - Separate div below navigation, also centered */
        .search-bar-container {
            position: absolute;
            top: 75px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 230, 118, 0.25);
            border-radius: 16px;
            padding: 10px 14px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 420px;
            max-width: 620px;
            transition: all 0.3s ease;
        }
        
        .search-bar-container:focus-within {
            border-color: rgba(0, 230, 118, 0.6);
            box-shadow: 0 8px 32px rgba(0, 230, 118, 0.2);
        }
        
        .search-bar-container.collapsed {
            padding: 8px 12px;
            min-width: auto;
            border-color: rgba(0, 230, 118, 0.15);
        }
        
        .search-bar-container.collapsed > *:not(.toggle-search-btn) {
            display: none;
        }
        
        .toggle-search-btn {
            background: rgba(0, 230, 118, 0.15);
            border: 1px solid rgba(0, 230, 118, 0.25);
            border-radius: 10px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #00c853;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .toggle-search-btn:hover {
            background: rgba(0, 230, 118, 0.3);
            color: white;
            transform: translateY(-1px);
        }
        
        .search-bar-container input {
            flex: 1;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .search-bar-container input:focus {
            outline: none;
            border-color: rgba(0, 230, 118, 0.5);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .search-bar-container input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-bar-container button {
            padding: 10px 16px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .search-bar-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 230, 118, 0.4);
        }
        
        /* Leaflet Zoom Control - Top right corner */
        .leaflet-top.leaflet-left {
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
        }
        
        .leaflet-control-zoom {
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 230, 118, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            margin-top: 0 !important;
        }
        
        .leaflet-control-zoom a {
            background: rgba(0, 230, 118, 0.2) !important;
            color: white !important;
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            border: none !important;
            transition: all 0.3s ease !important;
        }
        
        .leaflet-control-zoom a:hover {
            background: rgba(0, 230, 118, 0.4) !important;
            color: white !important;
        }
        
        .leaflet-control-zoom a:first-child {
            border-radius: 6px 6px 0 0 !important;
        }
        
        .leaflet-control-zoom a:last-child {
            border-radius: 0 0 6px 6px !important;
        }
        
        /* Rotation control styling - Position under zoom controls */
        .leaflet-control-rotate {
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 230, 118, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            margin-top: 8px !important; /* Space below zoom control */
        }
        
        .leaflet-control-rotate a {
            color: white !important;
            background: rgba(0, 230, 118, 0.2) !important;
            border-radius: 6px !important;
            transition: all 0.3s ease !important;
            border: none !important;
        }
        
        .leaflet-control-rotate a:hover {
            background: rgba(0, 230, 118, 0.4) !important;
            color: white !important;
        }
        
        .leaflet-control-rotate-toggle {
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            font-size: 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .leaflet-control-rotate-toggle::before {
            content: '⟳' !important;
            font-size: 24px !important;
        }
        
        .leaflet-control-rotate-reset {
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            font-weight: bold !important;
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* FILTER PANEL - Top left, horizontally aligned */
        .filter-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 230, 118, 0.2);
            border-radius: 16px;
            padding: 14px 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        
        .filter-panel.collapsed {
            padding: 10px 14px;
        }
        
        .filter-panel.collapsed .filter-content {
            display: none;
        }
        
        /* Rotation Panel - Compact bar, no collapse needed */
        
        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .filter-header h4 {
            margin: 0;
            font-size: 1rem;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            white-space: nowrap;
        }
        
        .filter-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .filter-option:hover {
            background: rgba(0, 230, 118, 0.2);
            border-color: rgba(0, 230, 118, 0.3);
            transform: translateY(-1px);
        }
        
        .filter-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #00c853;
        }
        
        .filter-option label {
            cursor: pointer;
            color: white;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 500;
        }
        
        .filter-count {
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-left: 4px;
        }
        
        /* SEARCH RESULTS - Right side, below search bar */
        .search-panel {
            position: absolute;
            top: 140px;
            right: 20px;
            z-index: 1000;
            width: 340px;
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 170px);
            overflow-y: auto;
            display: none;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 230, 118, 0.2);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        .search-panel.active {
            display: block;
        }
        
        .search-panel::-webkit-scrollbar {
            width: 6px;
        }
        
        .search-panel::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .search-panel::-webkit-scrollbar-thumb {
            background: rgba(0, 230, 118, 0.5);
            border-radius: 3px;
        }
        
        .search-panel::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 230, 118, 0.7);
        }
        
        /* MAP LEGEND - Bottom left */
        .map-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 230, 118, 0.15);
            border-radius: 12px;
            padding: 16px;
            min-width: 180px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        
        /* LEGEND - Bottom left, above filter panel visually */
        .map-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 230, 118, 0.2);
            border-radius: 16px;
            padding: 18px;
            min-width: 180px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        /* AI CHAT TOGGLE - Bottom right */
        .chat-toggle {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0, 230, 118, 0.5);
            transition: all 0.3s ease;
        }
        
        .chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 40px rgba(0, 230, 118, 0.7);
        }
        
        .chat-toggle img {
            animation: floatLogo 3s ease-in-out infinite;
        }
        
        @keyframes floatLogo {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }
        
        /* AI CHAT CONTAINER - Bottom right, above toggle */
        .chat-container {
            position: absolute;
            bottom: 100px;
            right: 20px;
            z-index: 1000;
            width: 380px;
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 140px);
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 230, 118, 0.15);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        
        /* Enhance chat header with logo */
        .chat-header img {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
        }
        
        /* ROUTING CONTAINER - Left side, below filter panel with proper spacing */
        .leaflet-routing-container {
            position: fixed !important;
            top: auto !important;
            bottom: 220px !important;
            left: 20px !important;
            right: auto !important;
            max-width: 280px !important;
            max-height: calc(100vh - 320px) !important;
            overflow-y: auto !important;
            background: rgba(0, 0, 0, 0.9) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 230, 118, 0.15) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            color: white !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5) !important;
            z-index: 998 !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .search-bar-container {
                min-width: 350px;
                max-width: 500px;
            }
            
            .filter-panel {
                gap: 12px;
            }
        }
        
        @media (max-width: 1024px) {
            .search-bar-container {
                min-width: 300px;
                max-width: 400px;
            }
            
            .filter-panel {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 12px 16px;
            }
            
            .filter-content {
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }
            
            .search-panel {
                width: 280px;
            }
            
            .chat-container {
                width: 320px;
            }
            
            .leaflet-routing-container {
                max-width: 250px !important;
            }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 1024px) {
            .top-bar {
                gap: 16px;
                padding: 12px 20px;
            }
            
            .search-bar-container {
                min-width: 350px;
                max-width: 500px;
            }
            
            .filter-panel {
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            /* Top Navigation Bar - Keep at top */
            .top-bar {
                top: 10px;
                left: 10px;
                right: 10px;
                transform: none;
                padding: 8px 12px;
                gap: 6px;
                font-size: 0.85rem;
                flex-wrap: wrap;
                justify-content: flex-start;
                max-width: calc(100vw - 20px);
            }
            
            .top-bar h3 {
                font-size: 0.85rem !important;
                width: 100%;
                order: -1;
                margin-bottom: 4px;
            }
            
            .top-bar a {
                padding: 5px 10px !important;
                font-size: 0.75rem !important;
            }
            
            .top-bar a svg {
                width: 12px !important;
                height: 12px !important;
            }
            
            /* Filter Panel - Top left, below nav, default collapsed on mobile */
            .filter-panel {
                top: 65px;
                left: 10px;
                right: auto;
                bottom: auto;
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
                padding: 8px 12px;
                min-width: auto;
                max-width: 160px;
            }
            
            .filter-header {
                margin-bottom: 0;
            }
            
            .filter-header h4 {
                font-size: 0.8rem;
            }
            
            .filter-toggle-btn {
                padding: 2px 6px !important;
            }
            
            .filter-content {
                flex-direction: column;
                gap: 5px;
                margin-top: 8px;
            }
            
            .filter-option {
                padding: 6px 8px;
                font-size: 0.75rem;
            }
            
            .filter-option input[type="checkbox"] {
                width: 14px;
                height: 14px;
            }
            
            .filter-option label {
                font-size: 0.75rem;
            }
            
            .filter-count {
                font-size: 0.65rem;
                padding: 1px 5px;
            }
            
            /* Rotation Panel - Compact at top left, below filter */
            .rotation-panel {
                top: 120px !important;
                left: 10px !important;
                right: auto !important;
                padding: 4px !important;
                gap: 3px !important;
            }
            
            .rotation-panel button {
                padding: 4px !important;
            }
            
            .rotation-panel button svg {
                width: 14px !important;
                height: 14px !important;
            }
            
            #bearingDisplay {
                font-size: 0.65rem !important;
                padding: 0 4px !important;
            }
            
            .leaflet-top.leaflet-left {
                top: 10px !important;
                right: 10px !important;
            }
            
            /* Search Bar - Move to bottom, above chat */
            .search-bar-container {
                top: auto;
                bottom: 85px;
                left: 10px;
                right: 10px;
                transform: none;
                min-width: auto;
                max-width: none;
                padding: 8px 10px;
                width: calc(100vw - 20px);
                gap: 6px;
            }
            
            .search-bar-container input {
                font-size: 13px;
                padding: 8px 10px;
            }
            
            .search-bar-container button {
                padding: 8px 10px;
            }
            
            .search-bar-container button svg {
                width: 16px;
                height: 16px;
            }
            
            .toggle-search-btn {
                display: none !important;
            }
            
            /* Search Results Panel - Top right */
            .search-panel {
                top: 65px;
                bottom: auto;
                right: 10px;
                left: auto;
                width: 180px;
                max-width: 180px;
                max-height: 45vh;
                padding: 8px;
                font-size: 0.75rem;
            }
            
            .search-result-item {
                padding: 8px;
                font-size: 0.75rem;
            }
            
            .search-result-item h4 {
                font-size: 0.8rem;
                margin-bottom: 4px;
            }
            
            .search-result-item p {
                font-size: 0.7rem;
                line-height: 1.3;
            }
            
            /* Map Controls - Top right corner, compact */
            .leaflet-top.leaflet-left {
                top: 10px !important;
                right: 10px !important;
                left: auto !important;
            }
            
            .leaflet-control-zoom {
                margin-top: 0 !important;
            }
            
            .leaflet-control-zoom a {
                width: 30px !important;
                height: 30px !important;
                line-height: 30px !important;
                font-size: 16px !important;
            }
            
            .leaflet-control-rotate {
                margin-top: 6px !important;
            }
            
            .leaflet-control-rotate a {
                width: 30px !important;
                height: 30px !important;
                line-height: 30px !important;
            }
            
            /* Map Legend - Bottom left, compact */
            .map-legend {
                bottom: 10px;
                left: 10px;
                padding: 8px 10px;
                font-size: 0.7rem;
                min-width: 120px;
                border-radius: 8px;
            }
            
            .map-legend h4 {
                font-size: 0.75rem;
                margin-bottom: 6px;
            }
            
            .legend-item {
                margin-bottom: 4px;
                gap: 5px;
            }
            
            .legend-color {
                width: 12px;
                height: 12px;
            }
            
            /* Chat Toggle - Bottom right */
            .chat-toggle {
                bottom: 10px;
                right: 10px;
                width: 50px;
                height: 50px;
            }
            
            .chat-toggle img {
                width: 28px;
                height: 28px;
            }
            
            /* Chat Container - Full width at bottom when open */
            .chat-container {
                bottom: 75px;
                right: 10px;
                left: 10px;
                width: calc(100vw - 20px);
                max-height: 55vh;
                border-radius: 12px;
            }
            
            .chat-header {
                padding: 10px 12px;
            }
            
            .chat-header h4 {
                font-size: 0.95rem;
            }
            
            .chat-header img {
                width: 28px;
                height: 28px;
            }
            
            .chat-messages {
                padding: 10px;
                max-height: 250px;
                font-size: 0.85rem;
            }
            
            .chat-message {
                padding: 8px;
                font-size: 0.8rem;
                margin-bottom: 8px;
            }
            
            .chat-message img {
                width: 24px;
                height: 24px;
            }
            
            .chat-input-container {
                padding: 10px;
                gap: 6px;
            }
            
            .chat-input {
                padding: 8px 10px;
                font-size: 13px;
            }
            
            .chat-input-container button {
                padding: 8px 10px;
            }
            
            /* Routing Container - Hide on mobile to save space */
            .leaflet-routing-container {
                display: none !important;
            }
            
            /* Modals - Full width on mobile */
            #successModal > div,
            #reservationModal > div {
                width: 95% !important;
                max-width: 95% !important;
                padding: 20px 15px !important;
                max-height: 85vh !important;
                overflow-y: auto !important;
            }
            
            #successModal h2 {
                font-size: 1.3rem !important;
            }
            
            #successIcon {
                width: 50px !important;
                height: 50px !important;
                margin-bottom: 15px !important;
            }
            
            #successIcon svg {
                width: 30px !important;
                height: 30px !important;
            }
            
            #reservationModal h3 {
                font-size: 1.1rem !important;
            }
            
            #compartmentGrid {
                gap: 6px !important;
            }
            
            .compartment-cell {
                padding: 10px 8px !important;
                font-size: 0.8rem !important;
            }
        }
                bottom: 10px;
                right: 10px;
                width: 52px;
                height: 52px;
            }
            
            .chat-toggle img {
                width: 30px;
                height: 30px;
            }
            
            .chat-container {
                bottom: 150px;
                right: 10px;
                left: 10px;
                width: calc(100vw - 20px);
                max-height: 50vh;
                border-radius: 12px;
            }
            
            .chat-header {
                padding: 12px;
            }
            
            .chat-header h3 {
                font-size: 0.9rem;
            }
            
            .chat-messages {
                padding: 10px;
                max-height: 200px;
            }
            
            .chat-message {
                padding: 8px;
                font-size: 0.85rem;
                margin-bottom: 8px;
            }
            
            .chat-input-container {
                padding: 10px;
                gap: 8px;
            }
            
            .chat-input {
                padding: 8px;
                font-size: 14px;
            }
            
            /* Routing Container */
            .leaflet-routing-container {
                display: none !important; /* Hide on mobile to save space */
            }
            
            /* Success Modal - Full screen on mobile */
            #successModal > div {
                width: 95% !important;
                padding: 30px 20px !important;
            }
            
            #successModal h2 {
                font-size: 1.4rem !important;
            }
            
            #successIcon {
                width: 60px !important;
                height: 60px !important;
                margin-bottom: 20px !important;
            }
            
            #successIcon svg {
                width: 36px !important;
                height: 36px !important;
            }
            
            /* Reservation Modal */
            #reservationModal > div {
                width: 95% !important;
                padding: 25px 15px !important;
                max-height: 85vh !important;
            }
        }
        
        @media (max-width: 480px) {
            /* Extra small devices */
            .top-bar {
                padding: 8px 10px;
            }
            
            .top-bar h3 {
                font-size: 0.85rem !important;
            }
            
            .top-bar a {
                padding: 5px 10px !important;
                font-size: 0.75rem !important;
            }
            
            .search-bar-container {
                padding: 8px 10px;
            }
            
            .search-bar-container input {
                font-size: 13px;
                padding: 6px 10px;
            }
            
            .filter-panel {
                max-width: 180px;
                padding: 8px;
            }
            
            .filter-header h4 {
                font-size: 0.8rem;
            }
            
            .filter-option {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            
            .search-panel {
                width: 180px;
                max-width: 180px;
            }
            
            .map-legend {
                padding: 6px;
                min-width: 120px;
            }
            
            .legend-item {
                font-size: 0.7rem;
            }
            
            .legend-color {
                width: 12px;
                height: 12px;
            }
            
            .chat-container {
                max-height: 45vh;
            }
            
            .chat-messages {
                max-height: 150px;
            }
            
            .chat-toggle {
                width: 48px;
                height: 48px;
            }
        }
        
        /* Landscape orientation adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            .top-bar {
                padding: 6px 10px;
            }
            
            .search-bar-container {
                bottom: 60px;
            }
            
            .chat-container {
                max-height: 60vh;
                bottom: 120px;
            }
            
            .filter-panel {
                top: 60px;
            }
            
            #reservationModal > div,
            #successModal > div {
                max-height: 90vh !important;
                overflow-y: auto;
            }
        }
        
        /* Touch-friendly tap targets */
        @media (hover: none) and (pointer: coarse) {
            .control-button,
            .toggle-nav-btn,
            .toggle-search-btn,
            button,
            a {
                min-height: 44px;
            }
            
            .filter-option {
                min-height: 40px;
                display: flex;
                align-items: center;
            }
        }
        
        /* Extra small mobile devices (360px and below) */
        @media (max-width: 480px) {
            .top-bar {
                padding: 6px 8px;
                gap: 4px;
            }
            
            .top-bar h3 {
                font-size: 0.8rem !important;
            }
            
            .top-bar a {
                padding: 4px 8px !important;
                font-size: 0.7rem !important;
            }
            
            .filter-panel,
            .rotation-panel {
                max-width: 140px;
                font-size: 0.7rem;
            }
            
            .search-panel {
                width: 150px;
                max-width: 150px;
            }
            
            .map-legend {
                min-width: 100px;
                padding: 6px 8px;
            }
            
            .chat-toggle {
                width: 45px;
                height: 45px;
            }
            
            .chat-toggle img {
                width: 24px;
                height: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Map Container -->
    <div id="map"></div>
    
    <!-- Top Bar - Navigation -->
    <div class="top-bar" id="topBar">
        <button class="toggle-nav-btn" onclick="toggleNavBar()" title="Toggle Navigation">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h3 style="margin: 0; font-size: 1.1rem; color: white; white-space: nowrap;">Welcome, <?php echo $visitor_name; ?></h3>
        <a href="my-reservations.php" style="padding: 8px 16px; font-size: 0.9rem; text-decoration: none; background: linear-gradient(135deg, #00c853 0%, #059669 100%); color: white; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease; white-space: nowrap;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Reservations
        </a>
        <a href="logout.php" style="padding: 8px 16px; font-size: 0.9rem; text-decoration: none; background: rgba(255, 255, 255, 0.05); color: white; border: 1px solid rgba(0, 230, 118, 0.15); border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease; white-space: nowrap;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
        </a>
    </div>
    
    <!-- Search Bar - Separate centered div -->
    <div class="search-bar-container" id="searchBar">
        <button class="toggle-search-btn" onclick="toggleSearchBar()" title="Toggle Search">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
        <input type="text" id="searchInput" placeholder="Search by name, plot, or family...">
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); z-index: 3000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #0a0a0a 0%, #050505 100%); border: 1px solid rgba(0, 200, 83, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); text-align: center;">
            <div id="successIcon" style="width: 80px; height: 80px; background: linear-gradient(135deg, #5a9b6f 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <svg style="width: 48px; height: 48px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 id="successTitle" style="margin: 0 0 16px 0; font-size: 1.8rem; color: #5a9b6f;">Reservation Successful!</h2>
            <div id="successMessage" style="color: rgba(255,255,255,0.8); line-height: 1.8; margin-bottom: 24px;"></div>
            <button onclick="closeSuccessModal()" style="padding: 14px 32px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;">
                Got it!
            </button>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div id="reservationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #0a0a0a 0%, #050505 100%); border: 1px solid rgba(0, 230, 118, 0.35); border-radius: 24px; padding: 36px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 80px rgba(0,0,0,0.6); position: relative;">
            <style>
                #reservationForm input:focus,
                #reservationForm select:focus,
                #reservationForm textarea:focus {
                    outline: none;
                    border-color: #00c853 !important;
                    background: rgba(255,255,255,0.08) !important;
                    box-shadow: 0 0 0 3px rgba(0, 230, 118, 0.2) !important;
                }
                
                .modal-close-btn {
                    position: absolute;
                    top: 18px;
                    right: 18px;
                    background: rgba(255,255,255,0.05);
                    border: 1px solid rgba(255,255,255,0.1);
                    border-radius: 10px;
                    padding: 8px;
                    cursor: pointer;
                    color: rgba(255,255,255,0.6);
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .modal-close-btn:hover {
                    background: rgba(181, 90, 90, 0.15);
                    border-color: rgba(181, 90, 90, 0.3);
                    color: #b55a5a;
                }
                
                #reservationForm select {
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: right 12px center;
                    background-size: 20px;
                    padding-right: 40px !important;
                    cursor: pointer;
                }
                
                #reservationForm select option {
                    background: #0a0a0a;
                    color: white;
                    padding: 12px;
                }
                
                #reservationForm button[type="submit"]:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(0, 230, 118, 0.5);
                }
                
                #reservationForm button[type="button"]:hover {
                    background: rgba(255,255,255,0.1);
                    border-color: rgba(255,255,255,0.2);
                }
                
                .compartment-grid {
                    display: grid;
                    gap: 8px;
                    margin: 16px 0;
                    padding: 16px;
                    background: rgba(0,0,0,0.3);
                    border-radius: 12px;
                }
                
                .compartment-cell {
                    padding: 16px 12px;
                    background: rgba(255,255,255,0.08);
                    border: 3px solid rgba(0, 230, 118, 0.4);
                    border-radius: 10px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-align: center;
                    font-weight: 600;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                    position: relative;
                }
                
                .compartment-cell:hover {
                    background: rgba(0, 230, 118, 0.3);
                    border-color: #00c853;
                    transform: scale(1.08);
                    box-shadow: 0 4px 16px rgba(0, 230, 118, 0.4);
                }
                
                .compartment-cell.selected {
                    background: linear-gradient(135deg, #00c853 0%, #059669 100%);
                    border-color: #00c853;
                    border-width: 4px;
                    box-shadow: 0 6px 20px rgba(0, 230, 118, 0.6);
                    transform: scale(1.05);
                }
                
                .compartment-cell.reserved {
                    background: rgba(181, 90, 90, 0.25);
                    border-color: #b55a5a;
                    border-width: 3px;
                    cursor: not-allowed;
                    opacity: 0.6;
                }
                
                .compartment-cell.reserved::after {
                    content: '✕';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-size: 2rem;
                    color: rgba(181, 90, 90, 0.8);
                    font-weight: bold;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                /* Compartment cell overlay on map - Enhanced visibility */
                .compartment-cell-overlay {
                    stroke-dasharray: 5, 5 !important;
                    stroke-width: 3 !important;
                    stroke: #00c853 !important;
                    stroke-opacity: 0.9 !important;
                    fill: #00c853 !important;
                    fill-opacity: 0.35 !important;
                    transition: all 0.3s ease;
                }
                
                .compartment-cell-overlay:hover {
                    stroke: #059669 !important;
                    stroke-width: 4 !important;
                    fill-opacity: 0.6 !important;
                }
            </style>
            <button type="button" class="modal-close-btn" onclick="closeReservationModal()" aria-label="Close modal">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h2 style="margin: 0; font-size: 1.8rem; background: linear-gradient(135deg, #00c853 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Reserve This Plot</h2>
            </div>
            
            <form id="reservationForm">
                <input type="hidden" id="plot_id" name="plot_id">
                <input type="hidden" id="compartment_id" name="compartment_id">
                
                <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid rgba(0, 230, 118, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <svg style="width: 20px; height: 20px; color: #00c853;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        <h3 style="margin: 0; font-size: 1.1rem;">Plot Information</h3>
                    </div>
                    <div id="plotInfo" style="color: var(--zinc-300);"></div>
                    
                    <!-- Compartment Selector (shown only for plots with grids) -->
                    <div id="compartmentSelector" style="display: none;">
                        <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 16px 0;">
                        <h4 style="margin: 0 0 8px 0; font-size: 0.95rem; color: var(--zinc-300);">Select Compartment:</h4>
                        <div id="compartmentGrid" class="compartment-grid"></div>
                        <p style="margin: 8px 0 0 0; font-size: 0.85rem; color: rgba(255,255,255,0.5); text-align: center;">
                            <span style="color: #b55a5a;">● Reserved</span> | 
                            <span style="color: #00c853;">● Available</span>
                        </p>
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: var(--zinc-200);">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Reservation Type *
                    </label>
                    <select name="reservation_type" id="reservation_type" required style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; font-size: 1rem; transition: all 0.2s ease;">
                        <option value="">Select Type</option>
                    </select>
                    <div id="priceDisplay" style="margin-top: 10px; padding: 12px; background: rgba(0, 230, 118, 0.05); border: 1px solid rgba(0, 230, 118, 0.2); border-radius: 8px; color: #00c853; font-weight: 600; display: none;"></div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: var(--zinc-200);">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Purpose *
                    </label>
                    <input type="text" name="purpose" required placeholder="e.g., Family burial plot" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; font-size: 1rem; transition: all 0.2s ease;">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: var(--zinc-200);">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Intended For *
                    </label>
                    <input type="text" name="intended_for" required placeholder="Name of deceased or family member" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; font-size: 1rem; transition: all 0.2s ease;">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: var(--zinc-200);">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Contact Number *
                    </label>
                    <input type="tel" name="contact_number" required placeholder="09XX XXX XXXX" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: white; font-size: 1rem; transition: all 0.2s ease;">
                </div>
                
                <div style="background: rgba(201, 168, 108, 0.1); border: 1px solid rgba(201, 168, 108, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <div style="display: flex; gap: 12px;">
                        <svg style="width: 24px; height: 24px; color: #c9a86c; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p style="margin: 0; font-size: 0.95rem; color: #c9a86c; line-height: 1.5;">
                            Your reservation will be pending until approved by the administrator. You can submit payment after approval.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" style="flex: 1; padding: 14px 24px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Submit Reservation
                    </button>
                    <button type="button" onclick="closeReservationModal()" style="padding: 14px 24px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s ease;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results Panel -->
    <div class="search-panel" id="searchResultsPanel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: rgba(0,0,0,0.5); border-radius: 8px;">
            <h4 style="margin: 0; font-size: 1rem; color: white;">Search Results</h4>
            <button onclick="document.getElementById('searchResultsPanel').classList.remove('active')" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; padding: 4px 8px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="searchResults" class="search-results"></div>
    </div>
    
    <!-- Map Legend -->
    <div class="map-legend">
        <h4 style="margin-bottom: 12px; font-size: 0.9rem;">Legend</h4>
        <div class="legend-item">
            <div class="legend-color" style="background: #5a87a8;"></div>
            <span style="font-size: 0.85rem;">Standard Burial</span>    
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #c9a86c;"></div>
            <span style="font-size: 0.85rem;">Premium/Fenced</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #5a9b6f;"></div>
            <span style="font-size: 0.85rem;">Available Plot</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #b55a5a;"></div>
            <span style="font-size: 0.85rem;">Search Result</span>
        </div>
    </div>
    
    <!-- Filter Panel -->
    <div class="filter-panel" id="filterPanel">
        <div class="filter-header">
            <h4>Filter Map</h4>
            <button class="filter-toggle-btn" onclick="toggleFilterPanel()" title="Collapse/Expand">
                <svg id="filterToggleIcon" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
        <div class="filter-content">
            <div class="filter-option" onclick="toggleFilter('burials')">
                <input type="checkbox" id="filterBurials" checked onchange="event.stopPropagation(); toggleFilter('burials')">
                <label for="filterBurials">Burial Records</label>
                <span class="filter-count" id="burialsCount">0</span>
            </div>
            <div class="filter-option" onclick="toggleFilter('available')">
                <input type="checkbox" id="filterAvailable" checked onchange="event.stopPropagation(); toggleFilter('available')">
                <label for="filterAvailable">Available Plots</label>
                <span class="filter-count" id="availableCount">0</span>
            </div>
        </div>
    </div>
    
    <!-- Rotation Control - Compact bar at top left, below filter panel -->
    <div class="rotation-panel" id="rotationPanel" style="position: absolute; top: 65px; left: 20px; z-index: 1000; background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(0, 230, 118, 0.15); border-radius: 10px; padding: 6px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4); transition: all 0.3s ease; display: flex; align-items: center; gap: 4px;">
        <button onclick="rotateMap(-15)" title="Rotate 15° Left" style="background: rgba(0, 230, 118, 0.2); border: none; border-radius: 6px; padding: 6px; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
            <svg style="width: 16px; height: 16px; transform: rotate(180deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>
        <button onclick="resetRotation()" title="Reset to North" style="background: linear-gradient(135deg, #00c853 0%, #059669 100%); border: none; border-radius: 6px; padding: 6px 8px; cursor: pointer; color: white; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 3px; transition: all 0.3s ease;">
            <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
            N
        </button>
        <button onclick="rotateMap(15)" title="Rotate 15° Right" style="background: rgba(0, 230, 118, 0.2); border: none; border-radius: 6px; padding: 6px; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>
        <span id="bearingDisplay" style="font-size: 0.7rem; color: rgba(255, 255, 255, 0.6); padding: 0 6px; white-space: nowrap;">0°</span>
    </div>
    
    <!-- AI Assistant Toggle -->
    <button class="chat-toggle" id="chatToggle" onclick="toggleChat()">
        <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant" style="width: 40px; height: 40px;">
    </button>
    
    <!-- AI Assistant Chat -->
    <div class="chat-container" id="chatContainer" style="display: none;">
        <div class="chat-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant" style="width: 36px; height: 36px;">
                <div>
                    <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600;">AI Assistant</h4>
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.6);">Always here to help</p>
                </div>
            </div>
            <button onclick="toggleChat()" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-message assistant" style="display: flex; gap: 10px; align-items: start;">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI" style="width: 28px; height: 28px; flex-shrink: 0; margin-top: 2px;">
                <div>
                    Hello! I'm your AI assistant. I can help you find burial locations, provide directions, or answer questions about the cemetery. How can I assist you today?
                </div>
            </div>
        </div>
        <div class="chat-input-container">
            <input type="text" id="chatInput" class="chat-input" placeholder="Ask me anything..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendMessage()" class="btn-primary" style="padding: 10px 16px;">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Scripts -->
    <!-- Load Leaflet first -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Then Leaflet Rotate -->
    <script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate-src.js"></script>
    <!-- Then other Leaflet plugins -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <!-- Optional: Fullscreen control -->
    <script>
        // Load fullscreen control if available
        var fullscreenScript = document.createElement('script');
        fullscreenScript.src = 'https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.js';
        fullscreenScript.onerror = function() {
            console.log('Fullscreen control not available');
        };
        document.head.appendChild(fullscreenScript);
    </script>
    
    <!-- Leaflet Routing Machine -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <!-- Theme utilities -->
    <script src="../assets/js/theme.js"></script>
    
    <!-- Visitor map functionality -->
    <script src="../assets/js/visitor.js?v=5"></script>
</body>
</html>
