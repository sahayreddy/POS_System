<?php
// Configuration for XAMPP
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "stunning_pos_db"; // Must match the database name you created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Stop execution and show error if connection fails
    die("Connection failed: " . $conn->connect_error);
}

// Set default employee ID for simplicity
$employee_id = 1; 
?>  