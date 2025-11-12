(function () {
    // --- Profile Dropdown Handler ---
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) {
        const toggleBtn = dropdown.querySelector('#profile');
        const menu = dropdown.querySelector('.nav-dropdown-menu');

        function open() {
            dropdown.classList.add('open');
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
        function close() {
            dropdown.classList.remove('open');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
        function toggle(e) {
            e.stopPropagation();
            if (dropdown.classList.contains('open')) close(); else open();
        }

        toggleBtn.addEventListener('click', toggle);
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    }

    // --- Cart Badge Handler ---
    // Load cart count on page load (for buyers only)
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        loadCartCount();
    }

    function loadCartCount() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/cart/count.php', true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);

                    if (cartBadge && data.count !== undefined) {
                        cartBadge.textContent = data.count;

                        // Show/hide badge based on count using class
                        if (data.count > 0) {
                            cartBadge.classList.add('visible');
                        } else {
                            cartBadge.classList.remove('visible');
                        }
                    }
                } catch (e) {
                    console.error('Failed to load cart count:', e);
                    // Silent fail - badge will remain hidden
                }
            }
        };

        xhr.onerror = function() {
            console.error('Failed to load cart count: Network error');
            // Silent fail - badge will remain hidden
        };

        xhr.send();
    }

    // --- Top-up Modal Handler ---
    const balanceBtn = document.getElementById('balanceBtn');
    const topupModal = document.getElementById('topupModal');
    const closeTopupModal = document.getElementById('closeTopupModal');
    const cancelTopup = document.getElementById('cancelTopup');
    const topupForm = document.getElementById('topupForm');
    const topupAmountInput = document.getElementById('topupAmount');
    const submitTopupBtn = document.getElementById('submitTopup');
    const navbarBalance = document.getElementById('navbarBalance');

    if (balanceBtn && topupModal) {
        // Open modal
        balanceBtn.addEventListener('click', function() {
            topupModal.classList.add('show');
            topupAmountInput.value = '';
            topupAmountInput.focus();
        });

        // Close modal functions
        function closeModal() {
            topupModal.classList.remove('show');
            topupForm.reset();
            // Remove active class from quick buttons
            document.querySelectorAll('.quick-amount-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }

        closeTopupModal.addEventListener('click', closeModal);
        cancelTopup.addEventListener('click', closeModal);

        // Close modal when clicking overlay
        topupModal.addEventListener('click', function(e) {
            if (e.target === topupModal) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && topupModal.classList.contains('show')) {
                closeModal();
            }
        });

        // Quick amount buttons
        const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
        quickAmountBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                topupAmountInput.value = amount;

                // Toggle active class
                quickAmountBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Form submission
        topupForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const amount = parseInt(topupAmountInput.value);

            // Validation
            if (!amount || amount < 10000) {
                showToast('Minimal top-up Rp 10.000', 'error');
                return;
            }

            if (amount > 100000000) {
                showToast('Maksimal top-up Rp 100.000.000', 'error');
                return;
            }

            // Disable button and show loading
            submitTopupBtn.disabled = true;
            submitTopupBtn.querySelector('.btn-text').classList.add('hidden');
            submitTopupBtn.querySelector('.btn-loading').classList.add('visible');

            // Send AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/buyer/topup.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                // Re-enable button
                submitTopupBtn.disabled = false;
                submitTopupBtn.querySelector('.btn-text').classList.remove('hidden');
                submitTopupBtn.querySelector('.btn-loading').classList.remove('visible');

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            // Update balance display in navbar
                            if (navbarBalance && response.data && response.data.new_balance) {
                                const formattedBalance = new Intl.NumberFormat('id-ID').format(response.data.new_balance);
                                navbarBalance.textContent = 'Rp ' + formattedBalance;
                            }

                            // Show success message
                            showToast(response.message || 'Top-up berhasil!', 'success');

                            // Close modal
                            setTimeout(() => {
                                closeModal();
                            }, 1000);
                        } else {
                            showToast(response.message || 'Top-up gagal. Silakan coba lagi.', 'error');
                        }
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        showToast(response.message || 'Top-up gagal. Silakan coba lagi.', 'error');
                    } catch (e) {
                        showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                    }
                }
            };

            xhr.onerror = function() {
                // Re-enable button
                submitTopupBtn.disabled = false;
                submitTopupBtn.querySelector('.btn-text').classList.remove('hidden');
                submitTopupBtn.querySelector('.btn-loading').classList.remove('visible');

                showToast('Koneksi gagal. Periksa internet Anda.', 'error');
            };

            // Send request
            xhr.send(JSON.stringify({ amount: amount }));
        });
    }

    // Helper function to show toast (using global toast if available)
    function showToast(message, type = 'info') {
        // Check if global toast function exists
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else {
            // Fallback to alert
            alert(message);
        }
    }
})();