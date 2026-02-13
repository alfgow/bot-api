# 🤖 Bot API — CRUD con JWT Bearer Token

API REST para gestionar `bot_users` y `n8n_chat_histories` con autenticación JWT (Bearer Token con vigencia de **1 año**).

## 📋 Requisitos

- **Node.js** >= 18
- **MySQL** >= 8.0
- Base de datos creada previamente (ejemplo: `bot_db`)

## 🚀 Instalación

```bash
# 1. Instalar dependencias
npm install

# 2. Configurar variables de entorno
# Editar .env con tus credenciales de MySQL
#   DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME

# 3. Crear la base de datos en MySQL (si no existe)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS bot_db;"

# 4. Iniciar el servidor (las tablas se crean automáticamente)
npm run dev
```

## 🔐 Autenticación

La API usa **JWT Bearer Token**. El token tiene una vigencia de **365 días** (1 año).

### Login
```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'
```

**Respuesta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "expiresIn": "365d"
}
```

### Usar el token
Incluir en todos los requests protegidos:
```
Authorization: Bearer <tu_token>
```

### Registrar nuevo usuario (requiere token)
```bash
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <tu_token>" \
  -d '{"username": "nuevo_usuario", "password": "password123"}'
```

---

## 📡 Endpoints

### Health Check
| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/health` | ❌ | Estado del servidor |

### Auth
| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/auth/login` | ❌ | Obtener token |
| POST | `/api/auth/register` | ✅ | Crear nuevo usuario API |

### Bot Users
| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/bot-users` | ✅ | Listar todos (paginado) |
| GET | `/api/bot-users/:sessionId` | ✅ | Obtener por session_id |
| POST | `/api/bot-users` | ✅ | Crear nuevo |
| PUT | `/api/bot-users/:sessionId` | ✅ | Actualizar |
| DELETE | `/api/bot-users/:sessionId` | ✅ | Eliminar (cascade a chat) |

**Filtros disponibles en GET /api/bot-users:**
- `?status=new`
- `?bot_status=free`
- `?questionnaire_status=none`
- `?rol=cliente`
- `?nombre=Juan` (búsqueda parcial)
- `?page=1&limit=20`

### Chat Histories
| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/chat-histories` | ✅ | Listar todos (paginado) |
| GET | `/api/chat-histories/:id` | ✅ | Obtener por ID |
| GET | `/api/chat-histories/session/:sessionId` | ✅ | Todos los mensajes de una sesión |
| POST | `/api/chat-histories` | ✅ | Crear mensaje |
| PUT | `/api/chat-histories/:id` | ✅ | Actualizar mensaje |
| DELETE | `/api/chat-histories/:id` | ✅ | Eliminar mensaje |
| DELETE | `/api/chat-histories/session/:sessionId` | ✅ | Eliminar TODOS los mensajes de una sesión |

---

## 📝 Ejemplos de uso

### Crear un bot_user
```bash
curl -X POST http://localhost:3000/api/bot-users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "session_id": "wa_521234567890",
    "nombre": "Juan Pérez",
    "telefono_real": "+521234567890",
    "rol": "cliente"
  }'
```

### Crear un mensaje de chat
```bash
curl -X POST http://localhost:3000/api/chat-histories \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "session_id": "wa_521234567890",
    "message": {"type": "human", "content": "Hola, quiero información"}
  }'
```

### Obtener historial de chat de una sesión
```bash
curl http://localhost:3000/api/chat-histories/session/wa_521234567890 \
  -H "Authorization: Bearer <token>"
```

---

## 🏗️ Estructura del Proyecto

```
bot-api/
├── .env                          # Variables de entorno
├── .env.example                  # Template de variables
├── package.json
├── server.js                     # Entry point + inicialización
└── src/
    ├── config/
    │   └── database.js           # Pool de conexiones MySQL
    ├── controllers/
    │   ├── auth.controller.js    # Login y registro
    │   ├── botUsers.controller.js     # CRUD bot_users
    │   └── chatHistories.controller.js # CRUD n8n_chat_histories
    ├── database/
    │   └── init.sql              # Schema de tablas
    ├── middleware/
    │   └── auth.js               # Verificación JWT
    └── routes/
        ├── auth.routes.js
        ├── botUsers.routes.js
        └── chatHistories.routes.js
```

## ⚙️ Variables de Entorno

| Variable | Descripción | Default |
|----------|-------------|---------|
| `PORT` | Puerto del servidor | `3000` |
| `DB_HOST` | Host de MySQL | `localhost` |
| `DB_PORT` | Puerto de MySQL | `3306` |
| `DB_USER` | Usuario de MySQL | `root` |
| `DB_PASSWORD` | Password de MySQL | — |
| `DB_NAME` | Nombre de la BD | `bot_db` |
| `JWT_SECRET` | Clave secreta para JWT | — |
| `JWT_EXPIRES_IN` | Vigencia del token | `365d` |
| `ADMIN_USERNAME` | Username del admin inicial | `admin` |
| `ADMIN_PASSWORD` | Password del admin inicial | `admin123` |
