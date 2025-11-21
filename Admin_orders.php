<?php
session_start();
require_once 'connect.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = false;
$message = '';
$orders = [];
$edit_order = null; 


if (!$user_id) {
    header('Location: login.php');
    exit();
}

try {
  
    $query = "SELECT vaitro FROM taikhoan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn vai trò: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && $user['vaitro'] === 'admin') {
        $is_admin = true;
    } else {
       
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

// thực hiện xóa chi tiết đơn hâng
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id > 0) {
        try {
            $query_details = "DELETE FROM order_details WHERE order_id = ?";
            $stmt_details = mysqli_prepare($conn, $query_details);
            mysqli_stmt_bind_param($stmt_details, 'i', $delete_id);
            mysqli_stmt_execute($stmt_details);
            mysqli_stmt_close($stmt_details);

     
            $query_order = "DELETE FROM orders WHERE id = ?";
            $stmt_order = mysqli_prepare($conn, $query_order);
            mysqli_stmt_bind_param($stmt_order, 'i', $delete_id);
            mysqli_stmt_execute($stmt_order);
            mysqli_stmt_close($stmt_order);

            $message = "<div class='alert success'>Đã xóa đơn hàng #$delete_id thành công.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert error'>Lỗi khi xóa đơn hàng: " . $e->getMessage() . "</div>";
            error_log("Lỗi xóa đơn hàng: " . $e->getMessage());
        }
        
        header('Location: admin-orders.php');
        exit();
    }
}


if (isset($_POST['update_status'])) {
    $order_id_update = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);
    

    $valid_statuses = ['Pending', 'Processing', 'Shipping', 'Completed', 'Cancelled'];
    
    if ($order_id_update > 0 && in_array($new_status, $valid_statuses)) {
        try {
            $query_update = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $query_update);
            mysqli_stmt_bind_param($stmt_update, 'si', $new_status, $order_id_update);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            $message = "<div class='alert success'>Đã cập nhật trạng thái đơn hàng #$order_id_update thành công.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert error'>Lỗi khi cập nhật: " . $e->getMessage() . "</div>";
            error_log("Lỗi cập nhật trạng thái đơn hàng: " . $e->getMessage());
        }
    } else {
        $message = "<div class='alert error'>Trạng thái không hợp lệ hoặc thiếu thông tin.</div>";
    }
}


try {
    $query_orders = "SELECT o.id, o.total_amount, o.created_at, o.status, t.email 
                     FROM orders o 
                     JOIN taikhoan t ON o.user_id = t.id 
                     ORDER BY o.created_at DESC";
    $stmt_orders = mysqli_prepare($conn, $query_orders);
    if ($stmt_orders === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn đơn hàng: " . mysqli_error($conn));
    }
    mysqli_stmt_execute($stmt_orders);
    $result_orders = mysqli_stmt_get_result($stmt_orders);
    
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt_orders);

   
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        if ($edit_id > 0) {
         
            $query_edit = "SELECT o.*, c.full_name, c.address, c.phone, t.email 
                           FROM orders o 
                           JOIN customers c ON o.user_id = c.user_id 
                           JOIN taikhoan t ON o.user_id = t.id
                           WHERE o.id = ?";
            $stmt_edit = mysqli_prepare($conn, $query_edit);
            mysqli_stmt_bind_param($stmt_edit, 'i', $edit_id);
            mysqli_stmt_execute($stmt_edit);
            $result_edit = mysqli_stmt_get_result($stmt_edit);
            $edit_order = mysqli_fetch_assoc($result_edit);
            mysqli_stmt_close($stmt_edit);

            
            $query_details = "SELECT od.quantity, od.price, p.name 
                              FROM order_details od 
                              JOIN products p ON od.product_id = p.id
                              WHERE od.order_id = ?";
            $stmt_details = mysqli_prepare($conn, $query_details);
            mysqli_stmt_bind_param($stmt_details, 'i', $edit_id);
            mysqli_stmt_execute($stmt_details);
            $edit_order['details'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt_details), MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_details);
        }
    }
} catch (Exception $e) {
    $message = "<div class='alert error'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
    error_log("Lỗi lấy danh sách đơn hàng: " . $e->getMessage());
}

if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng Admin | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <style>
      
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Playfair Display', serif;
        }
        body {
            background: #f4f7f9; 
            color: #333;
        }
        
        
        .admin-header {
            background: #8b4e75; 
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header .logo {
            font-family: 'Lobster', cursive;
            font-size: 28px;
            font-weight: 700;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background: #a6668c;
        }
        .logout-btn {
            background: #6a1e4b;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: #5b3e55;
        }

        
        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
        }
        .section-title {
            color: #8b4e75;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        
       
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .order-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .order-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }
        .order-card h3 {
            color: #6a1e4b;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .order-card p {
            font-size: 1rem;
            margin: 5px 0;
        }
        .action-btn {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .action-btn a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 700;
            transition: opacity 0.3s;
        }
        .action-btn .edit {
            background: #f0ad4e; 
            color: white;
        }
        .action-btn .delete {
            background: #d9534f; 
            color: white;
        }

        .edit-form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        .edit-form-container h2 {
            color: #8b4e75;
            border-bottom: 2px solid #8b4e75;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group button {
            background: #5cb85c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .form-group button:hover {
            background: #4cae4c;
        }
        .order-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .order-details-table th, .order-details-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .order-details-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">Admin Panel</div>
        <nav class="admin-nav">
            <a href="admin-products.php">Quản lý Sản phẩm</a>
            <a href="admin-orders.php" class="active">Quản lý Đơn hàng</a>
        </nav>
        <a href="?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </header>

    <section class="admin-container">
        <h1 class="section-title">QUẢN LÝ ĐƠN HÀNG</h1>

        <?php echo $message; // Hiển thị thông báo (thành công/lỗi) ?>
        
        <?php if ($edit_order): ?>
            <div class="edit-form-container">
                <h2>Chỉnh sửa Đơn hàng #<?php echo htmlspecialchars($edit_order['id']); ?></h2>
                <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($edit_order['full_name']) . ' (' . htmlspecialchars($edit_order['email']) . ')'; ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($edit_order['address']); ?></p>
                <p><strong>Tổng tiền:</strong> <?php echo number_format($edit_order['total_amount'], 0, ',', '.') . ' VND'; ?></p>

                <h3>Chi tiết sản phẩm</h3>
                <table class="order-details-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Tổng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($edit_order['details'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.') . ' VND'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                
                <form method="POST" action="admin-orders.php">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($edit_order['id']); ?>">
                    <div class="form-group">
                        <label for="status">Cập nhật Trạng thái:</label>
                        <select name="status" id="status" required>
                            <option value="Pending" <?php echo ($edit_order['status'] == 'Pending' ? 'selected' : ''); ?>>Đang chờ xử lý</option>
                            <option value="Processing" <?php echo ($edit_order['status'] == 'Processing' ? 'selected' : ''); ?>>Đang xử lý</option>
                            <option value="Shipping" <?php echo ($edit_order['status'] == 'Shipping' ? 'selected' : ''); ?>>Đang vận chuyển</option>
                            <option value="Completed" <?php echo ($edit_order['status'] == 'Completed' ? 'selected' : ''); ?>>Hoàn thành</option>
                            <option value="Cancelled" <?php echo ($edit_order['status'] == 'Cancelled' ? 'selected' : ''); ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_status">Cập nhật Trạng thái</button>
                        <a href="admin-orders.php" class="btn" style="background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Thoát chỉnh sửa</a>
                    </div>
                </form>
            </div>
            <hr>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p style="text-align: center;">Chưa có đơn hàng nào.</p>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h3>Đơn hàng #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <span style="color: <?php 
                                // Tô màu trạng thái
                                switch ($order['status']) {
                                    case 'Pending': echo '#f0ad4e'; break; // Vàng
                                    case 'Processing': echo '#5bc0de'; break; // Xanh dương
                                    case 'Shipping': echo '#0275d8'; break; // Xanh đậm
                                    case 'Completed': echo '#5cb85c'; break; // Xanh lá
                                    case 'Cancelled': echo '#d9534f'; break; // Đỏ
                                    default: echo '#333';
                                }
                            ?>; font-weight: 700;"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <div class="action-btn">
                            <a href="?edit=<?php echo $order['id']; ?>" class="edit"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?delete=<?php echo $order['id']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa đơn hàng #<?php echo $order['id']; ?> này? Thao tác này KHÔNG thể hoàn tác.');"><i class="fas fa-trash-alt"></i> Xóa</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>
