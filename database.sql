-- ============================================================
-- ShopVerse — Online Shopping System (DBS Project)
-- Developed by MUHAMMAD OBAID
-- Database: dbs_shopverse
-- ============================================================

CREATE DATABASE IF NOT EXISTS dbs_shopverse
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dbs_shopverse;

-- Admin table (new backend)
CREATE TABLE IF NOT EXISTS Admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Customer (
    customer_id INT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    mobile_number VARCHAR(20),
    total_spent DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Address (
    address_id INT PRIMARY KEY,
    customer_id INT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
);

CREATE TABLE IF NOT EXISTS Cart (
    cart_id INT PRIMARY KEY,
    customer_id INT UNIQUE,
    created_at DATETIME,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
);

CREATE TABLE IF NOT EXISTS Category (
    category_id INT PRIMARY KEY,
    category_name VARCHAR(100),
    description TEXT
);

CREATE TABLE IF NOT EXISTS Supplier (
    supplier_id INT PRIMARY KEY,
    supplier_name VARCHAR(100),
    contact_person VARCHAR(100),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT
);

CREATE TABLE IF NOT EXISTS Product (
    product_id INT PRIMARY KEY,
    category_id INT,
    supplier_id INT,
    product_name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    weight DECIMAL(10,2),
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES Category(category_id),
    FOREIGN KEY (supplier_id) REFERENCES Supplier(supplier_id)
);

CREATE TABLE IF NOT EXISTS Cart_Items (
    cart_item_id INT PRIMARY KEY,
    cart_id INT,
    product_id INT,
    quantity INT,
    FOREIGN KEY (cart_id) REFERENCES Cart(cart_id),
    FOREIGN KEY (product_id) REFERENCES Product(product_id)
);

CREATE TABLE IF NOT EXISTS Orders (
    order_id INT PRIMARY KEY,
    customer_id INT,
    order_date DATETIME,
    total_amount DECIMAL(10,2),
    discount_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
);

CREATE TABLE IF NOT EXISTS Order_Items (
    order_item_id INT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    unit_price DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    FOREIGN KEY (product_id) REFERENCES Product(product_id)
);

CREATE TABLE IF NOT EXISTS Payment (
    payment_id INT PRIMARY KEY,
    order_id INT,
    amount_paid DECIMAL(10,2),
    payment_status VARCHAR(50),
    transaction_ref VARCHAR(100),
    payment_date DATETIME,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id)
);

CREATE TABLE IF NOT EXISTS Supplier_Orders (
    supplier_order_id INT PRIMARY KEY,
    supplier_id INT,
    order_date DATETIME,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    status VARCHAR(50),
    total_cost DECIMAL(10,2),
    FOREIGN KEY (supplier_id) REFERENCES Supplier(supplier_id)
);

CREATE TABLE IF NOT EXISTS Supplier_Order_Items (
    supplier_order_item_id INT PRIMARY KEY,
    supplier_order_id INT,
    product_id INT,
    supplier_price DECIMAL(10,2),
    quantity INT,
    FOREIGN KEY (supplier_order_id) REFERENCES Supplier_Orders(supplier_order_id),
    FOREIGN KEY (product_id) REFERENCES Product(product_id)
);

-- Sample Data
INSERT INTO Category (category_id, category_name, description) VALUES
(1, 'Electronics',  'Phones, laptops, gadgets and accessories'),
(2, 'Clothing',     'Men and women fashion and apparel'),
(3, 'Books',        'Academic, fiction and non-fiction books'),
(4, 'Home & Garden','Furniture, decor and garden supplies'),
(5, 'Sports',       'Fitness, outdoor and sports equipment');

INSERT INTO Supplier (supplier_id, supplier_name, contact_person, phone_number, email, address) VALUES
(1, 'TechPro Supplies',  'Ali Khan',    '0300-1234567', 'ali@techpro.pk',   'Karachi, Pakistan'),
(2, 'FashionHub',        'Sara Ahmed',  '0321-9876543', 'sara@fashionhub.pk','Lahore, Pakistan'),
(3, 'BookWorld',         'Omar Sheikh', '0333-5556666', 'omar@bookworld.pk', 'Islamabad, Pakistan');

INSERT INTO Product (product_id,category_id,supplier_id,product_name,description,price,stock_quantity,reorder_level,weight,image_url) VALUES
(1,1,1,'Wireless Bluetooth Earbuds','Premium sound quality with 24-hour battery life. IPX5 water-resistant.',29.99,150,20,0.2,'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500'),
(2,1,1,'Smart LED Desk Lamp','Touch-sensitive, 3 color modes, USB charging port built-in.',24.99,80,15,1.1,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=500'),
(3,1,1,'Mechanical Keyboard','TKL layout, RGB backlit, blue switches. Plug & play.',59.99,45,10,0.9,'https://images.unsplash.com/photo-1595225476474-87563907a212?w=500'),
(4,1,1,'USB-C Hub 7-in-1','HDMI 4K, 3×USB-A, SD card, PD charging. Compatible with all laptops.',34.99,200,25,0.3,'https://images.unsplash.com/photo-1587393855524-087f83d95bc9?w=500'),
(5,2,2,'Classic White T-Shirt','100% cotton, unisex fit, sizes XS–XXL. Machine washable.',14.99,300,50,0.3,'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500'),
(6,2,2,'Slim Fit Chinos','Stretch fabric, 4 colors, waist 28–40. Premium comfort.',39.99,120,20,0.6,'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=500'),
(7,3,3,'Clean Code by Robert Martin','A Handbook of Agile Software Craftsmanship. Must-read for developers.',32.99,60,10,0.8,'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=500'),
(8,3,3,'Database System Concepts','7th Edition. Silberschatz, Korth & Sudarshan.',54.99,40,8,1.4,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=500'),
(9,4,1,'Minimalist Wall Clock','Silent quartz movement, 30cm diameter, modern design.',19.99,90,15,0.7,'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?w=500'),
(10,5,2,'Adjustable Dumbbell Set','5–25 kg, quick-lock mechanism, anti-slip grip.',89.99,30,5,12.0,'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500');

-- Accounts are seeded via setup.php (password hashes require PHP)
