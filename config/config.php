<?php
// config/config.php - global settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Put your Google Maps JavaScript API key here (must have "Maps JavaScript API" + "Places API" enabled)
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE');

// Base URL of the project (change if your folder name is different, e.g. http://localhost/freshlink)
define('BASE_URL', 'http://localhost/freshlink');

// Default search radius (km)
define('DEFAULT_RADIUS_KM', 5);
