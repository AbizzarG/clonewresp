<?php
ob_start();
session_start();
require_once '../../config/Database.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../");
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['buyer', 'guest'])) {
    header("Location: ../../");
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['name'] ?? 'Guest';
$userEmail = $_SESSION['email'] ?? '';
$userBalance = 0;
$userAddress = '';
$success = '';
$error = '';

if (($_SESSION['role'] ?? '') === 'buyer' && $userId) {
    try {
        $db = new Database();
        $pdo = $db->connect();
        $stmt = $pdo->prepare("SELECT name, email, balance, address FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userName = $user['name'];
            $userEmail = $user['email'];
            $userBalance = $user['balance'];
            $userAddress = $user['address'] ?? '';
        }
    } catch (Exception $e) {
        $userBalance = 0;
        $userAddress = '';
    }
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $newName = trim($_POST['name'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');

    if ($newName === '') {
        $error = 'Nama tidak boleh kosong.';
    } else {
        try {
            $db = new Database();
            $pdo = $db->connect();
            $stmt = $pdo->prepare('UPDATE users SET name = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
            $stmt->execute([$newName, $newAddress, $userId]);

            $_SESSION['name'] = $newName;
            $userName = $newName;
            $userAddress = $newAddress;
            $success = 'Profil berhasil diperbarui.';
        } catch (Exception $e) {
            $error = 'Gagal memperbarui profil. Coba lagi.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success !== '',
            'message' => $success ?: $error,
            'data' => ['name' => $userName, 'address' => $userAddress]
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
    <title>Profile Buyer</title>
    <link rel="stylesheet" href="../../resources/buyer/profile.css">
</head>
<body>
    <?php include '../components/Navbar.php'?>

    <div class='profile-page'>
        <div class='profile-card'>
            <div class='profile-header'>
                <div class='profile-title'>
                    <img class='image' src="https://avatar.iran.liara.run/public/47" alt="profile-pic">
                    <h1><?php echo htmlspecialchars($userName); ?></h1>
                </div>
                <div class='header-actions'>
                    <button class='edit-link' id="toggleEditBtn" type="button">
                        Ubah Profil
                    </button>
                    <a class='edit-link' href="edit_password.php" style="margin-left: 8px;">
                        Ubah Password
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- View Mode -->
            <div class='profile-info' id="viewMode">
                <div class='info-row'>
                    <div class='info-label'>Nama</div>
                    <span class='info-value' id="viewName"><?php echo htmlspecialchars($userName); ?></span>
                </div>

                <div class='info-row'>
                    <div class='info-label'>Email</div>
                    <span class='info-value'><?php echo htmlspecialchars($userEmail); ?></span>
                </div>

                <div class='info-row'>
                    <div class='info-label'>Alamat</div>
                    <span class='info-value multiline' id="viewAddress"><?php echo nl2br(htmlspecialchars($userAddress)); ?></span>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Saldo</div>
                    <span class='info-value'>Rp <?php echo number_format($userBalance, 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <!-- Edit Form -->
            <form method="POST" action="" class="edit-profile-form" id="editMode" style="display: none;" novalidate>
                <input type="hidden" name="edit_profile" value="1">
                <div class="form-group">
                    <label for="name">Nama</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <span id='emailUser'><?php echo htmlspecialchars($userEmail); ?></span>
                </div>

                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($userAddress); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn secondary" id="cancelEditBtn">Batal</button>
                    <button type="submit" class="btn primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <?php 
    require_once '../components/ModalConfirmation.php';
    ModalConfirmation::render([
        'id' => 'confirmEditProfile',
        'title' => 'Konfirmasi Perubahan Profil',
        'message' => 'Apakah Anda yakin ingin menyimpan perubahan pada profil Anda?',
        'confirmText' => 'Ya, Simpan',
        'cancelText' => 'Batal',
        'confirmClass' => 'primary'
    ]);

    require_once '../components/LoadingStateUI.php';
    LoadingStateUI::render([
        'id' => 'loadingEditProfile',
        'message' => 'Menyimpan perubahan profil...'
    ]);

    require_once '../components/Toast.php';
    Toast::render();
    ?>

    <script src="../../public/buyer/profile.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>