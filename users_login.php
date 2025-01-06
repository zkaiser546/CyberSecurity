<?php
session_start();
include('../CyberSecurity/database/dbConnect.php');
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Debug log
        error_log("Login attempt - Email: " . $_POST['email']);
        
        $email = $_POST['email'];
        $password = hash('sha3-512', $_POST['password']);
        $response = ['success' => false, 'message' => '', 'redirect' => ''];

        // Super Admin check
        $supadminSql = "SELECT spAd_ID, password FROM supAdmin WHERE email = ?";
        $supadminStmt = $conn->prepare($supadminSql);
        
        if (!$supadminStmt) {
            throw new Exception("Preparation failed: " . $conn->error);
        }
        
        $supadminStmt->bind_param("s", $email);
        $supadminStmt->execute();
        $supadminResult = $supadminStmt->get_result();

        if ($supadminRow = $supadminResult->fetch_assoc()) {
            error_log("Super Admin found - Comparing passwords");
            error_log("Submitted hash: " . $password);
            error_log("Stored hash: " . $supadminRow['password']);
            
            if ($password === $supadminRow['password']) {
                $_SESSION['spAd_ID'] = $supadminRow['spAd_ID'];
                
                $updateSql = "UPDATE supAdmin SET status = 'Active' WHERE spAd_ID = ?";
                $updateStmt = $conn->prepare($updateSql);
                
                if (!$updateStmt) {
                    throw new Exception("Update preparation failed: " . $conn->error);
                }
                
                $updateStmt->bind_param("s", $supadminRow['spAd_ID']);
                $updateStmt->execute();
                $updateStmt->close();
                
                $response = ['success' => true, 'redirect' => './super_admin/super_admin.php'];
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            // Admin check
            $adminSql = "SELECT admin_ID, password FROM admin WHERE email = ?";
            $adminStmt = $conn->prepare($adminSql);
            
            if (!$adminStmt) {
                throw new Exception("Admin preparation failed: " . $conn->error);
            }
            
            $adminStmt->bind_param("s", $email);
            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();

            if ($adminRow = $adminResult->fetch_assoc()) {
                error_log("Admin found - Comparing passwords");
                
                if ($password === $adminRow['password']) {
                    $_SESSION['admin_ID'] = $adminRow['admin_ID'];
                    
                    $updateSql = "UPDATE admin SET status = 'Active' WHERE admin_ID = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    
                    if (!$updateStmt) {
                        throw new Exception("Update preparation failed: " . $conn->error);
                    }
                    
                    $updateStmt->bind_param("s", $adminRow['admin_ID']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $response = ['success' => true, 'redirect' => './admin/admin.php'];
                } else {
                    $response['message'] = 'Invalid password';
                }
            } else {
                // User check
                $userSql = "SELECT user_ID, password, status FROM users WHERE email = ?";
                $userStmt = $conn->prepare($userSql);
                
                if (!$userStmt) {
                    throw new Exception("User preparation failed: " . $conn->error);
                }
                
                $userStmt->bind_param("s", $email);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                if ($userRow = $userResult->fetch_assoc()) {
                    error_log("User found - Comparing passwords");
                    
                    if ($password === $userRow['password']) {
                        $_SESSION['user_ID'] = $userRow['user_ID'];
                        
                        $updateSql = "UPDATE users SET status = 'Active' WHERE user_ID = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        
                        if (!$updateStmt) {
                            throw new Exception("Update preparation failed: " . $conn->error);
                        }
                        
                        $updateStmt->bind_param("s", $userRow['user_ID']);
                        $updateStmt->execute();
                        $updateStmt->close();
                        
                        $response = ['success' => true, 'redirect' => './user/user.php'];
                    } else {
                        $response['message'] = 'Invalid password';
                    }
                }
            }
        }

        if (!$response['success']) {
            $response['message'] = 'Invalid email or password';
        }

        // Close all statements
        if (isset($supadminStmt)) $supadminStmt->close();
        if (isset($adminStmt)) $adminStmt->close();
        if (isset($userStmt)) $userStmt->close();

        echo json_encode($response);
        exit;
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
?>