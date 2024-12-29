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
    user_id VARCHAR(20) PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) NOT NULL CHECK (status IN ('Active', 'Inactive'))
)";

// Create Feedback table if it doesn't exist
$sqlFeedback = "CREATE TABLE IF NOT EXISTS feedback (
    feedback_dD VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    feedback_text VARCHAR(500) NOT NULL,
    stars INT NOT NULL CHECK (Stars BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Create verification_codes table
$sqlReset = "CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 10 MINUTE),
    attempts INT DEFAULT 0,
    INDEX idx_email (email)
);";

// Create admin_logs table
$sqlLogs = "CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(255) NOT NULL,
    feedback_dD VARCHAR(255) NOT NULL,
    action VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE CASCADE,
    FOREIGN KEY (feedback_dD) REFERENCES feedback(feedback_dD) ON DELETE CASCADE
)";

// Execute the creation of tables in the correct order
$tables = [
    'Super_Admin' => $Super_Admin,
    'Admin' => $sqlAdmin,
    'Users' => $sqlUsers,
    'Feedback' => $sqlFeedback,
    'Verification Codes' => $sqlReset,
    'Admin Logs' => $sqlLogs
];

foreach ($tables as $tableName => $query) {
    if ($conn->query($query) === TRUE) {
        echo "Table '$tableName' created successfully<br>";
    } else {
        echo "Error creating $tableName table: " . $conn->error . "<br>";
    }
}

// Close the connection
$conn->close();
?>