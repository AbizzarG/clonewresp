/**
 * Product Detail Page - Add to Cart Functionality
 * Pure JavaScript (no frameworks allowed per spec)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Seleksi Elemen ---
    const addToCartBtn = document.querySelector('.detail-btn-add-cart');
    const quantityInput = document.querySelector('#quantity');

    // Jika tidak ada tombol add to cart (guest atau stok habis), skip
    if (!addToCartBtn || !quantityInput) {
        return;
    }

    // --- Add to Cart Handler ---
    addToCartBtn.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        const maxStock = parseInt(this.getAttribute('data-max-stock'));
        const quantity = parseInt(quantityInput.value);

        // Validasi quantity
        if (isNaN(quantity) || quantity < 1) {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Jumlah produk harus minimal 1');
            }
            return;
        }

        if (quantity > maxStock) {
            if (typeof window.showToast === 'function') {
                window.showToast('error', `Stok tidak mencukupi. Maksimal ${maxStock} item`);
            }
            return;
        }

        // Disable button sementara untuk mencegah double-click
        addToCartBtn.disabled = true;
        const originalHTML = addToCartBtn.innerHTML;
        addToCartBtn.innerHTML = '<i class="btn-icon" data-lucide="loader-2"></i> Menambahkan...';
        if (window.lucide) window.lucide.createIcons();

        // Kirim AJAX POST ke API menggunakan XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/cart/add.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            // Re-enable button
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = originalHTML;
            if (window.lucide) window.lucide.createIcons();

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);

                    if (data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', data.message || 'Berhasil ditambahkan ke keranjang!');
                        }
                        updateCartBadge();
                        quantityInput.value = 1;
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', data.message || 'Gagal menambahkan ke keranjang');
                        }
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan coba lagi.');
                    }
                }
            } else {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', data.message || 'Gagal menambahkan ke keranjang');
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan coba lagi.');
                    }
                }
            }
        };

        xhr.onerror = function() {
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = originalHTML;
            if (window.lucide) window.lucide.createIcons();
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
            }
        };

        // Send request
        const requestData = JSON.stringify({
            product_id: parseInt(productId),
            quantity: quantity
        });
        xhr.send(requestData);
    });

    // --- Helper: Update Cart Badge ---
    function updateCartBadge() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/cart/count.php', true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);

                    // Cari cart badge di navbar
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge && data.count !== undefined) {
                        cartBadge.textContent = data.count;

                        // Show badge jika count > 0
                        if (data.count > 0) {
                            cartBadge.classList.remove('badge-hidden');
                            cartBadge.classList.add('badge-visible');
                        } else {
                            cartBadge.classList.remove('badge-visible');
                            cartBadge.classList.add('badge-hidden');
                        }
                    }
                } catch (e) {
                    console.error('Update cart badge error:', e);
                    // Silent fail
                }
            }
        };

        xhr.onerror = function() {
            console.error('Update cart badge error: Network error');
            // Silent fail
        };

        xhr.send();
    }

    // --- Validasi Input Quantity Real-time ---
    quantityInput.addEventListener('input', function() {
        const maxStock = parseInt(addToCartBtn.getAttribute('data-max-stock'));
        let value = parseInt(this.value);

        // Auto-correct jika melebihi max stock
        if (value > maxStock) {
            this.value = maxStock;
            if (typeof window.showToast === 'function') {
                window.showToast('error', `Maksimal pembelian ${maxStock} item`);
            }
        }

        // Auto-correct jika kurang dari 1
        if (value < 1 || isNaN(value)) {
            this.value = 1;
        }
    });

});
