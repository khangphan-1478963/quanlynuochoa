<?php
session_start();
// Đảm bảo file connect.php đã kết nối $conn

// Bật hiển thị lỗi để debug (nên tắt khi đưa vào production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Đặt require_once sau error_reporting

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'] ?? null; // Sử dụng toán tử null coalescing cho code sạch hơn

// Kiểm tra đăng nhập và đơn hàng
if (!$user_id || $order_id <= 0) {
    // Sử dụng header redirect thay vì JS, trừ khi cần hiển thị message phức tạp
    // Tuy nhiên, nếu muốn giữ nguyên logic hiển thị cảnh báo, cần đảm bảo json_encode không bị lỗi.
    $message = !$user_id ? "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập." : "Đơn hàng không hợp lệ.";
    
    // Giữ nguyên đoạn JS để phù hợp với code ban đầu
    $target_page = $user_id ? 'profile.php' : 'login.php';
    echo "<script>
        if (confirm(" . json_encode($message) . ")) {
            window.location.href = '{$target_page}';
        } else {
            window.location.href = 'dashboard.php';
        }
    </script>";
    exit();
}

$error = null;
$order = null;
$order_details = [];

try {
    // 1. Cải tiến: Chỉ truy vấn thông tin cần thiết.
    // Lấy thông tin đơn hàng và thông tin khách hàng (đã được lưu khi đặt hàng)
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
        // Lấy chi tiết đơn hàng
        $query_details = "SELECT od.quantity, od.price, p.name 
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
        
        // Cải tiến: Kiểm tra nếu không có chi tiết sản phẩm
        if (empty($order_details)) {
             $error = "Đơn hàng này không có sản phẩm nào được ghi nhận.";
        }
    }
} catch (Exception $e) {
    // Cải tiến: Hiển thị lỗi thân thiện với người dùng (chỉ hiển thị lỗi chung chung)
    $error = "Đã xảy ra lỗi khi lấy thông tin đơn hàng. Vui lòng thử lại sau.";
    // Ghi log chi tiết lỗi vào hệ thống (thay vì in ra màn hình)
    error_log("Lỗi lấy thông tin đơn hàng (ID: $order_id, User: $user_id): " . $e->getMessage());
    // Nếu vẫn muốn debug:
    // $error = "Lỗi khi lấy thông tin đơn hàng: " . $e->getMessage(); 
}

// Cải tiến: Đóng kết nối để giải phóng tài nguyên
if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo htmlspecialchars($order_id); ?> | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lobster&display=swap" rel="stylesheet">
    <style>
        /* === Reset & Font === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Playfair Display', serif; /* Giữ nguyên font Playfair Display cho nội dung */
        }

        body {
            background: #fffafc; /* Tím pastel nhạt */
            color: #5b3e55; /* Tím đậm nhẹ nhàng */
        }

        /* === Header === */
        header {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: sticky; /* Thay đổi từ fixed sang sticky để tối ưu scroll trên mobile */
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
            /* Sửa: Loại bỏ inline style, dùng CSS class/selector cho font */
            font-family: 'Playfair Display', serif; /* Sử dụng Playfair Display cho các link */
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
        
        /* ... (Giữ nguyên phần còn lại của CSS đã tốt) ... */

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
            /* Giảm margin-top vì header đã thành sticky */
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
            font-weight: 700; /* Thêm độ đậm cho thông báo lỗi */
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
            transform: none; /* Bỏ hover transform để tránh gây rối khi đọc */
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
            font-weight: 700;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-item-info {
             display: flex;
             flex-direction: column;
             flex-grow: 1;
        }
        
        .order-item h3 {
            font-size: 1.6rem;
            color: #8b4e75;
            margin-bottom: 5px;
        }

        .order-item .price {
            font-size: 1.4rem;
            color: #5b3e55;
            font-weight: 700;
            margin-left: 15px;
        }

        .order-item .quantity {
            font-size: 1.4rem;
            color: #5b3e55;
            margin-left: 15px;
        }
        
        .item-details {
            display: flex;
            align-items: center;
            justify-content: flex-end;
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
            max-width: 300px; /* Giới hạn chiều rộng nút */
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
            /* ... (Giữ nguyên responsive media query) ... */
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

            .order-item {
                flex-direction: column; /* Xếp dọc các mục trong item */
                align-items: flex-start;
            }
            
            .item-details {
                justify-content: flex-start;
                margin-top: 5px;
            }

            .order-item h3 {
                font-size: 1.4rem;
            }

            .order-item .price, .order-item .quantity {
                font-size: 1.2rem;
                margin-left: 0; /* Bỏ margin-left cho mobile */
                margin-right: 15px; /* Thêm margin-right giữa giá và số lượng */
            }

            .back-btn {
                font-size: 1.5rem;
                padding: 10px 20px;
            }
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

    <section class="order-details-container">
        <h1 class="section-title">CHI TIẾT ĐƠN HÀNG</h1>
        <?php if ($error): ?>
             <p class='error'><?php echo htmlspecialchars($error); ?></p> 
        <?php endif; ?>

        <?php if (isset($order) && $order && empty($error)): ?> 
            <div class="order-details-content">
                <h2>Thông tin đơn hàng</h2>
                <p><strong>Mã đơn hàng:</strong> #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Trạng thái:</strong> <span style="color: #6a1e4b; font-weight: 700;"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span></p>

                <h2>Thông tin giao hàng</h2>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($order['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8'); ?></p>

                <h2>Chi tiết sản phẩm</h2>
                <div class="order-items">
                    <?php 
                    $total_items = 0;
                    foreach ($order_details as $item): 
                        $total_items += $item['quantity'];
                    ?>
                        <div class="order-item">
                            <div class="order-item-info">
                                <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>
                            <div class="item-details">
                                <p class="quantity">SL: <?php echo number_format($item['quantity']); ?></p>
                                <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p style="text-align: right; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 15px;">
                    <strong>Tổng số lượng sản phẩm:</strong> <?php echo number_format($total_items); ?>
                </p>
                <p style="text-align: right; font-size: 2rem; color: #8b4e75;">
                    <strong>TỔNG THANH TOÁN:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?>
                </p>
                
                <a href="profile.php" class="back-btn"><i class="fas fa-chevron-left"></i> Quay về hồ sơ</a>
            </div>
        <?php endif; ?>
    </section>

    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>