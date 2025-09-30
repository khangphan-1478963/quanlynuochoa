<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Kiểm tra đăng nhập và đơn hàng
if (!$user_id || $order_id <= 0) {
    $message = !$user_id ? "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập." : "Đơn hàng không hợp lệ.";
    echo "<script>
        if (confirm(" . json_encode($message) . ")) {
            window.location.href = '" . ($user_id ? 'profile.php' : 'login.php') . "';
        } else {
            window.location.href = 'dashboard.php';
        }
    </script>";
    exit();
}

try {
    // Lấy thông tin đơn hàng
    $query = "SELECT o.*, c.full_name, c.address, c.phone, t.email 
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
        // Lấy chi tiết đơn hàng
        $query_details = "SELECT od.*, p.name 
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
    <title>Chi tiết đơn hàng | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <style>
        /* === Reset & Font === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Playfair Display', serif;
        }

        body {
            background: #fffafc; /* Tím pastel nhạt */
            color: #5b3e55; /* Tím đậm nhẹ nhàng */
        }

        /* === Header === */
        header {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-family: 'Lobster', cursive;
            font-size: 36px;
            font-weight: 700;
            color: #8b4e75; /* Tím pastel đậm */
            letter-spacing: 2px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 60px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            font-family: 'sans-serif' !important;
            font-size: 25px;
            text-decoration: none;
            color: #5b3e55;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .nav-links a:hover {
            color: #d6a4c1; /* Tím pastel sáng khi hover */
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #d6a4c1;
            bottom: -5px;
            left: 0;
            transition: width 0.3s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

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

        /* === Order Details Section === */
        .order-details-container {
            margin-top: 100px;
            padding: 50px 60px;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            color: #804e6f;
            margin-bottom: 40px;
            margin-top: 40px;
        }

        .error {
            color: #d32f2f;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .order-details-content {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            transition: transform 0.3s;
        }

        .order-details-content:hover {
            transform: translateY(-5px);
        }

        .order-details-content h2 {
            font-size: 2rem;
            color: #8b4e75;
            margin-bottom: 20px;
        }

        .order-details-content p {
            font-size: 1.6rem;
            color: #5b3e55;
            margin: 10px 0;
        }

        .order-details-content p strong {
            color: #6a1e4b;
        }

        .order-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        .order-item {
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .order-item h3 {
            font-size: 1.6rem;
            color: #8b4e75;
            margin-bottom: 5px;
        }

        .order-item .price {
            font-size: 1.4rem;
            color: #5b3e55;
        }

        .order-item .quantity {
            font-size: 1.4rem;
            color: #5b3e55;
        }

        .back-btn {
            display: block;
            font-size: 1.8rem;
            padding: 15px 40px;
            background: #d6a4c1;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            text-align: center;
            margin: 20px auto 0;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #b37b9e;
        }

        .back-btn:active {
            transform: scale(0.98);
        }

        /* === Footer === */
        footer {
            background: #f6d9e9;
            color: #a6668c;
            padding: 50px;
            text-align: center;
            margin-top: 40px;
        }

        /* === Responsive === */
        @media (max-width: 768px) {
            nav {
                padding: 20px 30px;
            }

            .logo {
                font-size: 28px;
            }

            .order-details-container {
                padding: 30px 20px;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .error {
                font-size: 1.2rem;
            }

            .order-details-content {
                padding: 20px;
            }

            .order-details-content h2 {
                font-size: 1.8rem;
            }

            .order-details-content p {
                font-size: 1.4rem;
            }

            .order-item h3 {
                font-size: 1.4rem;
            }

            .order-item .price, .order-item .quantity {
                font-size: 1.2rem;
            }

            .back-btn {
                font-size: 1.5rem;
                padding: 10px 20px;
            }
        }
    </style>
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
    <section class="order-details-container">
        <h1 class="section-title">CHI TIẾT ĐƠN HÀNG</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <?php if (isset($order) && $order): ?>
            <div class="order-details-content">
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
                        <div class="order-item">
                            <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            <p class="quantity">Số lượng: <?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="profile.php" class="back-btn">Quay về hồ sơ</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>