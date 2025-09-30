<?php
session_start();
require_once 'connect.php';

// Định nghĩa project_root và base_url
$project_root = 'C:/Program Files (x86)/VertrigoServ/www/LTW';
$base_url = '/LTW/';

// Lấy id từ URL
$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$collection = null;
$products = array();

try {
    // Lấy thông tin bộ sưu tập
    $query_collection = "SELECT * FROM collections WHERE id = ?";
    $stmt_collection = mysqli_prepare($conn, $query_collection);
    if ($stmt_collection === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn collection: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_collection, 'i', $collection_id);
    mysqli_stmt_execute($stmt_collection);
    $result_collection = mysqli_stmt_get_result($stmt_collection);
    $collection = mysqli_fetch_assoc($result_collection);
    mysqli_stmt_close($stmt_collection);

    // Lấy danh sách sản phẩm thuộc bộ sưu tập
    $query_products = "SELECT * FROM products WHERE collection_id = ? ORDER BY id DESC";
    $stmt_products = mysqli_prepare($conn, $query_products);
    if ($stmt_products === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn products: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_products, 'i', $collection_id);
    mysqli_stmt_execute($stmt_products);
    $result_products = mysqli_stmt_get_result($stmt_products);
    while ($row = mysqli_fetch_assoc($result_products)) {
        $products[] = $row;
    }
    mysqli_stmt_close($stmt_products);

    if (!$collection) {
        throw new Exception("Bộ sưu tập không tồn tại.");
    }
} catch (Exception $e) {
    error_log("Lỗi trong collection_details: " . $e->getMessage());
    $error = "Không thể tải thông tin bộ sưu tập. Vui lòng thử lại sau.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Bộ Sưu Tập | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/profile-style.css">
    <style>
        .details-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .collection-header { text-align: center; margin-bottom: 20px; }
        .collection-header img { width: 100%; max-width: 600px; height: auto; object-fit: cover; border-radius: 10px; }
        .collection-header h2 { font-size: 2rem; color: #d8a1c4; font-family: 'Playfair Display', serif; margin: 10px 0; }
        .collection-header p { color: #666; font-size: 1.2rem; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background-color: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; }
        .product-info { padding: 15px; text-align: center; }
        .product-info h3 { margin: 0; font-size: 1.5rem; color: #d8a1c4; }
        .product-info p { margin: 5px 0; color: #666; }
        .no-data { text-align: center; color: #666; font-size: 1.2rem; margin: 20px 0; }
        .error { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
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
                <a href="search.php"><i class="fas fa-search"></i></a>
                <a href="profile.php"><i class="fas fa-user"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="details-container">
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif ($collection): ?>
            <div class="collection-header">
                <?php
                $display_image = isset($collection['image']) && !empty($collection['image']) ? $base_url . $collection['image'] : 'https://via.placeholder.com/600x300';
                error_log("Collection image path: " . $display_image); // Debug
                ?>
                <img src="<?php echo htmlspecialchars($display_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <h2><?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo htmlspecialchars($collection['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p class="no-data">Không có sản phẩm nào trong bộ sưu tập này.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php
                            $product_image = isset($product['image']) && !empty($product['image']) ? $base_url . $product['image'] : 'https://via.placeholder.com/250x200';
                            error_log("Product image path: " . $product_image); // Debug
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

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>