<?php
include "../database/dbConnect.php";
session_start();
header('Content-Type: application/json');

function generateReplyId($conn) {
    $sql = "SELECT reply_id FROM feedback_replies ORDER BY reply_id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['reply_id'];
        $sequence = intval(substr($lastId, 2)) + 1;
    } else {
        $sequence = 1;
    }
    
    return 'RP' . str_pad($sequence, 8, '0', STR_PAD_LEFT);
}

function logAdminAction($conn, $adminId, $feedbackId) {
    $action = "Reply to Feedback";
    
    $logQuery = "INSERT INTO admin_logs (admin_id, feedback_dD, action) 
                 VALUES (?, ?, ?)";
    
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("sss", 
        $adminId, 
        $feedbackId, 
        $action
    );
    
    return $logStmt->execute();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['admin_ID'])) {
        throw new Exception('Authentication required');
    }

    $adminId = $_SESSION['admin_ID'];
    $reply = $_POST['reply'];
    $feedbackId = $_POST['feedback_id'];

    if (empty($reply) || empty($feedbackId)) {
        throw new Exception('Invalid reply data');
    }

    
    $conn->begin_transaction();

    try {
        // Check if reply already exists
        $checkStmt = $conn->prepare("SELECT reply_id FROM feedback_replies WHERE feedback_id = ?");
        $checkStmt->bind_param("s", $feedbackId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            throw new Exception('A reply already exists for this feedback');
        }

        $replyId = generateReplyId($conn);
        
        
        $insertStmt = $conn->prepare("INSERT INTO feedback_replies (reply_id, feedback_id, admin_id, reply_text, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insertStmt->bind_param("ssss", $replyId, $feedbackId, $adminId, $reply);
        
        if (!$insertStmt->execute()) {
            throw new Exception('Failed to save reply');
        }

        if (!logAdminAction($conn, $adminId, $feedbackId)) {
            throw new Exception('Failed to log admin action');
        }

        
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Reply sent and logged successfully!'
        ]);

    } catch (Exception $e) {
       
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>