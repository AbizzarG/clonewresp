-- Updated Product Seeder with Images and Rich Text Descriptions
-- Run this AFTER product_seeder_001.sql to UPDATE existing products

-- Electronics products (TechHub Store)
UPDATE product SET
    description = '<h3>High-Performance Gaming Beast</h3><p>Dominate the battlefield with the <strong>ASUS ROG Gaming Laptop</strong> powered by NVIDIA RTX 4060 graphics card. Experience ultra-smooth gameplay with 16GB DDR5 RAM and lightning-fast 512GB NVMe SSD.</p><h4>Key Features:</h4><ul><li>NVIDIA GeForce RTX 4060 6GB</li><li>Intel Core i7-13700H Processor</li><li>16GB DDR5 RAM (expandable)</li><li>512GB PCIe 4.0 NVMe SSD</li><li>15.6" FHD 144Hz Display</li><li>RGB Backlit Keyboard</li></ul><p><em>Perfect for gaming, streaming, and content creation!</em></p>',
    main_image_path = 'https://placehold.co/600x400/1a1a1a/ffffff?text=Laptop+Gaming+ROG'
WHERE product_name = 'Laptop Gaming ROG';

UPDATE product SET
    description = '<h3>The Ultimate iPhone Experience</h3><p>Introducing the <strong>iPhone 15 Pro Max</strong> with revolutionary A17 Pro chip and stunning titanium design. Capture incredible photos with the new 48MP main camera system.</p><h4>What''s New:</h4><ul><li>A17 Pro chip - fastest ever</li><li>Titanium Design - Lightweight & Strong</li><li>256GB Storage</li><li>48MP Main Camera + 5x Telephoto</li><li>Action Button for quick shortcuts</li><li>USB-C connectivity</li></ul><p>Available in Natural Titanium, Blue Titanium, White Titanium, and Black Titanium.</p>',
    main_image_path = 'https://placehold.co/600x400/333333/ffffff?text=iPhone+15+Pro+Max'
WHERE product_name = 'iPhone 15 Pro Max';

UPDATE product SET
    description = '<h3>Galaxy Power in Your Pocket</h3><p>Meet the <strong>Samsung Galaxy S24 Ultra</strong> - the ultimate Android flagship with integrated S Pen, stunning 200MP camera, and all-day battery life.</p><h4>Specifications:</h4><ul><li>Snapdragon 8 Gen 3 for Galaxy</li><li>12GB RAM + 256GB Storage</li><li>6.8" Dynamic AMOLED 2X Display</li><li>200MP + 50MP + 12MP + 10MP Cameras</li><li>Built-in S Pen</li><li>5000mAh Battery</li></ul><p><strong>Galaxy AI</strong> features for enhanced productivity and creativity!</p>',
    main_image_path = 'https://placehold.co/600x400/1a1a2e/ffffff?text=Samsung+S24+Ultra'
WHERE product_name = 'Samsung Galaxy S24 Ultra';

UPDATE product SET
    description = '<h3>Industry-Leading Noise Cancellation</h3><p>Experience silence like never before with <strong>Sony WH-1000XM5</strong>. Enhanced noise canceling, exceptional sound quality, and all-day comfort for your listening pleasure.</p><h4>Features:</h4><ul><li>Industry-leading noise cancellation</li><li>30-hour battery life</li><li>Crystal-clear hands-free calling</li><li>Intuitive touch controls</li><li>Multipoint connection</li><li>Premium comfort fit</li></ul><p><em>Perfect companion for travel, work, or relaxation.</em></p>',
    main_image_path = 'https://placehold.co/600x400/000000/ffffff?text=Sony+WH-1000XM5'
WHERE product_name = 'Sony WH-1000XM5';

UPDATE product SET
    description = '<h3>Your Creative Studio</h3><p>The <strong>iPad Pro 12.9"</strong> with M2 chip delivers desktop-class performance in a portable design. Perfect for designers, artists, and professionals on the go.</p><h4>Powerful Features:</h4><ul><li>Apple M2 chip - Desktop performance</li><li>12.9" Liquid Retina XDR display</li><li>256GB Storage</li><li>12MP Ultra Wide front camera</li><li>All-day battery life</li><li>Compatible with Apple Pencil 2</li></ul><p>Transform your workflow with iPadOS and desktop-class apps!</p>',
    main_image_path = 'https://placehold.co/600x400/2c3e50/ffffff?text=iPad+Pro+12.9'
WHERE product_name = 'iPad Pro 12.9"';

-- Furniture products (Furniture Paradise)
UPDATE product SET
    description = '<h3>Modern Comfort Meets Style</h3><p>Elevate your living room with our <strong>3-Seater Modern Sofa</strong>. Crafted with premium fabric and ergonomic design for ultimate comfort.</p><h4>Product Details:</h4><ul><li>High-quality fabric upholstery in elegant grey</li><li>Solid wood frame for durability</li><li>High-density foam cushions</li><li>Dimensions: 200cm x 85cm x 90cm</li><li>Easy assembly with instructions</li><li>Weight capacity: 300kg</li></ul><p><strong>Perfect for:</strong> Living rooms, family rooms, apartments</p>',
    main_image_path = 'https://placehold.co/600x400/7f8c8d/ffffff?text=Modern+Sofa'
WHERE product_name = 'Sofa 3 Seater Modern';

UPDATE product SET
    description = '<h3>Organize Your Workspace</h3><p>The <strong>Minimalist Office Desk</strong> combines form and function. Perfect for modern home offices and workspaces.</p><h4>Features:</h4><ul><li>Premium wood finish</li><li>2 built-in storage drawers</li><li>Cable management system</li><li>Dimensions: 120cm x 60cm x 75cm</li><li>Scratch-resistant surface</li><li>Load capacity: 50kg</li></ul><p><em>Create your ideal workspace with clean, minimalist design.</em></p>',
    main_image_path = 'https://placehold.co/600x400/8b7355/ffffff?text=Office+Desk'
WHERE product_name = 'Office Desk Minimalist';

UPDATE product SET
    description = '<h3>Sleep in Luxury</h3><p>Our <strong>King Size Bed Frame</strong> combines solid wood construction with modern aesthetics. Built to last with timeless design.</p><h4>Specifications:</h4><ul><li>Solid wood frame - Premium quality</li><li>Elegant headboard design</li><li>King size: 180cm x 200cm</li><li>Strong slat support system</li><li>Easy assembly included</li><li>Weight capacity: 250kg</li></ul><p>Transform your bedroom into a luxury retreat!</p>',
    main_image_path = 'https://placehold.co/600x400/654321/ffffff?text=King+Bed+Frame'
WHERE product_name = 'King Size Bed Frame';

UPDATE product SET
    description = '<h3>Family Dining Redefined</h3><p>Gather your family with our <strong>Dining Table Set</strong> for 6. Elegant design meets practical functionality.</p><h4>What''s Included:</h4><ul><li>1 Dining table (150cm x 90cm)</li><li>6 Cushioned chairs with premium fabric</li><li>Solid wood construction</li><li>Scratch-resistant tabletop</li><li>Easy-clean surface</li><li>Contemporary design</li></ul><p><strong>Perfect for:</strong> Family dinners, gatherings, celebrations</p>',
    main_image_path = 'https://placehold.co/600x400/a0522d/ffffff?text=Dining+Table+Set'
WHERE product_name = 'Dining Table Set 6 Chairs';

UPDATE product SET
    description = '<h3>Work Comfortably All Day</h3><p>The <strong>Ergonomic Office Chair</strong> provides superior support for long working hours. Say goodbye to back pain!</p><h4>Ergonomic Features:</h4><ul><li>Adjustable lumbar support</li><li>Breathable mesh back</li><li>Height-adjustable armrests</li><li>Tilt and recline mechanism</li><li>360° swivel base</li><li>Smooth-rolling casters</li></ul><p><em>Invest in your health and productivity with proper seating!</em></p>',
    main_image_path = 'https://placehold.co/600x400/34495e/ffffff?text=Office+Chair'
WHERE product_name = 'Ergonomic Office Chair';

-- Book products (Book Haven)
UPDATE product SET
    description = '<h3>The Bible of Computer Science</h3><p><strong>The Art of Computer Programming</strong> by Donald Knuth - A comprehensive monograph written by computer scientist Donald Knuth that covers many kinds of programming algorithms.</p><h4>Volume Overview:</h4><ul><li><strong>Volume 1:</strong> Fundamental Algorithms</li><li><strong>Volume 2:</strong> Seminumerical Algorithms</li><li><strong>Volume 3:</strong> Sorting and Searching</li></ul><p>This boxed set is an essential reference for any serious programmer. Knuth''s writing is clear, rigorous, and timeless.</p><p><em>"If you think you''re a really good programmer, read Art of Computer Programming. You should definitely send me a résumé if you can read the whole thing." - Bill Gates</em></p>',
    main_image_path = 'https://placehold.co/600x400/8b4513/ffffff?text=TAOCP+Books'
WHERE product_name = 'The Art of Computer Programming Vol. 1-3';

UPDATE product SET
    description = '<h3>Write Code That Matters</h3><p><strong>Clean Code</strong> by Robert C. Martin - A Handbook of Agile Software Craftsmanship. Learn the principles of writing clean, maintainable code.</p><h4>Topics Covered:</h4><ul><li>Meaningful names and functions</li><li>Comments and formatting</li><li>Error handling best practices</li><li>Unit testing and TDD</li><li>Classes and data structures</li><li>Emergence and refactoring</li></ul><p><strong>Perfect for:</strong> Developers who want to improve their craft and write better code.</p><p><em>"Even bad code can function. But if code isn''t clean, it can bring a development organization to its knees."</em></p>',
    main_image_path = 'https://placehold.co/600x400/4a4a4a/ffffff?text=Clean+Code'
WHERE product_name = 'Clean Code by Robert Martin';

UPDATE product SET
    description = '<h3>The Magic Never Ends</h3><p><strong>Harry Potter Complete Collection</strong> - All 7 books in a beautiful special edition box set. Experience the entire magical journey of Harry Potter!</p><h4>Complete Series Includes:</h4><ul><li>Philosopher''s Stone</li><li>Chamber of Secrets</li><li>Prisoner of Azkaban</li><li>Goblet of Fire</li><li>Order of the Phoenix</li><li>Half-Blood Prince</li><li>Deathly Hallows</li></ul><p>Special edition with exclusive cover art and premium packaging. <strong>Perfect gift</strong> for Potterheads of all ages!</p>',
    main_image_path = 'https://placehold.co/600x400/722f37/ffffff?text=Harry+Potter+Box'
WHERE product_name = 'Harry Potter Complete Collection';

UPDATE product SET
    description = '<h3>Transform Your Life One Habit at a Time</h3><p><strong>Atomic Habits</strong> by James Clear - An Easy & Proven Way to Build Good Habits & Break Bad Ones. The #1 New York Times bestseller!</p><h4>What You''ll Learn:</h4><ul><li>The Four Laws of Behavior Change</li><li>How to make habits stick</li><li>The 1% improvement principle</li><li>Breaking bad habits effectively</li><li>Building systems, not goals</li><li>Identity-based habits</li></ul><p><em>"Habits are the compound interest of self-improvement. Getting 1% better every day counts for a lot in the long-run."</em></p>',
    main_image_path = 'https://placehold.co/600x400/2ecc71/ffffff?text=Atomic+Habits'
WHERE product_name = 'Atomic Habits by James Clear';

UPDATE product SET
    description = '<h3>Your Journey to Software Mastery</h3><p><strong>The Pragmatic Programmer</strong> - 20th Anniversary Edition. Timeless wisdom for the modern developer, updated for today''s challenges.</p><h4>Core Topics:</h4><ul><li>Pragmatic philosophy and approach</li><li>Software craftsmanship</li><li>Tools and automation</li><li>Design by contract</li><li>Refactoring and testing</li><li>Pragmatic projects</li></ul><p><strong>New in 20th Anniversary Edition:</strong> Updated examples, new material on functional programming, and modern development practices!</p>',
    main_image_path = 'https://placehold.co/600x400/3498db/ffffff?text=Pragmatic+Programmer'
WHERE product_name = 'The Pragmatic Programmer';

-- Clothing products (Fashion Hub)
UPDATE product SET
    description = '<h3>Classic Style, Premium Quality</h3><p>Our <strong>Premium Cotton Polo Shirt</strong> is the perfect blend of comfort and style. Made from 100% premium cotton for all-day comfort.</p><h4>Product Features:</h4><ul><li>100% Premium combed cotton</li><li>Breathable and soft fabric</li><li>Available colors: Navy, White, Black, Grey</li><li>Classic collar design</li><li>Sizes: S, M, L, XL, XXL</li><li>Easy care - machine washable</li></ul><p><strong>Versatile styling:</strong> Perfect for casual outings, smart-casual events, or everyday wear!</p>',
    main_image_path = 'https://placehold.co/600x400/2c3e50/ffffff?text=Polo+Shirt'
WHERE product_name = 'Polo Shirt Premium Cotton';

UPDATE product SET
    description = '<h3>Your Perfect Fit</h3><p><strong>Slim Fit Denim Jeans</strong> - Classic blue denim with modern stretch fabric for comfort and style. A wardrobe essential!</p><h4>Details:</h4><ul><li>Classic blue wash</li><li>Stretch denim fabric (98% cotton, 2% elastane)</li><li>Slim fit design</li><li>5-pocket styling</li><li>Belt loops</li><li>Available sizes: 28-38</li></ul><p><em>Dress them up or down - these jeans work for any occasion!</em></p>',
    main_image_path = 'https://placehold.co/600x400/4a69bd/ffffff?text=Slim+Jeans'
WHERE product_name = 'Slim Fit Jeans Denim';

UPDATE product SET
    description = '<h3>Timeless Vintage Style</h3><p><strong>Genuine Leather Jacket</strong> with vintage aesthetic. Crafted from premium leather for durability and that perfect worn-in look.</p><h4>Specifications:</h4><ul><li>100% Genuine leather</li><li>Vintage distressed finish</li><li>YKK zipper hardware</li><li>Multiple pockets (inner & outer)</li><li>Quilted lining for warmth</li><li>Sizes: S, M, L, XL</li></ul><p><strong>Style tip:</strong> Pair with jeans and boots for the ultimate vintage rebel look!</p>',
    main_image_path = 'https://placehold.co/600x400/654321/ffffff?text=Leather+Jacket'
WHERE product_name = 'Leather Jacket Vintage';

UPDATE product SET
    description = '<h3>Run Your Best</h3><p><strong>Running Sport Sneakers</strong> designed for performance and comfort. Lightweight construction with superior cushioning for your daily runs.</p><h4>Features:</h4><ul><li>Lightweight breathable mesh upper</li><li>EVA foam cushioned sole</li><li>Flexible rubber outsole</li><li>Moisture-wicking lining</li><li>Available in multiple colors</li><li>Sizes: 39-45 (EU)</li></ul><p><em>Whether you''re training for a marathon or just hitting the gym, these sneakers have got you covered!</em></p>',
    main_image_path = 'https://placehold.co/600x400/e74c3c/ffffff?text=Running+Sneakers'
WHERE product_name = 'Sneakers Running Sport';

UPDATE product SET
    description = '<h3>Dress for Success</h3><p><strong>Formal Dress Shirt</strong> - Long sleeve business shirt perfect for office, interviews, and formal events. Wrinkle-resistant fabric keeps you looking sharp.</p><h4>Product Details:</h4><ul><li>Wrinkle-resistant fabric blend</li><li>Classic button-down collar</li><li>Long sleeves with adjustable cuffs</li><li>Chest pocket</li><li>Colors: White, Light Blue, Black</li><li>Slim and Regular fit available</li></ul><p><strong>Care:</strong> Machine washable, minimal ironing required!</p>',
    main_image_path = 'https://placehold.co/600x400/ecf0f1/333333?text=Formal+Shirt'
WHERE product_name = 'Formal Dress Shirt';

-- Sports products (Sports World)
UPDATE product SET
    description = '<h3>Find Your Zen</h3><p><strong>Premium Yoga Mat</strong> - Non-slip surface provides excellent grip for all yoga poses. Includes carrying strap for easy transport.</p><h4>Specifications:</h4><ul><li>6mm thickness for comfort</li><li>Non-slip textured surface</li><li>Eco-friendly TPE material</li><li>Dimensions: 183cm x 61cm</li><li>Lightweight and portable</li><li>Easy to clean</li></ul><p><em>Perfect for yoga, pilates, stretching, and meditation practices!</em></p>',
    main_image_path = 'https://placehold.co/600x400/9b59b6/ffffff?text=Yoga+Mat'
WHERE product_name = 'Yoga Mat Premium';

UPDATE product SET
    description = '<h3>Build Strength at Home</h3><p><strong>Adjustable Dumbbell Set 20kg</strong> - Complete home gym solution with multiple weight configurations. Save space and money!</p><h4>What''s Included:</h4><ul><li>2 Dumbbell handles with secure collars</li><li>Multiple weight plates (1kg, 2kg, 3kg)</li><li>Total weight: 20kg (10kg per dumbbell)</li><li>Adjustable from 2kg to 10kg each</li><li>Chrome finish handles</li><li>Storage case included</li></ul><p><strong>Perfect for:</strong> Home workouts, strength training, bodybuilding</p>',
    main_image_path = 'https://placehold.co/600x400/34495e/ffffff?text=Dumbbell+Set+20kg'
WHERE product_name = 'Dumbbell Set 20kg';

UPDATE product SET
    description = '<h3>Conquer the Trails</h3><p><strong>Professional Mountain Bike 27.5"</strong> - Built for off-road adventures with 21-speed gear system and front suspension.</p><h4>Bike Specifications:</h4><ul><li>27.5" aluminum alloy frame</li><li>Shimano 21-speed gear system</li><li>Front suspension fork</li><li>Dual disc brakes</li><li>All-terrain tires</li><li>Adjustable seat height</li></ul><p><em>Ready to tackle mountains, trails, and city streets with confidence!</em></p>',
    main_image_path = 'https://placehold.co/600x400/27ae60/ffffff?text=Mountain+Bike'
WHERE product_name = 'Mountain Bike 27.5"';

UPDATE product SET
    description = '<h3>Lightweight Power</h3><p><strong>Carbon Fiber Badminton Racket</strong> - Professional-grade racket for serious players. Ultra-lightweight for maximum swing speed.</p><h4>Features:</h4><ul><li>100% Carbon fiber frame</li><li>Ultra-lightweight (85g)</li><li>Flexible shaft for power</li><li>High-tension string (up to 30lbs)</li><li>Ergonomic grip</li><li>Carrying case included</li></ul><p><strong>Ideal for:</strong> Tournament play, competitive matches, training</p>',
    main_image_path = 'https://placehold.co/600x400/e67e22/ffffff?text=Badminton+Racket'
WHERE product_name = 'Badminton Racket Carbon';

UPDATE product SET
    description = '<h3>Your Home Fitness Solution</h3><p><strong>Electric Foldable Treadmill</strong> - Powerful motor, space-saving design, and LCD display tracking. Achieve your fitness goals at home!</p><h4>Specifications:</h4><ul><li>2.5 HP motor (quiet operation)</li><li>Speed range: 1-12 km/h</li><li>LCD display (speed, time, distance, calories)</li><li>3-level manual incline adjustment</li><li>Foldable design for storage</li><li>Weight capacity: 120kg</li></ul><p><em>No more excuses! Start your fitness journey today from the comfort of home.</em></p>',
    main_image_path = 'https://placehold.co/600x400/2c3e50/ffffff?text=Treadmill+Foldable'
WHERE product_name = 'Treadmill Electric Foldable';
