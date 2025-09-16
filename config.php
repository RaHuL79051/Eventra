<?php
// Database Connection
$host = "localhost";
$user = "root"; // Change if needed
$pass = "12345";     // Change if needed
$db   = "college_event_system";

$conn = new mysqli($host, $user, $pass, $db, 3306);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
