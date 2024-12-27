<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "feedback_system";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);

    // Throw exception if there's a connection error
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

} catch (Exception $e) {
    // Display error message
    echo "Error: " . $e->getMessage();
}
?>
