<?php
session_start();
require_once 'includes/header.php';
?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'includes/topbar.php'; ?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        /* Full-height map layout under the visitor topbar */
        html, body, .admin-layout, .admin-main { height: 100vh !important; overflow: hidden; }
        .admin-main { padding: 0 !important; }
        #map { position: absolute; top: 80px; left: 0; right: 0; bottom: 0; width: 100%; height: auto; }
        .rotation-panel {
            top: 100px !important;
            left: auto !important;
            right: 20px !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
        }
        .leaflet-top.leaflet-left { top: 60px !important; right: 20px !important; left: auto !important; }
        .leaflet-top.leaflet-right { top: 132px !important; right: 20px !important; left: auto !important; }
        .leaflet-control-rotate { display: none !important; }
        .leaflet-control-layers {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }
        .leaflet-popup-content-wrapper { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(16, 185, 129, 0.15); }
        .leaflet-popup-content { color: #0f172a; margin: 16px; }
        .leaflet-popup-tip { background: rgba(255, 255, 255, 0.95); }
        
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
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            color: #0f172a !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5) !important;
            z-index: 999 !important;
        }
        
        .leaflet-routing-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .leaflet-routing-container::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.05);
            border-radius: 3px;
        }
        
        .leaflet-routing-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .leaflet-routing-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Hide routing geocoder inputs */
        .leaflet-routing-geocoders {
            display: none !important;
        }
        
        .leaflet-routing-container h2,
        .leaflet-routing-container h3 {
            color: #0f172a !important;
            font-size: 1rem !important;
            margin: 0 0 12px 0 !important;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .leaflet-routing-alt {
            background: rgba(15, 23, 42, 0.05) !important;
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 12px !important;
            padding: 12px !important;
            margin: 8px 0 !important;
            color: #0f172a !important;
        }
        
        .leaflet-routing-icon {
            filter: invert(1) !important;
        }
        
        .leaflet-routing-geocoder {
            display: none !important;
        }
        
        /* Minimize routing panel button */
        .leaflet-routing-collapse-btn {
            background: rgba(16, 185, 129, 0.3) !important;
            border-radius: 8px !important;
            color: #0f172a !important;
            padding: 4px 8px !important;
            cursor: pointer !important;
        }
        
        .leaflet-routing-collapse-btn:hover {
            background: rgba(16, 185, 129, 0.5) !important;
        }
        
        /* Widget Organization - Prevent Overlaps */
        /* TOP BAR - Navigation buttons centered */
        .top-bar {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.15);
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
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .toggle-nav-btn:hover {
            background: rgba(16, 185, 129, 0.3);
        }
        
        /* SEARCH BAR - Top left, compact */
        .search-bar-container {
            position: absolute;
            top: 90px;
            left: 20px;
            right: auto;
            transform: none;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 14px;
            padding: 8px 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 260px;
            max-width: 360px;
            width: auto;
            transition: all 0.3s ease;
        }
        
        .search-bar-container:focus-within {
            border-color: rgba(16, 185, 129, 0.6);
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.2);
        }
        
        .search-bar-container.collapsed {
            padding: 8px 12px;
            min-width: auto;
            border-color: rgba(16, 185, 129, 0.15);
        }
        
        .search-bar-container.collapsed > *:not(.toggle-search-btn) {
            display: none;
        }
        
        .toggle-search-btn {
            width: 32px;
            height: 32px;
            background: #f0fdf4;
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 8px;
            padding: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .toggle-search-btn:hover {
            background: #d1fae5;
            color: #047857;
        }
        
        .search-bar-container input {
            flex: 1;
            padding: 8px 12px;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 8px;
            color: #0f172a;
            font-size: 0.9rem;
            min-width: 0;
            transition: all 0.3s ease;
        }
        
        .search-bar-container input:focus {
            outline: none;
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(15, 23, 42, 0.08);
        }
        
        .search-bar-container input::placeholder {
            color: rgba(15, 23, 42, 0.5);
        }
        
        .search-bar-container button {
            padding: 10px 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        /* Leaflet Controls - Right side stack */
        .leaflet-top.leaflet-left {
            top: 60px !important;
            right: 20px !important;
            left: auto !important;
        }

        .leaflet-top.leaflet-right {
            top: 132px !important;
            right: 20px !important;
            left: auto !important;
        }

        .leaflet-control-layers {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }

        .leaflet-control-zoom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
            margin-top: 0 !important;
        }
        
        .leaflet-control-zoom a {
            background: #ffffff !important;
            color: #10b981 !important;
            width: 38px !important;
            height: 38px !important;
            line-height: 38px !important;
            border: 1px solid #d1fae5 !important;
            transition: all 0.2s ease !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
        }

        .leaflet-control-zoom a:hover {
            background: #10b981 !important;
            color: #ffffff !important;
            border-color: #10b981 !important;
        }

        .leaflet-control-zoom a:first-child {
            border-radius: 10px 10px 0 0 !important;
            border-bottom: 1px solid #d1fae5 !important;
        }

        .leaflet-control-zoom a:last-child {
            border-radius: 0 0 10px 10px !important;
        }
        
        /* Rotation control styling - Position under zoom controls */
        .leaflet-control-rotate {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            margin-top: 8px !important; /* Space below zoom control */
        }
        
        .leaflet-control-rotate a {
            color: #0f172a !important;
            background: rgba(16, 185, 129, 0.2) !important;
            border-radius: 6px !important;
            transition: all 0.3s ease !important;
            border: none !important;
        }
        
        .leaflet-control-rotate a:hover {
            background: rgba(16, 185, 129, 0.4) !important;
            color: #0f172a !important;
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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(16, 185, 129, 0.2);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            background: rgba(15, 23, 42, 0.05);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .filter-option:hover {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }
        
        .filter-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #10b981;
        }
        
        .filter-option label {
            cursor: pointer;
            color: #0f172a;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 500;
        }
        
        .filter-count {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #0f172a;
            margin-left: 4px;
        }
        
        /* SEARCH RESULTS - Top left, below search bar */
        .search-panel {
            position: absolute;
            top: 150px;
            left: 20px;
            right: auto;
            z-index: 1000;
            width: 340px;
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 170px);
            overflow-y: auto;
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        .search-panel.active {
            display: block;
        }
        
        .search-panel::-webkit-scrollbar {
            width: 6px;
        }
        
        .search-panel::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.05);
            border-radius: 3px;
        }
        
        .search-panel::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .search-panel::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* MAP LEGEND - Bottom left */
        .map-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.15);
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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 18px;
            min-width: 180px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 1px solid rgba(0, 0, 0, 0.08);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.5);
            transition: all 0.3s ease;
        }
        
        .chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 40px rgba(16, 185, 129, 0.7);
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
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.15);
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
            left: calc(var(--sidebar-width) + 20px) !important;
            right: auto !important;
            max-width: 280px !important;
            max-height: calc(100vh - 320px) !important;
            overflow-y: auto !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            color: #0f172a !important;
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
                width: 34px !important;
                height: 34px !important;
                line-height: 34px !important;
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

        /* =========================================================
           MODERN AI CHAT UI
           ========================================================= */
        #chatToggle {
            position: absolute;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: 2px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4), 0 2px 8px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 999;
            animation: float 4s ease-in-out infinite;
        }

        #chatToggle:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.5);
        }

        #chatToggle img {
            width: 34px;
            height: 34px;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        #chatContainer {
            position: absolute;
            bottom: 96px;
            right: 24px;
            z-index: 1000;
            width: 400px;
            max-width: calc(100vw - 48px);
            max-height: calc(100vh - 180px);
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border: 1px solid rgba(16, 185, 129, 0.18);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.12), 0 8px 24px rgba(16, 185, 129, 0.1);
            font-family: 'Poppins', sans-serif;
        }

        #chatContainer .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
        }

        #chatContainer .chat-header .chat-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #chatContainer .chat-header img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px;
            animation: none;
        }

        #chatContainer .chat-header h4 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            color: #ffffff;
        }

        #chatContainer .chat-header p {
            margin: 0;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.85);
        }

        #chatContainer .chat-header button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff;
            cursor: pointer;
            padding: 8px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        #chatContainer .chat-header button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        #chatContainer .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-height: 320px;
            max-height: 420px;
            background: #f8fafc;
        }

        #chatContainer .chat-message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 100%;
            animation: messageIn 0.3s ease forwards;
        }

        #chatContainer .chat-message.user {
            flex-direction: row-reverse;
            align-self: flex-end;
        }

        #chatContainer .chat-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            flex-shrink: 0;
            object-fit: cover;
            background: #e2e8f0;
        }

        #chatContainer .chat-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.5;
            word-wrap: break-word;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        #chatContainer .chat-message.assistant .chat-bubble {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-top-left-radius: 4px;
        }

        #chatContainer .chat-message.user .chat-bubble {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border-top-right-radius: 4px;
        }

        #chatContainer .chat-message.typing .chat-bubble {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 14px 18px;
        }

        .typing-dots {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .typing-dots span {
            width: 7px;
            height: 7px;
            background: #94a3b8;
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }

        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        @keyframes messageIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        #chatContainer .chat-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }

        #chatContainer .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.9rem;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
        }

        #chatContainer .chat-input:focus {
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        #chatContainer .chat-input::placeholder {
            color: #94a3b8;
        }

        #chatContainer .chat-input-container button {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            flex-shrink: 0;
        }

        #chatContainer .chat-input-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }

        #chatContainer .chat-input-container button:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 640px) {
            #chatToggle {
                width: 52px;
                height: 52px;
                bottom: 18px;
                right: 18px;
            }

            #chatToggle img {
                width: 28px;
                height: 28px;
            }

            #chatContainer {
                bottom: 82px;
                right: 10px;
                left: 10px;
                width: auto;
                max-width: none;
                border-radius: 20px;
            }

            #chatContainer .chat-messages {
                min-height: 220px;
                max-height: 300px;
                padding: 14px;
            }

            #chatContainer .chat-bubble {
                font-size: 0.85rem;
                padding: 10px 14px;
            }
        }
    </style>
    <!-- Map Container -->
    <div id="map"></div>
    
    <!-- Map Controls -->
    
    <!-- Search Bar - Separate centered div -->
    <div class="search-bar-container" id="searchBar">
        <button class="toggle-search-btn" onclick="toggleSearchBar()" title="Toggle Search">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
        <input type="text" id="searchInput" placeholder="Search by plot number...">
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); backdrop-filter: blur(10px); z-index: 3000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); text-align: center;">
            <div id="successIcon" style="width: 80px; height: 80px; background: linear-gradient(135deg, #5a9b6f 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <svg style="width: 48px; height: 48px; color: #0f172a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 id="successTitle" style="margin: 0 0 16px 0; font-size: 1.8rem; color: #5a9b6f;">Reservation Successful!</h2>
            <div id="successMessage" style="color: rgba(15,23,42,0.8); line-height: 1.8; margin-bottom: 24px;"></div>
            <button onclick="closeSuccessModal()" style="padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #0f172a; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;">
                Got it!
            </button>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div id="reservationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 24px; padding: 36px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 80px rgba(0,0,0,0.6); position: relative;">
            <style>
                #reservationForm input:focus,
                #reservationForm select:focus,
                #reservationForm textarea:focus {
                    outline: none;
                    border-color: #10b981 !important;
                    background: rgba(15,23,42,0.08) !important;
                    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2) !important;
                }
                
                .modal-close-btn {
                    position: absolute;
                    top: 18px;
                    right: 18px;
                    background: rgba(15,23,42,0.05);
                    border: 1px solid rgba(15,23,42,0.1);
                    border-radius: 10px;
                    padding: 8px;
                    cursor: pointer;
                    color: rgba(15,23,42,0.6);
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
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%230f172a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: right 12px center;
                    background-size: 20px;
                    padding-right: 40px !important;
                    cursor: pointer;
                }
                
                #reservationForm select option {
                    background: #ffffff;
                    color: #0f172a;
                    padding: 12px;
                }
                
                #reservationForm button[type="submit"]:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
                }
                
                #reservationForm button[type="button"]:hover {
                    background: rgba(15,23,42,0.1);
                    border-color: rgba(15,23,42,0.2);
                }
                
                .compartment-grid {
                    display: grid;
                    gap: 8px;
                    margin: 16px 0;
                    padding: 16px;
                    background: rgba(15,23,42,0.05);
                    border-radius: 12px;
                }
                
                .compartment-cell {
                    padding: 16px 12px;
                    background: rgba(15,23,42,0.08);
                    border: 3px solid rgba(16, 185, 129, 0.4);
                    border-radius: 10px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-align: center;
                    font-weight: 600;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                    position: relative;
                }
                
                .compartment-cell:hover {
                    background: rgba(16, 185, 129, 0.3);
                    border-color: #10b981;
                    transform: scale(1.08);
                    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
                }
                
                .compartment-cell.selected {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    border-color: #10b981;
                    border-width: 4px;
                    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
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
                    stroke: #10b981 !important;
                    stroke-opacity: 0.9 !important;
                    fill: #10b981 !important;
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
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h2 style="margin: 0; font-size: 1.8rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Reserve This Plot</h2>
            </div>
            
            <form id="reservationForm">
                <input type="hidden" id="plot_id" name="plot_id">
                <input type="hidden" id="compartment_id" name="compartment_id">
                
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <svg style="width: 20px; height: 20px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        <h3 style="margin: 0; font-size: 1.1rem;">Plot Information</h3>
                    </div>
                    <div id="plotInfo" style="color: #475569;"></div>
                    
                    <!-- Compartment Selector (shown only for plots with grids) -->
                    <div id="compartmentSelector" style="display: none;">
                        <hr style="border: none; border-top: 1px solid rgba(15,23,42,0.1); margin: 16px 0;">
                        <h4 style="margin: 0 0 8px 0; font-size: 0.95rem; color: #475569;">Select Compartment:</h4>
                        <div id="compartmentGrid" class="compartment-grid"></div>
                        <p style="margin: 8px 0 0 0; font-size: 0.85rem; color: rgba(15,23,42,0.5); text-align: center;">
                            <span style="color: #b55a5a;">● Reserved</span> | 
                            <span style="color: #10b981;">● Available</span>
                        </p>
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: #334155;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Reservation Type *
                    </label>
                    <select name="reservation_type" id="reservation_type" required style="width: 100%; padding: 14px 16px; background: rgba(15,23,42,0.05); border: 1px solid rgba(15,23,42,0.1); border-radius: 10px; color: #0f172a; font-size: 1rem; transition: all 0.2s ease;">
                        <option value="">Select Type</option>
                    </select>
                    <div id="priceDisplay" style="margin-top: 10px; padding: 12px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 8px; color: #10b981; font-weight: 600; display: none;"></div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: #334155;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Purpose *
                    </label>
                    <input type="text" name="purpose" required placeholder="e.g., Family burial plot" style="width: 100%; padding: 14px 16px; background: rgba(15,23,42,0.05); border: 1px solid rgba(15,23,42,0.1); border-radius: 10px; color: #0f172a; font-size: 1rem; transition: all 0.2s ease;">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: #334155;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Intended For *
                    </label>
                    <input type="text" name="intended_for" required placeholder="Name of deceased or family member" style="width: 100%; padding: 14px 16px; background: rgba(15,23,42,0.05); border: 1px solid rgba(15,23,42,0.1); border-radius: 10px; color: #0f172a; font-size: 1rem; transition: all 0.2s ease;">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; color: #334155;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Contact Number *
                    </label>
                    <input type="tel" name="contact_number" required placeholder="09XX XXX XXXX" style="width: 100%; padding: 14px 16px; background: rgba(15,23,42,0.05); border: 1px solid rgba(15,23,42,0.1); border-radius: 10px; color: #0f172a; font-size: 1rem; transition: all 0.2s ease;">
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
                    <button type="submit" style="flex: 1; padding: 14px 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #0f172a; border: none; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Submit Reservation
                    </button>
                    <button type="button" onclick="closeReservationModal()" style="padding: 14px 24px; background: rgba(15,23,42,0.05); color: #0f172a; border: 1px solid rgba(15,23,42,0.1); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.2s ease;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Search Results Panel -->
    <div class="search-panel" id="searchResultsPanel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 12px; background: rgba(15,23,42,0.08); border-radius: 8px;">
            <h4 style="margin: 0; font-size: 1rem; color: #0f172a;">Search Results</h4>
            <button onclick="document.getElementById('searchResultsPanel').classList.remove('active')" style="background: rgba(15,23,42,0.1); border: 1px solid rgba(15,23,42,0.2); border-radius: 6px; padding: 4px 8px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(15,23,42,0.2)'" onmouseout="this.style.background='rgba(15,23,42,0.1)'">
                <svg style="width: 16px; height: 16px; color: #0f172a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="legend-color" style="background: #5a9b6f;"></div>
            <span style="font-size: 0.85rem;">Available Plot</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #c9a86c;"></div>
            <span style="font-size: 0.85rem;">Pending Reservation</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #b55a5a;"></div>
            <span style="font-size: 0.85rem;">Reserved Plot</span>
        </div>
    </div>
    

    
    <!-- Rotation Control - Compact bar on the right -->
    <div class="rotation-panel" id="rotationPanel" style="position: absolute; top: 20px; left: 20px; z-index: 1000; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(16, 185, 129, 0.15); border-radius: 10px; padding: 6px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; display: flex; align-items: center; gap: 4px;">
        <button onclick="rotateMap(-15)" title="Rotate 15° Left" style="width: 28px; height: 28px; background: #f0fdf4; border: none; border-radius: 6px; padding: 0; cursor: pointer; color: #047857; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">L</button>
        <button onclick="resetRotation()" title="Reset to North" style="width: 28px; height: 28px; background: #10b981; border: none; border-radius: 6px; padding: 0; cursor: pointer; color: #ffffff; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">N</button>
        <button onclick="rotateMap(15)" title="Rotate 15° Right" style="width: 28px; height: 28px; background: #f0fdf4; border: none; border-radius: 6px; padding: 0; cursor: pointer; color: #047857; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">R</button>
        <span id="bearingDisplay" style="font-size: 0.7rem; color: #64748b; padding: 0 6px; white-space: nowrap;">0°</span>
    </div>
    
    <button id="chatToggle" onclick="toggleChat()" aria-label="Open AI Assistant">
        <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant">
    </button>

    <!-- AI Assistant Chat -->
    <div id="chatContainer" style="display: none;">
        <div class="chat-header">
            <div class="chat-title">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant">
                <div>
                    <h4>AI Assistant</h4>
                    <p>Always here to help</p>
                </div>
            </div>
            <button onclick="toggleChat()" aria-label="Close chat">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-message assistant">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI" class="chat-avatar">
                <div class="chat-bubble">Hello! I'm your AI assistant. I can help you find burial locations, provide directions, or answer questions about the cemetery. How can I assist you today?</div>
            </div>
        </div>
        <div class="chat-input-container">
            <input type="text" id="chatInput" class="chat-input" placeholder="Ask me anything..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendMessage()" aria-label="Send message">
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
    
    <!-- Visitor map functionality -->
    <script>AVAILABLE_PLOTS_ONLY = true;</script>
    <script src="../assets/js/visitor.js?v=8"></script>
    </main>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
