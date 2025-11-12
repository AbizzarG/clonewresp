(function () {
    'use strict';

    // Modal Confirmation Handler
    function initModalConfirmation() {
        const modals = document.querySelectorAll('.modal-overlay');
        
        modals.forEach(overlay => {
            const modalId = overlay.id.replace('Overlay', '');
            const closeBtn = overlay.querySelector('.modal-close');
            const cancelBtn = overlay.querySelector('.modal-btn-cancel');
            const confirmBtn = overlay.querySelector('.modal-btn-confirm');

            // Show modal
            window.showModal = function(id) {
                const modal = document.getElementById(id + 'Overlay');
                if (modal) {
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                }
            };

            // Hide modal
            window.hideModal = function(id) {
                const modal = document.getElementById(id + 'Overlay');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.classList.remove('modal-open');
                }
            };

            // Close button
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    window.hideModal(modalId);
                });
            }

            // Cancel button
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    const action = cancelBtn.getAttribute('data-action');
                    if (action === 'cancel') {
                        window.hideModal(modalId);
                    }
                });
            }

            // Confirm button
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    const action = confirmBtn.getAttribute('data-action');
                    if (action === 'confirm') {
                        // Trigger custom event
                        const event = new CustomEvent('modalConfirm', {
                            detail: { modalId: modalId }
                        });
                        document.dispatchEvent(event);
                    }
                });
            }

            // Close on overlay click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    window.hideModal(modalId);
                }
            });

            // Close on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('show')) {
                    window.hideModal(modalId);
                }
            });
        });
    }

    // Loading State Handler
    function initLoadingState() {
        window.showLoading = function(id, message) {
            const loading = document.getElementById(id || 'loadingState');
            if (loading) {
                if (message) {
                    const msgEl = loading.querySelector('.loading-message');
                    if (msgEl) msgEl.textContent = message;
                }
                loading.classList.add('show');
                document.body.classList.add('modal-open');
            }
        };

        window.hideLoading = function(id) {
            const loading = document.getElementById(id || 'loadingState');
            if (loading) {
                loading.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initModalConfirmation();
            initLoadingState();
        });
    } else {
        initModalConfirmation();
        initLoadingState();
    }
})();