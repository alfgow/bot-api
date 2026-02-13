require('dotenv').config();

const express = require('express');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const bcrypt = require('bcryptjs');
const pool = require('./src/config/database');
const { authenticate } = require('./src/middleware/auth');

// Routes
const authRoutes = require('./src/routes/auth.routes');
const botUsersRoutes = require('./src/routes/botUsers.routes');
const chatHistoriesRoutes = require('./src/routes/chatHistories.routes');

const app = express();
const PORT = process.env.PORT || 3000;

// ─── Middleware ───────────────────────────────────────────────
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 1000,                // limit each IP to 1000 requests per windowMs
  message: { success: false, message: 'Demasiadas solicitudes, intente más tarde.' },
});
app.use(limiter);

// Stricter rate limit for auth routes
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20,
  message: { success: false, message: 'Demasiados intentos de autenticación, intente más tarde.' },
});

// ─── Routes ──────────────────────────────────────────────────
app.use('/api/auth', authLimiter, authRoutes);
app.use('/api/bot-users', authenticate, botUsersRoutes);
app.use('/api/chat-histories', authenticate, chatHistoriesRoutes);

// Health check (public)
app.get('/api/health', (req, res) => {
  res.json({
    success: true,
    message: 'API Bot is running',
    timestamp: new Date().toISOString(),
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ success: false, message: 'Ruta no encontrada.' });
});

// Global error handler
app.use((err, req, res, _next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({ success: false, message: 'Error interno del servidor.' });
});

// ─── Database initialization & Server start ──────────────────
async function initializeDatabase() {
  try {
    // Test connection
    const connection = await pool.getConnection();
    console.log('✅ Conexión a MySQL exitosa');
    connection.release();

    // Create tables
    const fs = require('fs');
    const path = require('path');
    const initSql = fs.readFileSync(path.join(__dirname, 'src', 'database', 'init.sql'), 'utf8');

    // Split by semicolons and execute each statement
    const statements = initSql
      .split(';')
      .map(s => s.trim())
      .filter(s => s.length > 0 && !s.startsWith('--'));

    for (const statement of statements) {
      await pool.execute(statement);
    }
    console.log('✅ Tablas creadas/verificadas');

    // Create default admin user if doesn't exist
    const adminUsername = process.env.ADMIN_USERNAME || 'admin';
    const adminPassword = process.env.ADMIN_PASSWORD || 'admin123';

    const [existing] = await pool.execute(
      'SELECT id FROM api_users WHERE username = ?',
      [adminUsername]
    );

    if (existing.length === 0) {
      const salt = await bcrypt.genSalt(10);
      const hash = await bcrypt.hash(adminPassword, salt);
      await pool.execute(
        'INSERT INTO api_users (username, password_hash) VALUES (?, ?)',
        [adminUsername, hash]
      );
      console.log(`✅ Usuario admin creado: ${adminUsername}`);
    } else {
      console.log(`ℹ️  Usuario admin ya existe: ${adminUsername}`);
    }

    return true;
  } catch (err) {
    console.error('❌ Error inicializando base de datos:', err.message);
    return false;
  }
}

async function start() {
  const dbReady = await initializeDatabase();

  if (!dbReady) {
    console.error('⚠️  No se pudo conectar a la base de datos. Verifica tu configuración en .env');
    process.exit(1);
  }

  app.listen(PORT, () => {
    console.log(`\n🚀 API Bot corriendo en http://localhost:${PORT}`);
    console.log(`📖 Endpoints disponibles:`);
    console.log(`   POST   /api/auth/login`);
    console.log(`   POST   /api/auth/register          (🔒 requiere token)`);
    console.log(`   GET    /api/bot-users               (🔒 requiere token)`);
    console.log(`   GET    /api/bot-users/:sessionId    (🔒 requiere token)`);
    console.log(`   POST   /api/bot-users               (🔒 requiere token)`);
    console.log(`   PUT    /api/bot-users/:sessionId    (🔒 requiere token)`);
    console.log(`   DELETE /api/bot-users/:sessionId    (🔒 requiere token)`);
    console.log(`   GET    /api/chat-histories          (🔒 requiere token)`);
    console.log(`   GET    /api/chat-histories/:id      (🔒 requiere token)`);
    console.log(`   GET    /api/chat-histories/session/:sessionId (🔒 requiere token)`);
    console.log(`   POST   /api/chat-histories          (🔒 requiere token)`);
    console.log(`   PUT    /api/chat-histories/:id      (🔒 requiere token)`);
    console.log(`   DELETE /api/chat-histories/:id      (🔒 requiere token)`);
    console.log(`   DELETE /api/chat-histories/session/:sessionId (🔒 requiere token)`);
    console.log(`   GET    /api/health                  (público)\n`);
  });
}

start();
