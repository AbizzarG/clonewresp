<?php
ob_start();
session_start();
require_once '../../config/Database.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header('Location: ../../');
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPassword)) {
        $error = 'Password saat ini tidak boleh kosong.';
    } else if (empty($newPassword)) {
        $error = 'Password baru tidak boleh kosong.';
    } else if ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password tidak sama.';
    } else if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
        $error = 'Password harus minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol.';
    } else {
        try {
            $db = new Database();
            $pdo = $db->connect();
            
            $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $error = 'Password saat ini salah.';
            } else {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmtPwd = $pdo->prepare('UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmtPwd->execute([$hashed, $userId]);
                
                $success = 'Password berhasil diubah.';
            }
        } catch (Exception $e) {
            $error = 'Gagal mengubah password. Coba lagi.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success !== '',
            'message' => $success ?: $error
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password</title>
    <link rel="stylesheet" href="../../resources/buyer/edit_password.css">
</head>
<body>
    <main class="edit-page">
        <section class="edit-card">
            <div class="edit-header">
                <h1>Ubah Password</h1>
                <a class="link-back" href="profile.php">
                    <i class='icon' data-lucide='arrow-left'></i>
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="edit-form" id="changePasswordForm" novalidate>
                <div class="form-group input-with-icon">
                    <label for="current_password">Password Saat Ini *</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <button type="button" class="pwd-toggle" data-target="current_password" aria-label="Toggle visibility">
                        <i class='icon' data-lucide='eye'></i>
                    </button>
                </div>

                <div class="form-group input-with-icon">
                    <label for="password">Password Baru *</label>
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="pwd-toggle" data-target="password" aria-label="Toggle visibility">
                        <i class='icon' data-lucide='eye'></i>
                    </button>
                    <small class="form-hint">Minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol</small>
                </div>

                <div class="form-group input-with-icon">
                    <label for="confirm_password">Konfirmasi Password Baru *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="pwd-toggle" data-target="confirm_password" aria-label="Toggle visibility">
                        <i class='icon' data-lucide='eye'></i>
                    </button>
                    <small id="pwdHint" class="hint"></small>
                </div>

                <div class="actions">
                    <button type="submit" class="btn primary">Ubah Password</button>
                </div>
            </form>
        </section>
    </main>

    <?php 
    require_once '../components/ModalConfirmation.php';
    ModalConfirmation::render([
        'id' => 'confirmChangePassword',
        'title' => 'Konfirmasi Ubah Password',
        'message' => 'Apakah Anda yakin ingin mengubah password?',
        'confirmText' => 'Ya, Ubah',
        'cancelText' => 'Batal',
        'confirmClass' => 'primary'
    ]);

    require_once '../components/LoadingStateUI.php';
    LoadingStateUI::render([
        'id' => 'loadingChangePassword',
        'message' => 'Mengubah password...'
    ]);

    require_once '../components/Toast.php';
    Toast::render();
    ?>
    <script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
    <script src="../../public/buyer/editPassword.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>