<?php
session_start();
require_once 'connect.php'; 


error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
// Lấy user_id từ Session
$user_id = $_SESSION['user_id'] ?? 0;

if ($order_id <= 0 || $user_id === 0) {
    // Chuyển hướng nếu không có ID đơn hàng hoặc người dùng chưa đăng nhập
    header('Location: cart.php');
    exit();
}

$error = null;
$order = null;
$order_details = [];

try {
    // 1. Truy vấn thông tin đơn hàng và người dùng 
    $query = "SELECT o.id, o.total_amount, o.created_at, o.status, 
                     c.full_name, c.address, c.phone, t.email 
              FROM orders o 
              JOIN customers c ON o.user_id = c.user_id 
              JOIN taikhoan t ON o.user_id = t.id 
              WHERE o.id = ? AND o.user_id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn orders: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$order) {
        $error = "Không tìm thấy đơn hàng hoặc bạn không có quyền xem.";
    } else {
       
        $query_details = "SELECT od.quantity, od.price, od.product_id, p.name 
                          FROM order_details od 
                          JOIN products p ON od.product_id = p.id
                          WHERE od.order_id = ?";
                          
        $stmt_details = mysqli_prepare($conn, $query_details);
        if ($stmt_details === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn order_details: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt_details, 'i', $order_id);
        mysqli_stmt_execute($stmt_details);
        $result_details = mysqli_stmt_get_result($stmt_details);
        
        while ($row = mysqli_fetch_assoc($result_details)) {
            $order_details[] = $row;
        }
        mysqli_stmt_close($stmt_details);

        if (empty($order_details)) {
             $error = "Đơn hàng này không có chi tiết sản phẩm nào.";
        }
    }
} catch (Exception $e) {
    $error = "Đã xảy ra lỗi khi lấy thông tin đơn hàng. Vui lòng thử lại sau.";
    error_log("Lỗi lấy thông tin đơn hàng (ID: $order_id, User: $user_id): " . $e->getMessage());
}

// Đóng kết nối
if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng #<?php echo htmlspecialchars($order_id); ?> | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/order-confirmation-style.css"> 
    <style>
       
        .nav-links a {
            font-family: 'Playfair Display', serif; 
        }
        .confirmation-message {
            font-weight: 700;
            color: #8b4e75;
            text-align: center;
            margin: 30px 0;
            font-size: 1.8rem;
        }
       
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dotted #ccc;
        }
        .order-item h3 {
            font-size: 1.6rem;
            color: #5b3e55;
            font-weight: 700;
        }
        .item-details {
            display: flex;
            gap: 20px;
            text-align: right;
        }
        .item-details .price {
            font-weight: 700;
            color: #8b4e75;
        }
        .total-summary {
            text-align: right; 
            border-top: 2px solid #8b4e75; 
            padding-top: 15px; 
            margin-top: 20px;
        }
        .total-summary p {
            font-size: 1.6rem;
            margin: 5px 0;
        }
        .total-summary .final-total {
            font-size: 2.2rem;
            color: #8b4e75;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Jardin Secret</div>
            <div class="nav-links">
                <a href="dashboard.php">TRANG CHỦ</a>
                <a href="products.php">NƯỚC HOA</a>
                <a href="collections.php">BỘ SƯU TẬP</a>
                <a href="about.php">VỀ CHÚNG TÔI</a>
                <a href="contact.php">LIÊN HỆ</a>
            </div>
            <div class="icons">
                <a href="search.php" aria-label="Tìm kiếm"><i class="fas fa-search"></i></a>
                <a href="profile.php" aria-label="Hồ sơ"><i class="fas fa-user"></i></a>
                <a href="cart.php" aria-label="Giỏ hàng"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </header>

    <section class="confirmation-container">
        <h1 class="section-title">XÁC NHẬN ĐƠN HÀNG THÀNH CÔNG <i class="fas fa-check-circle" style="color: #69b56f;"></i></h1>
        <?php if ($error): ?> 
             <p class='error' style="text-align: center; color: #d32f2f; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (isset($order) && $order && empty($error)): ?>
            <div class="confirmation-content">
                <p class="confirmation-message">Cảm ơn bạn đã đặt hàng! Mã đơn hàng của bạn là **#<?php echo htmlspecialchars($order['id']); ?>**. Đơn hàng sẽ được xử lý sớm nhất.</p>

                <h2>Thông tin đơn hàng</h2>
                <p><strong>Mã đơn hàng:</strong> #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Ngày đặt hàng:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Trạng thái:</strong> <span style="color: #8b4e75; font-weight: 700;"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span></p>

                <h2>Thông tin giao hàng</h2>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>

                <h2>Chi tiết sản phẩm đã đặt</h2>
                <div class="order-items">
                    <?php 
                    $total_items = 0;
                    foreach ($order_details as $item): 
                        $total_items += $item['quantity'];
                    ?>
                        <div class="order-item">
                            <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="item-details">
                                <p class="quantity">SL: <?php echo number_format($item['quantity']); ?></p>
                                <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-summary">
                    <p>Tổng số lượng sản phẩm: <strong><?php echo number_format($total_items); ?></strong></p>
                    <p class="final-total">TỔNG THANH TOÁN: <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                </div>

                <a href="products.php" class="back-btn"><i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm</a>
                <a href="profile.php" class="back-btn" style="margin-left: 10px; background-color: #b37b9e;"><i class="fas fa-user-circle"></i> Xem đơn hàng</a>
            </div>
        <?php endif; ?>
    </section>

    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>
