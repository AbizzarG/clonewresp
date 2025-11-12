document.addEventListener('DOMContentLoaded', function() {
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Enter product description...'
    });

    var form = document.getElementById('addProductForm');
    var descriptionInput = document.getElementById('description');

    var imageInput = document.getElementById('main_image');
    var imagePreview = document.getElementById('image-preview');

    imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.add('visible');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    form.addEventListener('submit', function(e) {
        descriptionInput.value = quill.root.innerHTML;

        // --- Client-side Validation ---
        var errors = [];
        var productName = document.getElementById('product_name').value;
        var category = document.getElementById('category_id').value;
        var price = document.getElementById('price').value;
        var stock = document.getElementById('stock').value;
        var file = imageInput.files[0];
        var isEditMode = form.dataset.isEditMode === '1';

        if (productName.trim() === '') errors.push('Product name is required.');
        if (category === '') errors.push('Category is required.');
        if (price < 1000) errors.push('Price must be at least 1000.');
        if (stock < 0) errors.push('Stock cannot be negative.');
        if (quill.getText().trim() === '') errors.push('Description is required.');

        if (file) {
            if (file.size > 2 * 1024 * 1024) { // 2MB
                errors.push('Image size must not exceed 2MB.');
            }
        } else {
            if (!isEditMode) {
                errors.push('Product image is required.');
            }
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert('Please fix the following errors:\n- ' + errors.join('\n- '));
        } else {
            form.querySelector('button[type="submit"]').disabled = true;
            form.querySelector('button[type="submit"]').textContent = 'Saving...';
        }
    });
});