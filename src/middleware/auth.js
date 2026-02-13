const jwt = require('jsonwebtoken');

/**
 * Middleware to verify JWT Bearer Token.
 * Expects header: Authorization: Bearer <token>
 */
function authenticate(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({
      success: false,
      message: 'Acceso denegado. Token no proporcionado.',
    });
  }

  const token = authHeader.split(' ')[1];

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded;
    next();
  } catch (err) {
    if (err.name === 'TokenExpiredError') {
      return res.status(401).json({
        success: false,
        message: 'Token expirado.',
      });
    }
    return res.status(401).json({
      success: false,
      message: 'Token inválido.',
    });
  }
}

module.exports = { authenticate };
