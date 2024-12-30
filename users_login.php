<?php
session_start();
include('../CyberSecurity/database/dbConnect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = hash('sha3-512', $_POST['password']);
    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    // Super Admin check
    $supadminSql = "SELECT spAd_ID, password FROM supadmin WHERE email = ?";
    $supadminStmt = $conn->prepare($supadminSql);
    $supadminStmt->bind_param("s", $email);
    $supadminStmt->execute();
    $supadminResult = $supadminStmt->get_result();

    // Admin check
    $adminSql = "SELECT admin_ID, password FROM admin WHERE email = ?";
    $adminStmt = $conn->prepare($adminSql);
    $adminStmt->bind_param("s", $email);
    $adminStmt->execute();
    $adminResult = $adminStmt->get_result();

    // User check
    $userSql = "SELECT user_ID, password, status FROM users WHERE email = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($supadminRow = $supadminResult->fetch_assoc()) {
        if ($password === $supadminRow['password']) {
            $_SESSION['spAd_ID'] = $supadminRow['spAd_ID'];
            $response = ['success' => true, 'redirect' => './super_admin/super_admin.php'];
        }
    }
    elseif ($adminRow = $adminResult->fetch_assoc()) {
        if ($password === $adminRow['password']) {
            $_SESSION['admin_ID'] = $adminRow['admin_ID'];
            $response = ['success' => true, 'redirect' => './admin/admin.php'];
        }
    }
    elseif ($userRow = $userResult->fetch_assoc()) {
        if ($password === $userRow['password']) {
            $_SESSION['user_ID'] = $userRow['user_ID'];
            
            // Fix the status update query
            $updateSql = "UPDATE users SET status = 'Active' WHERE user_ID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("s", $userRow['user_ID']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $response = ['success' => true, 'redirect' => './user/user.php'];
        }
    }

    if (!$response['success']) {
        $response['message'] = 'Invalid email or password';
    }

    // Close all statements
    $supadminStmt->close();
    $adminStmt->close();
    $userStmt->close();

    echo json_encode($response);
    exit;
}
?>