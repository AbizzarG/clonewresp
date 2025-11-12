<?php
/**
 * Reusable Toast Component
 * 
 * Usage:
 *   require_once '../components/Toast.php';
 *   Toast::render();
 *   
 */

class Toast {
    public static function render($options = []) {
        $containerId = $options['containerId'] ?? 'toastContainer';
        ?>
        <div class="toast-container" id="<?php echo htmlspecialchars($containerId); ?>"></div>
        <?php
        if (!isset($GLOBALS['toast_css_included'])) {
            $GLOBALS['toast_css_included'] = true;
            echo '<link rel="stylesheet" href="../../resources/components/toast.css">';
        }
        if (!isset($GLOBALS['toast_js_included'])) {
            $GLOBALS['toast_js_included'] = true;
            echo '<script src="../../public/components/toast.js"></script>';
        }
    }
}
?>