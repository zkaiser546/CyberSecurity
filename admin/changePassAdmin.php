<?php
session_start();
include '../database/dbConnect.php';

// Check if user is logged in
if (!isset($_SESSION['admin_ID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Ensure no output before setting headers
ob_start();
/*function validatePassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) return false;
    return true;
} */

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get user ID from session
    $supId = $_SESSION['admin_ID'];

    // Get and validate password data
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if (!$oldPassword || !$newPassword) {
        throw new Exception('Missing required password fields');
    }

   /* if (!validatePassword($newPassword)) {
        throw new Exception('Password must be at least 8 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.');
    }*/
    // Verify old password
    $stmt = $conn->prepare("SELECT password FROM admin WHERE admin_ID = ? AND password = ?");
    $stmt->bind_param("ss", $supId, $oldPassword); 
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
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadDir = 'uploads/profile_pictures/';

        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            throw new Exception('Failed to upload file');
        }

        $imagePath = $uploadDir . $filename;

        // Delete old profile picture if it exists
        $query = "SELECT image FROM admin WHERE admin_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $supId);
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

    $query .= " WHERE admin_ID = ?";
    $params[] = $supId;
    $types .= "s";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile: " . $stmt->error);
    }

    // Clear any previous output
    ob_clean();

    // Set headers and return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'image' => $imagePath ?? null
        ]
    ]);

} catch (Exception $e) {
    // Delete uploaded file if it exists
    if (isset($imagePath) && file_exists($imagePath)) {
        unlink($imagePath);
    }

    // Clear any previous output
    ob_clean();

    // Set headers and return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => htmlspecialchars($e->getMessage())
    ]);
}

// Flush output buffer
ob_end_flush();
?>
