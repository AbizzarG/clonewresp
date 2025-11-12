<?php
session_start();
require_once 'config/Database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $redirect = $_SESSION['role'] === 'seller' ? 'app/seller/index.php' : 'app/buyer/index.php';
    header("Location: {$redirect}");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /");
    exit();
}

// Guest login
if (isset($_GET['guest'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['name'] = 'Guest';
    $_SESSION['email'] = '';
    header("Location: app/buyer/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $db = new Database();
            $pdo = $db->connect();
            
            $stmt = $pdo->prepare("SELECT user_id, email, password, role, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                $redirect = $user['role'] === 'seller' ? 'app/seller/index.php' : 'app/buyer/index.php';
                header("Location: {$redirect}");
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again';
        }
    } else {
        $error = 'Please fill in both fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="resources/auth.css">
    <style>
    </style>
</head>
<body>
    <div class='auth-container min-h-screen'>
        <div class='auth-card'>
            <div class='header-container'>
                <h1 class='auth-title'>Masuk</h1>
                <a href='app/auth/register.php' class='sub-header'>Daftar</a>
            </div>
            <?php if (!empty($error)): ?>
                <div class='error-msg'><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method='POST' action='' class='form-container'>
                <div class='form-group'>
                    <label for="email">Your Email</label>
                    <input type="text" id='email' name="email" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';  ?>"
                    >
                </div>
                <div class='form-group input-with-icon'>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <button type='button' id='togglePassword' class='icon-btn password-toggle' data-target='password' aria-label='Toggle pass visibility'>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <button type="submit" class='btn' id='loginBtn' disabled>
                    Login
                </button>
            </form>

            <!-- Link Guest -->
            <div class='guest-container'>
                <a class='sub-header' href='?guest=1'>Masuk sebagai guest</a>
            </div>
        </div>
    </div>
    <script src='public/auth/auth.js'></script>
</body>
</html>