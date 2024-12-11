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
$Super_Admin = "CREATE TABLE IF NOT EXISTS SupAdmin (
    SpAd_ID VARCHAR(255) PRIMARY KEY,
    Firstname VARCHAR(255) NOT NULL,
    Lastname VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Image VARCHAR(255),
    Status VARCHAR(50) NOT NULL,
    Role VARCHAR(50) NOT NULL
)";



// Create Admin table if it doesn't exist
$sqlAdmin = "CREATE TABLE IF NOT EXISTS Admin (
    Admin_ID VARCHAR(255) PRIMARY KEY,
    Firstname VARCHAR(255) NOT NULL,
    Lastname VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Image VARCHAR(255),
    Status VARCHAR(50) NOT NULL
)";




// Create Users table if it doesn't exist
$sqlUsers = "CREATE TABLE IF NOT EXISTS Users (
    User_ID VARCHAR(255) PRIMARY KEY,
    Firstname VARCHAR(255) NOT NULL,
    Lastname VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Image VARCHAR(255),
    Status VARCHAR(50) NOT NULL
)";


// User ID format
$prefix = "USERCODE";


$result = $conn->query("SELECT User_ID FROM Users WHERE User_ID LIKE '$prefix%' ORDER BY User_ID DESC LIMIT 1");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastNumber = (int)substr($row['User_ID'], strlen($prefix)); 
    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
} else {
    $newNumber = "001";
}

$newId = $prefix . $newNumber;
echo "Generated User_ID: $newId";


$sqlInsertUser = "INSERT INTO Users (User_ID, Firstname, Lastname, Email, Password, Status) VALUES
    ('$newId', 'John', 'Doe', 'johndoe@example.com', 'password123', 'active')";


// Create Feedback table if it doesn't exist
$sqlFeedback = "CREATE TABLE IF NOT EXISTS Feedback (
    Feedback_ID VARCHAR(255) PRIMARY KEY,
    User_ID VARCHAR(255) NOT NULL,
    Feedback_Text VARCHAR(500) NOT NULL,
    Stars INT NOT NULL CHECK (Stars BETWEEN 1 AND 5),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID)
)";


// feedback ID format
$prefix = "FBACK";
$date = date("Ym"); 


$result = $conn->query("SELECT Feedback_ID FROM Feedback WHERE FeedBack_ID LIKE '$prefix$date%' ORDER BY FeedBack_ID DESC LIMIT 1");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastNumber = (int)substr($row['Feedback_ID'], 10);
    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
} else {
    $newNumber = "001"; 
}
$newFeedbackId = $prefix . $date . $newNumber;



$sqlInsert = "INSERT INTO Feedback (Feedback_ID, User_ID, Feedback_Text, Stars) VALUES
    ('$newFeedbackId', 'USERCODE001', 'Great platform! User-friendly and efficient.', 5)";

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


if ($conn->query($sqlInsert) === TRUE) {
    echo "Feedback added successfully with ID: $newFeedbackId";
} else {
    echo "Error inserting feedback: " . $conn->error;
}


if ($conn->query($sqlInsertUser) === TRUE) {
    echo "User added successfully with ID: $newId";
} else {
    echo "Error inserting user: " . $conn->error;
}


if ($conn->query($sqlFeedback) === TRUE) {
    echo "Table 'Feedback' created successfully.";
} else {
    echo "Error creating Feedback table: " . $conn->error;
}

$conn->close();
?>
