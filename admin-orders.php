<?php
session_start();
require_once 'connect.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Kiểm tra đăng nhập và vai trò admin
if (!$user_id) {
    header('Location: login.php');
    exit();
}

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
    error_log("Lỗi kiểm tra vai trò: " . $e->getMessage());
    header('Location: login.php');
    exit();
}


if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    session_destroy();
    header('Location: login.php');
    exit();
}

// Lấy tất cả đơn hàng
$orders = array();
try {
    $query = "SELECT o.*, t.email 
             FROM orders o 
             JOIN taikhoan t ON o.user_id = t.id 
             ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error = "Lỗi khi lấy đơn hàng: " . $e->getMessage();
    error_log("Lỗi lấy đơn hàng: " . $e->getMessage());
}

// Lấy danh sách sản phẩm
$products = array();
try {
    $query = "SELECT id, name, price FROM products";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $products[$row['id']] = $row;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error = "Lỗi khi lấy sản phẩm: " . $e->getMessage();
    error_log("Lỗi lấy sản phẩm: " . $e->getMessage());
}

// Xử lý xóa đơn hàng
if (isset($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    try {
        $query = "DELETE FROM orders WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: admin-orders.php');
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi xóa đơn hàng: " . $e->getMessage();
        error_log("Lỗi xóa đơn hàng: " . $e->getMessage());
    }
}

// Xử lý sửa đơn hàng
$edit_order = null;
$order_details = array();
$new_items = isset($_SESSION['new_order_items']) ? $_SESSION['new_order_items'] : array();

if (isset($_GET['edit'])) {
    $order_id = (int)$_GET['edit'];
    try {
        // Lấy thông tin đơn hàng
        $query = "SELECT o.*, t.email 
                 FROM orders o 
                 JOIN taikhoan t ON o.user_id = t.id 
                 WHERE o.id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $edit_order = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Lấy chi tiết đơn hàng
        $query = "SELECT od.*, p.name, p.price 
                 FROM order_details od 
                 JOIN products p ON od.product_id = p.id 
                 WHERE od.order_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $order_details[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Lưu order_id vào session để sử dụng khi thêm sản phẩm
        $_SESSION['edit_order_id'] = $order_id;
    } catch (Exception $e) {
        $error = "Lỗi khi lấy đơn hàng để sửa: " . $e->getMessage();
        error_log("Lỗi lấy đơn hàng để sửa: " . $e->getMessage());
    }
}

// Thêm sản phẩm mới vào danh sách tạm thời
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($product_id > 0 && $quantity > 0) {
        $new_items[] = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $products[$product_id]['name'],
            'price' => $products[$product_id]['price']
        );
        $_SESSION['new_order_items'] = $new_items;
    }
    header('Location: admin-orders.php?edit=' . $_SESSION['edit_order_id']);
    exit();
}

// Xóa sản phẩm khỏi danh sách tạm thời
if (isset($_GET['remove_item'])) {
    $index = (int)$_GET['remove_item'];
    if (isset($new_items[$index])) {
        unset($new_items[$index]);
        $new_items = array_values($new_items);
        $_SESSION['new_order_items'] = $new_items;
    }
    header('Location: admin-orders.php?edit=' . $_SESSION['edit_order_id']);
    exit();
}

// Cập nhật đơn hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = (int)$_POST['order_id'];
    $status = trim($_POST['status']);
    $created_at = trim($_POST['created_at']);

    // Tính tổng tiền từ danh sách sản phẩm mới
    $total_amount = 0;
    foreach ($new_items as $item) {
        $total_amount += $item['price'] * $item['quantity'] * 1000;
    }

    try {
        // Cập nhật bảng orders
        $query = "UPDATE orders SET status = ?, total_amount = ?, created_at = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sdsi', $status, $total_amount, $created_at, $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Xóa chi tiết đơn hàng cũ
        $query = "DELETE FROM order_details WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Thêm chi tiết đơn hàng mới
        foreach ($new_items as $item) {
            $query = "INSERT INTO order_details (order_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'iii', $order_id, $item['product_id'], $item['quantity']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Xóa session tạm thời
        unset($_SESSION['new_order_items']);
        unset($_SESSION['edit_order_id']);
        header('Location: admin-orders.php');
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi cập nhật đơn hàng: " . $e->getMessage();
        error_log("Lỗi cập nhật đơn hàng: " . $e->getMessage());
    }
}

// Hủy chỉnh sửa
if (isset($_GET['cancel_edit'])) {
    unset($_SESSION['new_order_items']);
    unset($_SESSION['edit_order_id']);
    header('Location: admin-orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/profile-style.css">
    <style>
        .error { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .order-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fff; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .order-card h3 { margin-top: 0; color: #4a4a4a; font-size: 1.4rem; }
        .order-card .action-btn { margin-top: 10px; }
        .order-card .action-btn a { color: #fff; padding: 5px 10px; border-radius: 3px; text-decoration: none; margin-right: 5px; font-size: 1.1rem; }
        .order-card .action-btn .edit { background: #4CAF50; }
        .order-card .action-btn .delete { background: #d32f2f; }
        .section-title { text-align: center; width: 100%; color: #4a4a4a; font-size: 2.5rem; margin-bottom: 30px; margin-top: 0px}

        /* CSS cho form sửa đơn hàng */
        form { max-width: 800px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); font-size: 1.2rem; }
        form label { display: block; font-weight: bold; margin-bottom: 8px; color: #4a4a4a; font-size: 1.5rem; }
        form input, form select { width: 100%; padding: 12px; margin-bottom: 20px; border: 2px solid #ddd; border-radius: 6px; font-size: 1.4rem; box-sizing: border-box; }
        form input[readonly] { background: #f5f5f5; color: #666; }
        form select { cursor: pointer; }
        .order-details { margin-top: 20px; border-top: 2px solid #ddd; padding: 20px; background: #f9f9f9; border-radius: 6px; font-size: 1.1rem; }
        .order-details h4 { margin-bottom: 15px; color: #4a4a4a; font-size: 1.4rem; }
        .order-details p { margin: 10px 0; color: #666; font-size: 1.1rem; }
        .button-group { display: flex; justify-content: space-between; gap: 15px; }
        .button-group .save-btn { flex: 1; padding: 12px; font-size: 1.5rem; border: none; border-radius: 6px; cursor: pointer; transition: background 0.3s; }
        .button-group .save-btn:hover { opacity: 0.9; }
        .item-list { margin: 20px 0; }
        .item-list p { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f5f5f5; border-radius: 5px; margin: 5px 0; }
        .item-list a { color: #d32f2f; text-decoration: none; font-size: 1rem; }

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
            </div >
            <div class="icons">
             <a href="?logout=1" style="color: #8b4e75; text-decoration: none; margin-left: 10px;" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="profile-container">
        <h1 class="section-title">QUẢN LÝ ĐƠN HÀNG</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($edit_order): ?>
            <!-- Form chỉnh sửa đơn hàng -->
            <form method="POST" action="">
                <input type="hidden" name="order_id" value="<?php echo $edit_order['id']; ?>">
                <label for="email">Email khách hàng:</label>
                <input type="text" id="email" value="<?php echo htmlspecialchars($edit_order['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>

                <label for="created_at">Ngày tạo:</label>
                <input type="datetime-local" name="created_at" id="created_at" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_order['created_at'])); ?>" required>

                <label for="status">Trạng thái:</label>
                <select name="status" id="status" required>
                    <option value="pending" <?php echo $edit_order['status'] == 'pending' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="shipped" <?php echo $edit_order['status'] == 'shipped' ? 'selected' : ''; ?>>Đã giao</option>
                    <option value="cancelled" <?php echo $edit_order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>

                <!-- Thêm sản phẩm mới -->
                <label for="product_id">Chọn sản phẩm:</label>
                <select name="product_id" id="product_id" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' - ' . number_format($product['price'] * 1000, 0, ',', '.') . ' VND'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantity">Số lượng:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required>

                <div class="button-group">
                    <button type="submit" name="add_item" class="save-btn">Thêm sản phẩm</button>
                </div>

                <!-- Hiển thị danh sách sản phẩm đã chọn -->
                <?php if (!empty($new_items)): ?>
                    <div class="item-list">
                        <h4>Danh sách sản phẩm đã chọn:</h4>
                        <?php $total_amount = 0; ?>
                        <?php foreach ($new_items as $index => $item): ?>
                            <?php $total_amount += $item['price'] * $item['quantity'] * 1000; ?>
                            <p>
                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> - 
                                Số lượng: <?php echo $item['quantity']; ?> - 
                                Giá: <?php echo number_format($item['price'] * 1000, 0, ',', '.') . ' VND'; ?>
                                <a href="?remove_item=<?php echo $index; ?>">Xóa</a>
                            </p>
                        <?php endforeach; ?>
                        <p><strong>Tổng tiền:</strong> <?php echo number_format($total_amount, 0, ',', '.') . ' VND'; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($order_details)): ?>
                    <div class="order-details">
                        <h4>Chi tiết đơn hàng hiện tại:</h4>
                        <?php foreach ($order_details as $detail): ?>
                            <p>
                                Sản phẩm: <?php echo htmlspecialchars($detail['name'], ENT_QUOTES, 'UTF-8'); ?>, 
                                Số lượng: <?php echo htmlspecialchars($detail['quantity'], ENT_QUOTES, 'UTF-8'); ?>, 
                                Giá: <?php echo number_format($detail['price'] * 1000, 0, ',', '.') . ' VND'; ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="update_order" class="save-btn">Cập nhật</button>
                    <a href="?cancel_edit=1" class="save-btn" style="background: #b37b9e; text-align: center">Hủy</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Danh sách đơn hàng -->
            <div class="orders-grid">
                <?php if (empty($orders)): ?>
                    <p class="no-data">Chưa có đơn hàng nào.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <h3>Đơn hàng #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                            <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="action-btn">
                                <a href="?edit=<?php echo $order['id']; ?>" class="edit">Sửa</a>
                                <a href="?delete=<?php echo $order['id']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa đơn hàng này?');">Xóa</a>
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