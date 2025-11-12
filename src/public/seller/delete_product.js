document.addEventListener('DOMContentLoaded', function() {
    const productTable = document.querySelector('.product-table');
    if (productTable) {
        productTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-delete')) {
                const button = e.target;
                const productId = button.dataset.productId;

                if (confirm('Are you sure you want to delete this product?')) {
                    button.disabled = true;
                    button.textContent = 'Deleting...';

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '/api/seller/delete_product.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');

                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.success) {
                                    button.closest('tr').remove();
                                } else {
                                    alert('Error: ' + data.message);
                                    button.disabled = false;
                                    button.textContent = 'Delete';
                                }
                            } catch (e) {
                                alert('An unexpected response was received from the server.');
                                button.disabled = false;
                                button.textContent = 'Delete';
                            }
                        } else {
                            alert('An error occurred while communicating with the server.');
                            button.disabled = false;
                            button.textContent = 'Delete';
                        }
                    };

                    xhr.onerror = function() {
                        alert('A network error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = 'Delete';
                    };

                    xhr.send(JSON.stringify({ product_id: productId }));
                }
            }
        });
    }
});
