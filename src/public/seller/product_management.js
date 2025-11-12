/**
 * Product Management Page JavaScript
*/

document.addEventListener('DOMContentLoaded', () => {
    // Filter dropdown
    const filterDropdown = document.querySelector('.pm-filter-dropdown');
    
    if (filterDropdown) {
        const filterDropdownBtn = document.getElementById('filterDropdownBtn');
        const filterDropdownMenu = filterDropdown.querySelector('.pm-filter-dropdown-menu');

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
    const selectWrappers = document.querySelectorAll('.pm-select-wrapper');
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
});

// --- Export Products Function ---
window.exportProducts = function(format) {
    // Get current filters
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category_id') || '';
    const search = urlParams.get('search') || '';

    // Build export URL
    let exportUrl = '/api/seller/export_products.php?format=' + format;
    if (category) exportUrl += '&category_id=' + encodeURIComponent(category);
    if (search) exportUrl += '&search=' + encodeURIComponent(search);

    // Trigger download
    window.location.href = exportUrl;
};

