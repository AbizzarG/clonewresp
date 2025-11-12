<?php
/**
 * Reusable Confirmation Modal Component
 * 
 * Usage:
 *   include '../components/ModalConfirmation.php';
 *   ModalConfirmation::render([
 *     'id' => 'confirmModal',
 *     'title' => 'Konfirmasi',
 *     'message' => 'Apakah Anda yakin?',
 *     'confirmText' => 'Ya, Konfirmasi',
 *     'cancelText' => 'Batal',
 *     'confirmClass' => 'primary'
 *   ]);
 */

class ModalConfirmation {
    public static function render($options = []) {
        $id = $options['id'] ?? 'modalConfirmation';
        $title = $options['title'] ?? 'Konfirmasi';
        $message = $options['message'] ?? 'Apakah Anda yakin ingin melanjutkan?';
        $confirmText = $options['confirmText'] ?? 'Konfirmasi';
        $cancelText = $options['cancelText'] ?? 'Batal';
        $confirmClass = $options['confirmClass'] ?? 'primary';
        $onConfirm = $options['onConfirm'] ?? '';
        $onCancel = $options['onCancel'] ?? '';
        ?>
        <div class="modal-overlay" id="<?php echo htmlspecialchars($id); ?>Overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title"><?php echo htmlspecialchars($title); ?></h2>
                    <button type="button" class="modal-close" aria-label="Close modal">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="modal-message"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn modal-btn-cancel" data-action="cancel">
                        <?php echo htmlspecialchars($cancelText); ?>
                    </button>
                    <button type="button" class="btn modal-btn-confirm <?php echo htmlspecialchars($confirmClass); ?>" data-action="confirm">
                        <?php echo htmlspecialchars($confirmText); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!isset($GLOBALS['modal_css_included'])) {
    $GLOBALS['modal_css_included'] = true;
    echo '<link rel="stylesheet" href="../../resources/components/modal-confirmation.css">';
}
if (!isset($GLOBALS['modal_js_included'])) {
    $GLOBALS['modal_js_included'] = true;
    echo '<script src="../../public/components/modalConfirmation.js"></script>';
}
?>