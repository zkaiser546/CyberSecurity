<?php
session_start();
include '../database/dbConnect.php'; 

// Check if super admin session exists
if (isset($_SESSION['spAd_ID'])) {
    $superAdminId = $_SESSION['spAd_ID'];

    // Update the super admin's status to 'Inactive'
    $updateSql = "UPDATE supadmin SET status = 'Inactive' WHERE spAd_ID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $superAdminId);

    if ($updateStmt->execute()) {
        
        unset($_SESSION['spAd_ID']);
        unset($_SESSION['username']); 
    }

    $updateStmt->close();
}


// Redirect to the login page
header("Location: ../login.php");
exit();
