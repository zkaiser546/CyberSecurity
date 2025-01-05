<?php
// retrieve_history.php
include "../database/dbConnect.php";
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_ID'])) {
        throw new Exception('Authentication required');
    }
    
    $userId = $_SESSION['user_ID'];
    
    $stmt = $conn->prepare("
        SELECT f.feedback_dD, f.feedback_text, f.stars, f.created_at, f.display_name,
               fr.reply_text, fr.created_at as reply_date
        FROM feedback f
        LEFT JOIN feedback_replies fr ON f.feedback_dD = fr.feedback_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $feedbackData = [];
    while ($row = $result->fetch_assoc()) {
        $feedbackData[] = [
            'feedback_id' => $row['feedback_dD'],
            'feedback_text' => $row['feedback_text'],
            'stars' => $row['stars'],
            'created_at' => $row['created_at'],
            'display_name' => $row['display_name'],
            'reply_text' => $row['reply_text'],
            'reply_date' => $row['reply_date']
        ];
    }
    
    echo json_encode($feedbackData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>