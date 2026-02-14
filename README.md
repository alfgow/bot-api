# 🤖 Bot API — PHP

API REST para gestionar usuarios de bot y su historial de chat.  
Construida en **PHP puro** (sin frameworks ni Composer), lista para hosting compartido.

---

##  Endpoints de la API

### Auth (Autenticación)

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `POST` | `/api/auth/login` | 🌐 Público | Iniciar sesión |
| `POST` | `/api/auth/register` | 🔒 Token | Crear nuevo usuario API |

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
