-- Products Table
create database goldshop;
\c goldshop;   

CREATE TABLE products (
    product_id       SERIAL PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    description      TEXT,
    price            NUMERIC(10, 2) NOT NULL,
    weight_grams     NUMERIC(6, 2),
    metal_type       VARCHAR(50) NOT NULL,
    design_style     VARCHAR(50),
    occasion         VARCHAR(50),
    is_trending      BOOLEAN DEFAULT FALSE,
    collection_key   VARCHAR(50),
    image_url_main   VARCHAR(255),
    images_gallery   TEXT[], 
    stock_quantity   INTEGER DEFAULT 0
);


-- Users Table

CREATE TABLE users (
    user_id          SERIAL PRIMARY KEY,
    profile_photo   VARCHAR(255),
    dob             DATE,
    email            VARCHAR(255) UNIQUE NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    first_name       VARCHAR(100),
    phone_number     VARCHAR(15),
    address_book     JSONB,  -- Store multiple addresses
    loyalty_points   INTEGER DEFAULT 0
);
 
-- Orders Table
CREATE TABLE orders (
    order_id         SERIAL PRIMARY KEY,
    user_id          INTEGER REFERENCES users(user_id) ON DELETE SET NULL,
    order_date       TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    total_amount     NUMERIC(10, 2) NOT NULL,
    status           VARCHAR(50) NOT NULL, -- e.g., 'Processing', 'Shipped', 'Delivered'
    shipping_address JSONB NOT NULL,       -- Snapshot of shipping info
    payment_method   VARCHAR(50),
    coupon_code_used VARCHAR(50),
    gift_wrap_message TEXT
);
ALTER TABLE orders 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;


-- Order Items Table
CREATE TABLE order_items (
    item_id            SERIAL PRIMARY KEY,
    order_id           INTEGER REFERENCES orders(order_id) ON DELETE CASCADE,
    product_id         INTEGER REFERENCES products(product_id) ON DELETE RESTRICT,
    quantity           INTEGER NOT NULL,
    unit_price_at_sale NUMERIC(10, 2) NOT NULL
);


CREATE TABLE payments (
    payment_id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(order_id) ON DELETE CASCADE,
    razorpay_order_id VARCHAR(100),
    razorpay_payment_id VARCHAR(100),
    razorpay_signature VARCHAR(255),
    amount NUMERIC(10, 2),
    currency VARCHAR(10),
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart (
    cart_id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(user_id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(product_id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE favorites (
    fav_id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(user_id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(product_id) ON DELETE CASCADE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, product_id)  -- same item repeated होणार नाही
);

INSERT INTO products 
(name, description, price, weight_grams, metal_type, design_style, occasion,
 is_trending, collection_key, image_url_main, images_gallery, stock_quantity)
VALUES
('Product 1', NULL, 0, NULL, 'Gold', 'Daily Wear', NULL,
 TRUE, '',
 '../uploads/rings1_1.webp',
 ARRAY['../uploads/rings1_2.webp','../uploads/rings1_3.webp'],
 10),

('Product 2', NULL, 0, NULL, 'Gold', 'Modern Wear', NULL,
 FALSE, 'Bestsellers',
 '../uploads/rings3_1.jpg',
 ARRAY['../uploads/rings3_2.jpg','../uploads/rings3_3.jpg'],
 10),

('Product 3', NULL, 0, NULL, 'Gold', 'Casual Wear', 'Festive',
 TRUE, '',
 '../uploads/rings2_1.webp',
 ARRAY['../uploads/rings2_2.webp','../uploads/rings2_3.webp'],
 10),

('Product 4', NULL, 0, NULL, 'Gold', 'Modern Wear', NULL,
 FALSE, 'Bestsellers',
 '../uploads/Chain1_1.webp',
 ARRAY['../uploads/chain1_2.webp','../uploads/chain1_3.webp'],
 15),

('Product 5', NULL, 0, NULL, 'Gold', 'Casual Wear', NULL,
 FALSE, 'Bestsellers',
 '../uploads/Bracelet1_1.webp',
 ARRAY['../uploads/Bracelet1_2.webp','../uploads/Bracelet1_3.webp','../uploads/Bracelet1_4.webp'],
 15),

('Product 6', NULL, 0, NULL, 'Gold', 'Festival', 'Wedding',
 FALSE, '',
 '../uploads/diamond_earing_1_1.webp',
 ARRAY['../uploads/diamond_earing_1_2.webp'],
 20);
