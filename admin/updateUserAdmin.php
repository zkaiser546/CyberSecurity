<?php
include '../database/dbConnect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_ID'], $data['username'], $data['email'], $data['status'])) {
    $userId = intval($data['user_ID']);
    $username = $data['username'];
    $email = $data['email'];
    $status = $data['status'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE user_ID = ?");
    $stmt->bind_param("sssi", $username, $email, $status, $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update user."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}
?>
