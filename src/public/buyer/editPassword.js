(function () {
    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.pwd-toggle');
        const eyeIcon = '<i class="icon" data-lucide="eye"></i>';
        const eyeOffIcon = '<i class="icon" data-lucide="eye-off"></i>';

        function updateIcon(btn, icon) {
            btn.innerHTML = icon;
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons(btn);
            }
        }

        toggles.forEach(btn => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            if (!btn.dataset.initialized) {
                btn.addEventListener('click', () => {
                    const hidden = input.type === 'password';
                    input.type = hidden ? 'text' : 'password';
                    updateIcon(btn, hidden ? eyeOffIcon : eyeIcon);
                });
                btn.dataset.initialized = 'true';
            }

            updateIcon(btn, eyeIcon);
        });
    }

    function waitForLucide() {
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            initPasswordToggles();
        } else {
            setTimeout(waitForLucide, 50);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForLucide);
    } else {
        waitForLucide();
    }

    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const hint = document.getElementById('pwdHint');

    function validatePasswordStrength(pwd) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(pwd);
    }

    function validate() {
        if (!password || !confirm || !hint) return;
        
        if (password.value === '' && confirm.value === '') {
            hint.textContent = '';
            hint.className = 'hint';
            return;
        }

        if (password.value !== confirm.value) {
            hint.textContent = 'Konfirmasi password tidak sama';
            hint.className = 'hint err';
            return;
        }

        if (!validatePasswordStrength(password.value)) {
            hint.textContent = 'Password harus minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol';
            hint.className = 'hint err';
            return;
        }

        hint.textContent = 'Konfirmasi password cocok';
        hint.className = 'hint ok';
    }

    if (password) password.addEventListener('input', validate);
    if (confirm) confirm.addEventListener('input', validate);

    const form = document.getElementById('changePasswordForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const currentPwd = document.getElementById('current_password').value;
            
            if (!currentPwd.trim()) {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Password saat ini tidak boleh kosong.');
                }
                return;
            }

            if (!password.value.trim()) {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Password baru tidak boleh kosong.');
                }
                return;
            }

            if (password.value !== confirm.value) {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Konfirmasi password tidak sama.');
                }
                return;
            }

            if (!validatePasswordStrength(password.value)) {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Password harus minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol.');
                }
                return;
            }

            if (typeof window.showModal === 'function') {
                window.showModal('confirmChangePassword');
            } else {
                form.submit();
            }
        });
    }

    document.addEventListener('modalConfirm', (e) => {
        if (e.detail.modalId === 'confirmChangePassword') {
            if (typeof window.hideModal === 'function') {
                window.hideModal('confirmChangePassword');
            }

            if (typeof window.showLoading === 'function') {
                window.showLoading('loadingChangePassword', 'Mengubah password...');
            }

            if (form) {
                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();
                
                xhr.open('POST', form.action || window.location.href, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (typeof window.hideLoading === 'function') {
                            window.hideLoading('loadingChangePassword');
                        }

                        if (xhr.status === 200 || xhr.status === 0) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                
                                if (response.success) {
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('success', response.message || 'Password berhasil diubah.');
                                    }
                                    
                                    form.reset();
                                    if (hint) {
                                        hint.textContent = '';
                                        hint.className = 'hint';
                                    }
                                    
                                    setTimeout(() => {
                                        window.location.href = 'profile.php';
                                    }, 1500);
                                } else {
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('error', response.message || 'Gagal mengubah password.');
                                    }
                                }
                            } catch (e) {
                                window.location.reload();
                            }
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('error', 'Terjadi kesalahan saat mengubah password.');
                            }
                        }
                    }
                };

                xhr.onerror = function() {
                    if (typeof window.hideLoading === 'function') {
                        window.hideLoading('loadingChangePassword');
                    }
                    
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
                    }
                };
                
                xhr.send(formData);
            }
        }
    });
})();