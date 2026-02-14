# 🤖 Bot API — PHP

API REST para gestionar usuarios de bot y su historial de chat.  
Construida en **PHP puro** (sin frameworks ni Composer), lista para hosting compartido.

---

## 📁 Estructura del Proyecto

```
bot-api/
├── .htaccess                          ← Reescritura de URLs
├── index.php                          ← Router principal
├── config.php                         ← ⚠️ Credenciales (NO se sube a git)
├── config.example.php                 ← Template de configuración
├── database.php                       ← Conexión PDO a MySQL
├── install.php                        ← Instalador (ejecutar 1 sola vez)
├── controllers/
│   ├── AuthController.php             ← Login / Register
│   ├── BotUsersController.php         ← CRUD bot_users
│   └── ChatHistoriesController.php    ← CRUD chat_histories
├── helpers/
│   └── JWT.php                        ← JWT HS256 (sin dependencias)
├── middleware/
│   └── auth.php                       ← Verificación Bearer Token
└── src/database/
    └── init.sql                       ← Schema de tablas
```

---

## 🚀 Instructivo de Despliegue en IONOS

### Paso 1: Subir archivos al servidor

Usa el **File Manager de IONOS** o un **cliente FTP** (FileZilla, WinSCP, etc.) para subir los archivos.

1. Conéctate a tu hosting IONOS por FTP:
   - **Host:** tu dominio o la IP del servidor
   - **Usuario:** tu usuario FTP de IONOS
   - **Password:** tu contraseña FTP de IONOS
   - **Puerto:** 21

2. Navega a la carpeta raíz de tu dominio/subdominio. En IONOS generalmente es:
   - `/` (raíz) si es el dominio principal
   - O la carpeta que hayas asignado al subdominio

3. Sube **todos los archivos y carpetas** del proyecto:
   ```
   .htaccess
   index.php
   config.example.php
   database.php
   install.php
   controllers/       (carpeta completa)
   helpers/            (carpeta completa)
   middleware/         (carpeta completa)
   src/                (carpeta completa)
   ```

   > ⚠️ **NO subas:** `node_modules/`, `server.js`, `package.json`, `.git/`, `config.php` (lo crearás directamente en el servidor).

---

### Paso 2: Crear el archivo `config.php` en el servidor

En el servidor, **copia** `config.example.php` y renómbralo a `config.php`. Luego edítalo con tus datos reales:

```php
<?php
// Database — datos de tu MySQL en IONOS
define('DB_HOST', 'db5019697680.hosting-data.io');  // Tu host de MySQL en IONOS
define('DB_PORT', 3306);
define('DB_USER', 'dbu2400034');                     // Tu usuario de MySQL
define('DB_PASSWORD', 'tu-password-real');            // Tu contraseña de MySQL
define('DB_NAME', 'dbs15318490');                     // Tu nombre de base de datos
define('DB_CHARSET', 'utf8mb4');

// JWT — CAMBIA ESTO por una cadena aleatoria larga
define('JWT_SECRET', 'genera-una-cadena-aleatoria-de-al-menos-64-caracteres-aqui-12345');
define('JWT_EXPIRES_IN', 365 * 24 * 60 * 60);       // 365 días

// Admin — CAMBIA la contraseña
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'tu-password-seguro');

// Rate limiting
define('RATE_LIMIT_WINDOW', 15 * 60);
define('RATE_LIMIT_MAX', 1000);
define('AUTH_RATE_LIMIT_MAX', 20);

// CORS
define('CORS_ORIGIN', '*');
```

> 💡 **Tip:** Para generar un JWT_SECRET aleatorio puedes usar:  
> https://www.random.org/strings/?num=4&len=16&digits=on&upperalpha=on&loweralpha=on  
> y concatenar los resultados.

---

### Paso 3: Ejecutar la instalación

Abre tu navegador y visita:

```
https://tu-dominio.com/install.php
```

Deberías ver una respuesta JSON como:

```json
{
    "success": true,
    "results": [
        "✅ Conexión a MySQL exitosa",
        "✅ Tablas creadas/verificadas",
        "✅ Usuario admin creado: admin",
        "",
        "🚀 Instalación completada exitosamente.",
        "⚠️  ELIMINA este archivo (install.php) en producción."
    ]
}
```

---

### Paso 4: Eliminar install.php

**¡IMPORTANTE!** Después de ejecutar la instalación, **elimina `install.php` del servidor** por seguridad. Puedes hacerlo desde el File Manager de IONOS o por FTP.

---

### Paso 5: Verificar que funciona

Visita el health check:

```
https://tu-dominio.com/api/health
```

Deberías ver:

```json
{
    "success": true,
    "message": "API Bot is running",
    "timestamp": "2026-02-13T18:00:00+00:00"
}
```

---

### Paso 6: Obtener tu token JWT

Haz un POST a `/api/auth/login` con tus credenciales de admin:

```bash
curl -X POST https://tu-dominio.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "tu-password-seguro"}'
```

Respuesta:

```json
{
    "success": true,
    "token": "eyJhbGciOiJIUzI1NiIs...",
    "expiresIn": "31536000s"
}
```

**Guarda ese token.** Lo necesitarás para todas las llamadas protegidas.

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
| `GET` | `/api/bot-users/{sessionId}` | 🔒 Token | Obtener uno por session_id |
| `POST` | `/api/bot-users` | 🔒 Token | Crear nuevo |
| `PUT` | `/api/bot-users/{sessionId}` | 🔒 Token | Actualizar |
| `DELETE` | `/api/bot-users/{sessionId}` | 🔒 Token | Eliminar |

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

### Health

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/health` | 🌐 Público | Estado de la API |

---

## 🔑 Autenticación

Todas las rutas protegidas requieren el header:

```
Authorization: Bearer <tu-token-jwt>
```

El token se obtiene con `POST /api/auth/login` y tiene validez de **1 año**.

---

## 📝 Ejemplos de Uso

### Crear un bot user

```bash
curl -X POST https://tu-dominio.com/api/bot-users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{"session_id": "abc123", "nombre": "Juan", "status": "new"}'
```

### Listar bot users con filtros

```bash
curl "https://tu-dominio.com/api/bot-users?status=new&page=1&limit=10" \
  -H "Authorization: Bearer TU_TOKEN"
```

### Crear un chat history

```bash
curl -X POST https://tu-dominio.com/api/chat-histories \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{"session_id": "abc123", "message": {"role": "user", "content": "Hola"}}'
```

---

## ⚠️ Checklist de Seguridad

- [ ] `config.php` tiene credenciales reales y **NO está en git**
- [ ] `install.php` fue **eliminado** del servidor después de usarlo
- [ ] `JWT_SECRET` es una cadena aleatoria de al menos 64 caracteres
- [ ] `ADMIN_PASSWORD` fue cambiado del valor por defecto
- [ ] El `.htaccess` bloquea acceso directo a archivos sensibles
