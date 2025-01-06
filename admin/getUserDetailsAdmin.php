<?php
include '../database/dbConnect.php';
header('Content-Type: application/json');

if (isset($_GET['user_ID'])) {
    $userId = $_GET['user_ID'];
    
    
    $stmt = $conn->prepare("SELECT user_id, username, email, status FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        echo json_encode(["error" => "User not found."]);
    }
} else {
    echo json_encode(["error" => "Invalid request."]);
}

$conn->close();
?>