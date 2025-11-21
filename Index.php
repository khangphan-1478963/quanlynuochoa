<?php 
// File index.php thường không cần session_start() trừ khi bạn muốn hiển thị thông tin người dùng ngay lập tức
// session_start(); 
?>
<!DOCTYPE html>
<html lang="vi"> <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trang Chủ | Jardin Secret - Hương Thơm Tinh Tế</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="css/style.css"> 
  </head>
<body>
  <header>
    <nav aria-label="Điều hướng chính">
        <div class="logo">Jardin Secret</div>
        <div class="nav-links">
            <a href="index.php" aria-current="page">TRANG CHỦ</a>
            <a href="products.php">NƯỚC HOA</a>
            <a href="collections.php">BỘ SƯU TẬP</a>
            <a href="about.php">VỀ CHÚNG TÔI</a>
            <a href="contact.php">LIÊN HỆ</a>
        </div>
        <div class="icons">
            <a href="search.php" aria-label="Tìm kiếm"><i class="fas fa-search"></i></a>
            <a href="profile.php" aria-label="Tài khoản"><i class="fas fa-user"></i></a>
            <a href="cart.php" aria-label="Giỏ hàng"><i class="fas fa-shopping-bag"></i></a>
        </div>
    </nav>
  </header>

  <section class="hero">
    <div class="overlay">
      <h2>Một hương thơm, một khu vườn, một tâm hồn</h2>
      <p class="slogan">Hãy hé lộ bí mật của bạn</p> <a href="products.php" class="btn">Khám phá ngay</a> </div>
  </section>

 <section id="products" class="products">
    <h3>Bộ Sưu Tập Tiêu Biểu</h3> <div class="product-list">
      <div class="product-card">
        <img src="images/perfume1.webp" alt="Fleur de Lune" loading="lazy">
        <h4>Fleur de Lune</h4>
        <p>Hương hoa mềm mại với chút ánh trăng dịu dàng.</p> <a href="product_detail.php?id=1" class="btn-small">Xem chi tiết</a>
      </div>
      <div class="product-card">
        <img src="images/perfume2.jpg" alt="Ambre Mystique" loading="lazy">
        <h4>Ambre Mystique</h4>
        <p>Hổ phách ấm áp quấn quýt trong làn sương vanilla.</p>
        <a href="product_detail.php?id=2" class="btn-small">Xem chi tiết</a>
      </div>
      <div class="product-card">
        <img src="images/perfume3.webp" alt="Rosée du Matin" loading="lazy">
        <h4>Rosée du Matin</h4>
        <p>Tươi mới, đọng sương, và nhẹ nhàng khó cưỡng.</p>
        <a href="product_detail.php?id=3" class="btn-small">Xem chi tiết</a>
      </div>
    </div>
    <div class="view-more">
        <a href="collections.php" class="btn">Xem tất cả bộ sưu tập</a>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 Jardin Secret. All rights reserved.</p>
  </footer>
</body>
</html>