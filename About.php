<?php


$title = "Về Chúng Tôi - Jardin Secret";
$description = "Khám phá câu chuyện, sứ mệnh và đội ngũ của Jardin Secret – nơi mang đến những trải nghiệm hương thơm độc đáo và tinh tế.";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:image" content="https://nuochoatinhte.vn/images/logo.png">
    <meta property="og:url" content="https://nuochoatinhte.vn/about.php">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://nuochoatinhte.vn/about.php">
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Lobster&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/about-style.css"> 
</head>
<body>
    <header>
        <nav aria-label="Điều hướng chính">
            <div class="logo">Jardin Secret</div>
            <div class="nav-links">
                <a href="dashboard.php">TRANG CHỦ</a>
                <a href="products.php">NƯỚC HOA</a>
                <a href="collections.php">BỘ SƯU TẬP</a>
                <a href="about.php" aria-current="page">VỀ CHÚNG TÔI</a> <a href="contact.php">LIÊN HỆ</a>
            </div>
            <div class="icons">
                <a href="search.php" aria-label="Tìm kiếm"><i class="fas fa-search"></i></a>
                <a href="profile.php" aria-label="Tài khoản"><i class="fas fa-user"></i></a>
                <a href="cart.php" aria-label="Giỏ hàng"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </header>
    <div class="header-content">
        <h1>VỀ CHÚNG TÔI</h1>
        <p class="slogan">Khám phá hương thơm - Chạm đến cảm xúc</p>
    </div>
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="#who-we-are">Chúng Tôi Là Ai?</a></li>
                <li><a href="#our-mission">Sứ Mệnh Của Chúng Tôi</a></li>
                <li><a href="#core-values">Giá Trị Cốt Lõi</a></li>
                <li><a href="#our-journey">Hành Trình Của Chúng Tôi</a></li>
                <li><a href="#team">Đội Ngũ</a></li> </ul>
        </div>
    </nav>

    <section class="about-us" id="about-us">
        <div class="container">
            <h2 id="who-we-are">Chúng tôi là ai?</h2>
            <p>
                Chào mừng bạn đến với <strong>Jardin Secret</strong> – nơi hương thơm kể những câu chuyện. Chúng tôi là một đội ngũ đam mê với sứ mệnh mang đến những trải nghiệm hương thơm độc đáo, tinh tế và đầy cảm xúc. Với hơn 10 năm kinh nghiệm trong ngành nước hoa, chúng tôi tự hào giới thiệu những dòng sản phẩm cao cấp được tuyển chọn từ các thương hiệu nổi tiếng trên toàn thế giới, cùng với những sáng tạo độc quyền mang đậm dấu ấn cá nhân.
            </p>
            <p>
                Mỗi chai nước hoa tại đây không chỉ là một mùi hương, mà còn là một hành trình cảm xúc, giúp bạn khám phá bản thân và thể hiện phong cách riêng. Chúng tôi tin rằng, một mùi hương phù hợp có thể thay đổi tâm trạng, khơi dậy ký ức và tạo nên những khoảnh khắc đáng nhớ.
            </p>

            <h2 id="our-mission">Sứ mệnh của chúng tôi</h2>
            <p>
                Tại Jardin Secret, sứ mệnh của chúng tôi là lan tỏa vẻ đẹp và cá tính qua từng giọt hương. Chúng tôi không chỉ cung cấp nước hoa, mà còn mang đến những giải pháp hương thơm cá nhân hóa, giúp bạn tìm thấy mùi hương định nghĩa chính mình. Chúng tôi cam kết đồng hành cùng bạn trong hành trình khám phá thế giới hương thơm, từ những nốt hương cổ điển đến những sáng tạo hiện đại đầy đột phá.
            </p>

            <h2 id="core-values">Giá trị cốt lõi</h2>
            <div class="core-values">
                <ul>
                    <li>**Chất lượng là ưu tiên hàng đầu:** Mọi sản phẩm đều được kiểm định kỹ lưỡng để đảm bảo chất lượng tốt nhất.</li>
                    <li>**Khách hàng là trung tâm:** Chúng tôi lắng nghe và đáp ứng nhu cầu của từng khách hàng với sự tận tâm.</li>
                    <li>**Phong cách và đẳng cấp:** Mỗi sản phẩm là một tác phẩm nghệ thuật, tôn vinh cái đẹp và sự tinh tế.</li>
                    <li>**Chân thành và tin cậy:** Chúng tôi xây dựng niềm tin với khách hàng qua sự minh bạch và tận tụy.</li>
                </ul>
            </div>

            <h2 id="our-journey">Hành trình của chúng tôi</h2>
            <p>
                Thành lập vào năm 2025, Jardin Secret bắt đầu từ niềm đam mê mãnh liệt với hương thơm của nhà sáng lập – **Phan Hoàng Khang**. Từ một cửa hàng nhỏ tại TP. Hồ Chí Minh, chỉ trong vài tháng (tính đến ngày 18/05/2025), chúng tôi đã thu hút hơn 300 khách hàng tin tưởng. Chúng tôi đang hợp tác với các nhà chế tác nước hoa hàng đầu để mang đến những sản phẩm độc đáo, phù hợp với thị hiếu và phong cách của người Việt.
            </p>

            <div class="image-gallery">
                <img src="images/autumn.webp" alt="Bộ sưu tập nước hoa cao cấp trên kệ trưng bày" loading="lazy">
                <img src="images/hinh1.jpg" alt="Quá trình chế tác nước hoa thủ công" loading="lazy">
            </div>
        </div>
    </section>

    <section class="products" id="products">
        <h2>Bộ Sưu Tập Nước Hoa</h2>
        <div class="product-list">
            <div class="product-card">
                <img src="images/aodai.webp" alt="Nước hoa Floral Elegance" loading="lazy">
                <h4>Floral Elegance</h4>
                <p>Mùi hương hoa cỏ thanh lịch, phù hợp cho những buổi hẹn hò lãng mạn.</p>
            </div>
            <div class="product-card">
                <img src="images/wood.jpg" alt="Nước hoa Woody Charm" loading="lazy">
                <h4>Woody Charm</h4>
                <p>Hương gỗ ấm áp, mạnh mẽ, dành cho những cá tính nổi bật.</p>
            </div>
            <div class="product-card">
                <img src="images/cam.webp" alt="Nước hoa Citrus Breeze" loading="lazy">
                <h4>Citrus Breeze</h4>
                <p>Nốt hương cam chanh tươi mát, mang lại cảm giác sảng khoái mỗi ngày.</p>
            </div>
        </div>
    </section>

    <section class="team" id="team">
        <div class="container">
            <h2>Đội Ngũ</h2>
            <div class="team-section">
                <div class="team-member">
                    <img src="images/avaKK.jpg" alt="Ảnh Phan Hoàng Khang - Nhà sáng lập" loading="lazy">
                    <div>
                        <h4>Phan Hoàng Khang</h4>
                        <p>Nhà sáng lập & Chuyên gia hương thơm</p>
                        <p>Với niềm đam mê mãnh liệt dành cho nước hoa, **Phan Hoàng Khang** đã dẫn dắt Jardin Secret trở thành một thương hiệu uy tín, mang đến những mùi hương độc đáo.</p>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/avaT.jpg" alt="Ảnh Ngô Thị Minh Thư - Chuyên gia nước hoa" loading="lazy">
                    <div>
                        <h4>Ngô Thị Minh Thư</h4>
                        <p>Chuyên gia nước hoa</p>
                        <p>Minh Thư có hơn 8 năm kinh nghiệm, từng hợp tác với nhiều thương hiệu quốc tế để tạo ra những mùi hương độc quyền.</p>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/avaH.jpg" alt="Ảnh Nguyễn Thị Bé Hằng - Chuyên gia nước hoa" loading="lazy">
                    <div>
                        <h4>Nguyễn Thị Bé Hằng</h4>
                        <p>Chuyên gia nước hoa</p>
                        <p>Hằng mang đến sự sáng tạo với hơn 5 năm kinh nghiệm, làm việc với các thương hiệu nước hoa cao cấp.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <div class="container">
            <h2>Liên Hệ</h2>
            <p>Để được tư vấn hoặc đặt hàng, vui lòng liên hệ qua:</p>
            <p>Email: <a href="mailto:info@nuochoatinhte.vn">info@nuochoatinhte.vn</a></p>
            <p>Điện thoại: <a href="tel:+84987654321">0987 654 321</a></p>
            <a href="mailto:info@nuochoatinhte.vn" class="btn">Gửi Email Ngay</a>
        </div>
    </section>

    <footer>
        <p>© <?php echo date("Y"); ?> Jardin Secret. All rights reserved.</p>
    </footer>

    <script>
        // Giữ nguyên script smooth scroll, nhưng sửa lại biến 'totalOffset' để tính toán chuẩn hơn
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const navbarHeight = document.querySelector('.navbar').offsetHeight; // Tính thêm chiều cao thanh điều hướng nội bộ
                    const extraOffset = 10; // Giảm offset thêm 1 chút
                    const totalOffset = headerHeight + navbarHeight + extraOffset;
                    
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - totalOffset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    history.pushState(null, null, targetId);
                }
            });
        });
    </script>
</body>
</html>
