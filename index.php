<?php
/**
 * ─── Bot API — Front Controller (Router) ──────────────────
 * All requests are routed through this file via .htaccess
 *
 * Endpoints:
 *   POST   /api/auth/login
 *   POST   /api/auth/register          (🔒 requires token)
 *   GET    /api/bot-users               (🔒 requires token)
 *   GET    /api/bot-users/{sessionId}   (🔒 requires token)
 *   POST   /api/bot-users               (🔒 requires token)
 *   PUT    /api/bot-users/{sessionId}   (🔒 requires token)
 *   DELETE /api/bot-users/{sessionId}   (🔒 requires token)
 *   GET    /api/chat-histories          (🔒 requires token)
 *   GET    /api/chat-histories/{id}     (🔒 requires token)
 *   GET    /api/chat-histories/session/{sessionId} (🔒 requires token)
 *   POST   /api/chat-histories          (🔒 requires token)
 *   PUT    /api/chat-histories/{id}     (🔒 requires token)
 *   DELETE /api/chat-histories/{id}     (🔒 requires token)
 *   DELETE /api/chat-histories/session/{sessionId} (🔒 requires token)
 *   GET    /api/health                  (public)
 */

// ─── CORS Headers ─────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── Check config exists ──────────────────────────────────
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'config.php no encontrado. Copia config.example.php a config.php y configura tus credenciales.',
    ]);
    exit;
}

// ─── Requires ─────────────────────────────────────────────
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BotUsersController.php';
require_once __DIR__ . '/controllers/ChatHistoriesController.php';

// ─── Parse Request ────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

// Get the path relative to this script
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Remove base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
if ($scriptName !== '/' && $scriptName !== '\\') {
    $path = substr($path, strlen($scriptName));
}

// Normalize: remove trailing slash, ensure leading slash
$path = '/' . trim($path, '/');

// ─── Router ───────────────────────────────────────────────

// ── Health Check (public) ─────────────────────────────────
if ($path === '/api/health' && $method === 'GET') {
    echo json_encode([
        'success'   => true,
        'message'   => 'API Bot is running',
        'timestamp' => date('c'),
    ]);
    exit;
}

// ── Auth Routes ───────────────────────────────────────────
if ($path === '/api/auth/login' && $method === 'POST') {
    AuthController::login();
    exit;
}

if ($path === '/api/auth/register' && $method === 'POST') {
    if (!authenticate()) exit;
    AuthController::register();
    exit;
}

// ── Bot Users Routes (all protected) ──────────────────────

// IMPORTANT: Specific routes MUST come before parameterized routes

// POST /api/bot-users/upsert
if ($path === '/api/bot-users/upsert' && $method === 'POST') {
    if (!authenticate()) exit;
    BotUsersController::upsert();
    exit;
}

// GET /api/bot-users/session/{sessionId}
if (preg_match('#^/api/bot-users/session/([^/]+)$#', $path, $matches) && $method === 'GET') {
    if (!authenticate()) exit;
    BotUsersController::getById($matches[1]);
    exit;
}

// PATCH /api/bot-users/session/{sessionId}
if (preg_match('#^/api/bot-users/session/([^/]+)$#', $path, $matches) && $method === 'PATCH') {
    if (!authenticate()) exit;
    BotUsersController::patch($matches[1]);
    exit;
}

// POST /api/bot-users/session/{sessionId}/counters
if (preg_match('#^/api/bot-users/session/([^/]+)/counters$#', $path, $matches) && $method === 'POST') {
    if (!authenticate()) exit;
    BotUsersController::incrementCounters($matches[1]);
    exit;
}

// GET /api/bot-users
if ($path === '/api/bot-users' && $method === 'GET') {
    if (!authenticate()) exit;
    BotUsersController::getAll();
    exit;
}

// POST /api/bot-users
if ($path === '/api/bot-users' && $method === 'POST') {
    if (!authenticate()) exit;
    BotUsersController::create();
    exit;
}

// GET /api/bot-users/{sessionId}  (legacy, same as /session/{sessionId})
if (preg_match('#^/api/bot-users/([^/]+)$#', $path, $matches) && $method === 'GET') {
    if (!authenticate()) exit;
    BotUsersController::getById($matches[1]);
    exit;
}

// PUT /api/bot-users/{sessionId}
if (preg_match('#^/api/bot-users/([^/]+)$#', $path, $matches) && $method === 'PUT') {
    if (!authenticate()) exit;
    BotUsersController::update($matches[1]);
    exit;
}

// DELETE /api/bot-users/{sessionId}
if (preg_match('#^/api/bot-users/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    if (!authenticate()) exit;
    BotUsersController::remove($matches[1]);
    exit;
}

// ── Chat Histories Routes (all protected) ─────────────────

// IMPORTANT: Specific routes MUST come before parameterized routes

// GET /api/chat-histories/session/{sessionId}
if (preg_match('#^/api/chat-histories/session/([^/]+)$#', $path, $matches) && $method === 'GET') {
    if (!authenticate()) exit;
    ChatHistoriesController::getBySessionId($matches[1]);
    exit;
}

// DELETE /api/chat-histories/session/{sessionId}
if (preg_match('#^/api/chat-histories/session/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
    if (!authenticate()) exit;
    ChatHistoriesController::removeBySessionId($matches[1]);
    exit;
}

// GET /api/chat-histories
if ($path === '/api/chat-histories' && $method === 'GET') {
    if (!authenticate()) exit;
    ChatHistoriesController::getAll();
    exit;
}

// POST /api/chat-histories
if ($path === '/api/chat-histories' && $method === 'POST') {
    if (!authenticate()) exit;
    ChatHistoriesController::create();
    exit;
}

// GET /api/chat-histories/{id}
if (preg_match('#^/api/chat-histories/(\d+)$#', $path, $matches) && $method === 'GET') {
    if (!authenticate()) exit;
    ChatHistoriesController::getById($matches[1]);
    exit;
}

// PUT /api/chat-histories/{id}
if (preg_match('#^/api/chat-histories/(\d+)$#', $path, $matches) && $method === 'PUT') {
    if (!authenticate()) exit;
    ChatHistoriesController::update($matches[1]);
    exit;
}

// DELETE /api/chat-histories/{id}
if (preg_match('#^/api/chat-histories/(\d+)$#', $path, $matches) && $method === 'DELETE') {
    if (!authenticate()) exit;
    ChatHistoriesController::remove($matches[1]);
    exit;
}

// ── 404 — Route Not Found ─────────────────────────────────
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Ruta no encontrada.']);
