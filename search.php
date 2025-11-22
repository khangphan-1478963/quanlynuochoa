<?php
session_start();
require_once 'connect.php';

// Định nghĩa project_root và base_url
$project_root = 'C:/Program Files (x86)/VertrigoServ/www/LTW';
$base_url = '/LTW/';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Lấy user_id
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Hàm đồng bộ giỏ hàng từ $_SESSION['cart'] vào database (sao chép từ products.php)
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

// Load giỏ hàng từ database nếu $_SESSION['cart'] rỗng (sao chép từ products.php)
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

// Xử lý tìm kiếm
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = array();
$error = '';

if ($search_term !== '') {
    try {
        // Tìm kiếm sản phẩm theo tên hoặc mô tả
        $query = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn search: " . mysqli_error($conn));
        }
        $search_like = '%' . $search_term . '%';
        mysqli_stmt_bind_param($stmt, 'ss', $search_like, $search_like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        error_log("Lỗi trong search: " . $e->getMessage());
        $error = "Không thể thực hiện tìm kiếm. Vui lòng thử lại sau.";
    }
}

// Xử lý thêm sản phẩm vào giỏ hàng (tương tự products.php)
$cart_message = '';
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    error_log("Received product_id: " . $product_id); // Debug
    $found = false;
    $selected_product = null;

    // Tìm sản phẩm theo ID
    foreach ($products as $product) {
        if ($product['id'] == $product_id) {
            $found = true;
            $selected_product = $product;
            break;
        }
    }

    error_log("Product found: " . ($found ? 'Yes' : 'No')); // Debug

    if ($found && $selected_product) {
        if (!$user_id) {
            $cart_message = "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập.";
            echo "<script>
                if (confirm(" . json_encode($cart_message) . ")) {
                    window.location.href = 'login.php';
                }
            </script>";
            exit();
        }

        // Đã đăng nhập, thêm sản phẩm vào giỏ
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1; // Tăng số lượng
        } else {
            // Thêm sản phẩm mới vào giỏ
            $_SESSION['cart'][$product_id] = array(
                'id' => $selected_product['id'],
                'name' => $selected_product['name'],
                'image' => $selected_product['image'],
                'price' => $selected_product['price'] * 1000,
                'quantity' => 1
            );
        }
        // Debug: Kiểm tra giỏ hàng sau khi thêm
        error_log("Cart after adding: " . print_r($_SESSION['cart'], true));
        
        // Đồng bộ giỏ hàng vào database
        if (syncCartToDatabase($conn, $user_id)) {
            $cart_message = "Đã thêm " . htmlspecialchars($selected_product['name'], ENT_QUOTES, 'UTF-8') . " vào giỏ hàng!";
        } else {
            $cart_message = "Lỗi khi đồng bộ giỏ hàng.";
        }
    } else {
        $cart_message = "Sản phẩm không tồn tại.";
    }
    error_log("Cart message: " . $cart_message); // Debug
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm Kiếm Sản Phẩm | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/search-style.css">
    <style>
        /* CSS cho thông báo */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            font-size: 16px;
            opacity: 1;
            transition: opacity 0.5s;
        }
        .notification.error {
            background-color: #d32f2f;
        }
        .notification.hide {
            opacity: 0;
        }
    </style>
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

    <!-- Thông báo -->
    <div id="notification" class="notification"></div>

    <!-- Main Content -->
    <section class="search-container">
        <h1 class="section-title">TÌM KIẾM SẢN PHẨM</h1>
        <form class="search-form" method="GET" action="search.php">
            <input type="text" name="q" placeholder="Nhập tên sản phẩm..." value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
        </form>

        <?php if ($cart_message): ?>
            <p class="cart-message"><?php echo htmlspecialchars($cart_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif ($search_term === ''): ?>
            <p class="no-data">Vui lòng nhập từ khóa để tìm kiếm.</p>
        <?php else: ?>
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p class="no-data">Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" onclick='openModal(<?php echo json_encode($product); ?>)'>
                            <?php
                            $product_image = isset($product['image']) && !empty($product['image']) ? $base_url . $product['image'] : 'https://via.placeholder.com/250x200';
                            error_log("Using image path: " . $product_image); // Debug
                            ?>
                            <img src="<?php echo htmlspecialchars($product_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p>Giá: <?php echo number_format($product['price'] * 1000, 0, ',', '.') ?> VND</p>
                                <p><?php echo htmlspecialchars(substr($product['description'], 0, 50), ENT_QUOTES, 'UTF-8'); ?>...</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <img id="modalImage" src="" alt="Sản phẩm">
            <h3 id="modalName"></h3>
            <p id="modalPrice"></p>
            <p id="modalDescription"></p>
            <form method="POST" action="search.php?q=<?php echo urlencode($search_term); ?>">
                <input type="hidden" name="product_id" id="modalProductId">
                <input type="hidden" name="add_to_cart" value="1">
                <button type="submit" class="add-to-cart">Thêm vào giỏ hàng</button>
            </form>
        </div>
    </div>

    <!-- JavaScript để xử lý modal -->
    <script>
        // Hàm hiển thị thông báo
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.textContent = message;
                notification.className = 'notification' + (isError ? ' error' : '');
                notification.style.display = 'block';
                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.classList.remove('hide');
                    }, 500); // Thời gian fade out
                }, 3000); // Hiển thị 3 giây
            } else {
                console.error('Notification element not found!');
            }
        }

        function openModal(product) {
            const modal = document.getElementById('productModal');
            const modalImage = document.getElementById('modalImage');
            const modalName = document.getElementById('modalName');
            const modalPrice = document.getElementById('modalPrice');
            const modalDescription = document.getElementById('modalDescription');
            const modalProductId = document.getElementById('modalProductId');

            // Sử dụng đường dẫn hình từ database
            let imagePath = product.image ? '<?php echo $base_url; ?>' + product.image : 'https://via.placeholder.com/250x200';
            modalImage.src = imagePath;
            console.log('Image path set to:', imagePath); // Debug

            modalName.textContent = product.name || 'Tên sản phẩm không có';
            modalPrice.textContent = 'Giá: ' + new Intl.NumberFormat('vi-VN').format(product.price * 1000) + ' VND';
            modalDescription.textContent = product.description || 'Mô tả không có';
            modalProductId.value = product.id || 0;

            modal.style.display = 'flex';
            console.log('Modal opened with product_id:', product.id); // Debug
        }

        function closeModal() {
            const modal = document.getElementById('productModal');
            modal.style.display = 'none';
        }

        // Đóng modal khi bấm ra ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Hiển thị thông báo nếu có
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($cart_message): ?>
                showNotification('<?php echo addslashes($cart_message); ?>', <?php echo strpos($cart_message, 'Lỗi') !== false ? 'true' : 'false'; ?>);
            <?php endif; ?>
        });
    </script>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>
