<?php
/**
 * Reusable Order Detail Modal Component
 */

class ModalOrderDetail {
    public static function render($options = []) {
        $id = $options['id'] ?? 'modalOrderDetail';
        ?>
        <div class="modal-overlay oh-modal-overlay" id="<?php echo htmlspecialchars($id); ?>Overlay">
            <div class="modal-container oh-modal-container">
                <div class="modal-header oh-modal-header">
                    <h2 class="modal-title">Detail Pesanan</h2>
                    <button type="button" class="modal-close" aria-label="Close modal">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <div class="modal-body oh-modal-body" id="<?php echo htmlspecialchars($id); ?>Content">
                    <!-- future js generated content -->
                    <div class="oh-modal-loading">Memuat detail pesanan...</div>
                </div>
            </div>
        </div>
        <?php
    }
}

ModalOrderDetail::render();

if (!isset($GLOBALS['modal_order_detail_css_included'])) {
    $GLOBALS['modal_order_detail_css_included'] = true;
    echo '<link rel="stylesheet" href="../../resources/components/modal-order-detail.css">';
}
if (!isset($GLOBALS['modal_order_detail_js_included'])) {
    $GLOBALS['modal_order_detail_js_included'] = true;
    echo '<script src="../../public/components/modalOrderDetail.js"></script>';
}
?>