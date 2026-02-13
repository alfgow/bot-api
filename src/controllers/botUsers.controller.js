const pool = require('../config/database');

// Campos permitidos para crear/actualizar bot_users
const ALLOWED_FIELDS = [
  'session_id', 'status', 'api_contact_id', 'nombre', 'telefono_real',
  'rol', 'bot_status', 'rejected_count', 'questionnaire_status',
  'property_id', 'count_outcontext', 'last_intencion', 'last_accion',
  'last_bot_reply', 'veces_pidiendo_nombre', 'veces_pidiendo_telefono',
];

/**
 * GET /api/bot-users
 * Query params: ?status=new&bot_status=free&page=1&limit=20
 */
async function getAll(req, res) {
  try {
    const page = Math.max(1, parseInt(req.query.page, 10) || 1);
    const limit = Math.min(100, Math.max(1, parseInt(req.query.limit, 10) || 20));
    const offset = (page - 1) * limit;

    // Build dynamic WHERE clauses from query params
    const filters = [];
    const params = [];
    const filterableFields = ['status', 'bot_status', 'questionnaire_status', 'rol', 'nombre'];

    for (const field of filterableFields) {
      if (req.query[field]) {
        if (field === 'nombre') {
          filters.push(`${field} LIKE ?`);
          params.push(`%${req.query[field]}%`);
        } else {
          filters.push(`${field} = ?`);
          params.push(req.query[field]);
        }
      }
    }

    const whereClause = filters.length > 0 ? `WHERE ${filters.join(' AND ')}` : '';

    // Count total
    const [countRows] = await pool.execute(
      `SELECT COUNT(*) as total FROM bot_users ${whereClause}`,
      params
    );
    const total = countRows[0].total;

    // Fetch paginated results
    const [rows] = await pool.execute(
      `SELECT * FROM bot_users ${whereClause} ORDER BY created_at DESC LIMIT ${limit} OFFSET ${offset}`,
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
    console.error('getAll bot_users error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * GET /api/bot-users/:sessionId
 */
async function getById(req, res) {
  try {
    const { sessionId } = req.params;

    const [rows] = await pool.execute(
      'SELECT * FROM bot_users WHERE session_id = ?',
      [sessionId]
    );

    if (rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Bot user no encontrado.',
      });
    }

    return res.json({ success: true, data: rows[0] });
  } catch (err) {
    console.error('getById bot_users error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * POST /api/bot-users
 * Body: { session_id, status?, nombre?, ... }
 */
async function create(req, res) {
  try {
    const { session_id } = req.body;

    if (!session_id) {
      return res.status(400).json({
        success: false,
        message: 'session_id es requerido.',
      });
    }

    // Check if already exists
    const [existing] = await pool.execute(
      'SELECT session_id FROM bot_users WHERE session_id = ?',
      [session_id]
    );

    if (existing.length > 0) {
      return res.status(409).json({
        success: false,
        message: 'Ya existe un bot_user con ese session_id.',
      });
    }

    // Build INSERT dynamically from allowed fields
    const fields = [];
    const placeholders = [];
    const values = [];

    for (const field of ALLOWED_FIELDS) {
      if (req.body[field] !== undefined) {
        fields.push(field);
        placeholders.push('?');
        values.push(req.body[field]);
      }
    }

    const sql = `INSERT INTO bot_users (${fields.join(', ')}) VALUES (${placeholders.join(', ')})`;
    await pool.execute(sql, values);

    // Fetch the created record
    const [rows] = await pool.execute(
      'SELECT * FROM bot_users WHERE session_id = ?',
      [session_id]
    );

    return res.status(201).json({
      success: true,
      message: 'Bot user creado exitosamente.',
      data: rows[0],
    });
  } catch (err) {
    console.error('create bot_users error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * PUT /api/bot-users/:sessionId
 * Body: { status?, nombre?, telefono_real?, ... }
 */
async function update(req, res) {
  try {
    const { sessionId } = req.params;

    // Check existence
    const [existing] = await pool.execute(
      'SELECT session_id FROM bot_users WHERE session_id = ?',
      [sessionId]
    );

    if (existing.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Bot user no encontrado.',
      });
    }

    // Build SET clause dynamically (exclude session_id from updates)
    const updates = [];
    const values = [];
    const updatableFields = ALLOWED_FIELDS.filter(f => f !== 'session_id');

    for (const field of updatableFields) {
      if (req.body[field] !== undefined) {
        updates.push(`${field} = ?`);
        values.push(req.body[field]);
      }
    }

    if (updates.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'No se proporcionaron campos para actualizar.',
      });
    }

    values.push(sessionId);
    const sql = `UPDATE bot_users SET ${updates.join(', ')} WHERE session_id = ?`;
    await pool.execute(sql, values);

    // Fetch updated record
    const [rows] = await pool.execute(
      'SELECT * FROM bot_users WHERE session_id = ?',
      [sessionId]
    );

    return res.json({
      success: true,
      message: 'Bot user actualizado exitosamente.',
      data: rows[0],
    });
  } catch (err) {
    console.error('update bot_users error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * DELETE /api/bot-users/:sessionId
 */
async function remove(req, res) {
  try {
    const { sessionId } = req.params;

    const [result] = await pool.execute(
      'DELETE FROM bot_users WHERE session_id = ?',
      [sessionId]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        success: false,
        message: 'Bot user no encontrado.',
      });
    }

    return res.json({
      success: true,
      message: 'Bot user eliminado exitosamente (y su historial de chat asociado).',
    });
  } catch (err) {
    console.error('remove bot_users error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

module.exports = { getAll, getById, create, update, remove };
