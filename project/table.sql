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
    images_gallery   TEXT[],  -- Array of gallery images
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
