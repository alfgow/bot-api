const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const pool = require('../config/database');

/**
 * POST /api/auth/login
 * Body: { username, password }
 * Returns: { success, token, expiresIn }
 */
async function login(req, res) {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({
        success: false,
        message: 'Username y password son requeridos.',
      });
    }

    const [rows] = await pool.execute(
      'SELECT * FROM api_users WHERE username = ? AND is_active = 1',
      [username]
    );

    if (rows.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Credenciales inválidas.',
      });
    }

    const user = rows[0];
    const isMatch = await bcrypt.compare(password, user.password_hash);

    if (!isMatch) {
      return res.status(401).json({
        success: false,
        message: 'Credenciales inválidas.',
      });
    }

    const token = jwt.sign(
      { id: user.id, username: user.username },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '365d' }
    );

    return res.json({
      success: true,
      token,
      expiresIn: process.env.JWT_EXPIRES_IN || '365d',
    });
  } catch (err) {
    console.error('Login error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

/**
 * POST /api/auth/register
 * Body: { username, password }
 * Requires: Bearer Token (only authenticated users can create new users)
 */
async function register(req, res) {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({
        success: false,
        message: 'Username y password son requeridos.',
      });
    }

    if (password.length < 6) {
      return res.status(400).json({
        success: false,
        message: 'El password debe tener al menos 6 caracteres.',
      });
    }

    // Check if username already exists
    const [existing] = await pool.execute(
      'SELECT id FROM api_users WHERE username = ?',
      [username]
    );

    if (existing.length > 0) {
      return res.status(409).json({
        success: false,
        message: 'El username ya existe.',
      });
    }

    const salt = await bcrypt.genSalt(10);
    const passwordHash = await bcrypt.hash(password, salt);

    const [result] = await pool.execute(
      'INSERT INTO api_users (username, password_hash) VALUES (?, ?)',
      [username, passwordHash]
    );

    return res.status(201).json({
      success: true,
      message: 'Usuario creado exitosamente.',
      data: { id: result.insertId, username },
    });
  } catch (err) {
    console.error('Register error:', err);
    return res.status(500).json({ success: false, message: 'Error interno del servidor.' });
  }
}

module.exports = { login, register };
