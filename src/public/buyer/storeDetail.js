(function() {
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterDropdown = document.getElementById('filterDropdown');
    const filterDropdownContainer = document.querySelector('.pd-filter-dropdown');
    const searchInput = document.querySelector('.search-input[name="search"]');

    if (filterToggleBtn && filterDropdown && filterDropdownContainer) {
        filterToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterDropdown.classList.toggle('show');
            filterDropdownContainer.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!filterDropdown.contains(e.target) && !filterToggleBtn.contains(e.target)) {
                filterDropdown.classList.remove('show');
                filterDropdownContainer.classList.remove('open');
            }
        });
    }

    const selectWrappers = document.querySelectorAll('.pd-select-wrapper');
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

    if (searchInput) {
        const searchForm = searchInput.closest('form');
        const urlParams = new URLSearchParams(window.location.search);
        const storeId = urlParams.get('id');

        if (searchForm && storeId) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const params = new URLSearchParams();
                params.append('id', storeId);
                
                const searchValue = searchInput.value.trim();
                if (searchValue) {
                    params.append('search', searchValue);
                }

                window.location.href = 'store_detail.php?' + params.toString();
            });
        }
    }
})();