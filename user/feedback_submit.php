<?php
include "../database/dbConnect.php";
session_start();

function checkUserSession() {
    return isset($_SESSION['user_ID']);
}

function generateFeedbackId($conn) {
    $sql = "SELECT feedback_dD FROM feedback ORDER BY feedback_dD DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['feedback_dD'];
        $sequence = intval(substr($lastId, 2)) + 1;
    } else {
        $sequence = 1;
    }
    
    return 'FB' . str_pad($sequence, 8, '0', STR_PAD_LEFT);
}

header('Content-Type: application/json');
error_reporting(0); // Prevent PHP errors from breaking JSON response

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!checkUserSession()) {
        throw new Exception('Authentication required');
    }

    $userId = $_SESSION['user_ID'];
    $feedback = $_POST['feedback'];
    $stars = intval($_POST['rating']);
    $isAnonymous = isset($_POST['anonymous']) ? filter_var($_POST['anonymous'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($feedback) || $stars < 1 || $stars > 5) {
        throw new Exception('Invalid feedback data');
    }

    // Fetch user data
    $stmt = $conn->prepare("SELECT username, manage_users FROM users WHERE user_ID = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Check if user is blocked
    if ($user['manage_users'] === 'Blocked') {
        throw new Exception('You have been Blocked by Admin! You are not allowed to submit feedback.');
    }

    $feedbackId = generateFeedbackId($conn);
    $displayName = $isAnonymous ? substr($user['username'], 0, 1) . '***' : $user['username'];
    
    $insertStmt = $conn->prepare("INSERT INTO feedback (feedback_dD, user_id, feedback_text, stars, display_name) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssis", $feedbackId, $userId, $feedback, $stars, $displayName);
    
    if (!$insertStmt->execute()) {
        throw new Exception('Failed to save feedback');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your feedback!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
