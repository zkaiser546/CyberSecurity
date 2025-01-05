<?php
include "../database/dbConnect.php";
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin_ID'])) {
        throw new Exception('Authentication required');
    }
    
    $stmt = $conn->prepare("  SELECT 
            f.feedback_dD,
            f.feedback_text,
            f.stars,
            f.display_name,
            CASE WHEN fr.reply_id IS NOT NULL THEN 1 ELSE 0 END as has_reply
        FROM feedback f
        LEFT JOIN feedback_replies fr ON f.feedback_dD = fr.feedback_id
        ORDER BY f.feedback_dD DESC");
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query');
    }
    
    $result = $stmt->get_result();
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = array(
            'feedback_dD' => $row['feedback_dD'],
            'feedback_text' => $row['feedback_text'],
            'stars' => $row['stars'],
            'display_name' => $row['display_name'],
            'has_reply' => (bool)$row['has_reply']
        );
    }
    
    if (empty($feedback)) {
        echo json_encode([]);
    } else {
        echo json_encode(array_values($feedback));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>