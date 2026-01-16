CREATE TABLE area_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_code VARCHAR(3) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_area_code (area_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_code_id INT NOT NULL,
    did_number VARCHAR(20) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_did_number (did_number),
    KEY idx_dids_area_code (area_code_id),
    CONSTRAINT fk_dids_area_code
        FOREIGN KEY (area_code_id)
        REFERENCES area_codes(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE did_usage_daily (
    did_id INT NOT NULL,
    usage_date DATE NOT NULL,
    usage_count INT NOT NULL DEFAULT 0,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (did_id, usage_date),
    KEY idx_usage_date (usage_date),
    CONSTRAINT fk_usage_did
        FOREIGN KEY (did_id)
        REFERENCES dids(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ip_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    label VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
