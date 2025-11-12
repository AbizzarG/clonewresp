/**
 * Order History Page JavaScript
*/

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons first
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Filter dropdown
    const filterDropdown = document.querySelector('.oh-filter-dropdown');
    
    if (filterDropdown) {
        const filterDropdownBtn = document.getElementById('filterDropdownBtn');
        const filterDropdownMenu = filterDropdown.querySelector('.oh-filter-dropdown-menu');

        function open() {
            filterDropdown.classList.add('open');
            if (filterDropdownBtn) {
                filterDropdownBtn.setAttribute('aria-expanded', 'true');
            }
        }
        
        function close() {
            filterDropdown.classList.remove('open');
            if (filterDropdownBtn) {
                filterDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        }
        
        function toggle(e) {
            e.stopPropagation();
            if (filterDropdown.classList.contains('open')) {
                close();
            } else {
                open();
            }
        }

        if (filterDropdownBtn) {
            filterDropdownBtn.addEventListener('click', toggle);
        }
        
        document.addEventListener('click', (e) => {
            if (!filterDropdown.contains(e.target)) {
                close();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && filterDropdown.classList.contains('open')) {
                close();
            }
        });
    }

    // Select dropdown icon toggle
    const selectWrappers = document.querySelectorAll('.oh-select-wrapper');
    selectWrappers.forEach(wrapper => {
        const select = wrapper.querySelector('select');
        if (select) {
            select.addEventListener('focus', () => {
                wrapper.classList.add('active');
            });
            select.addEventListener('blur', () => {
                wrapper.classList.remove('active');
            });
            select.addEventListener('change', () => {
                wrapper.classList.remove('active');
            });
        }
    });

    // Filter form submit
    const filterForm = document.querySelector('.oh-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
        });
    }

    // Detail button
    const detailButtons = document.querySelectorAll('.oh-btn-detail');
    detailButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (orderId) {
                showOrderDetail(orderId);
            }
        });
    });

    // Confirm Received button
    const confirmReceivedButtons = document.querySelectorAll('.oh-btn-confirm-received');
    confirmReceivedButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (orderId) {
                confirmOrderReceived(orderId, btn);
            }
        });
    });
});

/**
 * Confirm order received
 */
function confirmOrderReceived(orderId, button) {
    // Confirm with user
    if (!confirm('Apakah Anda yakin telah menerima pesanan ini?')) {
        return;
    }

    // Disable button and show loading
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<i data-lucide="loader-2" class="oh-spinner"></i> Memproses...';

    // Re-initialize Lucide icons for spinner
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Make AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../api/buyer/confirm_received.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);

                if (response.success) {
                    // Show success message
                    if (typeof window.showToast === 'function') {
                        window.showToast('success', response.message);
                    } else {
                        alert(response.message);
                    }

                    // Reload page after short delay to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', response.message);
                    } else {
                        alert(response.message);
                    }

                    // Re-enable button
                    button.disabled = false;
                    button.innerHTML = originalText;
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                button.disabled = false;
                button.innerHTML = originalText;
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        } else {
            // HTTP error
            try {
                const response = JSON.parse(xhr.responseText);
                alert(response.message || 'Terjadi kesalahan. Silakan coba lagi.');
            } catch (e) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }

            button.disabled = false;
            button.innerHTML = originalText;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    };

    xhr.onerror = function() {
        alert('Koneksi gagal. Periksa internet Anda.');
        button.disabled = false;
        button.innerHTML = originalText;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    // Send request
    xhr.send(JSON.stringify({ order_id: orderId }));
}

/**
 * Show order detail modal
 */
function showOrderDetail(orderId) {
    // Show modal
    const modalOverlay = document.getElementById('modalOrderDetailOverlay');
    if (modalOverlay) {
        modalOverlay.classList.add('show');
        document.body.classList.add('modal-open');

        // Load order details
        loadOrderDetails(orderId);
    }
}

/**
 * Load order details via AJAX
 */
function loadOrderDetails(orderId) {
    const contentDiv = document.getElementById('modalOrderDetailContent');
    if (!contentDiv) return;

    // Show loading
    contentDiv.innerHTML = '<div class="oh-modal-loading">Memuat detail pesanan...</div>';

    // Make request to API
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../../api/order/detail.php?order_id=${orderId}`, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        renderOrderDetail(response.data);
                    } else {
                        contentDiv.innerHTML = `<div class="oh-modal-loading">${response.message || 'Gagal memuat detail pesanan'}</div>`;
                    }
                } catch (e) {
                    contentDiv.innerHTML = '<div class="oh-modal-loading">Terjadi kesalahan saat memproses data.</div>';
                }
            } else {
                contentDiv.innerHTML = '<div class="oh-modal-loading">Terjadi kesalahan saat memuat detail pesanan.</div>';
            }
        }
    };
    xhr.send();
}

/**
 * Render order detail content
 */
function renderOrderDetail(order) {
    const contentDiv = document.getElementById('modalOrderDetailContent');
    if (!contentDiv) return;

    const statusInfo = getStatusInfo(order.status);
    const orderDate = formatDateTime(order.created_at);
    const confirmedDate = order.confirmed_at ? formatDateTime(order.confirmed_at) : null;
    const deliveryTime = order.delivery_time ? formatDateTime(order.delivery_time) : null;
    const receivedDate = order.received_at ? formatDateTime(order.received_at) : null;

    let html = `
        <div class="oh-detail-section">
            <h3 class="oh-detail-section-title">Informasi Pesanan</h3>
            <div class="oh-detail-info">
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Order ID</span>
                    <span class="oh-detail-value">#${String(order.order_id).padStart(6, '0')}</span>
                </div>
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Tanggal Pesanan</span>
                    <span class="oh-detail-value">${orderDate}</span>
                </div>
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Toko</span>
                    <span class="oh-detail-value">${escapeHtml(order.store_name)}</span>
                </div>
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Status</span>
                    <span class="oh-detail-value">
                        <span class="oh-detail-status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </span>
                </div>
                ${confirmedDate ? `
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Tanggal Konfirmasi</span>
                    <span class="oh-detail-value">${confirmedDate}</span>
                </div>
                ` : ''}
                ${deliveryTime ? `
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Estimasi Pengiriman</span>
                    <span class="oh-detail-value">${deliveryTime}</span>
                </div>
                ` : ''}
                ${receivedDate ? `
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Tanggal Diterima</span>
                    <span class="oh-detail-value">${receivedDate}</span>
                </div>
                ` : ''}
            </div>
        </div>

        <div class="oh-detail-section">
            <h3 class="oh-detail-section-title">Produk</h3>
            <div class="oh-detail-product-list">
    `;

    order.items.forEach(item => {
        html += `
            <div class="oh-detail-product-item">
                <img src="${escapeHtml(item.main_image_path)}" 
                     alt="${escapeHtml(item.product_name)}"
                     class="oh-detail-product-img">
                <div class="oh-detail-product-info">
                    <div class="oh-detail-product-name">${escapeHtml(item.product_name)}</div>
                    <div class="oh-detail-product-meta">
                        <span>Qty: ${item.quantity}</span>
                        <span>Harga: Rp ${formatNumber(item.price_at_order)}</span>
                    </div>
                </div>
                <div class="oh-detail-product-price">
                    Rp ${formatNumber(item.subtotal)}
                </div>
            </div>
        `;
    });

    html += `
            </div>
        </div>

        <div class="oh-detail-section">
            <h3 class="oh-detail-section-title">Alamat Pengiriman</h3>
            <div class="oh-detail-address">${escapeHtml(order.shipping_address)}</div>
        </div>

        <div class="oh-detail-section">
            <h3 class="oh-detail-section-title">Total Pembayaran</h3>
            <div class="oh-detail-info">
                <div class="oh-detail-row">
                    <span class="oh-detail-label">Total</span>
                    <span class="oh-detail-value">
                        Rp ${formatNumber(order.total_price)}
                    </span>
                </div>
            </div>
        </div>
    `;

    // Rejection reason
    if (order.status === 'rejected' && order.reject_reason) {
        html += `
            <div class="oh-detail-section">
                <h3 class="oh-detail-section-title">Alasan Penolakan</h3>
                <div class="oh-detail-alert oh-detail-alert-danger">
                    ${escapeHtml(order.reject_reason)}
                </div>
                <div class="oh-detail-alert oh-detail-alert-success">
                    <i data-lucide="rotate-ccw"></i> Dana sebesar Rp ${formatNumber(order.total_price)} telah dikembalikan ke saldo Anda.
                </div>
            </div>
        `;
    }

    // Delivery info
    if (order.status === 'on_delivery' && deliveryTime) {
        html += `
            <div class="oh-detail-section">
                <div class="oh-detail-alert oh-detail-alert-info">
                    <strong>Pesanan sedang dalam perjalanan</strong><br>
                    Estimasi tiba: ${deliveryTime}
                </div>
            </div>
        `;
    }

    // Approved info
    if (order.status === 'approved') {
        html += `
            <div class="oh-detail-section">
                <div class="oh-detail-alert oh-detail-alert-success">
                    Pesanan Anda telah disetujui oleh penjual. Pesanan akan segera diproses.
                </div>
            </div>
        `;
    }

    // Waiting approval info
    if (order.status === 'waiting_approval') {
        html += `
            <div class="oh-detail-section">
                <div class="oh-detail-alert oh-detail-alert-warning">
                    Pesanan Anda sedang menunggu persetujuan dari penjual. Harap tunggu konfirmasi.
                </div>
            </div>
        `;
    }

    contentDiv.innerHTML = html;
    
    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Get status info
 */
function getStatusInfo(status) {
    const statuses = {
        'waiting_approval': { text: 'Menunggu Persetujuan', class: 'oh-status-waiting' },
        'approved': { text: 'Disetujui', class: 'oh-status-approved' },
        'rejected': { text: 'Ditolak', class: 'oh-status-rejected' },
        'on_delivery': { text: 'Dalam Pengiriman', class: 'oh-status-delivery' },
        'received': { text: 'Diterima', class: 'oh-status-received' }
    };
    return statuses[status] || { text: status, class: 'oh-status-default' };
}

/**
 * Format date time
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const day = String(date.getDate()).padStart(2, '0');
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hours}:${minutes}`;
}

/**
 * Format number
 */
function formatNumber(num) {
    return Number(num).toLocaleString('id-ID');
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Modal close handlers
document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('modalOrderDetailOverlay');
    if (!modalOverlay) return;

    const closeBtn = modalOverlay.querySelector('.modal-close');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            hideOrderDetail();
        });
    }

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            hideOrderDetail();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('show')) {
            hideOrderDetail();
        }
    });
});

function hideOrderDetail() {
    const modalOverlay = document.getElementById('modalOrderDetailOverlay');
    if (modalOverlay) {
        modalOverlay.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}