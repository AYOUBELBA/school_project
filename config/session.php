<?php

// Only set ini settings if session is not active
if (session_status() === PHP_SESSION_NONE) {
    // Session ini settings must be set before session_start()
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    session_start();
}
?>