-- Seeding product table
-- Clear existing products first to avoid duplicates
DELETE FROM product WHERE store_id IN (SELECT store_id FROM store);

-- Electronics products (TechHub Store)
INSERT INTO product (store_id, product_name, description, price, stock) VALUES
    ((SELECT store_id FROM store WHERE store_name = 'TechHub Store'), 'Laptop Gaming ROG', 'High-performance gaming laptop with RTX 4060, 16GB RAM, 512GB SSD', 15999000.00, 15),
    ((SELECT store_id FROM store WHERE store_name = 'TechHub Store'), 'iPhone 15 Pro Max', 'Latest iPhone with A17 Pro chip, 256GB storage, titanium design', 19999000.00, 20),
    ((SELECT store_id FROM store WHERE store_name = 'TechHub Store'), 'Samsung Galaxy S24 Ultra', 'Flagship Android phone with S Pen, 12GB RAM, 256GB storage', 17999000.00, 25),
    ((SELECT store_id FROM store WHERE store_name = 'TechHub Store'), 'Sony WH-1000XM5', 'Premium noise-canceling wireless headphones', 4999000.00, 30),
    ((SELECT store_id FROM store WHERE store_name = 'TechHub Store'), 'iPad Pro 12.9"', 'Powerful tablet with M2 chip, 256GB storage, Liquid Retina display', 16999000.00, 18),

-- Furniture products (Furniture Paradise)
    ((SELECT store_id FROM store WHERE store_name = 'Furniture Paradise'), 'Sofa 3 Seater Modern', 'Comfortable modern sofa with premium fabric, grey color', 5500000.00, 10),
    ((SELECT store_id FROM store WHERE store_name = 'Furniture Paradise'), 'Office Desk Minimalist', 'Wooden office desk with storage drawers, 120x60cm', 2500000.00, 15),
    ((SELECT store_id FROM store WHERE store_name = 'Furniture Paradise'), 'King Size Bed Frame', 'Solid wood bed frame with headboard, modern design', 7800000.00, 8),
    ((SELECT store_id FROM store WHERE store_name = 'Furniture Paradise'), 'Dining Table Set 6 Chairs', 'Elegant dining set for family, includes 6 cushioned chairs', 8900000.00, 6),
    ((SELECT store_id FROM store WHERE store_name = 'Furniture Paradise'), 'Ergonomic Office Chair', 'Adjustable office chair with lumbar support and mesh back', 1800000.00, 20),

-- Book products (Book Haven)
    ((SELECT store_id FROM store WHERE store_name = 'Book Haven'), 'The Art of Computer Programming Vol. 1-3', 'Classic computer science textbook by Donald Knuth', 1250000.00, 12),
    ((SELECT store_id FROM store WHERE store_name = 'Book Haven'), 'Clean Code by Robert Martin', 'A Handbook of Agile Software Craftsmanship', 450000.00, 1),
    ((SELECT store_id FROM store WHERE store_name = 'Book Haven'), 'Harry Potter Complete Collection', 'All 7 books in special edition box set', 1800000.00, 15),
    ((SELECT store_id FROM store WHERE store_name = 'Book Haven'), 'Atomic Habits by James Clear', 'Bestselling self-improvement book about building good habits', 250000.00, 50),
    ((SELECT store_id FROM store WHERE store_name = 'Book Haven'), 'The Pragmatic Programmer', 'Your Journey to Mastery, 20th Anniversary Edition', 550000.00, 25),

-- Clothing products (Fashion Hub)
    ((SELECT store_id FROM store WHERE store_name = 'Fashion Hub'), 'Polo Shirt Premium Cotton', 'Comfortable polo shirt, available in multiple colors', 299000.00, 100),
    ((SELECT store_id FROM store WHERE store_name = 'Fashion Hub'), 'Slim Fit Jeans Denim', 'Classic blue denim jeans with stretch fabric', 450000.00, 80),
    ((SELECT store_id FROM store WHERE store_name = 'Fashion Hub'), 'Leather Jacket Vintage', 'Genuine leather jacket with vintage style', 2500000.00, 15),
    ((SELECT store_id FROM store WHERE store_name = 'Fashion Hub'), 'Sneakers Running Sport', 'Lightweight running sneakers with cushioned sole', 850000.00, 60),
    ((SELECT store_id FROM store WHERE store_name = 'Fashion Hub'), 'Formal Dress Shirt', 'Long sleeve formal shirt for office and events', 350000.00, 70),

-- Sports products (Sports World)
    ((SELECT store_id FROM store WHERE store_name = 'Sports World'), 'Yoga Mat Premium', 'Non-slip yoga mat with carrying strap, 6mm thickness', 250000.00, 50),
    ((SELECT store_id FROM store WHERE store_name = 'Sports World'), 'Dumbbell Set 20kg', 'Adjustable dumbbell set with multiple weight plates', 1200000.00, 25),
    ((SELECT store_id FROM store WHERE store_name = 'Sports World'), 'Mountain Bike 27.5"', 'Professional mountain bike with 21-speed gear system', 4500000.00, 10),
    ((SELECT store_id FROM store WHERE store_name = 'Sports World'), 'Badminton Racket Carbon', 'Lightweight carbon fiber badminton racket', 650000.00, 35),
    ((SELECT store_id FROM store WHERE store_name = 'Sports World'), 'Treadmill Electric Foldable', 'Home treadmill with LCD display and incline feature', 6500000.00, 8);

-- Insert category relationships for products
-- (category_item will be auto-cleared by CASCADE when products are deleted)
INSERT INTO category_item (category_id, product_id) VALUES
    -- Electronics products
    ((SELECT category_id FROM category WHERE name = 'Electronics'), (SELECT product_id FROM product WHERE product_name = 'Laptop Gaming ROG')),
    ((SELECT category_id FROM category WHERE name = 'Electronics'), (SELECT product_id FROM product WHERE product_name = 'iPhone 15 Pro Max')),
    ((SELECT category_id FROM category WHERE name = 'Electronics'), (SELECT product_id FROM product WHERE product_name = 'Samsung Galaxy S24 Ultra')),
    ((SELECT category_id FROM category WHERE name = 'Electronics'), (SELECT product_id FROM product WHERE product_name = 'Sony WH-1000XM5')),
    ((SELECT category_id FROM category WHERE name = 'Electronics'), (SELECT product_id FROM product WHERE product_name = 'iPad Pro 12.9"')),

    -- Furniture products
    ((SELECT category_id FROM category WHERE name = 'Furniture'), (SELECT product_id FROM product WHERE product_name = 'Sofa 3 Seater Modern')),
    ((SELECT category_id FROM category WHERE name = 'Furniture'), (SELECT product_id FROM product WHERE product_name = 'Office Desk Minimalist')),
    ((SELECT category_id FROM category WHERE name = 'Furniture'), (SELECT product_id FROM product WHERE product_name = 'King Size Bed Frame')),
    ((SELECT category_id FROM category WHERE name = 'Furniture'), (SELECT product_id FROM product WHERE product_name = 'Dining Table Set 6 Chairs')),
    ((SELECT category_id FROM category WHERE name = 'Furniture'), (SELECT product_id FROM product WHERE product_name = 'Ergonomic Office Chair')),

    -- Book products
    ((SELECT category_id FROM category WHERE name = 'Books'), (SELECT product_id FROM product WHERE product_name = 'The Art of Computer Programming Vol. 1-3')),
    ((SELECT category_id FROM category WHERE name = 'Books'), (SELECT product_id FROM product WHERE product_name = 'Clean Code by Robert Martin')),
    ((SELECT category_id FROM category WHERE name = 'Books'), (SELECT product_id FROM product WHERE product_name = 'Harry Potter Complete Collection')),
    ((SELECT category_id FROM category WHERE name = 'Books'), (SELECT product_id FROM product WHERE product_name = 'Atomic Habits by James Clear')),
    ((SELECT category_id FROM category WHERE name = 'Books'), (SELECT product_id FROM product WHERE product_name = 'The Pragmatic Programmer')),

    -- Clothing products
    ((SELECT category_id FROM category WHERE name = 'Clothing'), (SELECT product_id FROM product WHERE product_name = 'Polo Shirt Premium Cotton')),
    ((SELECT category_id FROM category WHERE name = 'Clothing'), (SELECT product_id FROM product WHERE product_name = 'Slim Fit Jeans Denim')),
    ((SELECT category_id FROM category WHERE name = 'Clothing'), (SELECT product_id FROM product WHERE product_name = 'Leather Jacket Vintage')),
    ((SELECT category_id FROM category WHERE name = 'Clothing'), (SELECT product_id FROM product WHERE product_name = 'Sneakers Running Sport')),
    ((SELECT category_id FROM category WHERE name = 'Clothing'), (SELECT product_id FROM product WHERE product_name = 'Formal Dress Shirt')),

    -- Sports products
    ((SELECT category_id FROM category WHERE name = 'Sports'), (SELECT product_id FROM product WHERE product_name = 'Yoga Mat Premium')),
    ((SELECT category_id FROM category WHERE name = 'Sports'), (SELECT product_id FROM product WHERE product_name = 'Dumbbell Set 20kg')),
    ((SELECT category_id FROM category WHERE name = 'Sports'), (SELECT product_id FROM product WHERE product_name = 'Mountain Bike 27.5"')),
    ((SELECT category_id FROM category WHERE name = 'Sports'), (SELECT product_id FROM product WHERE product_name = 'Badminton Racket Carbon')),
    ((SELECT category_id FROM category WHERE name = 'Sports'), (SELECT product_id FROM product WHERE product_name = 'Treadmill Electric Foldable'))
ON CONFLICT DO NOTHING;
