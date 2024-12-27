<?php
session_start(); // Start a session to store user data
include('../CyberSecurity/database/dbConnect.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user input from the form
    $email = $_POST['email'];
    $password = $_POST['password'];

         $supadminSql = "SELECT spAd_ID, password FROM supadmin WHERE email = ?";
        $supadminStmt = $conn->prepare($supadminSql);
        $supadminStmt->bind_param("s", $email);
        $supadminStmt->execute();
        $supadminStmt->store_result();

         $adminSql = "SELECT admin_ID, password FROM admin WHERE email = ?";
        $adminStmt = $conn->prepare($adminSql);
        $adminStmt->bind_param("s", $email);
        $adminStmt->execute();
        $adminStmt->store_result();

         $userSql = "SELECT user_ID, password FROM users WHERE email = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $userStmt->store_result();
        // Check if user was found
        if ($supadminStmt->num_rows > 0) {
             $supadminStmt->bind_result($supadminId, $adminHashedPassword);
            $supadminStmt->fetch();
             if (password_verify($password, $adminHashedPassword)) {
              $_SESSION['spAd_ID'] = $supadminId;
                //$_SESSION['emp_role'] = 'Admin';
            
            }
             header('Location: ./super_admin/super_admin.php');
                exit();
            // Verify the password with the hash stored in the database
          
        }
        
        else if($adminStmt->num_rows > 0){
            $adminStmt->bind_result($adminId, $adminHashedPassword);
            $adminStmt->fetch();
             if (password_verify($password, $adminHashedPassword)) {
              $_SESSION['admin_ID'] = $adminId;
             }
             header('Location: ./admin/admin.php');
                exit();
        }
         else if($userStmt->num_rows > 0){
            $userStmt->bind_result($userId, $adminHashedPassword);
            $userStmt->fetch();
             if (password_verify($password, $adminHashedPassword)) {
              $_SESSION['user_ID'] = $userId;
             }
             header('Location: ./user/user.php');
                exit();
        }

        else {
                $error_message = handleFailedAttempt($email, $conn);
            }
            $supadminStmt->close();
    }

    // If user not found or invalid password
    if (!$userFound) {
        echo "<script>alert('Invalid email or password.');</script>";
    }

?>
