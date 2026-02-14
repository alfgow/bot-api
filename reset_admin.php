<?php
/**
 * Reset admin password — DELETE THIS FILE AFTER USING IT
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getConnection();

    $passwordHash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmt = $db->prepare('UPDATE api_users SET password_hash = ? WHERE username = ?');
    $stmt->execute([$passwordHash, ADMIN_USERNAME]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Password actualizado para: ' . ADMIN_USERNAME,
        ]);
    } else {
        // User doesn't exist, create it
        $stmt = $db->prepare('INSERT INTO api_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([ADMIN_USERNAME, $passwordHash]);
        echo json_encode([
            'success' => true,
            'message' => 'Usuario admin creado: ' . ADMIN_USERNAME,
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
