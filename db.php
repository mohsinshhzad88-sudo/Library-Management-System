<?php
$host = "127.0.0.1";   // use this instead of localhost
$user = "root";
$password = "";
$database = "library";
$port = 3307;          // IMPORTANT

// Create connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>