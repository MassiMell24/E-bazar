CREATE TABLE ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(1000) DEFAULT '',
    price DECIMAL(10,2) DEFAULT 0.00,
    delivery_modes VARCHAR(100) DEFAULT '',
    sold BOOLEAN DEFAULT 0,
    buyer_id INT DEFAULT NULL,
    sold_at DATETIME DEFAULT NULL,
    sold_delivery_mode VARCHAR(100) DEFAULT NULL,
    buyer_confirmed_reception BOOLEAN DEFAULT 0,
    buyer_deleted BOOLEAN DEFAULT 0,
    seller_archived BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
