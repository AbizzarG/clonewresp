/**
 * Shopping Cart Page JavaScript
 * Pure JavaScript dengan XMLHttpRequest (no Fetch API)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Seleksi Elemen ---
    const cartItemsSection = document.querySelector('.cart-items-section');

    if (!cartItemsSection) {
        // Halaman cart kosong atau error, skip
        return;
    }

    // --- Event Delegation untuk Quantity Buttons ---
    cartItemsSection.addEventListener('click', function(e) {
        // Handle Decrease Button
        if (e.target.classList.contains('cart-qty-decrease') || e.target.closest('.cart-qty-decrease')) {
            const btn = e.target.closest('.cart-qty-decrease');
            if (!btn) return;
            
            const cartItemId = btn.getAttribute('data-cart-item-id');
            const cartItem = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
            if (!cartItem) return;
            
            const qtyInput = cartItem.querySelector('.cart-qty-input');
            if (!qtyInput) return;
            
            const currentQty = parseInt(qtyInput.value) || 1;

            if (currentQty > 1) {
                updateCartQuantity(cartItemId, currentQty - 1);
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Quantity minimal adalah 1');
                }
            }
        }

        // Handle Increase Button
        if (e.target.classList.contains('cart-qty-increase') || e.target.closest('.cart-qty-increase')) {
            const btn = e.target.closest('.cart-qty-increase');
            if (!btn) return;
            
            const cartItemId = btn.getAttribute('data-cart-item-id');
            const cartItem = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
            if (!cartItem) return;
            
            const qtyInput = cartItem.querySelector('.cart-qty-input');
            if (!qtyInput) return;
            
            const currentQty = parseInt(qtyInput.value) || 1;
            const maxStock = parseInt(qtyInput.getAttribute('data-max-stock')) || 999;

            if (currentQty < maxStock) {
                updateCartQuantity(cartItemId, currentQty + 1);
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', `Stok maksimal ${maxStock} item`);
                }
            }
        }

        // Handle Remove Button
        if (e.target.closest('.cart-item-remove')) {
            const btn = e.target.closest('.cart-item-remove');
            const cartItemId = btn.getAttribute('data-cart-item-id');
            const productName = btn.getAttribute('data-product-name');
            
            showRemoveConfirmation(cartItemId, productName);
        }
    });

    // Debounce timer object for each cart item
    const debounceTimers = {};

    // --- Handle Manual Input Change with Debouncing ---
    cartItemsSection.addEventListener('input', function(e) {
        if (e.target.classList.contains('cart-qty-input')) {
            const input = e.target;
            const cartItemId = input.getAttribute('data-cart-item-id');
            const maxStock = parseInt(input.getAttribute('data-max-stock'));
            let newQty = parseInt(input.value);

            // Validation
            if (isNaN(newQty) || newQty < 1) {
                newQty = 1;
                input.value = 1;
            }

            if (newQty > maxStock) {
                newQty = maxStock;
                input.value = maxStock;
                if (typeof window.showToast === 'function') {
                    window.showToast('error', `Stok maksimal ${maxStock} item`);
                }
            }

            // Clear previous timer for this cart item
            if (debounceTimers[cartItemId]) {
                clearTimeout(debounceTimers[cartItemId]);
            }

            // Set new timer with 500ms debounce
            debounceTimers[cartItemId] = setTimeout(() => {
                updateCartQuantity(cartItemId, newQty);
                delete debounceTimers[cartItemId];
            }, 500);
        }
    });

    // --- Function: Update Quantity via AJAX ---
    function updateCartQuantity(cartItemId, newQty) {
        const cartItem = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
        if (!cartItem) return;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/cart/update.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', data.message || 'Quantity berhasil diupdate');
                        }
                        updateCartItemUI(cartItemId, data.newQuantity, data.newSubtotal);
                        updateCartSummary();
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', data.message || 'Gagal mengupdate quantity');
                        }
                        location.reload();
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan refresh halaman.');
                    }
                }
            } else {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', data.message || 'Gagal mengupdate quantity');
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan refresh halaman.');
                    }
                }
            }
        };

        xhr.onerror = function() {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
            }
        };

        xhr.send(JSON.stringify({
            cart_item_id: parseInt(cartItemId),
            quantity: newQty
        }));
    }

    // --- Function: Remove Item via AJAX ---
    function removeCartItem(cartItemId) {
        const cartItem = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);

        if (!cartItem) return;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/cart/remove.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);

                    if (data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', data.message || 'Item berhasil dihapus');
                        }

                        // Remove item from DOM
                        cartItem.classList.add('fade-out');

                        setTimeout(() => {
                            cartItem.remove();

                            // Update summary
                            updateCartSummary();

                            // Update navbar badge
                            updateNavbarBadge();

                            // Check if cart is empty
                            const remainingItems = document.querySelectorAll('.cart-item');
                            if (remainingItems.length === 0) {
                                // Reload to show empty state
                                location.reload();
                            }
                        }, 300);

                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast('error', data.message || 'Gagal menghapus item');
                        }
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan refresh halaman.');
                    }
                }
            } else {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', data.message || 'Gagal menghapus item');
                    }
                } catch (e) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan. Silakan refresh halaman.');
                    }
                }
            }
        };

        xhr.onerror = function() {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
            }
        };

        // Send request
        xhr.send(JSON.stringify({
            cart_item_id: parseInt(cartItemId)
        }));
    }

    // --- Helper: Update Cart Item UI ---
    function updateCartItemUI(cartItemId, newQty, newSubtotal) {
        const cartItem = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
        if (!cartItem) return;

        // Update quantity input
        const qtyInput = cartItem.querySelector('.cart-qty-input');
        if (qtyInput) {
            qtyInput.value = newQty;
        }

        // Update data attributes for buttons
        const decreaseBtn = cartItem.querySelector('.cart-qty-decrease');
        const increaseBtn = cartItem.querySelector('.cart-qty-increase');
        if (decreaseBtn) decreaseBtn.setAttribute('data-current-qty', newQty);
        if (increaseBtn) increaseBtn.setAttribute('data-current-qty', newQty);

        // Update subtotal
        const subtotalElement = cartItem.querySelector('.cart-subtotal-price');
        if (subtotalElement) {
            subtotalElement.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(newSubtotal);
        }
    }

    // --- Helper: Update Cart Summary ---
    function updateCartSummary() {
        let totalItems = 0;
        let totalPrice = 0;

        // Calculate from all cart items
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach(item => {
            const qtyInput = item.querySelector('.cart-qty-input');
            const subtotalText = item.querySelector('.cart-subtotal-price').textContent;

            if (qtyInput) {
                totalItems += 1; // Count unique products (not total quantity)
            }

            // Extract price from "Rp 100.000" format
            const price = parseInt(subtotalText.replace(/[^\d]/g, ''));
            totalPrice += price;
        });

        // Update DOM
        const totalItemsElement = document.getElementById('cart-total-items');
        const totalPriceElement = document.getElementById('cart-total-price');

        if (totalItemsElement) {
            totalItemsElement.textContent = `${totalItems} produk`;
        }

        if (totalPriceElement) {
            totalPriceElement.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalPrice);
        }
    }

    // --- Helper: Update Navbar Badge ---
    function updateNavbarBadge() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/cart/count.php', true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    const cartBadge = document.querySelector('.cart-badge');

                    if (cartBadge && data.count !== undefined) {
                        cartBadge.textContent = data.count;

                        if (data.count > 0) {
                            cartBadge.classList.remove('badge-hidden');
                            cartBadge.classList.add('badge-visible');
                        } else {
                            cartBadge.classList.remove('badge-visible');
                            cartBadge.classList.add('badge-hidden');
                        }
                    }
                } catch (e) {
                    console.error('Failed to update badge:', e);
                }
            }
        };

        xhr.onerror = function() {
            console.error('Network error while updating badge');
        };

        xhr.send();
    }

    // --- Helper: Show Remove Confirmation Modal ---
    function showRemoveConfirmation(cartItemId, productName) {
        const modalOverlay = document.getElementById('confirmRemoveCartOverlay');
        if (!modalOverlay) {
            if (confirm(`Hapus "${productName}" dari keranjang?`)) {
                removeCartItem(cartItemId);
            }
            return;
        }

        const modalMessage = modalOverlay.querySelector('.modal-message');
        if (modalMessage) {
            modalMessage.textContent = `Hapus "${productName}" dari keranjang?`;
        }

        if (typeof window.showModal === 'function') {
            window.showModal('confirmRemoveCart');
        }

        const handleConfirm = (e) => {
            if (e.detail && e.detail.modalId === 'confirmRemoveCart') {
                document.removeEventListener('modalConfirm', handleConfirm);
                if (typeof window.hideModal === 'function') {
                    window.hideModal('confirmRemoveCart');
                }
                removeCartItem(cartItemId);
            }
        };

        document.addEventListener('modalConfirm', handleConfirm);
    }

});
