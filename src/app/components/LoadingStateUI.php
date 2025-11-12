<?php
/**
 * Reusable Loading State UI Component
 * 
 * Usage:
 *   include '../components/LoadingStateUI.php';
 *   LoadingStateUI::render([
 *     'id' => 'loadingState',
 *     'message' => 'Memproses...'
 *   ]);
 */

class LoadingStateUI {
    public static function render($options = []) {
        $id = $options['id'] ?? 'loadingState';
        $message = $options['message'] ?? 'Memproses...';
        $fullscreen = $options['fullscreen'] ?? false;
        $overlayClass = $fullscreen ? 'loading-overlay-fullscreen' : 'loading-overlay';
        ?>
        <div class="<?php echo htmlspecialchars($overlayClass); ?>" id="<?php echo htmlspecialchars($id); ?>">
            <div class="loading-container">
                <div class="loading-spinner">
                    <div class="spinner-circle"></div>
                </div>
                <p class="loading-message"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
        <?php
    }
}

if (!isset($GLOBALS['loading_css_included'])) {
    $GLOBALS['loading_css_included'] = true;
    echo '<link rel="stylesheet" href="../../resources/components/loading-state.css">';
}
if (!isset($GLOBALS['modal_js_included'])) {
    $GLOBALS['modal_js_included'] = true;
    echo '<script src="../../public/components/modalConfirmation.js"></script>';
}
?>