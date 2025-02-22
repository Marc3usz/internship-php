CREATE DATABASE IF NOT EXISTS my_database;
USE my_database;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) NOT NULL DEFAULT 0  -- 0 = normal user, 1 = admin
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(8191) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- default admin user with pre-hashed password using bcrypt, password is `admin123`. Default admin account needed to grant others admin rights
-- change the admin email if testing with SMTP service that only allows sending emails to owner in demo mode (example: Mailtrap)
INSERT INTO users (username, password, name, email, is_admin)
VALUES ('admin', '$2y$10$Cn1C9WKGBAg3P0Pc3269Hu9UQ.MYUi54KNGzfGVDexRiVuTnTWnru', 'Administrator', 'admin@admin.com', 1);
