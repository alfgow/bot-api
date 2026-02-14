<?php
/**
 * ─── Auth Controller ──────────────────────────────────────
 * POST /api/auth/login
 * POST /api/auth/register (protected)
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../config.php';

class AuthController
{
    /**
     * POST /api/auth/login
     * Body: { username, password }
     */
    public static function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username y password son requeridos.',
            ]);
            return;
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('SELECT * FROM api_users WHERE username = ? AND is_active = 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Credenciales inválidas.',
                ]);
                return;
            }

            $payload = [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'iat'      => time(),
                'exp'      => time() + JWT_EXPIRES_IN,
            ];

            $token = JWT::encode($payload, JWT_SECRET);

            echo json_encode([
                'success'   => true,
                'token'     => $token,
                'expiresIn' => JWT_EXPIRES_IN . 's',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * POST /api/auth/register
     * Body: { username, password }
     * Requires: Bearer Token
     */
    public static function register(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username y password son requeridos.',
            ]);
            return;
        }

        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'El password debe tener al menos 6 caracteres.',
            ]);
            return;
        }

        try {
            $db = Database::getConnection();

            // Check if username exists
            $stmt = $db->prepare('SELECT id FROM api_users WHERE username = ?');
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'El username ya existe.',
                ]);
                return;
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

            $stmt = $db->prepare('INSERT INTO api_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $passwordHash]);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
                'data'    => [
                    'id'       => (int) $db->lastInsertId(),
                    'username' => $username,
                ],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }
}
