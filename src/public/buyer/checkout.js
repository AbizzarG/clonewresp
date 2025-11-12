/**
 * Checkout Page JavaScript
 * Pure JavaScript dengan XMLHttpRequest (no Fetch API)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Helper Function: Show Toast ---
    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else {
            alert(message);
        }
    }

    // --- Seleksi Elemen ---
    const checkoutForm = document.getElementById('checkout-form');
    const shippingAddress = document.getElementById('shipping-address');
    const submitBtn = document.querySelector('.checkout-btn-submit');
    const formSection = document.querySelector('.checkout-form-section');

    if (!checkoutForm || !shippingAddress || !submitBtn) {
        return;
    }

    // --- Handle Form Submission ---
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Validate shipping address
        const address = shippingAddress.value.trim();

        if (address.length < 20) {
            showToast('Alamat pengiriman terlalu pendek. Minimal 20 karakter.', 'error');
            shippingAddress.focus();
            return;
        }

        if (address.length > 500) {
            showToast('Alamat pengiriman terlalu panjang. Maksimal 500 karakter.', 'error');
            shippingAddress.focus();
            return;
        }

        // Check if button is disabled
        if (submitBtn.disabled) {
            showToast('Saldo tidak mencukupi untuk melakukan pembayaran.', 'error');
            return;
        }

        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        formSection.classList.add('loading');

        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/order/create.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            // Remove loading state
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            formSection.classList.remove('loading');

            console.log('Response Status:', xhr.status);
            console.log('Response Text:', xhr.responseText);

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Parsed Data:', data);

                    if (data.success) {
                        console.log('Order success! Redirecting...');
                        showToast(data.message || 'Pesanan berhasil dibuat!', 'success');
                        setTimeout(() => {
                            console.log('Redirecting to order_success.php');
                            window.location.href = 'order_success.php';
                        }, 1000);
                    } else {
                        console.log('Order failed:', data.message);
                        showToast(data.message || 'Gagal membuat pesanan', 'error');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Error response:', data);
                    showToast(data.message || 'Gagal membuat pesanan', 'error');
                } catch (e) {
                    console.error('Error parsing error response:', e);
                    showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            }
        };

        xhr.onerror = function() {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            formSection.classList.remove('loading');
            showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
        };

        // Send request with shipping address
        xhr.send(JSON.stringify({
            shipping_address: address
        }));
    });

    // --- Real-time Address Validation ---
    shippingAddress.addEventListener('input', function() {
        const length = this.value.trim().length;

        // Remove all validation classes first
        this.classList.remove('warning', 'valid');

        // Add appropriate class based on length
        if (length > 0 && length < 20) {
            this.classList.add('warning');
        } else if (length >= 20) {
            this.classList.add('valid');
        }
    });

});
