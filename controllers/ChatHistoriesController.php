<?php
/**
 * ─── ChatHistories Controller ─────────────────────────────
 * CRUD for n8n_chat_histories table
 */

require_once __DIR__ . '/../database.php';

class ChatHistoriesController
{
    /**
     * GET /api/chat-histories
     * Query params: ?session_id=abc123&page=1&limit=50
     */
    public static function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $limit  = min(100, max(1, (int) ($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;

            $filters = [];
            $params  = [];

            if (!empty($_GET['session_id'])) {
                $filters[] = 'session_id = ?';
                $params[]  = $_GET['session_id'];
            }

            $whereClause = count($filters) > 0 ? 'WHERE ' . implode(' AND ', $filters) : '';

            // Count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM n8n_chat_histories $whereClause");
            $stmt->execute($params);
            $total = (int) $stmt->fetch()['total'];

            // Fetch
            $stmt = $db->prepare(
                "SELECT * FROM n8n_chat_histories $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Decode message JSON for each row
            foreach ($rows as &$row) {
                if (isset($row['message']) && is_string($row['message'])) {
                    $decoded = json_decode($row['message'], true);
                    if ($decoded !== null) {
                        $row['message'] = $decoded;
                    }
                }
            }

            echo json_encode([
                'success'    => true,
                'data'       => $rows,
                'pagination' => [
                    'page'       => $page,
                    'limit'      => $limit,
                    'total'      => $total,
                    'totalPages' => (int) ceil($total / $limit),
                ],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * GET /api/chat-histories/{id}
     */
    public static function getById(string $id): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('SELECT * FROM n8n_chat_histories WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Chat history no encontrado.']);
                return;
            }

            // Decode message JSON
            if (isset($row['message']) && is_string($row['message'])) {
                $decoded = json_decode($row['message'], true);
                if ($decoded !== null) {
                    $row['message'] = $decoded;
                }
            }

            echo json_encode(['success' => true, 'data' => $row]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * GET /api/chat-histories/session/{sessionId}
     */
    public static function getBySessionId(string $sessionId): void
    {
        try {
            $db = Database::getConnection();

            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $limit  = min(200, max(1, (int) ($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;

            // Verify session exists
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Session no encontrada.']);
                return;
            }

            // Count
            $stmt = $db->prepare('SELECT COUNT(*) as total FROM n8n_chat_histories WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $total = (int) $stmt->fetch()['total'];

            // Fetch (ASC for chronological order)
            $stmt = $db->prepare(
                "SELECT * FROM n8n_chat_histories WHERE session_id = ? ORDER BY id ASC LIMIT $limit OFFSET $offset"
            );
            $stmt->execute([$sessionId]);
            $rows = $stmt->fetchAll();

            // Decode message JSON
            foreach ($rows as &$row) {
                if (isset($row['message']) && is_string($row['message'])) {
                    $decoded = json_decode($row['message'], true);
                    if ($decoded !== null) {
                        $row['message'] = $decoded;
                    }
                }
            }

            echo json_encode([
                'success'    => true,
                'data'       => $rows,
                'pagination' => [
                    'page'       => $page,
                    'limit'      => $limit,
                    'total'      => $total,
                    'totalPages' => (int) ceil($total / $limit),
                ],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * POST /api/chat-histories
     * Body: { session_id, message }
     */
    public static function create(): void
    {
        $input     = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? '';
        $message   = $input['message'] ?? null;

        if (empty($sessionId) || $message === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'session_id y message son requeridos.',
            ]);
            return;
        }

        try {
            $db = Database::getConnection();

            // Verify session exists
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Session no encontrada. Debe existir un bot_user con ese session_id.',
                ]);
                return;
            }

            // Validate message is valid JSON
            if (is_string($message)) {
                $decoded = json_decode($message, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'El campo message debe ser un JSON válido.',
                    ]);
                    return;
                }
                $messageJson = $decoded;
            } else {
                $messageJson = $message;
            }

            $stmt = $db->prepare('INSERT INTO n8n_chat_histories (session_id, message) VALUES (?, ?)');
            $stmt->execute([$sessionId, json_encode($messageJson)]);

            $insertId = $db->lastInsertId();

            // Fetch created record
            $stmt = $db->prepare('SELECT * FROM n8n_chat_histories WHERE id = ?');
            $stmt->execute([$insertId]);
            $row = $stmt->fetch();

            // Decode message JSON
            if (isset($row['message']) && is_string($row['message'])) {
                $decoded = json_decode($row['message'], true);
                if ($decoded !== null) {
                    $row['message'] = $decoded;
                }
            }

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Chat history creado exitosamente.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * PUT /api/chat-histories/{id}
     * Body: { message }
     */
    public static function update(string $id): void
    {
        $input   = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? null;

        if ($message === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'El campo message es requerido.',
            ]);
            return;
        }

        // Validate message JSON
        if (is_string($message)) {
            $decoded = json_decode($message, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El campo message debe ser un JSON válido.',
                ]);
                return;
            }
            $messageJson = $decoded;
        } else {
            $messageJson = $message;
        }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('UPDATE n8n_chat_histories SET message = ? WHERE id = ?');
            $stmt->execute([json_encode($messageJson), $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Chat history no encontrado.']);
                return;
            }

            $stmt = $db->prepare('SELECT * FROM n8n_chat_histories WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            // Decode message JSON
            if (isset($row['message']) && is_string($row['message'])) {
                $decoded = json_decode($row['message'], true);
                if ($decoded !== null) {
                    $row['message'] = $decoded;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Chat history actualizado exitosamente.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * DELETE /api/chat-histories/{id}
     */
    public static function remove(string $id): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('DELETE FROM n8n_chat_histories WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Chat history no encontrado.']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Chat history eliminado exitosamente.',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * DELETE /api/chat-histories/session/{sessionId}
     */
    public static function removeBySessionId(string $sessionId): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('DELETE FROM n8n_chat_histories WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            $count = $stmt->rowCount();

            echo json_encode([
                'success'      => true,
                'message'      => "$count registro(s) de chat eliminados para session $sessionId.",
                'deletedCount' => $count,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }
}
