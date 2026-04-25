-- TARA Database Schema



CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    toda_name VARCHAR(100) NOT NULL,
    terminal_lat DECIMAL(10, 8),
    terminal_lng DECIMAL(11, 8),
    base_fare DECIMAL(8, 2) NOT NULL,
    per_km_fare DECIMAL(8, 2) NOT NULL DEFAULT 0.00
);

CREATE TABLE IF NOT EXISTS stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT,
    stop_name VARCHAR(100) NOT NULL,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Insert sample admin for testing
INSERT INTO users (username, email, password_hash, role) VALUES 
('System Admin', 'admin@tara.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Note: the hash above is for the password "password"

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL,
    timestamp INT(10) UNSIGNED NOT NULL
);
