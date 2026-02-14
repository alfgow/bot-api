<?php
/**
 * ─── BotUsers Controller ──────────────────────────────────
 * CRUD for bot_users table
 */

require_once __DIR__ . '/../database.php';

class BotUsersController
{
    private const ALLOWED_FIELDS = [
        'session_id', 'status', 'api_contact_id', 'nombre', 'telefono_real',
        'rol', 'bot_status', 'rejected_count', 'questionnaire_status',
        'property_id', 'count_outcontext', 'last_intencion', 'last_accion',
        'last_bot_reply', 'veces_pidiendo_nombre', 'veces_pidiendo_telefono',
    ];

    /**
     * GET /api/bot-users
     * Query params: ?status=new&bot_status=free&page=1&limit=20
     */
    public static function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page  = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $filterableFields = ['status', 'bot_status', 'questionnaire_status', 'rol', 'nombre'];
            $filters = [];
            $params  = [];

            foreach ($filterableFields as $field) {
                if (!empty($_GET[$field])) {
                    if ($field === 'nombre') {
                        $filters[] = "$field LIKE ?";
                        $params[]  = '%' . $_GET[$field] . '%';
                    } else {
                        $filters[] = "$field = ?";
                        $params[]  = $_GET[$field];
                    }
                }
            }

            $whereClause = count($filters) > 0 ? 'WHERE ' . implode(' AND ', $filters) : '';

            // Count total
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM bot_users $whereClause");
            $stmt->execute($params);
            $total = (int) $stmt->fetch()['total'];

            // Fetch paginated
            $stmt = $db->prepare(
                "SELECT * FROM bot_users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

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
     * GET /api/bot-users/{sessionId}
     */
    public static function getById(string $sessionId): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bot user no encontrado.']);
                return;
            }

            echo json_encode(['success' => true, 'data' => $row]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * POST /api/bot-users
     * Body: { session_id, status?, nombre?, ... }
     */
    public static function create(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? '';

        if (empty($sessionId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'session_id es requerido.']);
            return;
        }

        try {
            $db = Database::getConnection();

            // Check if already exists
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ya existe un bot_user con ese session_id.',
                ]);
                return;
            }

            // Build INSERT dynamically
            $fields       = [];
            $placeholders = [];
            $values       = [];

            foreach (self::ALLOWED_FIELDS as $field) {
                if (isset($input[$field])) {
                    $fields[]       = $field;
                    $placeholders[] = '?';
                    $values[]       = $input[$field];
                }
            }

            $sql = 'INSERT INTO bot_users (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            // Fetch created record
            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Bot user creado exitosamente.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * PUT /api/bot-users/{sessionId}
     * Body: { status?, nombre?, telefono_real?, ... }
     */
    public static function update(string $sessionId): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $db = Database::getConnection();

            // Check existence
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bot user no encontrado.']);
                return;
            }

            // Build SET clause (exclude session_id from updates)
            $updates = [];
            $values  = [];
            $updatableFields = array_filter(self::ALLOWED_FIELDS, fn($f) => $f !== 'session_id');

            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $input)) {
                    $updates[] = "$field = ?";
                    $values[]  = $input[$field];
                }
            }

            if (count($updates) === 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se proporcionaron campos para actualizar.',
                ]);
                return;
            }

            $values[] = $sessionId;
            $sql = 'UPDATE bot_users SET ' . implode(', ', $updates) . ' WHERE session_id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            // Fetch updated record
            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Bot user actualizado exitosamente.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * POST /api/bot-users/upsert
     * Body: { session_id, status?, nombre?, ... }
     * Creates the bot_user if it doesn't exist, updates it if it does.
     */
    public static function upsert(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? '';

        if (empty($sessionId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'session_id es requerido.']);
            return;
        }

        try {
            $db = Database::getConnection();

            // Build fields dynamically
            $fields       = [];
            $placeholders = [];
            $values       = [];
            $updateParts  = [];

            foreach (self::ALLOWED_FIELDS as $field) {
                if (isset($input[$field])) {
                    $fields[]       = $field;
                    $placeholders[] = '?';
                    $values[]       = $input[$field];

                    if ($field !== 'session_id') {
                        $updateParts[] = "$field = VALUES($field)";
                    }
                }
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No se proporcionaron campos.']);
                return;
            }

            $sql = 'INSERT INTO bot_users (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

            if (!empty($updateParts)) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            $created = ($stmt->rowCount() === 1);

            // Fetch record
            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            http_response_code($created ? 201 : 200);
            echo json_encode([
                'success' => true,
                'message' => $created ? 'Bot user creado.' : 'Bot user actualizado.',
                'created' => $created,
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * PATCH /api/bot-users/session/{sessionId}
     * Body: { status?, bot_status?, nombre?, ... }
     * Partial update — only the provided fields are changed.
     */
    public static function patch(string $sessionId): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            $db = Database::getConnection();

            // Check existence
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bot user no encontrado.']);
                return;
            }

            $updates = [];
            $values  = [];
            $updatableFields = array_filter(self::ALLOWED_FIELDS, fn($f) => $f !== 'session_id');

            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $input)) {
                    $updates[] = "$field = ?";
                    $values[]  = $input[$field];
                }
            }

            if (count($updates) === 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se proporcionaron campos para actualizar.',
                ]);
                return;
            }

            $values[] = $sessionId;
            $sql = 'UPDATE bot_users SET ' . implode(', ', $updates) . ' WHERE session_id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            // Fetch updated record
            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Bot user actualizado.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * POST /api/bot-users/session/{sessionId}/counters
     * Body: { rejected_count?: 1, count_outcontext?: 1, veces_pidiendo_nombre?: -1, ... }
     * Atomic increments/decrements on numeric fields.
     */
    public static function incrementCounters(string $sessionId): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $counterFields = [
            'rejected_count', 'count_outcontext',
            'veces_pidiendo_nombre', 'veces_pidiendo_telefono',
        ];

        try {
            $db = Database::getConnection();

            // Check existence
            $stmt = $db->prepare('SELECT session_id FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bot user no encontrado.']);
                return;
            }

            $updates = [];
            $values  = [];

            foreach ($counterFields as $field) {
                if (isset($input[$field]) && is_numeric($input[$field])) {
                    $increment = (int) $input[$field];
                    $updates[] = "$field = GREATEST(0, $field + ?)";
                    $values[]  = $increment;
                }
            }

            if (count($updates) === 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se proporcionaron contadores válidos.',
                    'allowed' => $counterFields,
                ]);
                return;
            }

            $values[] = $sessionId;
            $sql = 'UPDATE bot_users SET ' . implode(', ', $updates) . ' WHERE session_id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            // Fetch updated record
            $stmt = $db->prepare('SELECT * FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Contadores actualizados.',
                'data'    => $row,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * DELETE /api/bot-users/{sessionId}
     */
    public static function remove(string $sessionId): void
    {
        try {
            $db = Database::getConnection();

            $stmt = $db->prepare('DELETE FROM bot_users WHERE session_id = ?');
            $stmt->execute([$sessionId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bot user no encontrado.']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Bot user eliminado exitosamente (y su historial de chat asociado).',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
    }
}
