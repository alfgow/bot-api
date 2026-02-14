<?php
/**
 * ─── Auth Middleware ───────────────────────────────────────
 * Verifies JWT Bearer Token from Authorization header.
 * Sets $GLOBALS['auth_user'] on success.
 */

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../config.php';

function authenticate(): bool
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Acceso denegado. Token no proporcionado.',
        ]);
        return false;
    }

    $token = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($token, JWT_SECRET);
        $GLOBALS['auth_user'] = $decoded;
        return true;
    } catch (Exception $e) {
        http_response_code(401);
        $msg = $e->getMessage();

        if (strpos($msg, 'expirado') !== false) {
            echo json_encode(['success' => false, 'message' => 'Token expirado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Token inválido.']);
        }
        return false;
    }
}
