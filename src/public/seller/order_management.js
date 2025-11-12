document.addEventListener('DOMContentLoaded', function() {
    // --- Seleksi Elemen ---
    const rejectModal = document.getElementById('rejectModal');
    const deliveryModal = document.getElementById('deliveryModal');
    const detailModal = document.getElementById('detailModal');
    const detailModalClose = document.getElementById('detailModalClose');
    const closeButtons = document.querySelectorAll('.modal .close');
    const orderTable = document.querySelector('.order-table');

    // --- Fungsi untuk menangani request AJAX dengan XMLHttpRequest ---
    function handleOrderUpdate(action, data) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/seller/update_order.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        alert(result.message || 'Order updated successfully.');
                        // Reload halaman untuk melihat perubahan
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'An unknown error occurred.'));
                    }
                } catch (e) {
                    alert('Error parsing server response.');
                }
            } else {
                alert('Server returned an error: ' + xhr.status);
            }
        };

        xhr.onerror = function() {
            alert('A network error occurred. Please try again.');
        };

        xhr.send(JSON.stringify({ action, ...data }));
    }

    // --- Event Listeners ---

    // 1. Menutup Modal
    closeButtons.forEach(btn => {
        btn.onclick = function() {
            rejectModal.classList.remove('show');
            deliveryModal.classList.remove('show');
            detailModal.classList.remove('show');
        };
    });

    if (detailModalClose) {
        detailModalClose.onclick = function() {
            detailModal.classList.remove('show');
        };
    }

    window.onclick = function(event) {
        if (event.target == rejectModal || event.target == deliveryModal || event.target == detailModal) {
            rejectModal.classList.remove('show');
            deliveryModal.classList.remove('show');
            detailModal.classList.remove('show');
        }
    };

    // 2. Aksi pada Tombol di Tabel Order
    if (orderTable) {
        orderTable.addEventListener('click', function(e) {
            const target = e.target;
            const orderId = target.dataset.orderId;
            if (!orderId) return;

            if (target.classList.contains('btn-approve')) {
                if (confirm('Are you sure you want to approve this order?')) {
                    handleOrderUpdate('approve', { order_id: orderId });
                }
            } else if (target.classList.contains('btn-reject')) {
                document.getElementById('rejectOrderId').value = orderId;
                rejectModal.classList.add('show');
            } else if (target.classList.contains('btn-delivery')) {
                document.getElementById('deliveryOrderId').value = orderId;
                deliveryModal.classList.add('show');
            } else if (target.classList.contains('btn-detail')) {
                showOrderDetail(orderId);
            }
        });
    }

    // 3. Submit Form di dalam Modal
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const orderId = document.getElementById('rejectOrderId').value;
            const reason = document.getElementById('reject_reason').value;
            if (!reason.trim()) {
                alert('Reason for rejection is required.');
                return;
            }
            handleOrderUpdate('reject', { order_id: orderId, reason: reason });
            rejectModal.classList.remove('show');
        });
    }

    const deliveryForm = document.getElementById('deliveryForm');
    if (deliveryForm) {
        deliveryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const orderId = document.getElementById('deliveryOrderId').value;
            const time = document.getElementById('delivery_time').value;
            handleOrderUpdate('set_delivery', { order_id: orderId, delivery_time: time });
            deliveryModal.classList.remove('show');
        });
    }

    // --- Function: Show Order Detail ---
    function showOrderDetail(orderId) {
        const detailModal = document.getElementById('detailModal');
        const detailBody = document.getElementById('detailModalBody');

        if (!detailModal || !detailBody) return;

        // Show modal
        detailModal.classList.add('show');
        detailBody.innerHTML = '<p style="text-align:center; color:#666;">Loading order details...</p>';

        // Fetch order details
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/order/detail.php?order_id=' + orderId, true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.data) {
                        renderOrderDetail(response.data);
                    } else {
                        detailBody.innerHTML = '<p style="color:red;">Error: ' + (response.message || 'Failed to load order details') + '</p>';
                    }
                } catch (e) {
                    detailBody.innerHTML = '<p style="color:red;">Error parsing response.</p>';
                }
            } else {
                detailBody.innerHTML = '<p style="color:red;">Failed to load order details. Server returned error ' + xhr.status + '</p>';
            }
        };

        xhr.onerror = function() {
            detailBody.innerHTML = '<p style="color:red;">Network error. Please check your connection.</p>';
        };

        xhr.send();
    }

    // --- Function: Render Order Detail ---
    function renderOrderDetail(order) {
        const detailBody = document.getElementById('detailModalBody');
        if (!detailBody) return;

        let html = '<div style="padding: 10px;">';

        // Order Info
        html += '<h3 style="margin-top:0; border-bottom:2px solid #f0f2f4; padding-bottom:10px;">Order Information</h3>';
        html += '<table style="width:100%; margin-bottom:20px;">';
        html += '<tr><td style="padding:8px 0; font-weight:500; width:40%;">Order ID:</td><td>#' + order.order_id + '</td></tr>';
        html += '<tr><td style="padding:8px 0; font-weight:500;">Order Date:</td><td>' + formatDate(order.created_at) + '</td></tr>';
        html += '<tr><td style="padding:8px 0; font-weight:500;">Buyer:</td><td>' + escapeHtml(order.buyer_name || 'N/A') + '</td></tr>';
        html += '<tr><td style="padding:8px 0; font-weight:500;">Status:</td><td><span style="padding:4px 12px; background:#f0f2f4; border-radius:4px; font-weight:500;">' + escapeHtml(order.status) + '</span></td></tr>';
        html += '<tr><td style="padding:8px 0; font-weight:500;">Total Price:</td><td style="font-size:18px; font-weight:700; color:#40B54B;">Rp ' + formatNumber(order.total_price) + '</td></tr>';
        html += '</table>';

        // Products
        html += '<h3 style="border-bottom:2px solid #f0f2f4; padding-bottom:10px;">Products</h3>';
        if (order.items && order.items.length > 0) {
            order.items.forEach(item => {
                // Fix image path - ensure it starts with /
                let imagePath = item.main_image_path || '';
                if (imagePath && !imagePath.startsWith('/') && !imagePath.startsWith('http')) {
                    imagePath = '/' + imagePath;
                }

                html += '<div style="display:flex; gap:15px; padding:15px 0; border-bottom:1px solid #f0f2f4;">';
                html += '<img src="' + escapeHtml(imagePath) + '" alt="Product" style="width:80px; height:80px; object-fit:cover; border-radius:6px;">';
                html += '<div style="flex:1;">';
                html += '<div style="font-weight:600; margin-bottom:5px;">' + escapeHtml(item.product_name) + '</div>';
                html += '<div style="color:#666; font-size:14px;">Qty: ' + item.quantity + ' × Rp ' + formatNumber(item.price_at_order) + '</div>';
                html += '<div style="font-weight:600; margin-top:5px;">Subtotal: Rp ' + formatNumber(item.subtotal) + '</div>';
                html += '</div></div>';
            });
        } else {
            html += '<p style="color:#666;">No items found.</p>';
        }

        // Shipping Address
        html += '<h3 style="border-bottom:2px solid #f0f2f4; padding-bottom:10px; margin-top:20px;">Shipping Address</h3>';
        html += '<p style="line-height:1.6; color:#333;">' + escapeHtml(order.shipping_address || 'N/A') + '</p>';

        // Additional Info
        if (order.confirmed_at) {
            html += '<p style="margin-top:10px; color:#666;"><strong>Confirmed at:</strong> ' + formatDate(order.confirmed_at) + '</p>';
        }
        if (order.delivery_time) {
            html += '<p style="margin-top:5px; color:#666;"><strong>Delivery time:</strong> ' + formatDate(order.delivery_time) + '</p>';
        }
        if (order.received_at) {
            html += '<p style="margin-top:5px; color:#666;"><strong>Received at:</strong> ' + formatDate(order.received_at) + '</p>';
        }
        if (order.reject_reason) {
            html += '<p style="margin-top:10px; padding:10px; background:#fee; border-left:4px solid #e74c3c; color:#c33;"><strong>Reject Reason:</strong> ' + escapeHtml(order.reject_reason) + '</p>';
        }

        html += '</div>';
        detailBody.innerHTML = html;
    }

    // --- Helper Functions ---
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = String(date.getDate()).padStart(2, '0');
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return day + ' ' + month + ' ' + year + ', ' + hours + ':' + minutes;
    }

    function formatNumber(num) {
        return Number(num).toLocaleString('id-ID');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // --- Export Orders Function ---
    window.exportOrders = function(format) {
        // Get current filters
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status') || '';
        const search = urlParams.get('search') || '';

        // Build export URL
        let exportUrl = '/api/seller/export_orders.php?format=' + format;
        if (status) exportUrl += '&status=' + encodeURIComponent(status);
        if (search) exportUrl += '&search=' + encodeURIComponent(search);

        // Trigger download
        window.location.href = exportUrl;
    };
});
