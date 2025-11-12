<?php
session_start();
require_once '../../config/Database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'seller' ? '../seller/index.php' : '../buyer/index.php';
    header("Location: {$redirect}");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $address = trim($_POST['address']);
    $role = $_POST['role'] ?? 'buyer';

    // Validation
    if (empty($name)) $errors[] = 'Nama tidak boleh kosong!';
    if (empty($email)) $errors[] = 'Email tidak boleh kosong!';
    if (empty($password)) $errors[] = 'Password tidak boleh kosong!';
    if (empty($confirmPassword)) $errors[] = 'Konfirmasi password tidak boleh kosong!';
    if (empty($address)) $errors[] = 'Alamat tidak boleh kosong!'; 
    if ($password !== $confirmPassword) $errors[] = 'Password dan konfirmasi password tidak sama!';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid!';
    if (!in_array($role, ['buyer', 'seller'])) $errors[] = 'Role tidak valid!';

    // Password strength validation
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = 'Password harus minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol';
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $pdo = $db->connect();

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah terdaftar!';
            } else {
                // Insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users(name, email, password, role, address) VALUES(?,?,?,?,?)");
                $stmt->execute([$name, $email, $hashedPassword, $role, $address]);

                $userId = $pdo->lastInsertId();

                // If seller, create store
                if ($role === 'seller' && isset($_POST['store_name']) && !empty($_POST['store_name'])) {
                    $storeName = trim($_POST['store_name']);
                    $storeDescription = $_POST['store_description'] ?? '';

                    $logoPath = null;
                    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../../upload/logos/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $fileExtension = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

                        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                            $fileName = 'store_'.$userId.'_'.time().'.'.$fileExtension;
                            $filePath = $uploadDir.$fileName;

                            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $filePath)) {
                                $logoPath = 'upload/logos/'.$fileName;
                            }
                        }
                    }

                    $stmt = $pdo->prepare('INSERT INTO store(user_id, store_name, store_description, store_logo_path) VALUES(?,?,?,?)');
                    $stmt->execute([$userId, $storeName, $storeDescription, $logoPath]);
                }

                // Set session and redirect
                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;

                $redirect = $role === 'seller' ? '../seller/index.php' : '../buyer/index.php';
                header("Location: {$redirect}");
                exit();
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: '.$e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../../resources/auth.css">
</head>
<body>
    <div class='auth-container min-h-screen auth-container-register'>
        <div class='auth-card'>         
            <div class='header-container'>
                <h1 class='auth-title'>Daftar</h1>
                <a href="../../" class='sub-header'>Login</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class='error-msg'>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class='form-container' method="POST" action="" enctype="multipart/form-data">
                <!-- Basic Info -->
                <div class="form-group">
                    <label for="name">Nama Lengkap *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group input-with-icon">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="icon-btn password-toggle" data-target="password" aria-label="Toggle password visibility">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                    <small style="color: #666; font-size: 12px;">
                        Minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol
                    </small>
                </div>

                <div class="form-group input-with-icon">
                    <label for="confirmPassword">Konfirmasi Password *</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <button type="button" class="icon-btn password-toggle" data-target="confirmPassword" aria-label="Toggle confirm password visibility">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>

                <div class="form-group">
                    <label for="address">Alamat *</label>
                    <textarea id="address" name="address" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <!-- Role Selection -->
                <div class="role-selection">
                    <div class="role-option">
                        <input type="radio" name="role" value="buyer" id="role_buyer" checked>
                        <label for="role_buyer">
                            <strong>
                                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                Pembeli
                            </strong><br>
                            <small>Beli produk dari berbagai toko</small>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" value="seller" id="role_seller">
                        <label for="role_seller">
                            <strong>
                                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                Penjual
                            </strong><br>
                            <small>Jual produk dan kelola toko</small>
                        </label>
                    </div>
                </div>

                <!-- Seller Fields -->
                <div class="seller-fields" id="sellerFields">
                    <h3>Informasi Toko</h3>
                    <div class="form-group">
                        <label for="store_name">Nama Toko *</label>
                        <input type="text" id="store_name" name="store_name">
                    </div>
                    
                    <div class="quill-container">
                        <label>Deskripsi Toko *</label>
                        <div id="storeDescriptionEditor" class="quill-editor"></div>
                        <input type="hidden" id="store_description" name="store_description">
                    </div>
                    
                    <div class="form-group">
                        <label for="store_logo">Logo Toko</label>
                        <input type="file" id="store_logo" name="store_logo" accept="image/*">
                    </div>
                </div>

                <button type="submit" class='btn'>
                    Daftar
                </button>
            </form>
        </div>
    </div>
    <script src="../../public/auth/auth.js"></script>
</body>
</html>