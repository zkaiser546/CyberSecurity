<?php
session_start();
include '../database/dbConnect.php'; // Include your database connection

// Check if user session exists
if (isset($_SESSION['user_ID'])) {
    $userId = $_SESSION['user_ID'];

    // Update the user's status to 'Inactive'
    $updateSql = "UPDATE users SET status = 'Inactive' WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $userId);

    if ($updateStmt->execute()) {
        // Unset the user session
        unset($_SESSION['user_ID']);
        unset($_SESSION['username']); 
    }

    $updateStmt->close();
}


session_destroy();

// Redirect to the login page
header("Location: ../login.php");
exit();
?>
