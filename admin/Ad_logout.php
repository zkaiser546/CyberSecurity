<?php
session_start();
include '../database/dbConnect.php'; // Include your database connection

// Check if user session exists
if (isset($_SESSION['admin_ID'])) {
    $adminId = $_SESSION['admin_ID'];

    // Update the user's status to 'Inactive'
    $updateSql = "UPDATE admin SET status = 'Inactive' WHERE admin_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $adminId);

    if ($updateStmt->execute()) {
        // Unset the user session
        unset($_SESSION['admin_ID']);
        unset($_SESSION['username']); 
    }

    $updateStmt->close();
}


session_destroy();

// Redirect to the login page
header("Location: ../login.php");
exit();
?>
