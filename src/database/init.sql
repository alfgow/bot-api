-- ========================================
-- API Users table (for JWT authentication)
-- ========================================
CREATE TABLE IF NOT EXISTS api_users (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ========================================
-- Bot Users table
-- ========================================
CREATE TABLE IF NOT EXISTS bot_users (
  session_id VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'new',
  api_contact_id BIGINT NULL,
  nombre VARCHAR(255) NULL,
  telefono_real VARCHAR(20) NULL,
  rol VARCHAR(50) NULL,
  bot_status VARCHAR(32) NOT NULL DEFAULT 'free',
  rejected_count INT NOT NULL DEFAULT 0,
  questionnaire_status VARCHAR(32) NOT NULL DEFAULT 'none',
  property_id VARCHAR(64) NULL,
  count_outcontext INT NOT NULL DEFAULT 0,

  last_intencion VARCHAR(64) NULL,
  last_accion VARCHAR(64) NULL,
  last_bot_reply TEXT NULL,
  veces_pidiendo_nombre INT NOT NULL DEFAULT 0,
  veces_pidiendo_telefono INT NOT NULL DEFAULT 0,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (session_id),
  INDEX idx_bot_users_status (status),
  INDEX idx_bot_users_bot_status (bot_status),
  INDEX idx_bot_users_questionnaire_status (questionnaire_status),
  INDEX idx_bot_users_api_contact_id (api_contact_id)
) ENGINE=InnoDB;

-- ========================================
-- Chat Histories table
-- ========================================
CREATE TABLE IF NOT EXISTS n8n_chat_histories (
  id BIGINT NOT NULL AUTO_INCREMENT,
  session_id VARCHAR(32) NOT NULL,
  message JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  INDEX idx_chat_histories_session_id_id (session_id, id),
  CONSTRAINT fk_chat_histories_session
    FOREIGN KEY (session_id) REFERENCES bot_users(session_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
