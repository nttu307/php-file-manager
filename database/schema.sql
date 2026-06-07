SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- User accounts and roles.
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    storage_limit BIGINT UNSIGNED NULL DEFAULT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    updated_at BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uploaded file metadata. Physical files live in storage/uploads.
CREATE TABLE files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    extension VARCHAR(20) NOT NULL,
    file_type VARCHAR(50) NOT NULL DEFAULT 'file',
    size BIGINT UNSIGNED NOT NULL,
    path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500) NULL,
    public_token CHAR(48) NOT NULL,
    visibility ENUM('private', 'public') NOT NULL DEFAULT 'public',
    created_at BIGINT UNSIGNED NOT NULL,
    deleted_at BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_files_public_token (public_token),
    INDEX idx_files_user_id (user_id),
    INDEX idx_files_file_type (file_type),
    INDEX idx_files_visibility (visibility),
    INDEX idx_files_created_at (created_at),
    INDEX idx_files_deleted_at (deleted_at),
    CONSTRAINT fk_files_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit trail for data-changing actions.
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    file_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    INDEX idx_activity_user_id (user_id),
    INDEX idx_activity_file_id (file_id),
    INDEX idx_activity_action (action),
    INDEX idx_activity_created_at (created_at),
    CONSTRAINT fk_activity_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_activity_file_id
        FOREIGN KEY (file_id) REFERENCES files(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens. Raw tokens are never stored.
CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    used_at BIGINT UNSIGNED NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_password_resets_token_hash (token_hash),
    INDEX idx_password_resets_user_id (user_id),
    INDEX idx_password_resets_expires_at (expires_at),
    CONSTRAINT fk_password_resets_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
