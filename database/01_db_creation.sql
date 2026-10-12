-- 01_db_creation.sql
-- Initial Schema: Roles, Screens, Role-Screen Permissions, Users, Agents, and System Info
-- Database Engine: MySQL (InnoDB, utf8mb4)

-- 1. Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Screens Table
CREATE TABLE IF NOT EXISTS screens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    screen_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    route VARCHAR(150) NOT NULL,
    icon VARCHAR(50) DEFAULT 'grid',
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role-Screens Permissions Table
CREATE TABLE IF NOT EXISTS role_screens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    screen_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_create TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE,
    UNIQUE KEY uk_role_screen (role_id, screen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users Table (A single role per user, optional avatar image)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Providers Table (AI Provider Endpoints)
CREATE TABLE IF NOT EXISTS providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    endpoint_url VARCHAR(255) NOT NULL,
    code VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Models Table (Stores AI models per provider with status active/deprecated)
CREATE TABLE IF NOT EXISTS models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    provider_id INT NOT NULL,
    status ENUM('active', 'deprecated') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Agents Table (Stores provider_id referencing providers table)
CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    system_instructions TEXT NOT NULL,
    model VARCHAR(100) NOT NULL,
    temperature FLOAT DEFAULT 0.7,
    visibility ENUM('private', 'public_closed', 'public_open') DEFAULT 'private',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. User API Keys Table
CREATE TABLE IF NOT EXISTS user_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    api_key_encrypted TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. System Info Table (Tracks system and database versions)
CREATE TABLE IF NOT EXISTS system_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_key VARCHAR(50) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Workbench Documents Table (Knowledge base reference documents for workbench agents)
CREATE TABLE IF NOT EXISTS workbench_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT 'text',
    file_size INT DEFAULT 0,
    content MEDIUMTEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed Initial Data
-- ============================================================================

-- Seed Roles
INSERT INTO roles (id, name, description) VALUES
(1, 'SuperAdmin', 'Unrestricted administrative access to all system features, users, and screens'),
(2, 'User', 'Standard user access to AI agent creation and multi-agent collaborative chat')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description);

-- Seed System Screens (Navigation and Screen Permissions)
INSERT INTO screens (id, screen_key, name, route, icon, description) VALUES
(1, 'agents', 'Agents', '/agents', 'bot', 'List and create autonomous AI agents'),
(2, 'api_keys', 'API Keys', '/keys', 'key', 'Manage personal AI provider API credentials'),
(3, 'users', 'Users', '/users', 'users', 'Manage system user accounts and roles'),
(4, 'roles', 'Roles', '/roles', 'shield', 'Configure roles and screen permissions'),
(5, 'workbench', 'Workbench', '/workbench', 'layers', 'Sequential and iterative multi-agent execution pipeline'),
(6, 'providers', 'Providers', '/providers', 'server', 'Manage AI providers and API endpoint configurations'),
(7, 'models', 'Models', '/models', 'cpu', 'Manage AI models per provider and lifecycle status')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    route = VALUES(route),
    icon = VALUES(icon),
    description = VALUES(description);

-- Grant All Screen Permissions to SuperAdmin (role_id = 1)
INSERT INTO role_screens (role_id, screen_id, can_view, can_create, can_edit, can_delete) VALUES
(1, 1, 1, 1, 1, 1),
(1, 2, 1, 1, 1, 1),
(1, 3, 1, 1, 1, 1),
(1, 4, 1, 1, 1, 1),
(1, 5, 1, 1, 1, 1),
(1, 6, 1, 1, 1, 1),
(1, 7, 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
    can_view = 1, can_create = 1, can_edit = 1, can_delete = 1;

-- Grant Screen Permissions to Standard User (role_id = 2) - Workbench, Agents & API Keys
INSERT INTO role_screens (role_id, screen_id, can_view, can_create, can_edit, can_delete) VALUES
(2, 1, 1, 1, 1, 0),
(2, 2, 1, 1, 1, 1),
(2, 3, 0, 0, 0, 0),
(2, 4, 0, 0, 0, 0),
(2, 5, 1, 1, 1, 1),
(2, 6, 0, 0, 0, 0),
(2, 7, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
    can_view = VALUES(can_view),
    can_create = VALUES(can_create),
    can_edit = VALUES(can_edit),
    can_delete = VALUES(can_delete);

-- Seed Providers (AI Provider Endpoints)
INSERT INTO providers (id, name, endpoint_url, code) VALUES
(1, 'Google Gemini', 'https://generativelanguage.googleapis.com/v1beta/models/', 'gemini'),
(2, 'OpenAI', 'https://api.openai.com/v1/chat/completions', 'openai'),
(3, 'Anthropic Claude', 'https://api.anthropic.com/v1/messages', 'anthropic'),
(4, 'Groq (Ultra-fast)', 'https://api.groq.com/openai/v1/chat/completions', 'groq'),
(5, 'Ollama / Local', 'http://localhost:11434/api/generate', 'local')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    endpoint_url = VALUES(endpoint_url),
    code = VALUES(code);

-- Seed Models
INSERT INTO models (id, name, provider_id, status) VALUES
(1, 'gemini-3.5-flash', 1, 'active'),
(2, 'gemini-3.5-flash-lite', 1, 'active'),
(3, 'gemini-3.8-flash', 1, 'active'),
(4, 'gemini-2.5-pro', 1, 'deprecated'),
(5, 'gemini-2.0-flash', 1, 'deprecated'),
(6, 'gemini-1.5-pro', 1, 'deprecated'),
(7, 'gemini-1.5-flash', 1, 'deprecated'),
(8, 'gpt-4o', 2, 'active'),
(9, 'gpt-4o-mini', 2, 'active'),
(10, 'o1-preview', 2, 'active'),
(11, 'gpt-4', 2, 'deprecated'),
(12, 'gpt-3.5-turbo', 2, 'deprecated'),
(13, 'claude-3-5-sonnet', 3, 'active'),
(14, 'claude-3-5-haiku', 3, 'active'),
(15, 'claude-3-opus', 3, 'deprecated'),
(16, 'openai/gpt-oss-120b', 4, 'active'),
(17, 'llama-3.3-70b-versatile', 4, 'active'),
(18, 'llama-3.1-8b-instant', 4, 'active'),
(19, 'llama2-70b-4096', 4, 'deprecated')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    provider_id = VALUES(provider_id),
    status = VALUES(status);

-- Seed SuperAdmin User with Bcrypt Hashed Password (Password: Admin123!)
INSERT INTO users (id, role_id, username, full_name, email, password_hash, avatar, is_active) VALUES
(1, 1, 'superadmin', 'Super Administrator', 'admin@example.com', '$2y$10$MQvXuVsYndLgmV6/DahiIOqNbWgniJ2e95eQRQ6uYSzJ5f9LzNxC2', NULL, 1)
ON DUPLICATE KEY UPDATE 
    role_id = VALUES(role_id),
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    is_active = 1;

-- Seed System Info (system and database versions)
INSERT INTO system_info (item_key, version) VALUES
('system', '1.0.0'),
('database', '1.0.0')
ON DUPLICATE KEY UPDATE 
    version = VALUES(version);
