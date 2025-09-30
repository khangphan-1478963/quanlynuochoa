<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Hàm đồng bộ giỏ hàng từ $_SESSION['cart'] vào database
function syncCartToDatabase($conn, $user_id) {
    try {
        if (!$user_id) {
            throw new Exception("Không thể đồng bộ giỏ hàng: Người dùng chưa đăng nhập.");
        }

        // Xóa giỏ hàng cũ của user trong database
        $delete_query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị truy vấn DELETE: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi thực thi truy vấn DELETE: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        // Lưu giỏ hàng từ $_SESSION['cart'] vào database
        if (!empty($_SESSION['cart'])) {
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            if (!$stmt) {
                throw new Exception("Lỗi chuẩn bị truy vấn INSERT: " . mysqli_error($conn));
            }
            
            foreach ($_SESSION['cart'] as $product_id => $item) {
                if (isset($item['id'], $item['quantity']) && $item['quantity'] > 0) {
                    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $product_id, $item['quantity']);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Lỗi thực thi truy vấn INSERT: " . mysqli_stmt_error($stmt));
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        error_log("Lỗi đồng bộ giỏ hàng: " . $e->getMessage());
        return false;
    }
    return true;
}

// Load giỏ hàng từ database nếu $_SESSION['cart'] rỗng
if ($user_id && empty($_SESSION['cart'])) {
    try {
        $query = "SELECT c.product_id, c.quantity, p.id, p.name, p.image, p.price 
                 FROM cart c 
                 JOIN products p ON c.product_id = p.id 
                 WHERE c.user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị truy vấn SELECT: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Lỗi thực thi truy vấn SELECT: " . mysqli_stmt_error($stmt));
        }
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['cart'][$row['product_id']] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'image' => $row['image'],
                'price' => $row['price'] * 1000,
                'quantity' => $row['quantity']
            );
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        error_log("Lỗi load giỏ hàng từ database: " . $e->getMessage());
    }
}

if (!$user_id && !empty($_SESSION['cart'])) {
    // Nếu chưa đăng nhập nhưng có giỏ hàng trong session, xóa session để tránh lỗi
    $_SESSION['cart'] = array();
    $error = "Vui lòng đăng nhập để xem giỏ hàng.";
}

// Xử lý tăng số lượng
if (isset($_GET['increase'])) {
    $product_id = (int)$_GET['increase'];
    if ($user_id && isset($_SESSION['cart'][$product_id]) && isset($_SESSION['cart'][$product_id]['quantity'])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
        if (syncCartToDatabase($conn, $user_id)) {
            header('Location: cart.php');
            exit();
        } else {
            $error = "Lỗi khi cập nhật số lượng sản phẩm.";
        }
    } else {
        $error = "Vui lòng đăng nhập để chỉnh sửa giỏ hàng.";
    }
}

// Xử lý giảm số lượng
if (isset($_GET['decrease'])) {
    $product_id = (int)$_GET['decrease'];
    if ($user_id && isset($_SESSION['cart'][$product_id]) && isset($_SESSION['cart'][$product_id]['quantity'])) {
        $_SESSION['cart'][$product_id]['quantity'] -= 1;
        if ($_SESSION['cart'][$product_id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$product_id]);
        }
        if (syncCartToDatabase($conn, $user_id)) {
            header('Location: cart.php');
            exit();
        } else {
            $error = "Lỗi khi cập nhật số lượng sản phẩm.";
        }
    } else {
        $error = "Vui lòng đăng nhập để chỉnh sửa giỏ hàng.";
    }
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if ($user_id && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        if (syncCartToDatabase($conn, $user_id)) {
            header('Location: cart.php');
            exit();
        } else {
            $error = "Lỗi khi xóa sản phẩm.";
        }
    } else {
        $error = "Vui lòng đăng nhập để xóa sản phẩm.";
    }
}

// Tính tổng tiền
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $total += $item['price'] * $item['quantity'];
        }
    }
}

// Debug: Kiểm tra cấu trúc giỏ hàng
error_log("Cart content: " . print_r($_SESSION['cart'], true));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/cart-style.css">
</head>
<body>
    <!-- Header -->
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
                <a href="search.php"><i class="fas fa-search"></i></a>
                <a href="profile.php"><i class="fas fa-user"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="cart-container">
        <h1 class="section-title">GIỎ HÀNG</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <?php if (!$user_id): ?>
            <p class="empty-cart">Vui lòng <a href="login.php">đăng nhập</a> để xem giỏ hàng.</p>
        <?php elseif (empty($_SESSION['cart'])): ?>
            <p class="empty-cart">Giỏ hàng của bạn đang trống.</p>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <?php if (!isset($item['id']) || !isset($item['name']) || !isset($item['image']) || !isset($item['price']) || !isset($item['quantity'])) continue; ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="cart-item-info">
                            <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price"><?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            <div class="quantity-controls">
                                <a href="cart.php?decrease=<?php echo $item['id']; ?>" class="quantity-btn">-</a>
                                <span class="quantity"><?php echo $item['quantity']; ?></span>
                                <a href="cart.php?increase=<?php echo $item['id']; ?>" class="quantity-btn">+</a>
                            </div>
                            <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-item">Xóa</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-total">
                <h3>Tổng tiền: <?php echo number_format($total, 0, ',', '.') . ' VND'; ?></h3>
                <a href="checkout.php" class="checkout-btn">Thanh toán</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>