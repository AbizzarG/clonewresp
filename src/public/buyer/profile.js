(function() {
    const toggleBtn = document.getElementById('toggleEditBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');
    const form = document.querySelector('.edit-profile-form');

    if (toggleBtn && viewMode && editMode) {
        toggleBtn.addEventListener('click', () => {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
        });
    }

    if (cancelBtn && viewMode && editMode) {
        cancelBtn.addEventListener('click', () => {
            viewMode.style.display = 'flex';
            editMode.style.display = 'none';
            if (form) {
                form.reset();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (typeof window.showModal === 'function') {
                window.showModal('confirmEditProfile');
            } else {
                form.submit();
            }
        });
    }

    document.addEventListener('modalConfirm', (e) => {
        if (e.detail.modalId === 'confirmEditProfile') {
            if (typeof window.hideModal === 'function') {
                window.hideModal('confirmEditProfile');
            }

            if (typeof window.showLoading === 'function') {
                window.showLoading('loadingEditProfile', 'Menyimpan perubahan profil...');
            }

            if (form) {
                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();
                
                xhr.open('POST', form.action || window.location.href, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (typeof window.hideLoading === 'function') {
                            window.hideLoading('loadingEditProfile');
                        }

                        if (xhr.status === 200 || xhr.status === 0) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                
                                if (response.success) {
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('success', response.message || 'Profil berhasil diperbarui.');
                                    }
                                    
                                    if (response.data) {
                                        const viewName = document.getElementById('viewName');
                                        const viewAddress = document.getElementById('viewAddress');
                                        if (viewName && response.data.name) {
                                            viewName.textContent = response.data.name;
                                        }
                                        if (viewAddress && response.data.address) {
                                            viewAddress.innerHTML = response.data.address.replace(/\n/g, '<br>');
                                        }
                                    }
                                    
                                    if (viewMode && editMode) {
                                        viewMode.style.display = 'flex';
                                        editMode.style.display = 'none';
                                    }
                                } else {
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('error', response.message || 'Gagal memperbarui profil.');
                                    }
                                }
                            } catch (e) {
                                window.location.reload();
                            }
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('error', 'Terjadi kesalahan saat menyimpan profil.');
                            }
                        }
                    }
                };

                xhr.onerror = function() {
                    if (typeof window.hideLoading === 'function') {
                        window.hideLoading('loadingEditProfile');
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