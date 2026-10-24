CREATE TABLE IF NOT EXISTS users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tokens_hash (token_hash),
    CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS books (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status      ENUM('unread', 'reading', 'read') NOT NULL DEFAULT 'unread',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
     id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     user_id    BIGINT UNSIGNED NOT NULL,
     name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tags_user_name (user_id, name),
    CONSTRAINT fk_tags_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS book_tag (
     book_id BIGINT UNSIGNED NOT NULL,
     tag_id  BIGINT UNSIGNED NOT NULL,
     PRIMARY KEY (book_id, tag_id),
    CONSTRAINT fk_book_tag_book FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE,
    CONSTRAINT fk_book_tag_tag  FOREIGN KEY (tag_id)  REFERENCES tags (id)  ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
