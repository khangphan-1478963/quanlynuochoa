<?php
session_start();
require_once 'connect.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}



$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
// Kiểm tra đăng nhập
if (!$user_id) {
    $message = "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập để thanh toán.";
    echo "<script>
        if (confirm(" . json_encode($message) . ")) {
            window.location.href = 'login.php';
        } else {
            window.location.href = 'cart.php';
        }
    </script>";
    exit();
}
// Load thông tin khách hàng từ bảng customers
try {
    $query = "SELECT * FROM customers WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn customers: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customer = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$customer) {
        $error = "Vui lòng cập nhật thông tin cá nhân trước khi thanh toán.";
    }
} catch (Exception $e) {
    $error = "Lỗi khi lấy thông tin khách hàng: " . $e->getMessage();
    error_log("Lỗi lấy thông tin khách hàng: " . $e->getMessage());
}

// Load giỏ hàng từ session
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Tính tổng tiền
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    if (isset($item['price']) && isset($item['quantity']) && is_numeric($item['price']) && is_numeric($item['quantity'])) {
        $total += $item['price'] * $item['quantity'];
    } else {
        $error = "Dữ liệu giỏ hàng không hợp lệ. Vui lòng kiểm tra lại.";
        break;
    }
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $customer) {
    try {
        // Lấy email từ taikhoan bằng prepared statement
        $query_email = "SELECT email FROM taikhoan WHERE id = ?";
        $stmt_email = mysqli_prepare($conn, $query_email);
        if ($stmt_email === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn email: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_email, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt_email)) {
            throw new Exception("Lỗi thực thi truy vấn email: " . mysqli_stmt_error($stmt_email));
        }
        $result_email = mysqli_stmt_get_result($stmt_email);
        $email_row = mysqli_fetch_assoc($result_email);
        $email = $email_row ? $email_row['email'] : '';
        mysqli_stmt_close($stmt_email);

        if (empty($email)) {
            throw new Exception("Không tìm thấy email trong tài khoản.");
        }

        // Lưu đơn hàng vào bảng orders (chỉ dùng user_id và total)
        $query = "INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'pending', NOW())";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn INSERT orders: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'id', $user_id, $total);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi thực thi truy vấn INSERT orders: " . mysqli_stmt_error($stmt));
        }
        $order_id = mysqli_insert_id($conn); 
        mysqli_stmt_close($stmt);
        error_log("Đơn hàng #$order_id được tạo thành công.");

        // Lưu chi tiết đơn hàng vào bảng order_details
        $query = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn INSERT order_details: " . mysqli_error($conn));
        }

        foreach ($_SESSION['cart'] as $product_id => $item) {
            if (isset($item['id'], $item['quantity'], $item['price']) && is_numeric($product_id) && is_numeric($item['quantity']) && is_numeric($item['price'])) {
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                mysqli_stmt_bind_param($stmt, 'iiid', $order_id, $product_id, $quantity, $price);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Lỗi thực thi truy vấn INSERT order_details cho sản phẩm ID $product_id: " . mysqli_stmt_error($stmt));
                }
                error_log("Thêm chi tiết đơn hàng #$order_id - Sản phẩm ID $product_id.");
            } else {
                throw new Exception("Dữ liệu sản phẩm ID $product_id không hợp lệ.");
            }
        }
        mysqli_stmt_close($stmt);

        // Xóa giỏ hàng trong database
        $delete_query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn DELETE cart: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi thực thi truy vấn DELETE cart: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        error_log("Xóa giỏ hàng của user_id $user_id thành công.");

        // Xóa giỏ hàng trong session
        $_SESSION['cart'] = array();

        // Chuyển hướng đến trang xác nhận
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit();
    } catch (Exception $e) {
        $error = "Lỗi khi đặt hàng: " . $e->getMessage();
        error_log("Lỗi đặt hàng: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/checkout-style.css">
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
    <section class="checkout-container">
        <h1 class="section-title">THANH TOÁN</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <div class="checkout-content">
            <!-- Thông tin giao hàng -->
            <div class="shipping-info">
                <h2>Thông tin giao hàng</h2>
                <?php if ($customer): ?>
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($customer['gender'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><a href="profile.php">Chỉnh sửa thông tin</a></p>
                    <form method="POST" action="">
                        <button type="submit" class="place-order-btn">Đặt hàng</button>
                    </form>
                <?php else: ?>
                    <p>Vui lòng <a href="profile.php">cập nhật thông tin cá nhân</a> trước khi thanh toán.</p>
                <?php endif; ?>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="order-summary">
                <h2>Tóm tắt đơn hàng</h2>
                <div class="order-items">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <?php if (!isset($item['id']) || !isset($item['name']) || !isset($item['image']) || !isset($item['price']) || !isset($item['quantity'])) continue; ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="order-item-info">
                                <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                                <p class="quantity">Số lượng: <?php echo $item['quantity']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="order-total">
                    <h3>Tổng tiền: <?php echo number_format($total, 0, ',', '.') . ' VND'; ?></h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>
