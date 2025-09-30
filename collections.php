<?php
session_start();
require_once 'connect.php';

// Định nghĩa project_root
$project_root = 'C:/Program Files (x86)/VertrigoServ/www/LTW';

// Lấy danh sách bộ sưu tập từ database
try {
    $query_collections = "SELECT * FROM collections ORDER BY id DESC";
    $result_collections = mysqli_query($conn, $query_collections);
    if ($result_collections === false) {
        throw new Exception("Lỗi truy vấn collections: " . mysqli_error($conn));
    }
    $collections = array();
    $row_count = mysqli_num_rows($result_collections);
    error_log("Số bản ghi trong collections: " . $row_count); // Debug số bản ghi
    while ($row = mysqli_fetch_assoc($result_collections)) {
        error_log("Collection data: " . json_encode($row)); // Debug dữ liệu từng bản ghi
        $collections[] = $row;
    }
    mysqli_free_result($result_collections);

    error_log("Mảng collections trước khi lấy products: " . json_encode($collections));

    // Lấy sản phẩm cho mỗi bộ sưu tập (bỏ tham chiếu &$collection)
    foreach ($collections as $index => $collection) {
        $query_products = "SELECT * FROM products WHERE collection_id = ? LIMIT 3";
        $stmt = mysqli_prepare($conn, $query_products);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn products: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $collection['id']);
        mysqli_stmt_execute($stmt);
        $result_products = mysqli_stmt_get_result($stmt);
        $products = array();
        while ($row = mysqli_fetch_assoc($result_products)) {
            $products[] = $row;
        }
        $collections[$index]['products'] = $products; // Gán lại products mà không dùng tham chiếu
        mysqli_stmt_close($stmt);
    }

    error_log("Mảng collections sau khi lấy products: " . json_encode($collections));
} catch (Exception $e) {
    error_log("Lỗi lấy danh sách collections hoặc products: " . $e->getMessage());
    $error = "Không thể tải danh sách bộ sưu tập. Vui lòng thử lại sau.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bộ Sưu Tập | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/profile-style.css">
    <style>
        .collections-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .collections-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .collection-card { background-color: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .collection-card:hover { transform: translateY(-5px); }
        .collection-card img { width: 100%; height: 200px; object-fit: cover; }
        .collection-info { padding: 15px; text-align: center; }
        .collection-info h3 { margin: 0; font-size: 1.5rem; color: #d8a1c4; font-family: 'Playfair Display', serif; }
        .collection-info p { margin: 10px 0; color: #666; font-size: 1rem; }
        .product-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .product-list img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .view-details { display: inline-block; padding: 10px 20px; background-color: #d8a1c4; color: white; text-decoration: none; border-radius: 5px; margin: 10px; margin-bottom:0; }
        .view-details:hover { background-color: #c48fb0; }
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
    <section class="collections-container">
        <h1 class="section-title">BỘ SƯU TẬP</h1>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } else { ?>
            <div class="collections-grid">
                <?php if (empty($collections)): ?>
                    <p class="no-data">Chưa có bộ sưu tập nào.</p>
                <?php else: ?>
                    <?php foreach ($collections as $collection): ?>
                        <?php error_log("Hiển thị collection: " . $collection['name']); // Debug hiển thị ?>
                        <div class="collection-card">
                            <?php
                            $base_url = '/LTW/';
                            $display_image = 'https://via.placeholder.com/300x200'; // Mặc định
                            if (isset($collection['image']) && !empty($collection['image'])) {
                                $absolute_image_path = $project_root . DIRECTORY_SEPARATOR . $collection['image'];
                                if (file_exists($absolute_image_path)) {
                                    $display_image = $base_url . $collection['image'];
                                    error_log("Ảnh tồn tại: " . $absolute_image_path);
                                } else {
                                    error_log("Ảnh không tồn tại: " . $absolute_image_path);
                                }
                            } else {
                                error_log("Không có đường dẫn ảnh cho collection: " . $collection['name']);
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($display_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="collection-info">
                                <h3><?php echo htmlspecialchars($collection['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars(substr($collection['description'], 0, 100), ENT_QUOTES, 'UTF-8'); ?>...</p>
                                <div class="product-list">
                                    <?php foreach ($collection['products'] as $product): ?>
                                        <img src="<?php echo htmlspecialchars($base_url . $product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php endforeach; ?>
                                </div>
                                <a href="collection_details.php?id=<?php echo $collection['id']; ?>" class="view-details">Xem chi tiết</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php } ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>