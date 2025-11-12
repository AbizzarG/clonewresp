document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.getElementById('productGrid');
    const searchInput = document.querySelector('.search-input[name="search"]');
    const filterDropdownBtn = document.getElementById('filterDropdownBtn');
    const filterDropdownMenu = document.getElementById('filterDropdownMenu');
    const skeletonContainer = document.getElementById('skeletonContainer');
    
    if (filterDropdownBtn && filterDropdownMenu) {
        filterDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            filterDropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!filterDropdownBtn.contains(e.target) && !filterDropdownMenu.contains(e.target)) {
                filterDropdownMenu.classList.remove('show');
            }
        });
    }
    
    let debounceTimer;
    function debounce(func, delay = 500) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }
    
    function showSkeleton() {
        if (productGrid && skeletonContainer) {
            const productCards = productGrid.querySelectorAll('.pd-product-card:not(.pd-skeleton-card)');
            productCards.forEach(card => card.classList.add('hidden'));
            skeletonContainer.classList.remove('hidden');
        }
    }
    
    if (searchInput) {
        const searchForm = searchInput.closest('form');
        const debouncedSubmit = debounce(() => {
            if (searchForm) {
                searchForm.submit();
            }
        }, 500);
        
        searchInput.addEventListener('input', function() {
            showSkeleton();
            debouncedSubmit();
        });
    }
    
    const filterForm = document.querySelector('.pd-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            showSkeleton();
        });
    }
    
    const paginationLinks = document.querySelectorAll('.pd-page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function() {
            showSkeleton();
        });
    });
    
    // add cart handler
    if (productGrid) {
        productGrid.addEventListener('click', function(e) {
            if (e.target.classList.contains('pd-btn-add-to-cart')) {
                const button = e.target;
                const productId = button.getAttribute('data-product-id');
                
                button.disabled = true;
                button.textContent = 'Menambahkan...';
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/api/cart/add.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                
                xhr.onload = function() {
                    button.disabled = false;
                    button.textContent = 'Add to Cart';
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            
                            if (data.success) {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('success', data.message || 'Berhasil ditambahkan ke keranjang!');
                                }
                                updateCartBadge();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('error', data.message || 'Gagal menambahkan ke keranjang!');
                                }
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            if (typeof window.showToast === 'function') {
                                window.showToast('error', 'Terjadi kesalahan. Silakan coba lagi.');
                            }
                        }
                    } else {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (typeof window.showToast === 'function') {
                                window.showToast('error', data.message || 'Gagal menambahkan ke keranjang!');
                            }
                        } catch (e) {
                            if (typeof window.showToast === 'function') {
                                window.showToast('error', 'Terjadi kesalahan. Silakan coba lagi.');
                            }
                        }
                    }
                };
                
                xhr.onerror = function() {
                    button.disabled = false;
                    button.textContent = 'Add to Cart';
                    console.error('Add to cart error: Network error');
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
                    }
                };
                
                xhr.send(JSON.stringify({
                    product_id: parseInt(productId),
                    quantity: 1
                }));
            }
        });
    }
    
    // Helper: Update cart badge
    function updateCartBadge() {
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
                    console.error('Update cart badge error:', e);
                }
            }
        };
        
        xhr.onerror = function() {
            console.error('Update cart badge error: Network error');
        };
        
        xhr.send();
    }
});