<?php
include '../database/dbConnect.php';

if (isset($_GET['user_ID'])) {
    $userId = intval($_GET['user_ID']);
    $stmt = $conn->prepare("SELECT user_ID, username, email, status FROM users WHERE user_ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "User not found."]);
    }
} else {
    echo json_encode(["error" => "Invalid request."]);
}
?>
