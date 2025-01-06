<?php
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Update with a valid path

try {
    include '../database/dbConnect.php';

    // Retrieve and validate input
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['user_ID']) && isset($data['username'])) {
        $userId = $data['user_ID'];
        $username = trim($data['username']);

        // Validate username
        if (empty($username)) {
            echo json_encode([
                "success" => false,
                "message" => "Username cannot be empty"
            ]);
            exit;
        }

        // Check if username has actually changed
        $getCurrentData = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
        $getCurrentData->bind_param("s", $userId);
        $getCurrentData->execute();
        $currentData = $getCurrentData->get_result()->fetch_assoc();

        if ($currentData['username'] === $username) {
            echo json_encode([
                "success" => false,
                "message" => "No changes were made to the username."
            ]);
            exit;
        }

        // Check if new username exists
        $checkUsername = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $checkUsername->bind_param("ss", $username, $userId);
        $checkUsername->execute();
        $usernameResult = $checkUsername->get_result();

        if ($usernameResult->num_rows > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Username already exists for another user."
            ]);
            exit;
        }

        // Update username
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
        $stmt->bind_param("ss", $username, $userId);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Username updated successfully."
            ]);
        } else {
            throw new Exception("Failed to update username: " . $stmt->error);
        }
    } else {
        throw new Exception("Invalid input: Missing required fields.");
    }
} catch (Exception $e) {
    error_log("Error updating user: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error updating user: " . $e->getMessage()
    ]);
}

$conn->close();
