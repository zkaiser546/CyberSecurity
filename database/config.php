<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = mysqli_connect($servername, $username, $password);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS Feedback_System";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully <br>";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

mysqli_close($conn);

// Connect to the newly created database
$conn = new mysqli($servername, $username, $password, "Feedback_System");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create SuperAdmin table if it doesn't exist
$Super_Admin = "CREATE TABLE IF NOT EXISTS supAdmin (
    spAd_ID VARCHAR(255) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    status VARCHAR(50) NOT NULL,
    role VARCHAR(50) NOT NULL
)";



// Create Admin table if it doesn't exist
$sqlAdmin = "CREATE TABLE IF NOT EXISTS admin (
    admin_id VARCHAR(255) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    status VARCHAR(50) NOT NULL
)";



// Create Users table if it doesn't exist
$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY, -- Auto increment user ID
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) NOT NULL CHECK (status IN ('Active', 'Inactive'))
);";



// Create Feedback table if it doesn't exist
$sqlFeedback = "CREATE TABLE IF NOT EXISTS feedback (
    feedback_dD VARCHAR(255) PRIMARY KEY,
    user_id INT  NOT NULL,
    feedback_text VARCHAR(500) NOT NULL,
    stars INT NOT NULL CHECK (Stars BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
)";

$sqlReset = "CREATE TABLE verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email)
);";

if ($conn->query($sqlReset) === TRUE) {
    echo "Table 'Password reset table' created successfully<br>";
} else {
    echo "Error creating SupAdmin table: " . $conn->error;
}   


if ($conn->query($Super_Admin) === TRUE) {
    echo "Table 'SupAdmin' created successfully<br>";
} else {
    echo "Error creating SupAdmin table: " . $conn->error;
}    


if ($conn->query($sqlAdmin) === TRUE) {
    echo "Table 'Admin' created successfully<br>";
} else {
    echo "Error creating Admin table: " . $conn->error;
}


if ($conn->query($sqlUsers) === TRUE) {
    echo "Tables 'Users' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error;
}


if ($conn->query($sqlFeedback) === TRUE) {
    echo "Table 'Feedback' created successfully.";
} else {
    echo "Error creating Feedback table: " . $conn->error;
}

// Close the connection
$conn->close();
?>
