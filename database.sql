CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    password VARCHAR(255),
    role ENUM('customer', 'restaurant', 'delivery', 'admin'),
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP,
    address VARCHAR(255)
);

CREATE TABLE restaurants (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11),
    name VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected'),
    category VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE menu_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT(11),
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    image_url VARCHAR(255),
    category VARCHAR(100),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

CREATE TABLE delivery (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11),
    name VARCHAR(100),
    phone VARCHAR(20),
    status VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE orders (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    customer_id INT(11),
    restaurant_id INT(11),
    delivery_id INT(11),
    status ENUM('pending', 'confirmed', 'delivering', 'completed', 'cancelled'),
    total_price DECIMAL(10,2),
    created_at TIMESTAMP,
    cancelled_at DATETIME,
    cancelled_by INT(11),
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    FOREIGN KEY (delivery_id) REFERENCES delivery(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11),
    menu_item_id INT(11),
    quantity INT(11),
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);


ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_3;
ALTER TABLE orders ADD CONSTRAINT orders_ibfk_3 FOREIGN KEY (delivery_id) REFERENCES users(id);