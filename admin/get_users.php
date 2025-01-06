<?php
include '../database/dbConnect.php';
header('Content-Type: application/json');

// Decryption function
function decryptEmail($encryptedData, $key) {
    $combined = base64_decode($encryptedData);
    $iv = substr($combined, 0, 16);
    $encrypted = substr($combined, 16);
    return openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

// Encryption key
$encryptionKey = 'SecureFeedback250';

try {
    $sql = "SELECT user_id, username, email, status FROM users ORDER BY user_id DESC";
    $result = $conn->query($sql);
    $users = array();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $decryptedEmail = decryptEmail($row['email'], $encryptionKey); // Decrypt the email
            
            $users[] = array(
                'user_id' => htmlspecialchars($row['user_id']),
                'username' => htmlspecialchars($row['username']),
                'email' => htmlspecialchars($decryptedEmail), // Use decrypted email
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
