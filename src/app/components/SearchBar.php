<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$preserveId = '';
$isOrderManagement = ($currentPage === 'order_management.php');
$placeholder = $isOrderManagement ? 'Cari Order ID atau Nama Buyer' : 'Cari di Nimonspedia';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
?>
<link rel="stylesheet" href="../../resources/components/search-bar.css">
<div class="search-bar-container">
    <form class="search-form" method="GET" action="">
        <?php echo $preserveId; ?>
        <?php if ($isOrderManagement && !empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
        <?php endif; ?>
        <div class="search-input-group">
            <button type="submit" class="search-button" aria-label="Search">
                <i id='search-icon' data-lucide='search'></i>
            </button>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
            >
        </div>
    </form>
</div>
<script src="https://unpkg.com/lucide@latest" onload="lucide.createIcons()"></script>
