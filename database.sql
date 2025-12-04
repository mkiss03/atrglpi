-- ÁTR Beragadt Betegek - Database Schema
-- PHP + MySQL Web Application

-- Create database
CREATE DATABASE IF NOT EXISTS atr_betegek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atr_betegek;

-- ===================================
-- Table: admins
-- ===================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Table: atr_records
-- ===================================
CREATE TABLE IF NOT EXISTS atr_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intezmeny VARCHAR(10) NOT NULL COMMENT 'Fixed value: 140100',
    osztaly VARCHAR(20) NOT NULL COMMENT '9 character NNGYK/NNK9 code',
    tavido DATETIME NOT NULL COMMENT 'Távozási idő',
    atr_dismissing_type VARCHAR(50) NOT NULL COMMENT 'Elbocsátás módja',
    atr_nursing_cycle_id VARCHAR(100) NULL COMMENT 'ÁTR ápolási ciklus azonosító (opcionális)',
    atr_nursing_cycle_data_id VARCHAR(100) NOT NULL COMMENT 'ÁTR ápolási ciklus adat azonosító',
    created_ip VARCHAR(45) NOT NULL COMMENT 'IP address of creator (IPv4/IPv6)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_admin_id INT NULL COMMENT 'Admin who created this record',
    INDEX idx_osztaly (osztaly),
    INDEX idx_tavido (tavido),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by_admin (created_by_admin_id),
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Insert default admin user
-- Username: admin
-- Password: admin123
-- ===================================
INSERT INTO admins (username, password_hash, display_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Nagy Péter'),
('teszt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kovács Anna');

-- Note: Password for both users is: 'password'
-- To generate new password hash in PHP, use: password_hash('your_password', PASSWORD_DEFAULT);

-- ===================================
-- Sample data (optional)
-- ===================================
INSERT INTO atr_records (intezmeny, osztaly, tavido, atr_dismissing_type, atr_nursing_cycle_id, atr_nursing_cycle_data_id, created_ip, created_by_admin_id) VALUES
('140100', '001000536', '2025-12-01 14:30:00', 'HOME', '4KRYCDMJAS6VRPMH', 'TEST-001', '127.0.0.1', 1),
('140100', '001000537', '2025-12-02 09:15:00', 'OTHER_DEPARTMENT', '4KRYCDMJAS6VRPMH', 'TEST-002', '127.0.0.1', 1);

-- ===================================
-- Show tables
-- ===================================
SHOW TABLES;
