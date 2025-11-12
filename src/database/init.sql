CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- create users table
CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'guest' CHECK (role IN ('buyer', 'seller', 'guest')),
    name VARCHAR(255) NOT NULL,
    address TEXT,
    balance DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- create store table
CREATE TABLE IF NOT EXISTS store (
    store_id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(user_id) ON DELETE CASCADE,
    store_name VARCHAR(255) UNIQUE NOT NULL,
    store_description TEXT,
    store_logo_path VARCHAR(500),
    balance DECIMAL(18, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- create category table
CREATE TABLE IF NOT EXISTS category (
    category_id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- create product table
CREATE TABLE IF NOT EXISTS product (
    product_id SERIAL PRIMARY KEY,
    store_id INTEGER REFERENCES store(store_id) ON DELETE CASCADE,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(18, 2) NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    main_image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS category_item (
    category_id INTEGER REFERENCES category(category_id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES product(product_id) ON DELETE CASCADE,
    PRIMARY KEY (category_id, product_id)
);

-- create cart_item
CREATE TABLE IF NOT EXISTS cart_item (
    cart_item_id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES product(product_id) ON DELETE CASCADE,
    buyer_id INTEGER REFERENCES users(user_id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(product_id, buyer_id)
);

-- create order table
CREATE TABLE IF NOT EXISTS orders (
    order_id SERIAL PRIMARY KEY,
    buyer_id INTEGER REFERENCES users(user_id) ON DELETE CASCADE,
    store_id INTEGER REFERENCES store(store_id) ON DELETE CASCADE,
    total_price DECIMAL(18, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'waiting_approval' CHECK (status IN ('waiting_approval', 'approved', 'rejected', 'on_delivery', 'received')),
    reject_reason TEXT,
    confirmed_at TIMESTAMP NULL,
    delivery_time TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(order_id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES product(product_id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    price_at_order DECIMAL(18, 2) NOT NULL,
    subtotal DECIMAL(18, 2) NOT NULL
);

INSERT INTO category (name) VALUES
  ('Electronics'), ('Furniture'), ('Books'), ('Clothing'), ('Sports')
ON CONFLICT (name) DO NOTHING;

-- create function for auto-updating updated_at column
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- indexing
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_store_user_id ON store(user_id);
CREATE INDEX IF NOT EXISTS idx_product_store_id ON product(store_id);
CREATE INDEX IF NOT EXISTS idx_cart_item_buyer_id ON cart_item(buyer_id);
CREATE INDEX IF NOT EXISTS idx_cart_item_product_id ON cart_item(product_id);
CREATE INDEX IF NOT EXISTS idx_order_buyer_id ON orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_order_store_id ON orders(store_id);
CREATE INDEX IF NOT EXISTS idx_order_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);

-- Default aja
INSERT INTO category (name) VALUES
    ('Electronics'),
    ('Furniture')
ON CONFLICT (name) DO NOTHING;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE t.tgname='update_users_updated_at' AND c.relname='users') THEN
    CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE t.tgname='update_store_updated_at' AND c.relname='store') THEN
    CREATE TRIGGER update_store_updated_at BEFORE UPDATE ON store FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE t.tgname='update_product_updated_at' AND c.relname='product') THEN
    CREATE TRIGGER update_product_updated_at BEFORE UPDATE ON product FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_trigger t JOIN pg_class c ON c.oid=t.tgrelid WHERE t.tgname='update_cart_item_updated_at' AND c.relname='cart_item') THEN
    CREATE TRIGGER update_cart_item_updated_at BEFORE UPDATE ON cart_item FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
  END IF;
END $$;

-- Constraint untuk memastikan nilai tidak negatif
DO $$
BEGIN
    -- Users balance constraint
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_users_balance_non_negative'
    ) THEN
        ALTER TABLE users ADD CONSTRAINT check_users_balance_non_negative
            CHECK (balance >= 0);
    END IF;

    -- Store balance constraint
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_store_balance_non_negative'
    ) THEN
        ALTER TABLE store ADD CONSTRAINT check_store_balance_non_negative
            CHECK (balance >= 0);
    END IF;

    -- Product price constraint (harus > 0, tidak bisa gratis)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_product_price_positive'
    ) THEN
        ALTER TABLE product ADD CONSTRAINT check_product_price_positive
            CHECK (price > 0);
    END IF;

    -- Cart item quantity constraint (minimal 1)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_cart_item_quantity_positive'
    ) THEN
        ALTER TABLE cart_item ADD CONSTRAINT check_cart_item_quantity_positive
            CHECK (quantity > 0);
    END IF;

    -- Orders total_price constraint (harus > 0)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_orders_total_price_positive'
    ) THEN
        ALTER TABLE orders ADD CONSTRAINT check_orders_total_price_positive
            CHECK (total_price > 0);
    END IF;

    -- Order items quantity constraint (minimal 1)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_order_items_quantity_positive'
    ) THEN
        ALTER TABLE order_items ADD CONSTRAINT check_order_items_quantity_positive
            CHECK (quantity > 0);
    END IF;

    -- Order items price_at_order constraint (harus > 0)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_order_items_price_positive'
    ) THEN
        ALTER TABLE order_items ADD CONSTRAINT check_order_items_price_positive
            CHECK (price_at_order > 0);
    END IF;

    -- Order items subtotal constraint (harus > 0)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_order_items_subtotal_positive'
    ) THEN
        ALTER TABLE order_items ADD CONSTRAINT check_order_items_subtotal_positive
            CHECK (subtotal > 0);
    END IF;
END $$;
