-- Seeding users table
INSERT INTO users (email, password, role, name, address, balance) VALUES 
    ('buyer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Test Buyer', 'Jl. Test Buyer No. 1, Jakarta', 100000000.00),
    ('seller@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Test Seller', 'Jl. Test Seller No. 1, Jakarta', 500000.00)
ON CONFLICT (email) DO NOTHING;

-- NOTE: passnya "password"