document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('editStoreForm')) {
        const displayDiv = document.getElementById('displayStoreInfo');
        const editForm = document.getElementById('editStoreForm');
        const editBtn = document.getElementById('editStoreBtn');
        const cancelBtn = document.getElementById('cancelEditBtn');
        const logoInput = document.getElementById('store_logo');
        const logoPreview = document.getElementById('logo-preview');

        const quill = new Quill('#editor', { theme: 'snow' });
        const descriptionInput = document.getElementById('store_description_hidden');

        editBtn.addEventListener('click', () => {
            displayDiv.style.display = 'none';
            editForm.style.display = 'block';
        });

        cancelBtn.addEventListener('click', () => {
            displayDiv.style.display = 'block';
            editForm.style.display = 'none';
        });

        editForm.addEventListener('submit', () => {
            descriptionInput.value = quill.root.innerHTML;
        });

        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});