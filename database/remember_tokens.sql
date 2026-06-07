CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    last_used_at BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_remember_tokens_selector (selector),
    INDEX idx_remember_tokens_user_id (user_id),
    INDEX idx_remember_tokens_expires_at (expires_at),
    CONSTRAINT fk_remember_tokens_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
