<?php
session_start();
require_once 'connect.php';

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug cấu hình PHP
error_log("PHP upload_max_filesize: " . ini_get('upload_max_filesize'));
error_log("PHP post_max_size: " . ini_get('post_max_size'));
error_log("PHP file_uploads: " . ini_get('file_uploads'));

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    session_destroy();
    header('Location: login.php');
    exit();
}

// Kiểm tra đăng nhập
if (!$user_id) {
    $message = "Bạn chưa đăng nhập tài khoản. Vui lòng đăng nhập.";
    echo "<script>
        if (confirm(" . json_encode($message) . ")) {
            window.location.href = 'login.php';
        } else {
            window.location.href = 'products.php';
        }
    </script>";
    exit();
}

// Lấy thông tin từ cả taikhoan và customers
try {
    // Lấy email từ taikhoan
    $query_taikhoan = "SELECT email FROM taikhoan WHERE id = ?";
    $stmt_taikhoan = mysqli_prepare($conn, $query_taikhoan);
    if ($stmt_taikhoan === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn taikhoan: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_taikhoan, 'i', $user_id);
    mysqli_stmt_execute($stmt_taikhoan);
    $result_taikhoan = mysqli_stmt_get_result($stmt_taikhoan);
    $taikhoan = mysqli_fetch_assoc($result_taikhoan);
    mysqli_stmt_close($stmt_taikhoan);

    // Lấy thông tin từ customers
    $query_customers = "SELECT * FROM customers WHERE user_id = ?";
    $stmt_customers = mysqli_prepare($conn, $query_customers);
    if ($stmt_customers === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn customers: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_customers, 'i', $user_id);
    mysqli_stmt_execute($stmt_customers);
    $result_customers = mysqli_stmt_get_result($stmt_customers);
    $customers_data = mysqli_fetch_assoc($result_customers);
    mysqli_stmt_close($stmt_customers);

    if (!$customers_data) {
        // Nếu chưa có thông tin, tạo bản ghi mặc định
        $query = "INSERT INTO customers (user_id, full_name, address, phone, gender, avatar) VALUES (?, '', '', '', 'other', NULL)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị truy vấn INSERT: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: profile.php');
        exit();
    }
} catch (Exception $e) {
    $error = "Lỗi khi lấy thông tin: " . $e->getMessage();
    error_log("Lỗi lấy thông tin: " . $e->getMessage());
}

// Lấy dữ liệu cho các khung
try {
    // 1. Đơn hàng đã tạo (orders)
    $query_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3";
    $stmt_orders = mysqli_prepare($conn, $query_orders);
    if ($stmt_orders === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn orders: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_orders, 'i', $user_id);
    mysqli_stmt_execute($stmt_orders);
    $result_orders = mysqli_stmt_get_result($stmt_orders);
    $orders = array();
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt_orders);

    // 2. Sản phẩm trong giỏ hàng (cart)
    $query_cart = "SELECT c.product_id, c.quantity, p.name, p.image, p.price 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = ? LIMIT 3";
    $stmt_cart = mysqli_prepare($conn, $query_cart);
    if ($stmt_cart === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn cart: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_cart, 'i', $user_id);
    mysqli_stmt_execute($stmt_cart);
    $result_cart = mysqli_stmt_get_result($stmt_cart);
    $cart_items = array();
    while ($row = mysqli_fetch_assoc($result_cart)) {
        $row['price'] = $row['price'] * 1000; // Nhân giá với 1000 (theo logic trước)
        $cart_items[] = $row;
    }
    mysqli_stmt_close($stmt_cart);
} catch (Exception $e) {
    $error = "Lỗi khi lấy dữ liệu khung: " . $e->getMessage();
    error_log("Lỗi lấy dữ liệu khung: " . $e->getMessage());
}

// Xử lý cập nhật thông tin và upload avatar khi ở chế độ chỉnh sửa
$edit_mode = false;
$upload_message = '';
if (isset($_POST['edit'])) {
    $edit_mode = true;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : 'other';

    // Xử lý upload avatar
    $avatar_path = isset($customers_data['avatar']) ? $customers_data['avatar'] : null; // Giữ ảnh cũ nếu không upload ảnh mới
    $upload_success = false; // Biến để kiểm tra upload thành công
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];

        error_log("File upload info: " . json_encode($_FILES['avatar'])); // Debug file upload

        if (!in_array($file_type, $allowed_types)) {
            $upload_message = "Chỉ hỗ trợ file JPG, PNG, JPEG.";
        } elseif ($file_size > $max_size) {
            $upload_message = "File ảnh vượt quá 2MB.";
        } else {
            $upload_dir = 'uploads/avatars/';
            $file_name = time() . '_' . basename($_FILES['avatar']['name']);
            $upload_file = $upload_dir . $file_name;

            // Đường dẫn tuyệt đối từ thư mục gốc của dự án
            $project_root = 'C:/Program Files (x86)/VertrigoServ/www/LTW'; // Khớp với đường dẫn thực tế
            $absolute_upload_dir = $project_root . DIRECTORY_SEPARATOR . $upload_dir;
            $absolute_upload_file = $project_root . DIRECTORY_SEPARATOR . $upload_file;

            error_log("Thư mục upload (tuyệt đối): " . $absolute_upload_dir);
            error_log("File upload (tuyệt đối): " . $absolute_upload_file);

            if (!is_dir($absolute_upload_dir)) {
                if (mkdir($absolute_upload_dir, 0777, true)) {
                    error_log("Thư mục $absolute_upload_dir đã được tạo.");
                } else {
                    $upload_message = "Không thể tạo thư mục upload!";
                    error_log("Lỗi khi tạo thư mục $absolute_upload_dir: " . var_export(error_get_last(), true));
                }
            }

            if (is_writable($absolute_upload_dir)) {
                error_log("Thư mục $absolute_upload_dir có quyền ghi.");
            } else {
                $upload_message = "Thư mục $absolute_upload_dir không có quyền ghi!";
                error_log("Thư mục $absolute_upload_dir KHÔNG có quyền ghi!");
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $absolute_upload_file)) {
                if (file_exists($absolute_upload_file)) {
                    $avatar_path = $upload_file; // Lưu đường dẫn tương đối vào database
                    $upload_success = true;
                    error_log("Upload thành công, đường dẫn: $avatar_path");
                } else {
                    $upload_message = "File không tồn tại sau khi upload!";
                    error_log("File $absolute_upload_file KHÔNG tồn tại sau khi upload!");
                }
            } else {
                $upload_message = "Lỗi khi upload ảnh: " . print_r(error_get_last(), true);
                error_log("Lỗi move_uploaded_file: " . var_export(error_get_last(), true));
            }
        }
    } else {
        if (isset($_FILES['avatar'])) {
            error_log("Không có file upload hoặc có lỗi: " . $_FILES['avatar']['error'] . " - Mã lỗi: " . $_FILES['avatar']['error']);
        } else {
            error_log("Không có file upload được gửi lên (avatar không tồn tại trong form).");
        }
    }

    // Debug giá trị trước khi update
    error_log("Giá trị avatar_path trước khi update: " . (isset($avatar_path) ? $avatar_path : 'NULL'));
    error_log("Giá trị user_id: $user_id");
    error_log("Toàn bộ POST data: " . json_encode($_POST));
    error_log("Toàn bộ FILES data: " . json_encode($_FILES));

    // Cập nhật database
    if (empty($upload_message)) {
        if (empty($full_name) || empty($address) || empty($phone)) {
            $upload_message = "Vui lòng nhập đầy đủ thông tin.";
        } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $upload_message = "Số điện thoại không hợp lệ.";
        } else {
            try {
                $query = "UPDATE customers SET full_name = ?, address = ?, phone = ?, gender = ?, avatar = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                if ($stmt === false) {
                    throw new Exception("Lỗi chuẩn bị truy vấn UPDATE: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, 'sssssi', $full_name, $address, $phone, $gender, $avatar_path, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    // Kiểm tra số hàng ảnh hưởng
                    $affected_rows = mysqli_stmt_affected_rows($stmt);
                    error_log("Số hàng ảnh hưởng bởi UPDATE: $affected_rows");
                    if ($affected_rows > 0 || $upload_success) {
                        // Cập nhật session avatar
                        if ($avatar_path && $upload_success) {
                            $_SESSION['avatar'] = $avatar_path;
                        }
                        $upload_message = "Cập nhật thành công!";
                        $edit_mode = false;
                    } else {
                        $upload_message = "Không có thay đổi nào được thực hiện.";
                    }

                    // Debug: Kiểm tra giá trị avatar sau khi cập nhật
                    $query_check = "SELECT avatar FROM customers WHERE user_id = ?";
                    $stmt_check = mysqli_prepare($conn, $query_check);
                    mysqli_stmt_bind_param($stmt_check, 'i', $user_id);
                    mysqli_stmt_execute($stmt_check);
                    $result_check = mysqli_stmt_get_result($stmt_check);
                    $updated_customers_data = mysqli_fetch_assoc($result_check);
                    error_log("Avatar trong database sau khi update: " . (isset($updated_customers_data['avatar']) ? $updated_customers_data['avatar'] : 'NULL'));
                    mysqli_stmt_close($stmt_check);

                    // Làm mới dữ liệu khách hàng sau khi cập nhật
                    $query_customers = "SELECT * FROM customers WHERE user_id = ?";
                    $stmt_customers = mysqli_prepare($conn, $query_customers);
                    mysqli_stmt_bind_param($stmt_customers, 'i', $user_id);
                    mysqli_stmt_execute($stmt_customers);
                    $result_customers = mysqli_stmt_get_result($stmt_customers);
                    $customers_data = mysqli_fetch_assoc($result_customers);
                    mysqli_stmt_close($stmt_customers);
                } else {
                    throw new Exception("Lỗi thực thi truy vấn UPDATE: " . mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            } catch (Exception $e) {
                $upload_message = "Lỗi khi cập nhật thông tin: " . $e->getMessage();
                error_log("Lỗi cập nhật thông tin: " . $e->getMessage());
            }
        }
    } else {
        $upload_message = "Lỗi khi xử lý upload: " . $upload_message;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel'])) {
    $edit_mode = false;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân | Jardin Secret</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/profile-style.css">
    <style>
        .error { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
        .success { color: #2e7d32; text-align: center; margin-bottom: 20px; font-size: 1.5rem; }
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
                <a href="?logout=1" style="color: #8b4e75; text-decoration: none; margin-left: 10px;">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="profile-container">
        <h1 class="section-title">THÔNG TIN CÁ NHÂN</h1>
        <?php if (!empty($upload_message)) {
            echo "<p class='" . (strpos($upload_message, 'thành công') !== false ? 'success' : 'error') . "'>$upload_message</p>";
            if (strpos($upload_message, 'thành công') !== false) {
                echo "<script>alert('$upload_message'); window.location.href='profile.php';</script>";
            } elseif (strpos($upload_message, 'lỗi') !== false) {
                echo "<script>alert('$upload_message'); history.back();</script>";
            }
        } ?>

        <div class="profile-content">
            <?php if (!$edit_mode): ?>
                <!-- Hiển thị thông tin tĩnh -->
                <div class="avatar-section">
                    <div class="avatar-frame">
                        <?php
                        // Debug giá trị từ database
                        error_log("Giá trị avatar từ database: " . (isset($customers_data['avatar']) ? $customers_data['avatar'] : 'NULL'));
                        $base_url = '/LTW/'; // Điều chỉnh theo đường dẫn gốc của má (dự án ở /LTW/)
                        $display_avatar = 'https://via.placeholder.com/150'; // Mặc định
                        if (isset($customers_data['avatar']) && !empty($customers_data['avatar'])) {
                            $display_avatar = $base_url . $customers_data['avatar'];
                            error_log("Đường dẫn hiển thị: $display_avatar");
                        } else {
                            error_log("Avatar không có trong database hoặc rỗng.");
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($display_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="avatar-img">
                    </div>
                </div>
                <div class="info-display">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($taikhoan['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($customers_data['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($customers_data['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($customers_data['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($customers_data['gender'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="POST" action="">
                        <button type="submit" name="edit" class="save-btn">Sửa</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Form chỉnh sửa -->
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="avatar-section">
                        <div class="avatar-frame">
                            <?php
                            // Debug giá trị từ database trong edit mode
                            error_log("Giá trị avatar từ database (edit mode): " . (isset($customers_data['avatar']) ? $customers_data['avatar'] : 'NULL'));
                            $base_url = '/LTW/'; // Điều chỉnh theo đường dẫn gốc của má
                            $display_avatar = 'https://via.placeholder.com/150'; // Mặc định
                            if (isset($customers_data['avatar']) && !empty($customers_data['avatar'])) {
                                $display_avatar = $base_url . $customers_data['avatar'];
                                error_log("Đường dẫn hiển thị (edit mode): $display_avatar");
                            } else {
                                error_log("Avatar không có trong database hoặc rỗng (edit mode).");
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($display_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="avatar-img">
                        </div>
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/jpg" class="avatar-input">
                        <label for="avatar" class="avatar-label">Chọn ảnh mới</label>
                    </div>
                    <label for="full_name">Họ và tên:</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($customers_data['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required <?php echo $edit_mode ? '' : 'readonly'; ?>>

                    <label for="address">Địa chỉ:</label>
                    <textarea name="address" id="address" required <?php echo $edit_mode ? '' : 'readonly'; ?>><?php echo htmlspecialchars($customers_data['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>

                    <label for="phone">Số điện thoại:</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($customers_data['phone'], ENT_QUOTES, 'UTF-8'); ?>" required <?php echo $edit_mode ? '' : 'readonly'; ?>>

                    <label for="gender">Giới tính:</label>
                    <select name="gender" id="gender" required <?php echo $edit_mode ? '' : 'disabled'; ?>>
                        <option value="male" <?php echo $customers_data['gender'] == 'male' ? 'selected' : ''; ?>>Nam</option>
                        <option value="female" <?php echo $customers_data['gender'] == 'female' ? 'selected' : ''; ?>>Nữ</option>
                        <option value="other" <?php echo $customers_data['gender'] == 'other' ? 'selected' : ''; ?>>Khác</option>
                    </select>

                    <div class="button-group">
                        <button type="submit" name="save" class="save-btn">Lưu</button>
                        <button type="submit" name="cancel" class="save-btn" style="background: #b37b9e;">Hủy</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Khung Đơn hàng đã tạo -->
        <div class="section-titlee">ĐƠN HÀNG GẦN ĐÂY</div>
        <div class="orders-grid">
            <?php if (empty($orders)): ?>
                <p class="no-data">Bạn chưa có đơn hàng nào.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h3>Đơn hàng #<?php echo htmlspecialchars($order['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><strong>Ngày tạo:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></p>
                        <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="view-details">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Khung Sản phẩm trong giỏ hàng -->
        <div class="section-titlee">SẢN PHẨM TRONG GIỎ HÀNG</div>
        <div class="cart-grid">
            <?php if (empty($cart_items)): ?>
                <p class="no-data">Giỏ hàng của bạn đang trống.</p>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" class="cart-img">
                        <div class="cart-info">
                            <h4><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p>Giá: <?php echo number_format($item['price'], 0, ',', '.') . ' VND'; ?></p>
                            <p>Số lượng: <?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="remove-item">Xóa</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>
</body>
</html>