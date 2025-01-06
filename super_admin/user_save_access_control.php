<?php
// Include database connection
include '../database/dbConnect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Loop through the POST data and save each admin's access control
    foreach ($_POST as $key => $value) {
        // Check if the POST key starts with 'access_control_' (this corresponds to access control select fields)
        if (strpos($key, 'access_control_') === 0) {
            $admin_id = substr($key, 15); // Extract admin_id from the key name
            $access_control_status = $value; // Get the selected status (Enabled/Disabled)

            // Check if this admin already has a record in the accessControl table
            $sql = "SELECT user_id FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // If the record exists, update it, otherwise insert a new record
            if ($result->num_rows > 0) {
                // Update the existing record
                $sqlUpdate = "UPDATE users SET manage_users = ? WHERE user_id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("ss", $access_control_status, $admin_id);
                $stmtUpdate->execute();
            } else {
                // Insert new record if none exists
                $sqlInsert = "INSERT INTO users (user_id, manage_users) VALUES (?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->bind_param("ss", $admin_id, $access_control_status);
                $stmtInsert->execute();
            }
        }
    }
    

    // After saving, redirect back to the access control page
    header("Location: super_admin.php"); // Change the URL to your actual access control page
    exit();
}
?>
