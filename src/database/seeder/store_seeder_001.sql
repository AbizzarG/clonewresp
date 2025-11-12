-- Seeding store table
INSERT INTO store (user_id, store_name, store_description, balance) VALUES
    ((SELECT user_id FROM users WHERE email = 'seller@test.com'), 'TechHub Store', 'Your one-stop shop for electronics and gadgets', 1500000.00)
    -- ((SELECT user_id FROM users WHERE email = 'seller@test.com'), 'Furniture Paradise', 'Quality furniture for your home and office', 2000000.00),
    -- ((SELECT user_id FROM users WHERE email = 'seller@test.com'), 'Book Haven', 'Wide selection of books for all ages', 750000.00),
    -- ((SELECT user_id FROM users WHERE email = 'seller@test.com'), 'Fashion Hub', 'Trendy clothing and accessories', 1250000.00),
    -- ((SELECT user_id FROM users WHERE email = 'seller@test.com'), 'Sports World', 'Everything you need for sports and fitness', 980000.00)
ON CONFLICT (store_name) DO NOTHING;
