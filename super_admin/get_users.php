<?php
include '../database/dbConnect.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT user_id, username, email, status 
            FROM users 
            ORDER BY user_id DESC";
    
    $result = $conn->query($sql);
    $users = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = array(
                'user_id' => htmlspecialchars($row['user_id']),
                'username' => htmlspecialchars($row['username']),
                'email' => htmlspecialchars($row['email']),
                'status' => htmlspecialchars($row['status'])
            );
        }
    }
    
    echo json_encode($users);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch users']);
}

$conn->close();
?>