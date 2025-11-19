<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi và debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình log thủ công
$log_file = 'logs/error.log';
if (!file_exists('logs')) {
    mkdir('logs', 0777, true);
}
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $result = file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    if ($result === false) {
        echo "<pre>Debug: Failed to write to log file $log_file</pre>";
    }
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Kiểm tra đăng nhập và vai trò admin
if (!$user_id) {
    header('Location: login.php');
    exit();
}jfsJFDYJA

try {
    $query = "SELECT vaitro FROM taikhoan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || $user['vaitro'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
} catch (Exception $e) {
    log_message("Lỗi kiểm tra vai trò: " . $e->getMessage());
    header('Location: login.php');
    exit();
}

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    session_destroy();
    header('Location: login.php');
    exit();
}

// Lấy tất cả danh mục
$categories = array();
try {
    $query = "SELECT id, name FROM categories";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[$row['id']] = $row['name'];
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error = "Lỗi khi lấy danh mục: " . $e->getMessage();
    log_message("Lỗi lấy danh mục: " . $e->getMessage());
}

// Lấy tất cả sản phẩm
$products = array();
try {
    $query = "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             ORDER BY p.id DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error = "Lỗi khi lấy sản phẩm: " . $e->getMessage();
    log_message("Lỗi lấy sản phẩm: " . $e->getMessage());
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    try {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: admin-products.php');
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi xóa sản phẩm: " . $e->getMessage();
        log_message("Lỗi xóa sản phẩm: " . $e->getMessage());
    }
}

// Xử lý thêm/sửa sản phẩm
$edit_product = null;
if (isset($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    try {
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $edit_product = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        $error = "Lỗi khi lấy sản phẩm để sửa: " . $e->getMessage();
        log_message("Lỗi lấy sản phẩm để sửa: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    // Xử lý hình ảnh
    $image = '';
    if ($product_id > 0 && isset($edit_product['image']) && !empty($edit_product['image'])) {
        $image = $edit_product['image'];
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $target_file;
        } else {
            $error = "Lỗi khi upload ảnh: " . $_FILES['image']['error'];
            echo "<pre>Debug: Upload error = $error</pre>";
            log_message("Upload error: $error");
        }
    }

    try {
        if ($product_id > 0) {
            // Cập nhật sản phẩm
            $query = "UPDATE products SET name = ?, price = ?, stock = ?, category_id = ?, description = ?, image = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                $error_msg = "Lỗi chuẩn bị câu lệnh UPDATE: " . mysqli_error($conn);
                die("<pre>Debug: $error_msg</pre>");
                log_message($error_msg);
            }
            mysqli_stmt_bind_param($stmt, 'sdiissi', $name, $price, $stock, $category_id, $description, $image, $product_id);
            $success = mysqli_stmt_execute($stmt);
            if (!$success) {
                $error_msg = "Lỗi thực thi UPDATE: " . mysqli_stmt_error($stmt);
                die("<pre>Debug: $error_msg</pre>");
                log_message($error_msg);
            }
        } else {
            // Thêm sản phẩm mới
            $query = "INSERT INTO products (name, price, stock, category_id, description, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                $error_msg = "Lỗi chuẩn bị câu lệnh INSERT: " . mysqli_error($conn);
                die("<pre>Debug: $error_msg</pre>");
                log_message($error_msg);
            }
            mysqli_stmt_bind_param($stmt, 'sdiiss', $name, $price, $stock, $category_id, $description, $image);
            $success = mysqli_stmt_execute($stmt);
            if (!$success) {
                $error_msg = "Lỗi thực thi INSERT: " . mysqli_stmt_error($stmt);
                die("<pre>Debug: $error_msg</pre>");
                log_message($error_msg);
            }
        }
        mysqli_stmt_close($stmt);
        header('Location: admin-products.php');
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi lưu sản phẩm: " . $e->getMessage();
        log_message("Lỗi lưu sản phẩm: $error");
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/profile-style.css">
    <style>
        .error { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        .product-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #fff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); text-align: center; }
        .product-card img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; }
        .product-card h3 { margin: 15px 0 10px; color: #4a4a4a; font-size: 1.6rem; }
        .product-card p { margin: 5px 0; color: #666; font-size: 1.2rem; }
        .product-card .description { font-style: italic; color: #777; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .product-card .action-btn { margin-top: 15px; }
        .product-card .price {color: red;}
        .product-card .action-btn a { color: #fff; padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-right: 8px; font-size: 1.2rem; }
        .product-card .action-btn .edit { background: #4CAF50; } 
        .product-card .action-btn .delete { background: #d32f2f; }
        .section-title { text-align: center; width: 100%; color: #4a4a4a; font-size: 2.5rem; margin-bottom: 30px; margin-top: 0; }
        .add-product-btn { display: block; width: 250px; margin: 20px auto; padding: 10px; background: #4CAF50; color: #fff; text-align: center; border-radius: 5px; text-decoration: none; font-size: 1.4rem; }
        .add-product-btn:hover { background: #45a049; }

        /* CSS cho form thêm/sửa sản phẩm */
        form { max-width: 600px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); font-size: 1.2rem; }
        form label { display: block; font-weight: bold; margin-bottom: 8px; color: #4a4a4a; font-size: 1.5rem; }
        form input, form textarea, form select { width: 100%; padding: 12px; margin-bottom: 20px; border: 2px solid #ddd; border-radius: 6px; font-size: 1.4rem; box-sizing: border-box; }
        form textarea { height: 100px; resize: vertical; }
        form input[type="file"] { padding: 5px; }
        .button-group { display: flex; justify-content: space-between; gap: 15px; }
        .button-group .save-btn { flex: 1; padding: 12px; font-size: 1.4rem; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s; margin-top: 8px; }
        .button-group .save-btn:hover { opacity: 0.9; }

        /* CSS cho nav-links */
        .nav-links { display: flex; justify-content: center; align-items: center; gap: 30px; width: 100%; }
        .nav-links a { font-size: 1.5rem; color: #4a4a4a; text-decoration: none; padding: 10px 20px; transition: color 0.3s; }
        .nav-links a:hover { color: #b37b9e; }

        .icons {
            display: flex;
            gap: 20px;
        }

        .icons a {
            font-size: 25px;
            color: #5b3e55;
            transition: all 0.3s;
        }

        .icons a:hover {
            color: #d6a4c1;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">Jardin Secret</div>
            <div class="nav-links">
                <a href="admin-orders.php">QUẢN LÝ ĐƠN HÀNG</a>
                <a href="admin-products.php">QUẢN LÝ SẢN PHẨM</a>
            </div>
            <div class="icons">
                <a href="?logout=1" style="color: #8b4e75; text-decoration: none; margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="profile-container">
        <h1 class="section-title">QUẢN LÝ SẢN PHẨM</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($edit_product || isset($_GET['add'])): ?>
            <!-- Form thêm/sửa sản phẩm -->
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                <label for="name">Tên sản phẩm:</label>
                <input type="text" name="name" id="name" value="<?php echo isset($edit_product) ? htmlspecialchars($edit_product['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="price">Giá (nghìn VND):</label>
                <input type="number" name="price" id="price" step="0.01" value="<?php echo isset($edit_product) ? htmlspecialchars($edit_product['price'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

                <label for="stock">Số lượng tồn kho:</label>
                <input type="number" name="stock" id="stock" value="<?php echo isset($edit_product) ? htmlspecialchars($edit_product['stock'], ENT_QUOTES, 'UTF-8') : '0'; ?>" min="0" required>

                <label for="category_id">Danh mục:</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Chọn danh mục</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo isset($edit_product) && $edit_product['category_id'] == $id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="description">Mô tả:</label>
                <textarea name="description" id="description"><?php echo isset($edit_product) ? htmlspecialchars($edit_product['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>

                <label for="image">Hình ảnh:</label>
                <input type="file" name="image" id="image" accept="image/*" <?php echo !$edit_product ? 'required' : ''; ?>>
                <?php if ($edit_product && isset($edit_product['image']) && !empty($edit_product['image'])): ?>
                    <p>Hình ảnh hiện tại: <img src="<?php echo htmlspecialchars($edit_product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover;"></p>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="save_product" class="save-btn">Lưu</button>
                    <a href="admin-products.php" class="save-btn" style="background: #b37b9e; text-align: center">Hủy</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Nút thêm sản phẩm -->
            <a href="?add=1" class="add-product-btn">Thêm sản phẩm mới</a>

            <!-- Danh sách sản phẩm -->
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p class="no-data">Chưa có sản phẩm nào.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars(isset($product['image']) ? $product['image'] : 'https://via.placeholder.com/250x200', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class ="price"><strong>Giá:</strong> <?php echo number_format($product['price'] * 1000, 0, ',', '.') . ' VND'; ?></div>
                            <p><strong>Số lượng:</strong> <?php echo htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Danh mục:</strong> <?php echo isset($product['category_name']) ? htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') : 'Chưa có danh mục'; ?></p>
                            <p class="description"><?php echo htmlspecialchars(isset($product['description']) && strlen($product['description']) > 50 ? substr($product['description'], 0, 50) . '...' : ($product['description'] ?: 'Không có mô tả'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="action-btn">
                                <a href="?edit=<?php echo $product['id']; ?>" class="edit">Sửa</a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">Xóa</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>