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
 top: 20px !important;
 right: 20px !important;
 left: auto !important;
 box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08) !important;
 }
 .leaflet-top.leaflet-left { display: none !important; }
 .leaflet-top.leaflet-right { display: none !important; }
 .leaflet-control-attribution { display: none !important; }
 .leaflet-control-rotate { display: none !important; }
 .leaflet-control-layers {
 background: rgba(255, 255, 255, 0.95) !important;
 border: 1px solid rgba(16, 185, 129, 0.15) !important;
 border-radius: 12px !important;
 box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
 }
 .leaflet-popup-content-wrapper {
 background: var(--surface);
 backdrop-filter: blur(20px);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 16px !important;
 box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12) !important;
 }
 .leaflet-popup-content { color: var(--text-strong); margin: 16px; font-family: 'Poppins', sans-serif; }
 .leaflet-popup-tip { background: var(--surface); }
 .leaflet-popup-close-button { color: #94a3b8 !important; font-size: 20px !important; padding: 6px 8px !important; }
 
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

 /* Google Maps style route lines — layered casing + main + inner */
 .route-casing, .route-line-fallback {
 line-cap: round !important;
 line-join: round !important;
 }
 .route-main {
 line-cap: round !important;
 line-join: round !important;
 filter: drop-shadow(0 2px 6px rgba(66, 133, 244, 0.5));
 }
 .route-inner {
 line-cap: round !important;
 line-join: round !important;
 }

 /* Pulsing rings for user location and destination markers */
 @keyframes routePulseRing {
 0% { transform: scale(0.5); opacity: 1; }
 100% { transform: scale(2.2); opacity: 0; }
 }

 /* Direction arrows along the route */
 .route-arrow {
 background: transparent !important;
 border: none !important;
 }

 /* Floating route info badge */
 .route-info-badge {
 background: transparent !important;
 border: none !important;
 }

 .leaflet-routing-container {
 position: fixed !important;
 top: auto !important;
 bottom: 140px !important;
 left: calc(var(--sidebar-width) + 20px) !important;
 right: auto !important;
 max-width: 280px !important;
 max-height: 300px !important;
 overflow-y: auto !important;
 background: rgba(255, 255, 255, 0.98) !important;
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2) !important;
 border-radius: 18px !important;
 padding: 18px !important;
 color: var(--text-strong) !important;
 box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(16, 185, 129, 0.08) !important;
 z-index: 998 !important;
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
 color: var(--text-strong) !important;
 font-size: 1rem !important;
 margin: 0 0 12px 0 !important;
 color: #059669;
 }
 
 .leaflet-routing-alt {
 background: rgba(15, 23, 42, 0.05) !important;
 border: 1px solid rgba(16, 185, 129, 0.15) !important;
 border-radius: 12px !important;
 padding: 12px !important;
 margin: 8px 0 !important;
 color: var(--text-strong) !important;
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
 color: var(--text-strong) !important;
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
 color: var(--text-strong);
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
 background: rgba(255, 255, 255, 0.98);
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 16px;
 padding: 10px 14px;
 box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08);
 display: flex;
 align-items: center;
 gap: 10px;
 min-width: 280px;
 max-width: 380px;
 width: auto;
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
 }
 
 .search-bar-container:focus-within {
 border-color: rgba(16, 185, 129, 0.6);
 box-shadow: 0 12px 40px rgba(16, 185, 129, 0.2), 0 0 0 4px rgba(16, 185, 129, 0.08);
 transform: translateY(-1px);
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
 padding: 10px 14px;
 background: rgba(15, 23, 42, 0.04);
 border: 1px solid rgba(15, 23, 42, 0.06);
 border-radius: 12px;
 color: var(--text-strong);
 font-size: 0.9rem;
 font-family: 'Poppins', sans-serif;
 min-width: 0;
 transition: all 0.3s ease;
 }
 
 .search-bar-container input:focus {
 outline: none;
 border-color: rgba(16, 185, 129, 0.5);
 background: rgba(16, 185, 129, 0.04);
 }
 
 .search-bar-container input::placeholder {
 color: rgba(15, 23, 42, 0.45);
 font-weight: 400;
 }
 
 .search-bar-container button {
 padding: 10px 16px;
 background: #059669;
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
 
 /* Leaflet Controls - Hidden, using custom controls */
 .leaflet-top.leaflet-left { display: none !important; }
 .leaflet-top.leaflet-right { display: none !important; }

 .leaflet-control-layers {
 background: rgba(255, 255, 255, 0.95) !important;
 border: 1px solid rgba(16, 185, 129, 0.15) !important;
 border-radius: 8px !important;
 box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
 }

 .leaflet-control-zoom {
 background: rgba(255, 255, 255, 0.98) !important;
 backdrop-filter: blur(20px);
 border: 1px solid rgba(16, 185, 129, 0.2) !important;
 border-radius: 14px !important;
 box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08) !important;
 margin-top: 0 !important;
 overflow: hidden;
 }
 
 .leaflet-control-zoom a {
 background: var(--surface) !important;
 color: #10b981 !important;
 width: 40px !important;
 height: 40px !important;
 line-height: 40px !important;
 border: none !important;
 border-bottom: 1px solid var(--border-subtle) !important;
 transition: all 0.2s ease !important;
 font-size: 1.2rem !important;
 font-weight: 700 !important;
 }

 .leaflet-control-zoom a:hover {
 background: #059669 !important;
 color: #ffffff !important;
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
 color: var(--text-strong) !important;
 background: rgba(16, 185, 129, 0.2) !important;
 border-radius: 6px !important;
 transition: all 0.3s ease !important;
 border: none !important;
 }
 
 .leaflet-control-rotate a:hover {
 background: rgba(16, 185, 129, 0.4) !important;
 color: var(--text-strong) !important;
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
 
 /* FILTER PANEL - Below search bar */
 .filter-panel {
 position: absolute;
 top: 150px;
 left: 20px;
 z-index: 1000;
 background: rgba(255, 255, 255, 0.98);
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 18px;
 padding: 14px 20px;
 box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08);
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
 color: #059669;
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
 background: rgba(15, 23, 42, 0.04);
 border: 1px solid rgba(15, 23, 42, 0.06);
 border-radius: 12px;
 cursor: pointer;
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
 white-space: nowrap;
 }
 
 .filter-option:hover {
 background: rgba(16, 185, 129, 0.1);
 border-color: rgba(16, 185, 129, 0.3);
 transform: translateY(-2px);
 box-shadow: 0 4px 12px rgba(16, 185, 129, 0.12);
 }
 
 .filter-option input[type="checkbox"] {
 width: 18px;
 height: 18px;
 cursor: pointer;
 accent-color: #10b981;
 border-radius: 4px;
 }
 
 .filter-option label {
 cursor: pointer;
 color: var(--text-strong);
 font-size: 0.9rem;
 margin: 0;
 font-weight: 500;
 }
 
 .filter-count {
 background: #059669;
 padding: 3px 10px;
 border-radius: 999px;
 font-size: 0.7rem;
 font-weight: 700;
 color: #ffffff;
 margin-left: 4px;
 box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
 }
 
 /* SEARCH RESULTS - Below filter panel */
 .search-panel {
 position: absolute;
 top: 210px;
 left: 20px;
 right: auto;
 z-index: 1000;
 width: 340px;
 max-width: calc(100vw - 40px);
 max-height: calc(100vh - 250px);
 overflow-y: auto;
 display: none;
 background: rgba(255, 255, 255, 0.98);
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 18px;
 padding: 14px;
 box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08);
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
 background: rgba(255, 255, 255, 0.98);
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2);
 border-radius: 18px;
 padding: 18px 20px;
 min-width: 200px;
 box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08);
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
 }

 .map-legend:hover {
 transform: translateY(-2px);
 box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(16, 185, 129, 0.12);
 }

 .legend-item {
 display: flex;
 align-items: center;
 gap: 12px;
 margin-bottom: 10px;
 color: var(--text-strong);
 font-size: 0.85rem;
 font-weight: 500;
 }

 .legend-item:last-child {
 margin-bottom: 0;
 }

 .legend-color {
 width: 18px;
 height: 18px;
 border-radius: 50%;
 flex-shrink: 0;
 border: 2px solid rgba(255, 255, 255, 0.8);
 box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
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
 background: #059669;
 border: 3px solid rgba(255, 255, 255, 0.8);
 cursor: pointer;
 display: flex;
 align-items: center;
 justify-content: center;
 box-shadow: 0 12px 36px rgba(16, 185, 129, 0.45), 0 4px 12px rgba(0, 0, 0, 0.1);
 transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
 }
 
 .chat-toggle:hover {
 transform: scale(1.1) translateY(-2px);
 box-shadow: 0 16px 48px rgba(16, 185, 129, 0.6), 0 6px 16px rgba(0, 0, 0, 0.12);
 }
 
 .chat-toggle:active {
 transform: scale(1.05);
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
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.18);
 border-radius: 24px;
 overflow: hidden;
 box-shadow: 0 24px 60px rgba(0, 0, 0, 0.15), 0 8px 24px rgba(16, 185, 129, 0.1);
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
 
 /* ROUTING CONTAINER - Left side, above legend */
 .leaflet-routing-container {
 position: fixed !important;
 top: auto !important;
 bottom: 140px !important;
 left: calc(var(--sidebar-width) + 20px) !important;
 right: auto !important;
 max-width: 280px !important;
 max-height: calc(100vh - 300px) !important;
 overflow-y: auto !important;
 background: rgba(255, 255, 255, 0.98) !important;
 backdrop-filter: blur(24px);
 border: 1px solid rgba(16, 185, 129, 0.2) !important;
 border-radius: 18px !important;
 padding: 18px !important;
 color: var(--text-strong) !important;
 box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(16, 185, 129, 0.08) !important;
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
 
 /* Rotation Panel - Right side on mobile */
 .rotation-panel {
 top: 10px !important;
 left: auto !important;
 right: 10px !important;
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

 @media (max-width: 480px) {
 .chat-toggle {
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
 width: 46px;
 height: 46px;
 border-radius: 50%;
 background: #059669;
 border: none;
 cursor: pointer;
 display: flex;
 align-items: center;
 justify-content: center;
 z-index: 999;
 transition: background 0.15s ease;
 }

 #chatToggle:hover {
 background: #047857;
 }

 #chatToggle img {
 width: 26px;
 height: 26px;
 }

 /* Docked assistant panel — right edge, under the header */
 #chatContainer {
 position: fixed;
 top: 60px;
 right: 0;
 bottom: 0;
 z-index: 1002;
 width: 360px;
 max-width: 100vw;
 display: flex;
 flex-direction: column;
 background: var(--surface);
 border-left: 1px solid var(--border-subtle);
 box-shadow: -12px 0 32px rgba(15, 23, 42, 0.08);
 font-family: 'Poppins', sans-serif;
 animation: chatPanelIn 0.22s ease;
 }

 @keyframes chatPanelIn {
 from { transform: translateX(24px); opacity: 0; }
 to { transform: translateX(0); opacity: 1; }
 }

 #chatContainer .chat-header {
 display: flex;
 align-items: center;
 justify-content: space-between;
 padding: 14px 16px;
 background: var(--surface);
 border-bottom: 1px solid var(--border-subtle);
 color: var(--text-strong);
 flex-shrink: 0;
 }

 #chatContainer .chat-header .chat-title {
 display: flex;
 align-items: center;
 gap: 11px;
 min-width: 0;
 }

 #chatContainer .chat-header img {
 width: 36px;
 height: 36px;
 border-radius: 10px;
 background: #ecfdf5;
 padding: 5px;
 animation: none;
 flex-shrink: 0;
 }

 #chatContainer .chat-header h4 {
 margin: 0;
 font-size: 0.9rem;
 font-weight: 600;
 color: var(--text-strong);
 letter-spacing: -0.01em;
 }

 #chatContainer .chat-header p {
 margin: 2px 0 0;
 font-size: 0.7rem;
 color: #94a3b8;
 display: flex;
 align-items: center;
 gap: 5px;
 }

 #chatContainer .chat-header p::before {
 content: '';
 width: 6px;
 height: 6px;
 border-radius: 50%;
 background: #22c55e;
 flex-shrink: 0;
 }

 #chatContainer .chat-header button,
 #chatContainer .chat-icon-btn {
 width: 30px;
 height: 30px;
 background: transparent;
 border: none;
 color: #64748b;
 cursor: pointer;
 padding: 0;
 border-radius: 6px;
 display: flex;
 align-items: center;
 justify-content: center;
 transition: background 0.15s ease, color 0.15s ease;
 flex-shrink: 0;
 }

 #chatContainer .chat-header button:hover,
 #chatContainer .chat-icon-btn:hover {
 background: var(--bg-subtle);
 color: var(--text-strong);
 }

 #chatContainer .chat-messages {
 flex: 1;
 overflow-y: auto;
 padding: 18px 16px;
 display: flex;
 flex-direction: column;
 gap: 14px;
 background: var(--bg-subtle);
 }

 #chatContainer .chat-messages::-webkit-scrollbar { width: 5px; }
 #chatContainer .chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
 html[data-theme="dark"] #chatContainer .chat-messages::-webkit-scrollbar-thumb { background: #334155; }

 #chatContainer .chat-message {
 display: flex;
 align-items: flex-start;
 gap: 9px;
 max-width: 88%;
 animation: messageIn 0.3s ease forwards;
 }

 #chatContainer .chat-message.user {
 flex-direction: row-reverse;
 align-self: flex-end;
 }

 #chatContainer .chat-avatar {
 width: 28px;
 height: 28px;
 border-radius: 8px;
 flex-shrink: 0;
 object-fit: cover;
 background: #ecfdf5;
 padding: 3px;
 }

 #chatContainer .chat-bubble {
 padding: 10px 14px;
 border-radius: 14px;
 font-size: 0.83rem;
 line-height: 1.55;
 word-wrap: break-word;
 }

 #chatContainer .chat-message.assistant .chat-bubble {
 background: var(--surface);
 color: var(--text-strong);
 border: 1px solid var(--glass-border);
 border-top-left-radius: 4px;
 }

 #chatContainer .chat-message.user .chat-bubble {
 background: #059669;
 color: #ffffff;
 border-top-right-radius: 4px;
 }

 #chatContainer .chat-message.typing .chat-bubble {
 background: var(--surface);
 border: 1px solid var(--glass-border);
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
 gap: 8px;
 padding: 12px 14px;
 background: var(--surface);
 border-top: 1px solid var(--border-subtle);
 flex-shrink: 0;
 }

 #chatContainer .chat-input {
 flex: 1;
 min-width: 0;
 height: 40px;
 padding: 0 14px;
 border: 1px solid var(--glass-border);
 border-radius: 10px;
 font-size: 0.83rem;
 color: var(--text-strong);
 background: var(--bg-subtle);
 outline: none;
 transition: border-color 0.15s ease, box-shadow 0.15s ease;
 font-family: 'Poppins', sans-serif;
 }

 #chatContainer .chat-input:focus {
 border-color: #059669;
 background: var(--surface);
 box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
 }

 #chatContainer .chat-input::placeholder {
 color: #94a3b8;
 }

 #chatContainer .chat-input-container button {
 width: 38px;
 height: 38px;
 border-radius: 10px;
 border: none;
 background: #059669;
 color: #ffffff;
 cursor: pointer;
 display: flex;
 align-items: center;
 justify-content: center;
 transition: background 0.15s ease;
 flex-shrink: 0;
 }

 #chatContainer .chat-input-container button:hover {
 background: #047857;
 }

 #chatContainer .chat-input-container button:disabled {
 background: #cbd5e1;
 cursor: not-allowed;
 }

 /* Secondary (voice) button in the input row */
 #chatContainer .chat-input-container .chat-voice-btn {
 background: transparent;
 border: 1px solid var(--glass-border);
 color: #64748b;
 }

 #chatContainer .chat-input-container .chat-voice-btn:hover {
 background: var(--bg-subtle);
 color: var(--text-strong);
 }

 @media (max-width: 640px) {
 #chatToggle {
 bottom: 18px;
 right: 18px;
 }

 #chatContainer {
 top: 60px;
 right: 0;
 left: 0;
 bottom: 0;
 width: auto;
 max-width: none;
 border-left: none;
 }

 #chatContainer .chat-messages {
 padding: 14px;
 }

 #chatContainer .chat-bubble {
 font-size: 0.82rem;
 }
 }

 /* Row/column tag shown on occupied grid compartments */
 .cell-rc-label {
 background: rgba(15, 23, 42, 0.85);
 color: #fff;
 border: 1px solid #fff;
 border-radius: 8px;
 font-size: 10px;
 font-weight: 700;
 padding: 1px 5px;
 box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
 white-space: nowrap;
 }
 .cell-rc-label::before {
 display: none;
 }

 /* Search pin marker */
 .search-pin-marker {
 background: transparent;
 border: none;
 }

 /* Talking animation for voice responses */
 .talking-active .talking-avatar {
 animation: talkingPulse 0.4s ease-in-out infinite;
 }

 @keyframes talkingPulse {
 0%, 100% { transform: scale(1); }
 50% { transform: scale(1.12); }
 }

 .talking-bubble {
 background: #ecfdf5 !important;
 border: 1px solid rgba(16, 185, 129, 0.3) !important;
 border-top-left-radius: 4px !important;
 }

 .talking-animation {
 display: flex;
 align-items: center;
 gap: 12px;
 padding: 4px 8px;
 }

 .talking-wave {
 display: flex;
 align-items: center;
 gap: 3px;
 height: 24px;
 }

 .talking-wave span {
 display: block;
 width: 4px;
 border-radius: 2px;
 background: #059669;
 animation: talkingWave 0.8s ease-in-out infinite;
 }

 .talking-wave span:nth-child(1) { animation-delay: 0s; height: 12px; }
 .talking-wave span:nth-child(2) { animation-delay: 0.1s; height: 20px; }
 .talking-wave span:nth-child(3) { animation-delay: 0.2s; height: 16px; }
 .talking-wave span:nth-child(4) { animation-delay: 0.3s; height: 22px; }
 .talking-wave span:nth-child(5) { animation-delay: 0.4s; height: 14px; }

 @keyframes talkingWave {
 0%, 100% { transform: scaleY(0.4); opacity: 0.6; }
 50% { transform: scaleY(1); opacity: 1; }
 }

 .talking-label {
 font-size: 0.82rem;
 font-weight: 600;
 color: #10b981;
 white-space: nowrap;
 }

 /* Theme-aware search result cards (overrides old dark leftovers in style.css) */
 .search-result-item {
 background: var(--surface) !important;
 border: 1px solid var(--border-subtle) !important;
 box-shadow: none !important;
 color: var(--text-body) !important;
 }

 .search-result-item:hover {
 background: var(--bg-subtle) !important;
 border-color: rgba(16, 185, 129, 0.35) !important;
 transform: none !important;
 box-shadow: none !important;
 }

 .search-result-item h4 { color: var(--text-strong) !important; }
 .search-result-item p { color: var(--text-muted) !important; }

 /* Map mode dropdown state colors */
 #mapModeDropdown {
 --mi-hover: #f8fafc;
 --mi-active-bg: #f0fdf4;
 --mi-active: #047857;
 }

 /* Dark mode — floating map widgets */
 html[data-theme="dark"] #welcomeBanner,
 html[data-theme="dark"] .top-bar,
 html[data-theme="dark"] .rotation-panel,
 html[data-theme="dark"] .search-bar-container,
 html[data-theme="dark"] .filter-panel,
 html[data-theme="dark"] .search-panel,
 html[data-theme="dark"] .map-legend,
 html[data-theme="dark"] #mapControlsPanel,
 html[data-theme="dark"] #mapModeDropdown,
 html[data-theme="dark"] .leaflet-control-layers,
 html[data-theme="dark"] .leaflet-routing-container {
 background: #1e293b !important;
 border-color: #334155 !important;
 backdrop-filter: none !important;
 box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45) !important;
 color: var(--text-body);
 }

 html[data-theme="dark"] .search-bar-container input {
 background: rgba(255, 255, 255, 0.05);
 border-color: #334155;
 color: var(--text-strong);
 }

 html[data-theme="dark"] .search-bar-container input:focus {
 background: rgba(16, 185, 129, 0.08);
 }

 html[data-theme="dark"] .search-bar-container input::placeholder {
 color: #64748b;
 }

 html[data-theme="dark"] .toggle-search-btn,
 html[data-theme="dark"] .toggle-nav-btn {
 background: rgba(16, 185, 129, 0.15);
 border-color: rgba(16, 185, 129, 0.3);
 color: #34d399;
 }

 html[data-theme="dark"] .toggle-search-btn:hover,
 html[data-theme="dark"] .toggle-nav-btn:hover {
 background: rgba(16, 185, 129, 0.25);
 color: #6ee7b7;
 }

 html[data-theme="dark"] .filter-option {
 background: rgba(255, 255, 255, 0.05);
 border-color: #334155;
 }

 html[data-theme="dark"] .filter-option:hover {
 background: rgba(16, 185, 129, 0.14);
 border-color: rgba(16, 185, 129, 0.4);
 transform: none;
 box-shadow: none;
 }

 html[data-theme="dark"] .filter-header h4,
 html[data-theme="dark"] .leaflet-routing-container h2,
 html[data-theme="dark"] .leaflet-routing-container h3 {
 background: none !important;
 -webkit-text-fill-color: #34d399 !important;
 color: #34d399 !important;
 }

 html[data-theme="dark"] .leaflet-routing-alt {
 background: #24344d !important;
 border-color: #334155 !important;
 color: var(--text-body) !important;
 }

 html[data-theme="dark"] .leaflet-routing-icon {
 filter: none !important;
 }

 html[data-theme="dark"] .leaflet-control-zoom a,
 html[data-theme="dark"] .leaflet-control-rotate a,
 html[data-theme="dark"] .leaflet-control-rotate-toggle,
 html[data-theme="dark"] .leaflet-control-rotate-reset {
 background: #1e293b !important;
 color: #cbd5e1 !important;
 border-color: #334155 !important;
 }

 html[data-theme="dark"] .leaflet-control-zoom a:hover,
 html[data-theme="dark"] .leaflet-control-rotate a:hover {
 background: #24344d !important;
 color: #f1f5f9 !important;
 }

 /* Map controls panel buttons (inline styles need !important) */
 html[data-theme="dark"] #mapControlsPanel > button {
 background: #24344d !important;
 color: #cbd5e1 !important;
 }

 html[data-theme="dark"] #mapModeBtn {
 background: #059669 !important;
 color: #ffffff !important;
 box-shadow: none !important;
 }

 html[data-theme="dark"] #mapModeDropdown {
 --mi-hover: rgba(255, 255, 255, 0.06);
 --mi-active-bg: rgba(16, 185, 129, 0.18);
 --mi-active: #34d399;
 }

 html[data-theme="dark"] #welcomeBanner > button,
 html[data-theme="dark"] .search-panel button {
 background: rgba(255, 255, 255, 0.08) !important;
 }

 html[data-theme="dark"] .map-legend h4 {
 color: var(--text-strong);
 }

 html[data-theme="dark"] .legend-color {
 border-color: rgba(255, 255, 255, 0.25);
 }
 </style>
 <!-- Map Container -->
 <div id="map"></div>
 
 <!-- Welcome Banner -->
 <div id="welcomeBanner" style="position: absolute; top: 100px; left: 50%; transform: translateX(-50%); z-index: 1001; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(24px); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 18px; padding: 14px 24px; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(16, 185, 129, 0.1); display: flex; align-items: center; gap: 14px; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); opacity: 1; max-width: 400px;">
 <div style="width: 40px; height: 40px; border-radius: 10px; background: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
 <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
 </div>
 <div>
 <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-strong);">Welcome to Matinao Memorial</h3>
 <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: var(--text-muted);">Search for a loved one or ask the AI assistant</p>
 </div>
 <button onclick="dismissWelcome()" style="background: rgba(15,23,42,0.06); border: none; width: 28px; height: 28px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; flex-shrink: 0;" onmouseover="this.style.background='rgba(15,23,42,0.12)'" onmouseout="this.style.background='rgba(15,23,42,0.06)'">
 <svg style="width: 14px; height: 14px; color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
 </button>
 </div>
 <script>
 function dismissWelcome() {
 const banner = document.getElementById('welcomeBanner');
 if (banner) {
 banner.style.opacity = '0';
 banner.style.transform = 'translateX(-50%) translateY(-10px)';
 setTimeout(() => banner.style.display = 'none', 500);
 }
 }
 // Auto-dismiss after 6 seconds
 setTimeout(dismissWelcome, 6000);
 </script>
 
 <!-- Map Controls -->
 
 <!-- Search Bar - Separate centered div -->
 <div class="search-bar-container" id="searchBar">
 <button class="toggle-search-btn" onclick="toggleSearchBar()" title="Toggle Search">
 <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
 </svg>
 </button>
 <input type="text" id="searchInput" placeholder="Search by name, plot, or family...">
 <button onclick="performSearch()" title="Search" style="width: 38px; height: 38px; background: #059669; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); flex-shrink: 0;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.3)'">
 <svg style="width: 18px; height: 18px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
 </button>
 </div>
 
 
 <!-- Search Results Panel -->
 <div class="search-panel" id="searchResultsPanel">
 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 14px 16px; background: rgba(16, 185, 129, 0.08); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.12);">
 <h4 style="margin: 0; font-size: 0.95rem; color: var(--text-strong); font-weight: 700; display: flex; align-items: center; gap: 8px;">
 <svg style="width: 18px; height: 18px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
 Search Results
 </h4>
 <button onclick="document.getElementById('searchResultsPanel').classList.remove('active')" style="background: rgba(15,23,42,0.06); border: 1px solid rgba(15,23,42,0.08); border-radius: 8px; padding: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(15,23,42,0.12)'" onmouseout="this.style.background='rgba(15,23,42,0.06)'">
 <svg style="width: 16px; height: 16px; color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
 </svg>
 </button>
 </div>
 <div id="searchResults" class="search-results"></div>
 </div>
 
 <!-- Map Legend -->
 <div class="map-legend">
 <h4 style="margin-bottom: 14px; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
 <svg style="width: 16px; height: 16px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
 Legend
 </h4>
 <div class="legend-item">
 <div class="legend-color" style="background: #5a87a8;"></div>
 <span>Standard Burial</span> 
 </div>
 <div class="legend-item">
 <div class="legend-color" style="background: #c9a86c;"></div>
 <span>Premium/Fenced</span>
 </div>
 <div class="legend-item">
 <div class="legend-color" style="background: #b55a5a;"></div>
 <span>Search Result</span>
 </div>
 </div>
 

 
 <!-- Map Controls Panel - Right side, below rotation -->
 <div id="mapControlsPanel" style="position: absolute; top: 72px; right: 20px; z-index: 1000; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(24px); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; padding: 6px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(16, 185, 129, 0.08); display: flex; flex-direction: column; gap: 4px;">
 <!-- Zoom buttons -->
 <button onclick="customZoomIn()" title="Zoom in" style="width: 36px; height: 36px; background: #f0fdf4; border: none; border-radius: 8px; padding: 0; cursor: pointer; color: #047857; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;" onmouseover="this.style.background='#059669'; this.style.color='#fff'" onmouseout="this.style.background='#f0fdf4'; this.style.color='#047857'">+</button>
 <button onclick="customZoomOut()" title="Zoom out" style="width: 36px; height: 36px; background: #f0fdf4; border: none; border-radius: 8px; padding: 0; cursor: pointer; color: #047857; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;" onmouseover="this.style.background='#059669'; this.style.color='#fff'" onmouseout="this.style.background='#f0fdf4'; this.style.color='#047857'">&minus;</button>
 <!-- Map mode dropdown -->
 <div style="position: relative;">
 <button onclick="toggleMapModeDropdown()" id="mapModeBtn" title="Map mode" style="width: 36px; height: 36px; background: #059669; border: none; border-radius: 8px; padding: 0; cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
 <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
 </button>
 <div id="mapModeDropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 6px; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(24px); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 6px; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12); min-width: 160px; flex-direction: column; gap: 2px;">
 <button onclick="switchMapMode('Google Satellite')" class="map-mode-item" style="background: var(--mi-active-bg); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--mi-active); display: flex; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; text-align: left;" onmouseover="this.style.background='var(--mi-active-bg)'" onmouseout="this.style.background='var(--mi-active-bg)'">
 <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
 Satellite
 </button>
 <button onclick="switchMapMode('Google Hybrid')" class="map-mode-item" style="background: transparent; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: var(--text-body); display: flex; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; text-align: left;" onmouseover="this.style.background='var(--mi-hover)'" onmouseout="this.style.background='transparent'">
 <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
 Hybrid
 </button>
 <button onclick="switchMapMode('Google Streets')" class="map-mode-item" style="background: transparent; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: var(--text-body); display: flex; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; text-align: left;" onmouseover="this.style.background='var(--mi-hover)'" onmouseout="this.style.background='transparent'">
 <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
 Streets
 </button>
 <button onclick="switchMapMode('Esri World Imagery')" class="map-mode-item" style="background: transparent; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: var(--text-body); display: flex; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; text-align: left;" onmouseover="this.style.background='var(--mi-hover)'" onmouseout="this.style.background='transparent'">
 <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h1.064M18 12h.5a2.5 2.5 0 002.5-2.5V8.5"/></svg>
 Esri Imagery
 </button>
 <button onclick="switchMapMode('OpenStreetMap')" class="map-mode-item" style="background: transparent; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: var(--text-body); display: flex; align-items: center; gap: 8px; transition: all 0.2s; width: 100%; text-align: left;" onmouseover="this.style.background='var(--mi-hover)'" onmouseout="this.style.background='transparent'">
 <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
 OpenStreetMap
 </button>
 </div>
 </div>
 </div>
 <script>
 function customZoomIn() {
 if (typeof map !== 'undefined' && map) map.zoomIn();
 }
 function customZoomOut() {
 if (typeof map !== 'undefined' && map) map.zoomOut();
 }
 function toggleMapModeDropdown() {
 const dd = document.getElementById('mapModeDropdown');
 dd.style.display = dd.style.display === 'none' ? 'flex' : 'none';
 }
 function switchMapMode(modeName) {
 if (!window._baseLayers || !window._baseLayers[modeName]) return;
 if (window._currentLayer) map.removeLayer(window._currentLayer);
 window._baseLayers[modeName].addTo(map);
 window._currentLayer = window._baseLayers[modeName];
 // Update active state
 document.querySelectorAll('.map-mode-item').forEach(b => {
 b.style.background = 'transparent';
 b.style.border = 'none';
 b.style.fontWeight = '500';
 b.style.color = 'var(--text-body)';
 });
 const activeBtn = event.currentTarget;
 activeBtn.style.background = 'var(--mi-active-bg)';
 activeBtn.style.border = '1px solid rgba(16, 185, 129, 0.3)';
 activeBtn.style.fontWeight = '600';
 activeBtn.style.color = 'var(--mi-active)';
 document.getElementById('mapModeDropdown').style.display = 'none';
 }
 // Close dropdown when clicking outside
 document.addEventListener('click', function(e) {
 const dd = document.getElementById('mapModeDropdown');
 const btn = document.getElementById('mapModeBtn');
 if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
 dd.style.display = 'none';
 }
 });
 </script>
 
 <!-- AI Assistant Toggle -->
 <button id="chatToggle" onclick="toggleChat()" aria-label="Open AI Assistant">
 <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant">
 </button>
 <div style="position: absolute; bottom: 32px; right: 96px; z-index: 999; background: rgba(15, 23, 42, 0.95); color: #fff; padding: 8px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2); pointer-events: none; opacity: 0; transition: opacity 0.3s ease; white-space: nowrap;" id="chatTooltip">
 Ask AI Assistant
 <span style="position: absolute; right: -5px; top: 50%; transform: translateY(-50%); width: 0; height: 0; border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-left: 6px solid rgba(15, 23, 42, 0.95);"></span>
 </div>
 <script>
 (function() {
 const toggle = document.getElementById('chatToggle');
 const tooltip = document.getElementById('chatTooltip');
 if (toggle && tooltip) {
 toggle.addEventListener('mouseenter', () => { tooltip.style.opacity = '1'; });
 toggle.addEventListener('mouseleave', () => { tooltip.style.opacity = '0'; });
 }
 })();
 </script>

 <!-- AI Assistant Chat -->
 <div id="chatContainer" style="display: none;">
 <div class="chat-header">
 <div class="chat-title">
 <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant">
 <div>
 <h4>MemoryGuide Assistant</h4>
 <p>Online · Ready to help</p>
 </div>
 </div>
 <div style="display:flex; align-items:center; gap:4px;">
 <button onclick="toggleTTS()" id="ttsToggle" title="Toggle voice output" class="chat-icon-btn">
 <svg id="ttsIcon" style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 9l-6 6M17 9l6 6"/></svg>
 </button>
 <button onclick="toggleChat()" aria-label="Close chat" class="chat-icon-btn">
 <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
 </svg>
 </button>
 </div>
 </div>
 <div class="chat-messages" id="chatMessages">
 <div class="chat-message assistant">
 <img src="../assets/images/ai-assistant-logo.svg" alt="AI" class="chat-avatar">
 <div class="chat-bubble">Hello! I'm your AI assistant. I can help you find burial locations, provide directions, or answer questions about the cemetery. How can I assist you today?</div>
 </div>
 </div>
 <div class="chat-input-container">
 <input type="text" id="chatInput" class="chat-input" placeholder="Ask me anything..." onkeypress="handleChatKeyPress(event)">
 <button onclick="toggleVoiceInput()" id="voiceBtn" aria-label="Voice input" title="Voice input" class="chat-voice-btn">
 <svg id="voiceIcon" style="width:17px; height:17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"/></svg>
 </button>
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
 <script src="../assets/js/visitor.js?v=23"></script>
 </main>
 </div>
 <script src="../assets/js/theme.js"></script>
 <script>
 if (typeof lucide !== 'undefined') lucide.createIcons();
 <?php if (($_GET['open'] ?? '') === 'chat'): ?>
 // Opened from sidebar "AI Assistant"
 if (typeof toggleChat === 'function') toggleChat();
 <?php endif; ?>
 </script>
</body>
</html>
