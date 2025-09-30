<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

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
                'price' => $row['price'] * 1000, // Nhân với 1000 thay vì 1 triệu
                'quantity' => $row['quantity']
            );
        }
        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        error_log("Lỗi load giỏ hàng từ database: " . $e->getMessage());
    }
}

try {
    $result = mysqli_query($conn, "SELECT p.*, p.category_id, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
    if (!$result) {
        throw new Exception("Lỗi truy vấn: " . mysqli_error($conn));
    }

    $products = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $row['price'] = $row['price'] * 1000; 
        $products[] = $row;

        error_log("Product: {$row['name']} - Price: {$row['price']} VND");
    }

    // Xử lý thêm sản phẩm vào giỏ hàng
    if (isset($_GET['add'])) {
        $product_id = (int)$_GET['add'];
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

        if ($found && $selected_product) {
            if (!$user_id) {
                // Chưa đăng nhập, hiển thị message box và chuyển hướng
                $message = "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập.";
                echo "<script>
                    if (confirm(" . json_encode($message) . ")) {
                        window.location.href = 'login.php';
                    }
                </script>";
                exit();
            }


            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += 1; 
            } else {
                // Thêm sản phẩm mới vào giỏ
                $_SESSION['cart'][$product_id] = array(
                    'id' => $selected_product['id'],
                    'name' => $selected_product['name'],
                    'image' => $selected_product['image'],
                    'price' => $selected_product['price'],
                    'quantity' => 1
                );
            }

            error_log("Cart after adding: " . print_r($_SESSION['cart'], true));
            

            if (syncCartToDatabase($conn, $user_id)) {

                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showNotification('Đã thêm vào giỏ hàng!');
                    });
                </script>";
            } else {
                $error = "Lỗi khi thêm sản phẩm vào giỏ.";
            }
        } else {
            $error = "Sản phẩm không tồn tại.";
        }
    }
} catch (Exception $e) {
    $error = "Lỗi khi lấy sản phẩm: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/products-style.css">
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
    <section class="products-container">
        <h1 class="section-title">NƯỚC HOA</h1>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

        <div class="filter-container">
            <select id="category-filter" class="filter-select" onchange="filterProducts()">
                <option value="all">Tất cả</option>
                <option value="1">Nước hoa Nam</option>
                <option value="2">Nước hoa Nữ</option>
                <option value="3">Unisex</option>
            </select>

            <select id="price-filter" class="filter-select" onchange="filterProducts()">
                <option value="all">Tất cả giá</option>
                <option value="0-2">Dưới 2 triệu</option>
                <option value="2-5">2 - 5 triệu</option>
                <option value="5-10">5 - 10 triệu</option>
                <option value="10-999">Trên 10 triệu</option>
            </select>

            <!-- Nút Reset -->
            <button id="reset-filter" class="filter-reset">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" onclick="openModal(<?php echo $product['id']; ?>)"
                    data-category-id="<?php echo $product['category_id']; ?>"
                    data-price="<?php echo $product['price']; ?>">
                    <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="price"><?php echo number_format($product['price'], 0, ',', '.') . ' VND'; ?></p>
                    <a href="products.php?add=<?php echo $product['id']; ?>" class="add-to-cart" onclick="event.stopPropagation();">THÊM VÀO GIỎ</a>
                </div>

                <!-- Modal cho sản phẩm -->
                <div id="modal-<?php echo $product['id']; ?>" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal(<?php echo $product['id']; ?>)">×</span>
                        <div class="modal-container">
                            <div class="modal-image">
                                <img src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="modal-info">
                                <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="category"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="description"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="price"><?php echo number_format($product['price'], 0, ',', '.') . ' VND'; ?></p>
                                <a href="products.php?add=<?php echo $product['id']; ?>" class="add-to-cart">THÊM VÀO GIỎ</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>

    <script>

        function showNotification(message) {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.textContent = message;
                notification.style.display = 'block';
                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.classList.remove('hide');
                    }, 500); 
                }, 3000); 
            } else {
                console.error('Notification element not found!');
            }
        }

        // Đảm bảo tất cả modal ban đầu ẩn
        document.addEventListener('DOMContentLoaded', function() {
            var modals = document.getElementsByClassName('modal');
            for (var i = 0; i < modals.length; i++) {
                modals[i].style.display = 'none';
            }

            // Kiểm tra và hiển thị thông báo nếu có
            <?php if (isset($_GET['add']) && $found): ?>
                showNotification('Đã thêm vào giỏ hàng!');
            <?php endif; ?>
        });


        function openModal(id) {
            var modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } else {
                console.log('Modal không tìm thấy cho ID: ' + id);
            }
        }


        function closeModal(id) {
            var modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Đóng modal khi bấm ra ngoài
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                var modals = document.getElementsByClassName('modal');
                for (var i = 0; i < modals.length; i++) {
                    modals[i].style.display = 'none';
                }
                document.body.style.overflow = 'auto';
            }
        }

        // Hàm lọc sản phẩm
        function filterProducts() {
            const selectedCategory = document.getElementById('category-filter').value;
            const selectedPrice = document.getElementById('price-filter').value;
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                const cardCategoryId = card.getAttribute('data-category-id');
                const rawPrice = card.getAttribute('data-price').replace(/,/g, '');
                const cardPrice = parseInt(rawPrice);

                const categoryMatch = 
                    selectedCategory === 'all' || selectedCategory === cardCategoryId;
                
                // Kiểm tra điều kiện giá
                const priceMatch = checkPriceMatch(selectedPrice, cardPrice);
                
                // Hiển thị nếu thỏa cả 2 điều kiện
                card.style.display = (categoryMatch && priceMatch) ? 'block' : 'none';
            });
        }

        function checkPriceMatch(priceRange, productPrice) {
            if (priceRange === 'all') return true;
            
            const priceInVND = productPrice; // Giữ nguyên vì đã là VND
            
            switch(priceRange) {
                case '0-2':
                    return priceInVND < 2000000;
                case '2-5':
                    return priceInVND >= 2000000 && priceInVND <= 5000000;
                case '5-10':
                    return priceInVND > 5000000 && priceInVND <= 10000000;
                case '10-999':
                    return priceInVND > 10000000;
                default:
                    return true;
            }
        }

        document.getElementById('reset-filter').addEventListener('click', function() {
            document.getElementById('category-filter').value = 'all';
            document.getElementById('price-filter').value = 'all';
            filterProducts();
        });

        // Thêm sự kiện load trang để ẩn các sản phẩm không phù hợp
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy query param
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            const price = urlParams.get('price');

            if (category) document.getElementById('category-filter').value = category;
            if (price) document.getElementById('price-filter').value = price;

            try {
                filterProducts();
            } catch (e) {
                console.error("Lỗi khi lọc sản phẩm:", e);
            }
        });
    </script>
</body>
</html>