<?php

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['name'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch balance
$userBalance = 0;
$storeBalance = 0;
if ($isLoggedIn) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/../../config/Database.php';
            $db = new Database();
            $pdo = $db->connect();
        }

        if ($userRole === 'buyer') {
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $userBalance = $result ? (int)$result['balance'] : 0;
        } elseif ($userRole === 'seller') {
            $stmt = $pdo->prepare("SELECT balance FROM store WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $storeBalance = $result ? (int)$result['balance'] : 0;
        }
    } catch (Exception $e) {
        error_log("Navbar balance fetch error: " . $e->getMessage());
    }
}
?>
<link rel="stylesheet" href="../../resources/components/navbar.css">

<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo/Brand -->
        <div class="navbar-brand">
            <a href="<?php echo $isLoggedIn ? '.' : '../../index.php'; ?>" class="brand-link">
                <img src="../../assets/logo.svg" alt="Nimonspedia Logo" class="brand-logo">
            </a>
        </div>
        <?php include 'SearchBar.php'; ?>

        <!-- Navigation Links -->
        <div class="navbar-nav">
            <?php if ($isLoggedIn): ?>
                <!-- User is logged in -->
                <!-- Balance Display -->
                <?php if ($userRole === 'buyer'): ?>
                    <button class="balance-display balance" id="balanceBtn" aria-label="Balance" title="Klik untuk top-up">
                        <i class='icon' data-lucide='wallet'></i>
                        <span id="navbarBalance">Rp <?php echo number_format($userBalance, 0, ',', '.'); ?></span>
                    </button>
                <?php elseif ($userRole === 'seller'): ?>
                    <div class="balance-display balance" title="Saldo toko">
                        <i class='icon' data-lucide='wallet'></i>
                        <span>Rp <?php echo number_format($storeBalance, 0, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($userRole === 'buyer'): ?>
                    <a href="../buyer/cart.php" class="menu-button cart-button" aria-label="Cart">
                        <div class="cart-icon-wrapper">
                            <i class='icon' data-lucide='shopping-cart'></i>
                            <span class="cart-badge">0</span>
                        </div>
                    </a>
                <?php endif; ?>

                <!-- Profile dropdown -->
                <div class="nav-dropdown" id="profileDropdown">
                    <button class="menu-button" id="profile" aria-haspopup="true" aria-expanded="false">
                        <i class='icon' data-lucide='user'></i>
                        <?php echo htmlspecialchars($userName); ?>
                        <i class='icon' data-lucide='chevron-down'></i>
                    </button>
                    <div class="nav-dropdown-menu" role="menu" aria-labelledby="profile">
                        <a class="dropdown-item" role="menuitem"
                           href="<?php echo ($userRole === 'seller') ? '../seller/index.php' : '../buyer/profile.php'; ?>">
                            <i class='icon-item' data-lucide='user'></i>
                            Profile
                        </a>
                        <?php if ($userRole === 'buyer') :?>
                            <a class="dropdown-item" role="menuitem"
                                href='../buyer/order_history.php'
                            >
                                <i class='icon-item' data-lucide='receipt-text'></i>
                                Order History
                            </a>
                        <?php endif;?>
                        <a class="dropdown-item" role="menuitem" href="<?php echo ($userRole === 'seller') ? '../seller/index.php?logout=1' : '../buyer/index.php?logout=1'; ?>">
                            <i class='icon-item' data-lucide='log-out'></i>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- User is not logged in -->
                <div class="nav-auth-links">
                    <a href="../auth/register.php" class="nav-link register-link">
                        Daftar
                    </a>
                    <a href="../../index.php" class="nav-link login-link">
                        Masuk
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($isLoggedIn && $userRole === 'buyer'): ?>
<div class="modal-overlay" id="topupModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Top-up Saldo</h3>
            <button class="modal-close" id="closeTopupModal" aria-label="Close">
                <i class='icon' data-lucide='x'></i>
            </button>
        </div>
        <form id="topupForm" class="modal-body">
            <div class="form-group">
                <label for="topupAmount" class="form-label">Jumlah Top-up</label>
                <div class="input-wrapper">
                    <span class="input-prefix">Rp</span>
                    <input
                        type="number"
                        id="topupAmount"
                        name="amount"
                        class="form-input"
                        placeholder="0"
                        min="10000"
                        step="10000"
                        required
                    >
                </div>
                <small class="form-hint">Minimal top-up Rp 10.000</small>
            </div>

            <div class="quick-amount-buttons">
                <button type="button" class="quick-amount-btn" data-amount="50000">50k</button>
                <button type="button" class="quick-amount-btn" data-amount="100000">100k</button>
                <button type="button" class="quick-amount-btn" data-amount="200000">200k</button>
                <button type="button" class="quick-amount-btn" data-amount="500000">500k</button>
            </div>

            <div class="current-balance">
                <span>Saldo saat ini:</span>
                <strong>Rp <?php echo number_format($userBalance, 0, ',', '.'); ?></strong>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelTopup">Batal</button>
                <button type="submit" class="btn-primary" id="submitTopup">
                    <span class="btn-text">Konfirmasi Top-up</span>
                    <span class="btn-loading">
                        <i class='icon spinner' data-lucide='loader-2'></i>
                        Processing...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
<script src="../../public/components/navbar.js"></script>