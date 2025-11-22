<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi để debug (NÊN TẮT khi đưa vào môi trường Production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['user_id'] ?? null;
$error = null;
$edit_order = null;
$order_details = [];
$products = [];
$new_items = $_SESSION['new_order_items'] ?? []; // Danh sách sản phẩm mới/chỉnh sửa

// 1. KIỂM TRA ĐĂNG NHẬP VÀ VAI TRÒ ADMIN
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

// 2. XỬ LÝ HÀNH ĐỘNG LOGOUT
if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['new_order_items']); // Xóa session tạm
    unset($_SESSION['edit_order_id']); // Xóa session tạm
    session_destroy();
    header('Location: login.php');
    exit();
}

// 3. LẤY DANH SÁCH SẢN PHẨM (Đã tối ưu: Dùng cho cả form thêm và hiển thị)
try {
    $query = "SELECT id, name, price FROM products";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        // LƯU Ý QUAN TRỌNG: Giá CSDL phải là VND (không có nhân 1000 ở đây)
        $products[$row['id']] = $row; 
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error = "Lỗi khi lấy sản phẩm: " . $e->getMessage();
    error_log("Lỗi lấy sản phẩm: " . $e->getMessage());
}

// 4. XỬ LÝ HÀNH ĐỘNG XÓA (DELETE)
if (isset($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    try {
        // Xóa chi tiết đơn hàng trước (Foreign Key Constraint)
        $query_details = "DELETE FROM order_details WHERE order_id = ?";
        $stmt_details = mysqli_prepare($conn, $query_details);
        mysqli_stmt_bind_param($stmt_details, 'i', $order_id);
        mysqli_stmt_execute($stmt_details);
        mysqli_stmt_close($stmt_details);

        // Xóa đơn hàng
        $query = "DELETE FROM orders WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Cập nhật thông báo
        $_SESSION['message'] = "Đã xóa đơn hàng #$order_id thành công.";

    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi khi xóa đơn hàng: " . $e->getMessage();
        error_log("Lỗi xóa đơn hàng: " . $e->getMessage());
    }
    header('Location: admin-orders.php');
    exit();
}

// 5. XỬ LÝ CHẾ ĐỘ SỬA (EDIT) - Tải dữ liệu ban đầu
if (isset($_GET['edit'])) {
    $order_id_edit = (int)$_GET['edit'];
    try {
        // Lấy thông tin đơn hàng
        $query = "SELECT o.*, t.email 
                 FROM orders o 
                 JOIN taikhoan t ON o.user_id = t.id 
                 WHERE o.id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $order_id_edit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $edit_order = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Lấy chi tiết đơn hàng VÀ GẮN VÀO SESSION NẾU CHƯA CÓ
        if (empty($new_items)) {
            $query_details = "SELECT od.product_id, od.quantity, od.price 
                             FROM order_details od
                             WHERE od.order_id = ?";
            $stmt_details = mysqli_prepare($conn, $query_details);
            mysqli_stmt_bind_param($stmt_details, 'i', $order_id_edit);
            mysqli_stmt_execute($stmt_details);
            $result_details = mysqli_stmt_get_result($stmt_details);
            
            while ($row = mysqli_fetch_assoc($result_details)) {
                // Sửa: Lấy tên sản phẩm từ danh sách $products đã tải
                $product_info = $products[$row['product_id']] ?? ['name' => 'Không xác định', 'price' => 0];
                
                $new_items[] = array(
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    // LƯU Ý QUAN TRỌNG: Giá trong new_items phải là giá gốc CSDL để tính tổng tiền đúng
                    'price' => $product_info['price'], 
                    'name' => $product_info['name']
                );
            }
            $_SESSION['new_order_items'] = $new_items;
        }

        // Lưu order_id vào session để sử dụng khi thêm/xóa sản phẩm
        $_SESSION['edit_order_id'] = $order_id_edit;
        
    } catch (Exception $e) {
        $error = "Lỗi khi lấy đơn hàng để sửa: " . $e->getMessage();
        error_log("Lỗi lấy đơn hàng để sửa: " . $e->getMessage());
    }
}

// 6. XỬ LÝ THÊM SẢN PHẨM MỚI (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $order_id_redirect = $_SESSION['edit_order_id'] ?? 0;

    if ($product_id > 0 && $quantity > 0 && isset($products[$product_id])) {
        $product_info = $products[$product_id];

        // Sửa: Thêm sản phẩm mới vào $new_items
        $new_items[] = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product_info['name'],
            'price' => $product_info['price'] // Giá gốc CSDL
        );
        $_SESSION['new_order_items'] = $new_items;
    }
    header('Location: admin-orders.php?edit=' . $order_id_redirect);
    exit();
}

// 7. XỬ LÝ XÓA SẢN PHẨM KHỎI DS TẠM (GET)
if (isset($_GET['remove_item']) && isset($_SESSION['edit_order_id'])) {
    $index = (int)$_GET['remove_item'];
    if (isset($new_items[$index])) {
        unset($new_items[$index]);
        $new_items = array_values($new_items); // Sắp xếp lại index
        $_SESSION['new_order_items'] = $new_items;
    }
    header('Location: admin-orders.php?edit=' . $_SESSION['edit_order_id']);
    exit();
}

// 8. CẬP NHẬT ĐƠN HÀNG (UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id_update = (int)$_POST['order_id'];
    $status = trim($_POST['status']);
    $created_at = trim($_POST['created_at']);
    
    // Sửa: Tính tổng tiền chính xác từ $new_items
    $total_amount = 0;
    foreach ($new_items as $item) {
        // Giả sử giá trong CSDL là VND, không cần nhân 1000
        $total_amount += $item['price'] * $item['quantity'];
    }

    try {
        // Cập nhật bảng orders
        $query_update = "UPDATE orders SET status = ?, total_amount = ?, created_at = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'sdsi', $status, $total_amount, $created_at, $order_id_update);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        // Xóa chi tiết đơn hàng cũ
        $query_delete_details = "DELETE FROM order_details WHERE order_id = ?";
        $stmt_delete_details = mysqli_prepare($conn, $query_delete_details);
        mysqli_stmt_bind_param($stmt_delete_details, 'i', $order_id_update);
        mysqli_stmt_execute($stmt_delete_details);
        mysqli_stmt_close($stmt_delete_details);

        // Thêm chi tiết đơn hàng mới
        $query_insert_details = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        foreach ($new_items as $item) {
            $stmt_insert_details = mysqli_prepare($conn, $query_insert_details);
            // Sửa: Thêm 'price' vào cột bind_param
            mysqli_stmt_bind_param($stmt_insert_details, 'iiid', $order_id_update, $item['product_id'], $item['quantity'], $item['price']); 
            mysqli_stmt_execute($stmt_insert_details);
            mysqli_stmt_close($stmt_insert_details);
        }

        // Xóa session tạm thời
        unset($_SESSION['new_order_items']);
        unset($_SESSION['edit_order_id']);
        $_SESSION['message'] = "Đã cập nhật đơn hàng #$order_id_update thành công.";

        header('Location: admin-orders.php');
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi cập nhật đơn hàng: " . $e->getMessage();
        error_log("Lỗi cập nhật đơn hàng: " . $e->getMessage());
    }
}

// 9. HỦY CHỈNH SỬA
if (isset($_GET['cancel_edit'])) {
    unset($_SESSION['new_order_items']);
    unset($_SESSION['edit_order_id']);
    header('Location: admin-orders.php');
    exit();
}

// 10. LẤY DANH SÁCH ĐƠN HÀNG ĐỂ HIỂN THỊ
$orders = [];
try {
    $query_orders = "SELECT o.*, t.email 
             FROM orders o 
             JOIN taikhoan t ON o.user_id = t.id 
             ORDER BY o.created_at DESC";
    $stmt_orders = mysqli_prepare($conn, $query_orders);
    mysqli_stmt_execute($stmt_orders);
    $result_orders = mysqli_stmt_get_result($stmt_orders);
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt_orders);
} catch (Exception $e) {
    $error = "Lỗi khi lấy đơn hàng: " . $e->getMessage();
    error_log("Lỗi lấy đơn hàng: " . $e->getMessage());
}

// Kiểm tra và hiển thị thông báo session
if (isset($_SESSION['message'])) {
    $message_success = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
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
        /* CSS Admin Tùy chỉnh */
        .header-admin { background: #8b4e75; padding: 10px 0; }
        .header-admin nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .header-admin .logo { color: white; font-family: 'Lobster', cursive; font-size: 28px; }
        .header-admin .nav-links a { color: white; font-size: 1.2rem; text-transform: uppercase; padding: 5px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-admin .nav-links a:hover { background: #a6668c; }
        .header-admin .icons a { color: white; font-size: 1.2rem; text-decoration: none; margin-left: 15px; }

        .error { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
        .success { color: #4CAF50; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .order-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fff; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .order-card h3 { margin-top: 0; color: #6a1e4b; font-size: 1.4rem; }
        .order-card .action-btn { margin-top: 10px; }
        .order-card .action-btn a { color: #fff; padding: 5px 10px; border-radius: 3px; text-decoration: none; margin-right: 5px; font-size: 1.1rem; }
        .order-card .action-btn .edit { background: #5cb85c; }
        .order-card .action-btn .delete { background: #d9534f; }
        .section-title { text-align: center; width: 100%; color: #8b4e75; font-size: 2.5rem; margin-bottom: 30px; margin-top: 20px}

        /* CSS cho form sửa đơn hàng */
        form { max-width: 800px; margin: 20px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); font-size: 1.2rem; }
        form label { display: block; font-weight: bold; margin-bottom: 8px; color: #4a4a4a; font-size: 1.5rem; }
        form input, form select { width: 100%; padding: 12px; margin-bottom: 20px; border: 2px solid #ddd; border-radius: 6px; font-size: 1.4rem; box-sizing: border-box; }
        form input[readonly] { background: #f5f5f5; color: #666; }
        .order-details, .item-list { border: 1px dashed #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .item-list p { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dotted #eee; }
        .item-list a { color: #d32f2f; text-decoration: none; font-size: 1rem; }
    </style>
</head>
<body>
    <header class="header-admin">
        <nav>
            <div class="logo">Admin Secret</div>
            <div class="nav-links">
                <a href="admin-orders.php">QUẢN LÝ ĐƠN HÀNG</a>
                <a href="admin-products.php">QUẢN LÝ SẢN PHẨM</a>
            </div >
            <div class="icons">
             <a href="?logout=1" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
            </div>
        </nav>
    </header>

    <section class="profile-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <h1 class="section-title">QUẢN LÝ ĐƠN HÀNG</h1>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (isset($message_success)): ?>
            <p class="success"><?php echo htmlspecialchars($message_success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($edit_order): ?>
            <form method="POST" action="admin-orders.php">
                <h2 style="color: #8b4e75; margin-bottom: 20px;">Chỉnh sửa Đơn hàng #<?php echo $edit_order['id']; ?></h2>
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

                <h3 style="margin-top: 30px; color: #6a1e4b;">Thêm/Sửa Sản phẩm:</h3>
                <label for="product_id">Chọn sản phẩm:</label>
                <select name="product_id" id="product_id" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ' - ' . number_format($product['price'], 0, ',', '.') . ' VND'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="quantity">Số lượng:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required>

                <div class="button-group">
                    <button type="submit" name="add_item" class="save-btn" style="background: #4CAF50;">Thêm sản phẩm</button>
                </div>

                <?php if (!empty($new_items)): ?>
                    <div class="item-list">
                        <h4>Danh sách sản phẩm sẽ được CẬP NHẬT:</h4>
                        <?php $total_amount = 0; ?>
                        <?php foreach ($new_items as $index => $item): ?>
                            <?php $total_amount += $item['price'] * $item['quantity']; ?>
                            <p>
                                <span><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> (SL: <?php echo $item['quantity']; ?>)</span>
                                <span>Giá: <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.') . ' VND'; ?>
                                <a href="?remove_item=<?php echo $index; ?>">Xóa</a></span>
                            </p>
                        <?php endforeach; ?>
                        <p style="border-top: 1px solid #ccc; padding-top: 10px;">
                            <strong>TỔNG TIỀN MỚI:</strong> <span style="color: #d32f2f;"><?php echo number_format($total_amount, 0, ',', '.') . ' VND'; ?></span>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <button type="submit" name="update_order" class="save-btn" style="background: #8b4e75;">LƯU CẬP NHẬT ĐƠN HÀNG</button>
                    <a href="?cancel_edit=1" class="save-btn" style="background: #b37b9e; text-align: center; text-decoration: none;">HỦY BỎ CHỈNH SỬA</a>
                </div>
            </form>
        <?php else: ?>
            <div class="orders-grid">
                <?php if (empty($orders)): ?>
                    <p class="no-data" style="text-align: center;">Chưa có đơn hàng nào.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <h3>Đơn hàng #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                            <p><strong>Trạng thái:</strong> <span style="color: #6a1e4b; font-weight: bold;"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                            <div class="action-btn">
                                <a href="?edit=<?php echo $order['id']; ?>" class="edit"><i class="fas fa-edit"></i> Sửa</a>
                                <a href="?delete=<?php echo $order['id']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa đơn hàng #<?php echo $order['id']; ?> này?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>