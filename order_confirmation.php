<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: cart.php');
    exit();
}

try {
    $query = "SELECT o.*, c.full_name, c.address, c.phone, t.email 
              FROM orders o 
              JOIN customers c ON o.user_id = c.user_id 
              JOIN taikhoan t ON o.user_id = t.id 
              WHERE o.id = ? AND o.user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$order) {
        $error = "Không tìm thấy đơn hàng.";
    } else {
        $query_details = "SELECT * FROM order_details WHERE order_id = ?";
        $stmt_details = mysqli_prepare($conn, $query_details);
        mysqli_stmt_bind_param($stmt_details, 'i', $order_id);
        mysqli_stmt_execute($stmt_details);
        $result_details = mysqli_stmt_get_result($stmt_details);
        $order_details = array();
        while ($row = mysqli_fetch_assoc($result_details)) {
            $order_details[] = $row;
        }
        mysqli_stmt_close($stmt_details);
    }
} catch (Exception $e) {
    $error = "Lỗi khi lấy thông tin đơn hàng: " . $e->getMessage();
    error_log("Lỗi lấy thông tin đơn hàng: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/order-confirmation-style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">Jardin Secret</div>
            <div class="nav-links">
                <a href="dashboard.php" style="font-family: 'Playfair Display', serif;">TRANG CHỦ</a>
                <a href="products.php" style="font-family: 'Playfair Display', serif;">NƯỚC HOA</a>
                <a href="collections.php" style="font-family: 'Playfair Display', serif;">BỘ SƯU TẬP</a>
                <a href="about.php" style="font-family: 'Playfair Display', serif;">VỀ CHÚNG TÔI</a>
                <a href="contact.php" style="font-family: 'Playfair Display', serif;">LIÊN HỆ</a>
            </div>
            <div class="icons">
                <a href="search.php"><i class="fas fa-search"></i></a>
                <a href="profile.php"><i class="fas fa-user"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="confirmation-container">
        <h1 class="section-title">XÁC NHẬN ĐƠN HÀNG</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <?php if (isset($order) && $order): ?>
            <div class="confirmation-content">
                <h2>Thông tin đơn hàng</h2>
                <p><strong>Mã đơn hàng:</strong> #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></p>

                <h2>Chi tiết sản phẩm</h2>
                <div class="order-items">
                    <?php foreach ($order_details as $item): ?>
                        <?php
                        // Lấy tên sản phẩm từ bảng products
                        $product_query = "SELECT name FROM products WHERE id = ?";
                        $stmt_product = mysqli_prepare($conn, $product_query);
                        mysqli_stmt_bind_param($stmt_product, 'i', $item['product_id']);
                        mysqli_stmt_execute($stmt_product);
                        $result_product = mysqli_stmt_get_result($stmt_product);
                        $product = mysqli_fetch_assoc($result_product);
                        $product_name = $product ? $product['name'] : 'Sản phẩm không xác định';
                        mysqli_stmt_close($stmt_product);
                        ?>
                        <div class="order-item">
                            <h3><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            <p class="quantity">Số lượng: <?php echo $item['quantity']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="confirmation-message">Cảm ơn bạn đã đặt hàng! Đơn hàng của bạn sẽ được xử lý trong thời gian sớm nhất.</p>
                <a href="products.php" class="back-btn">Quay về</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>