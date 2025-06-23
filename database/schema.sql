-- Create database
CREATE DATABASE IF NOT EXISTS dashboard_db;
USE dashboard_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    thumbnail TEXT,
    slug VARCHAR(255) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Slugs table
CREATE TABLE slugs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('product', 'category') NOT NULL,
    reference_id INT NOT NULL,
    INDEX idx_slug_type (slug, type),
    INDEX idx_reference (reference_id, type)
);

-- Insert sample data
INSERT INTO users (name, email, password, phone, address) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', '123 Main St'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', '456 Oak Ave');

INSERT INTO categories (name, slug) VALUES
('Electronics', 'electronics'),
('Clothing', 'clothing'),
('Books', 'books');

INSERT INTO products (title, description, slug, price, category_id) VALUES
('Smartphone', 'Latest smartphone with advanced features', 'smartphone', 599.99, 1),
('T-Shirt', 'Comfortable cotton t-shirt', 't-shirt', 29.99, 2),
('Programming Book', 'Learn programming fundamentals', 'programming-book', 49.99, 3);

INSERT INTO orders (user_id, total_price, status) VALUES
(1, 599.99, 'completed'),
(2, 79.98, 'pending'),
(1, 49.99, 'shipped');

INSERT INTO slugs (slug, type, reference_id) VALUES
('electronics', 'category', 1),
('clothing', 'category', 2),
('books', 'category', 3),
('smartphone', 'product', 1),
('t-shirt', 'product', 2),
('programming-book', 'product', 3);
