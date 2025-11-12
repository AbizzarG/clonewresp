(function () {
    'use strict';

    // Toast Handler
    function initToast() {
        // Ensure toast container exists
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        /**
         * Show toast notification
         * @param {string} type - 'success', 'error', 'info', 'warning'
         * @param {string} message - Toast message
         * @param {object} options - { title, duration }
         */
        window.showToast = function(type, message, options = {}) {
            if (!container) {
                container = document.getElementById('toastContainer');
                if (!container) return;
            }

            const title = options.title || (type === 'success' ? 'Berhasil' : type === 'error' ? 'Error' : 'Info');
            const duration = options.duration || 4000;
            const toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            // Icon SVG mapping
            const iconSvgMap = {
                success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path></svg>',
                info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>',
                warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>'
            };

            const iconSvg = iconSvgMap[type] || iconSvgMap.info;

            // Create toast element
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    ${iconSvg}
                </div>
                <div class="toast-content">
                    <p class="toast-title">${escapeHtml(title)}</p>
                    <p class="toast-message">${escapeHtml(message)}</p>
                </div>
                <button type="button" class="toast-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            `;

            // Append to container
            container.appendChild(toast);

            // Show toast with animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Close button handler
            const closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    hideToast(toastId);
                });
            }

            // Auto hide after duration
            if (duration > 0) {
                setTimeout(() => {
                    hideToast(toastId);
                }, duration);
            }

            return toastId;
        };

        /**
         * Hide toast
         * @param {string} toastId - Toast ID
         */
        window.hideToast = function(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.remove('show');
                toast.classList.add('hide');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        };

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToast);
    } else {
        initToast();
    }
})();