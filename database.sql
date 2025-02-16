CREATE DATABASE olympia;

USE olympia;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(30) NOT NULL,
    last_name VARCHAR(30) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio VARCHAR(120) DEFAULT '',
    role ENUM('superadmin', 'admin', 'user') NOT NULL DEFAULT 'user',
    balance DECIMAL(10, 2) DEFAULT 0.00,
    total_donats DECIMAL(10, 2) DEFAULT 0.00,
    total_score INT DEFAULT 0,
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE olympiads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    entry_fee INT DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    created_by INT,
    status ENUM('upcoming', 'open', 'finished') DEFAULT 'upcoming',
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    olympiad_id INT NOT NULL,
    question VARCHAR(255) NOT NULL,
    description TEXT,
    answers ENUM('3', '4', '5') NOT NULL,
    FOREIGN KEY (olympiad_id) REFERENCES olympiads(id)
);

CREATE TABLE answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    test_id INT NOT NULL,
    answer_text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (test_id) REFERENCES tests(id)
);

CREATE TABLE participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    olympiad_id INT NOT NULL,
    score INT DEFAULT 0,
    status ENUM('processed', 'unprocessed') DEFAULT 'unprocessed',
    payment_status ENUM('paid', 'unpaid') DEFAULT 'unpaid',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (olympiad_id) REFERENCES olympiads(id)
);

CREATE TABLE donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    olympiad_id INT DEFAULT NULL,
    amount INT NOT NULL,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (olympiad_id) REFERENCES olympiads(id) ON DELETE
    SET
        NULL
);

INSERT INTO
    users (
        first_name,
        last_name,
        email,
        username,
        password,
        role
    )
VALUES
    (
        'Super',
        'Admin',
        'superadmin@example.com',
        'superadmin',
        '65c2a32982abe41b1e6ff888d351ee6b7ade33affd4a595667ea7db910aecaa8',
        'superadmin'
    ),
    (
        'Admin',
        'Admin',
        'admin@example.com',
        'admin',
        '65c2a32982abe41b1e6ff888d351ee6b7ade33affd4a595667ea7db910aecaa8',
        'admin'
    ),
    (
        'User',
        'User',
        'user@example.com',
        'user',
        '65c2a32982abe41b1e6ff888d351ee6b7ade33affd4a595667ea7db910aecaa8',
        'user'
    );