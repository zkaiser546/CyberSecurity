<?php
include "../database/dbConnect.php";
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_ID'])) {
        throw new Exception('Authentication required');
    }
    
    $userId = $_SESSION['user_ID'];
    $stmt = $conn->prepare("SELECT feedback_dD, feedback_text, stars, 
                           DATE_FORMAT(created_at, '%Y-%m-%d') as created_at 
                           FROM feedback 
                           WHERE user_id = ?
                           ORDER BY feedback_dD DESC");
    $stmt->bind_param("s", $userId);  // Changed from "i" to "s" for VARCHAR
    $stmt->execute();
    $result = $stmt->get_result();
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    echo json_encode($feedback);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>