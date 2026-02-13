const pool = require('../config/database');

/**
 * GET /api/chat-histories
 * Query params: ?session_id=abc123&page=1&limit=50
 */
async function getAll(req, res) {
  try {
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 50));
    const offset = (page - 1) * limit;

    const filters = [];
    const params = [];

    if (req.query.session_id) {
      filters.push('session_id = ?');
      params.push(req.query.session_id);
    }

    const whereClause = filters.length > 0 ? `WHERE ${filters.join(' AND ')}` : '';

    // Count
    const [countRows] = await pool.execute(
      `SELECT COUNT(*) as total FROM n8n_chat_histories ${whereClause}`,
      params
    );
    const total = countRows[0].total;

    // Fetch
    const [rows] = await pool.execute(
      `SELECT * FROM n8n_chat_histories ${whereClause} ORDER BY id DESC LIMIT ${limit} OFFSET ${offset}`,
      params
    );

    return res.json({
      success: true,
      data: rows,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit),
      },
    });
  } catch (err) {
    console.error('getAll chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * GET /api/chat-histories/:id
 */
async function getById(req, res) {
  try {
    const { id } = req.params;

    const [rows] = await pool.execute(
      'SELECT * FROM n8n_chat_histories WHERE id = ?',
      [id]
    );

    if (rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Chat history no encontrado.',
      });
    }

    return res.json({ success: true, data: rows[0] });
  } catch (err) {
    console.error('getById chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * GET /api/chat-histories/session/:sessionId
 * Returns all chat messages for a specific session (ordered chronologically)
 */
async function getBySessionId(req, res) {
  try {
    const { sessionId } = req.params;
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(200, Math.max(1, parseInt(req.query.limit, 10) || 50));
    const offset = (page - 1) * limit;

    // Verify session exists
    const [sessionRows] = await pool.execute(
      'SELECT session_id FROM bot_users WHERE session_id = ?',
      [sessionId]
    );

    if (sessionRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Session no encontrada.',
      });
    }

    const [countRows] = await pool.execute(
      'SELECT COUNT(*) as total FROM n8n_chat_histories WHERE session_id = ?',
      [sessionId]
    );
    const total = countRows[0].total;

    const [rows] = await pool.execute(
      `SELECT * FROM n8n_chat_histories WHERE session_id = ? ORDER BY id ASC LIMIT ${limit} OFFSET ${offset}`,
      [sessionId]
    );

    return res.json({
      success: true,
      data: rows,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit),
      },
    });
  } catch (err) {
    console.error('getBySessionId chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * POST /api/chat-histories
 * Body: { session_id, message }
 */
async function create(req, res) {
  try {
    const { session_id, message } = req.body;

    if (!session_id || !message) {
      return res.status(400).json({
        success: false,
        message: 'session_id y message son requeridos.',
      });
    }

    // Verify session exists
    const [sessionRows] = await pool.execute(
      'SELECT session_id FROM bot_users WHERE session_id = ?',
      [session_id]
    );

    if (sessionRows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Session no encontrada. Debe existir un bot_user con ese session_id.',
      });
    }

    // Validate message is valid JSON (or object)
    let messageJson;
    if (typeof message === 'string') {
      try {
        messageJson = JSON.parse(message);
      } catch {
        return res.status(400).json({
          success: false,
          message: 'El campo message debe ser un JSON válido.',
        });
      }
    } else {
      messageJson = message;
    }

    const [result] = await pool.execute(
      'INSERT INTO n8n_chat_histories (session_id, message) VALUES (?, ?)',
      [session_id, JSON.stringify(messageJson)]
    );

    // Fetch created record
    const [rows] = await pool.execute(
      'SELECT * FROM n8n_chat_histories WHERE id = ?',
      [result.insertId]
    );

    return res.status(201).json({
      success: true,
      message: 'Chat history creado exitosamente.',
      data: rows[0],
    });
  } catch (err) {
    console.error('create chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * PUT /api/chat-histories/:id
 * Body: { message }
 */
async function update(req, res) {
  try {
    const { id } = req.params;
    const { message } = req.body;

    if (!message) {
      return res.status(400).json({
        success: false,
        message: 'El campo message es requerido.',
      });
    }

    // Validate message JSON
    let messageJson;
    if (typeof message === 'string') {
      try {
        messageJson = JSON.parse(message);
      } catch {
        return res.status(400).json({
          success: false,
          message: 'El campo message debe ser un JSON válido.',
        });
      }
    } else {
      messageJson = message;
    }

    const [result] = await pool.execute(
      'UPDATE n8n_chat_histories SET message = ? WHERE id = ?',
      [JSON.stringify(messageJson), id]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        success: false,
        message: 'Chat history no encontrado.',
      });
    }

    const [rows] = await pool.execute(
      'SELECT * FROM n8n_chat_histories WHERE id = ?',
      [id]
    );

    return res.json({
      success: true,
      message: 'Chat history actualizado exitosamente.',
      data: rows[0],
    });
  } catch (err) {
    console.error('update chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * DELETE /api/chat-histories/:id
 */
async function remove(req, res) {
  try {
    const { id } = req.params;

    const [result] = await pool.execute(
      'DELETE FROM n8n_chat_histories WHERE id = ?',
      [id]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        success: false,
        message: 'Chat history no encontrado.',
      });
    }

    return res.json({
      success: true,
      message: 'Chat history eliminado exitosamente.',
    });
  } catch (err) {
    console.error('remove chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * DELETE /api/chat-histories/session/:sessionId
 * Deletes ALL chat histories for a session
 */
async function removeBySessionId(req, res) {
  try {
    const { sessionId } = req.params;

    const [result] = await pool.execute(
      'DELETE FROM n8n_chat_histories WHERE session_id = ?',
      [sessionId]
    );

    return res.json({
      success: true,
      message: `${result.affectedRows} registro(s) de chat eliminados para session ${sessionId}.`,
      deletedCount: result.affectedRows,
    });
  } catch (err) {
    console.error('removeBySessionId chat_histories error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

module.exports = { getAll, getById, getBySessionId, create, update, remove, removeBySessionId };
