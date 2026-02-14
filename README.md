# 🤖 Bot API — PHP

API REST para gestionar usuarios de bot y su historial de chat.  
Construida en **PHP puro** (sin frameworks ni Composer), lista para hosting compartido.

---

## 📖 Endpoints de la API

### Auth (Autenticación)

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `POST` | `/api/auth/login` | 🌐 Público | Iniciar sesión |
| `POST` | `/api/auth/register` | 🔒 Token | Crear nuevo usuario API |

### Bot Users

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/bot-users` | 🔒 Token | Listar todos (paginado) |
| `GET` | `/api/bot-users/session/{sessionId}` | 🔒 Token | Obtener usuario por sesión |
| `POST` | `/api/bot-users` | 🔒 Token | Crear nuevo |
| `POST` | `/api/bot-users/upsert` | 🔒 Token | Crear o actualizar si ya existe |
| `PUT` | `/api/bot-users/{sessionId}` | 🔒 Token | Actualizar (completo) |
| `PATCH` | `/api/bot-users/session/{sessionId}` | 🔒 Token | Actualización parcial de campos |
| `POST` | `/api/bot-users/session/{sessionId}/counters` | 🔒 Token | Incrementos atómicos de contadores |
| `DELETE` | `/api/bot-users/{sessionId}` | 🔒 Token | Eliminar |

**Campos soportados:** `status`, `bot_status`, `questionnaire_status`, `property_id`, `api_contact_id`, `nombre`, `telefono_real`, `rol`, `rejected_count`, `count_outcontext`, `last_intencion`, `last_accion`, `last_bot_reply`, `veces_pidiendo_nombre`, `veces_pidiendo_telefono`

**Contadores (para `/counters`):** `rejected_count`, `count_outcontext`, `veces_pidiendo_nombre`, `veces_pidiendo_telefono`

### Chat Histories

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/chat-histories` | 🔒 Token | Listar todos (paginado) |
| `GET` | `/api/chat-histories/{id}` | 🔒 Token | Obtener uno por ID |
| `GET` | `/api/chat-histories/session/{sessionId}` | 🔒 Token | Obtener por sesión |
| `POST` | `/api/chat-histories` | 🔒 Token | Crear nuevo |
| `PUT` | `/api/chat-histories/{id}` | 🔒 Token | Actualizar |
| `DELETE` | `/api/chat-histories/{id}` | 🔒 Token | Eliminar uno |
| `DELETE` | `/api/chat-histories/session/{sessionId}` | 🔒 Token | Eliminar todos de una sesión |
