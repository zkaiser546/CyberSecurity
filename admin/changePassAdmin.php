<?php
session_start();
include '../database/dbConnect.php';

function logAdminAction($conn, $adminId) {
    $action = "Update Profile";
    
    $logQuery = "INSERT INTO admin_logs (admin_id, action) 
                 VALUES (?, ?)";
    
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("ss", 
        $adminId, 
        $action
    );
    
    return $logStmt->execute();
}

// Check if user is logged in
if (!isset($_SESSION['admin_ID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $adminId = $_SESSION['admin_ID'];
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if (!$oldPassword || !$newPassword) {
        throw new Exception('Missing required password fields');
    }

    $conn->begin_transaction();

    try {
        // Verify old password
        $stmt = $conn->prepare("SELECT password FROM admin WHERE admin_ID = ? AND password = ?");
        $stmt->bind_param("ss", $adminId, $oldPassword); 
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Current password is incorrect');
        }

        // Profile picture upload
        $imagePath = null;
        if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024;

            $file = $_FILES['profilePic'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
            }

            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum size is 5MB.');
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $uploadDir = 'uploads/profile_pictures/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                throw new Exception('Failed to upload file');
            }

            $imagePath = $uploadDir . $filename;

            // Handle old image deletion
            $query = "SELECT image FROM admin WHERE admin_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->bind_result($oldImage);
            $stmt->fetch();

            if ($oldImage && file_exists($oldImage)) {
                unlink($oldImage);
            }
            $stmt->close();
        }

        // Update user profile
        $query = "UPDATE admin SET password = ?";
        $params = [$newPassword];
        $types = "s";

        if ($imagePath !== null) {
            $query .= ", image = ?";
            $params[] = $imagePath;
            $types .= "s";
        }

        $query .= " WHERE admin_id = ?";
        $params[] = $adminId;
        $types .= "s";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile: " . $stmt->error);
        }

        // Log the action
        if (!logAdminAction($conn, $adminId)) {
            throw new Exception('Failed to log admin action');
        }

        $conn->commit();

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'image' => $imagePath ?? null
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        if (isset($imagePath) && file_exists($imagePath)) {
            unlink($imagePath);
        }
        throw $e;
    }

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}

ob_end_flush();
?>