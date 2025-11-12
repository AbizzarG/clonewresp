<?php
session_start();
require_once '../../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../../");
    exit();
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEditMode = (bool)$productId;

$errors = [];

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->prepare("SELECT store_id FROM store WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    die("Error: No store associated with this account.");
}
$storeId = $store['store_id'];

$product = [
    'product_name' => '',
    'description' => '',
    'category_id' => '',
    'price' => '',
    'stock' => '',
    'main_image_path' => null
];

if ($isEditMode) {
    $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = ? AND store_id = ? AND deleted_at IS NULL");
    $stmt->execute([$productId, $storeId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: product_management.php?error=notfound");
        exit();
    }

    $catStmt = $pdo->prepare("SELECT category_id FROM category_item WHERE product_id = ?");
    $catStmt->execute([$productId]);
    $categoryLink = $catStmt->fetch(PDO::FETCH_ASSOC);
    if ($categoryLink) {
        $product['category_id'] = $categoryLink['category_id'];
    }
}

$catStmt = $pdo->query("SELECT category_id, name FROM category ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $description = $_POST['description'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (empty($productName) || strlen($productName) > 200) $errors[] = 'Product name is required and must be less than 200 characters.';
    if (empty($description) || strlen($description) > 1000) $errors[] = 'Description is required and must be less than 1000 characters.';
    if ($categoryId === false) $errors[] = 'Please select a valid category.';
    if ($price === false || $price < 1000) $errors[] = 'Price must be a number and at least 1000.';
    if ($stock === false || $stock < 0) $errors[] = 'Stock must be a number and at least 0.';

    $imagePath = $product['main_image_path'];
    $oldImagePath = $product['main_image_path']; // Store old path for cleanup

    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['main_image'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if ($file['size'] > $maxSize) $errors[] = 'Image file size must be less than 2MB.';
        if (!in_array($file['type'], $allowedTypes)) $errors[] = 'Invalid file type. Only JPG, PNG, and WEBP are allowed.';

        if (empty($errors)) {
            $uploadDir = '../../upload/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'prod_' . $storeId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Set new image path
                $imagePath = 'upload/products/' . $fileName;

                // Delete old image only after successful new upload (in edit mode)
                if ($isEditMode && $oldImagePath && file_exists('../../' . $oldImagePath)) {
                    unlink('../../' . $oldImagePath);
                }
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    } elseif (!$isEditMode) {
        $errors[] = 'Product image is required.';
    }
    // If edit mode and no new image uploaded, $imagePath keeps the old value

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($isEditMode) {
                $sql = "UPDATE product SET product_name = ?, description = ?, price = ?, stock = ?, main_image_path = ? WHERE product_id = ? AND store_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$productName, $description, $price, $stock, $imagePath, $productId, $storeId]);

                $delStmt = $pdo->prepare("DELETE FROM category_item WHERE product_id = ?");
                $delStmt->execute([$productId]);
                $insStmt = $pdo->prepare("INSERT INTO category_item (product_id, category_id) VALUES (?, ?)");
                $insStmt->execute([$productId, $categoryId]);

                $pdo->commit();
                header("Location: product_management.php?status=updated");

            } else {
                $sql = "INSERT INTO product (store_id, product_name, description, price, stock, main_image_path) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$storeId, $productName, $description, $price, $stock, $imagePath]);
                $newProductId = $pdo->lastInsertId();

                $catItemSql = "INSERT INTO category_item (product_id, category_id) VALUES (?, ?)";
                $catItemStmt = $pdo->prepare($catItemSql);
                $catItemStmt->execute([$newProductId, $categoryId]);

                $pdo->commit();
                header("Location: product_management.php?status=added");
            }
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    $product['product_name'] = $productName;
    $product['description'] = $description;
    $product['category_id'] = $categoryId;
    $product['price'] = $price;
    $product['stock'] = $stock;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="../../resources/seller/add_product.css">
</head>
<body>
    <?php include '../components/Navbar.php'; ?>

    <div class="container">
        <h1><?php echo $isEditMode ? 'Edit Product' : 'Add New Product'; ?></h1>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="addProductForm" method="POST" action="add_product.php<?php echo $isEditMode ? '?id=' . $productId : ''; ?>" enctype="multipart/form-data" data-is-edit-mode="<?php echo $isEditMode ? '1' : '0'; ?>">
            <div class="form-group">
                <label for="product_name">Product Name (max 200 chars)</label>
                <input type="text" id="product_name" name="product_name" required maxlength="200" value="<?php echo htmlspecialchars($product['product_name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description (max 1000 chars)</label>
                <div id="editor" class="quill-editor"><?php echo $product['description']; ?></div>
                <input type="hidden" name="description" id="description">
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select a Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" required min="1000" value="<?php echo htmlspecialchars($product['price']); ?>">
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" required min="0" value="<?php echo htmlspecialchars($product['stock']); ?>">
            </div>

            <div class="form-group">
                <label for="main_image">Product Photo (<?php echo $isEditMode ? 'Ganti, opsional' : 'Wajib'; ?>, max 2MB)</label>
                <input type="file" id="main_image" name="main_image" accept="image/jpeg, image/png, image/webp" <?php echo !$isEditMode ? 'required' : ''; ?>>
                <div id="image-preview-container">
                    <?php if ($isEditMode && $product['main_image_path']): ?>
                        <p>Current Image:</p>
                    <?php endif; ?>
                    <img id="image-preview"
                         src="<?php
                            if ($isEditMode && $product['main_image_path']) {
                                $imagePath = $product['main_image_path'];
                                // Normalize path - add leading slash if not present and not a URL
                                if (!str_starts_with($imagePath, '/') && !str_starts_with($imagePath, 'http')) {
                                    $imagePath = '/' . $imagePath;
                                }
                                echo htmlspecialchars($imagePath);
                            } else {
                                echo '#';
                            }
                         ?>"
                         alt="Image Preview"
                         class="<?php echo ($isEditMode && $product['main_image_path']) ? 'visible' : ''; ?>"/>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Simpan <?php echo $isEditMode ? 'Perubahan' : ''; ?></button>
                <a href="product_management.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="../../public/seller/add_product.js"></script>
</body>
</html>