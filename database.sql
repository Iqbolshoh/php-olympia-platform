CREATE DATABASE olimpy;

USE olimpy;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(30) NOT NULL,
    last_name VARCHAR(30) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio VARCHAR(120) DEFAULT '',
    role ENUM('superadmin', 'admin', 'user') NOT NULL DEFAULT 'user',
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    total_donats INT DEFAULT 0,
    total_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

INSERT INTO users (first_name, last_name, email, username, password, role)
VALUES ('admin', 'admin', 'admin@example.com', 'admin', '0a25a187d5d33b84ba656020d3c8b41ae9c9617ced1c1524e8f530e14c87b775', 'admin'),
('user', 'user', 'user@example.com', 'user', '0a25a187d5d33b84ba656020d3c8b41ae9c9617ced1c1524e8f530e14c87b775', 'user'),
('owner', 'owner', 'owner@example.com', 'owner', '0a25a187d5d33b84ba656020d3c8b41ae9c9617ced1c1524e8f530e14c87b775', 'owner');
