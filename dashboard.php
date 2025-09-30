<?php
session_start();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jardin Secret | Main</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard-style.css">
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

    <!-- Main Video Content -->
    <section class="hero">
        <div class="video-container">
            <!-- Video 1: Nước hoa nam -->
            <div class="video-box">
               <video id="video1" muted playsinline>
                    <source src="videos/MalePerfume.mp4" type="video/mp4">
                </video>
                <div class="video-overlay">
                    <h2>DÀNH CHO NAM</h2>
                    <p>Khám phá bộ sưu tập nước hoa nam sang trọng với hương thơm quyến rũ</p>
                </div>
            </div>
            
            <!-- Video 2: Nước hoa nữ -->
            <div class="video-box">
                <video id="video2" muted playsinline>
                    <source src="videos/FemalePerfume.mp4" type="video/mp4">
                </video>
                <div class="video-overlay">
                    <h2>DÀNH CHO NỮ</h2>
                    <p>Những chai nước hoa tinh tế dành cho phái đẹp</p>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer>
        <p>© <?php echo date("Y"); ?> LUXE PERFUME. All rights reserved.</p>
    </footer>

    <script src="js/dashboard-script.js"></script>
</body>
</html>