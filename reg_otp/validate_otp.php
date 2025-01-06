<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session and set headers first
session_start();
header('Content-Type: application/json');
include "../database/dbConnect.php";

// Function to generate user ID with prefix
function generateUserId($conn) {
    $date = date('Ymd'); 
    
    // Get the last user ID from the entire table, not just today
    $sql = "SELECT user_id FROM users ORDER BY user_id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['user_id'];
        
        $sequence = intval(substr($lastId, -4)) + 1;
        
        
        if ($sequence > 9999) {
            $sequence = 1; 
        }
    } else {
        $sequence = 1; 
    }
    
    
    return 'USER' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get and validate JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    // Validate OTP presence
    if (!isset($input['otp'])) {
        throw new Exception('No OTP provided in request');
    }

    // Clean and validate OTP format
    $otp = trim($input['otp']);
    error_log("Submitted OTP after trim: " . $otp);
    error_log("Submitted OTP length: " . strlen($otp));
    error_log("Submitted OTP hex: " . bin2hex($otp));

    // Validate session
    if (!isset($_SESSION['user_info']) || !isset($_SESSION['user_info']['email'])) {
        error_log("Session contents: " . print_r($_SESSION, true));
        throw new Exception('No email found in session');
    }

    $email = $_SESSION['user_info']['email'];
    error_log("Email from session: " . $email);

    // Verify OTP from database with additional debugging
    $stmt = $conn->prepare("SELECT code, created_at, LENGTH(code) as code_length 
                           FROM verification_codes 
                           WHERE email = ? 
                           ORDER BY created_at DESC LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute OTP verification query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('No OTP found in database for this email');
    }

    error_log("Database record: " . print_r($row, true));
    error_log("Database OTP: " . $row['code'] . " (Type: " . gettype($row['code']) . ")");
    error_log("Database OTP length: " . $row['code_length']);
    error_log("Submitted OTP: " . $otp . " (Type: " . gettype($otp) . ")");

    // Check OTP expiration
    $otpTime = strtotime($row['created_at']);
    $currentTime = time();
    $timeDiff = $currentTime - $otpTime;
    
    error_log("OTP created at: " . $row['created_at']);
    error_log("Current time: " . date('Y-m-d H:i:s', $currentTime));
    error_log("Time difference: " . $timeDiff . " seconds");
    
    if ($timeDiff > 300) { // 5 minutes expiration
        throw new Exception('OTP has expired (created ' . $timeDiff . ' seconds ago)');
    }

    // Verify OTP matches with detailed error message
    if ((string)$row['code'] !== (string)$otp) {
        error_log("OTP mismatch - DB: '" . $row['code'] . "' vs Submitted: '" . $otp . "'");
        throw new Exception('Invalid OTP: Submitted code does not match stored code');
    }

    // If we get here, OTP is valid. Create user account
    $username = $_SESSION['user_info']['username'];
    $hashed_password = $_SESSION['user_info']['password'];

    error_log("Creating user account for username: " . $username);

    // Generate new user ID
    $user_id = generateUserId($conn);
    error_log("Generated User ID: " . $user_id);

    // Create user account with the generated ID
    $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password, status) 
                           VALUES (?, ?, ?, ?, 'Inactive')");
    
    if (!$stmt) {
        throw new Exception("Database prepare error for user creation: " . $conn->error);
    }

    $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create user account: " . $stmt->error);
    }

    $stmt->close();

    // Clear the OTP
    $stmt = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    // Update session
    $_SESSION = array();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    error_log("Account created successfully. User ID: " . $user_id);

    echo json_encode([
        'status' => 'success',
        'message' => 'OTP verified successfully'
    ]);

} catch (Exception $e) {
    error_log("OTP Validation Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>