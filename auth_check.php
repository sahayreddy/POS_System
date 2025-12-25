<?php
// This file must be included at the very top of every protected page.

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user ID is NOT set in the session
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect them to the login page
    header("Location: login.php");
    exit;
}

// User is logged in. The script continues execution on the protected page.

// Optional: Add a function to log the user out
function logout() {
    // Clear all session variables
    $_SESSION = array(); 
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit;
}
?>  