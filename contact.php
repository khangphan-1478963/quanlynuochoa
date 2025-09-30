<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - Jadin Secret</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
     <link rel="stylesheet" href="css/contact-style.css">
    <style>
       
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
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1>Liên hệ với chúng tôi</h1>
            <p>Jadin Secret luôn sẵn sàng lắng nghe và hỗ trợ quý khách mọi lúc, mọi nơi</p>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="container mb-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="contact-box">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Điện thoại</h3>
                    <p>028 999 2222</p>
                    <p>Hotline: 0909 123 456</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="contact-box">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email</h3>
                    <p><a href="mailto:jadinsecret2015@gmail.com">jadinsecret2015@gmail.com</a></p>
                    <p><a href="mailto:support@jadinsecret.com">support@jadinsecret.com</a></p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="contact-box">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Khung giờ hỗ trợ trực tuyến</h3>
                    <p>Thứ 2 - Thứ 6: 8:00 - 20:00</p>
                    <p>Thứ 7 - CN: 9:00 - 18:00</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Store Locations -->
    <section class="container mb-5">
        <h2 class="text-center section-title">Hệ thống cửa hàng</h2>
        <div class="row mt-5">
            <div class="col-md-6 mb-4">
                <!-- Thêm phần tìm kiếm -->
                <div class="search-container">
                    <select id="cityFilter">
                        <option value="">Chọn Thành phố</option>
                        <option value="Hà Nội">Hà Nội</option>
                        <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                        <option value="Shanghai">Shanghai</option>
                        <option value="London">London</option>
                        <option value="New York">New York</option>
                        <option value="Barcelona">Barcelona</option>
                        <option value="Tokyo">Tokyo</option>
                        <option value="Paris">Paris</option>
                        <option value="São Paulo">São Paulo</option>
                    </select>
                    <select id="districtFilter">
                        <option value="">Chọn Quận/Huyện</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Nhập tên đường, hộp cửa hàng...">
                    <button onclick="filterStores()">🔍</button>
                </div>
                <div class="store-list-container">
                    <p id="storeCount">Tổng số cửa hàng: <span id="storeCountNumber">33</span></p>
                    <ul class="store-list" id="storeList">
                        <!-- Danh sách cửa hàng sẽ được tạo động bởi JavaScript -->
                    </ul>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="map-container">
                    <iframe 
                        id="mapIframe" 
                        src="https://www.google.com/maps?q=Quận+1,+Sài+Gòn,+Vietnam&output=embed" 
                        frameborder="0" style="border:0;" allowfullscreen=""></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© 2025 Jardin Secret. All rights reserved.</p>
    </footer>

    <script>
        // Danh sách cửa hàng gốc với đầy đủ thông tin
        const stores = [
            // Hà Nội
            { address: "194 Lê Duẩn, Hà Nội, Vietnam", city: "Hà Nội", district: "Đống Đa", hours: "Giờ mở cửa: 8:00 - 20:00 hàng ngày" },
            { address: "205 Xã Đàn, P.Nam Đồng, Hà Nội, Vietnam", city: "Hà Nội", district: "Đống Đa", hours: "Giờ mở cửa: 8:00 - 20:00 Thứ 2-6, 9:00-18:00 Thứ 7-CN" },
            { address: "Số 52 Hàng Đậu - Đồng Xuân - Hoàn Kiếm, Hà Nội, Vietnam", city: "Hà Nội", district: "Hoàn Kiếm", hours: "Giờ mở cửa: 9:00 - 21:00 hàng ngày" },
            { address: "346 Bạch Mai, P. Bạch Mai, Q.Hai Bà Trưng, Hà Nội, Vietnam", city: "Hà Nội", district: "Hai Bà Trưng", hours: "Giờ mở cửa: 8:30 - 19:30 hàng ngày" },
            { address: "Số 15 Trần Phú, Ba Đình, Hà Nội, Vietnam", city: "Hà Nội", district: "Ba Đình", hours: "Giờ mở cửa: 8:00 - 20:00 Thứ 2-6, 9:00 - 18:00 Thứ 7-CN" },
            { address: "89 Tam Trinh, Hoàng Mai, Hà Nội, Vietnam", city: "Hà Nội", district: "Hoàng Mai", hours: "Giờ mở cửa: 8:00 - 20:00 hàng ngày" },
            { address: "102 Phố Cổm, Phù Lầm, Hà Đông, Hà Nội, Vietnam", city: "Hà Nội", district: "Hà Đông", hours: "Giờ mở cửa: 9:00 - 19:00 hàng ngày" },
            // TP. Hồ Chí Minh
            { address: "123 Lý Tự Trọng, Quận 1, TP. Hồ Chí Minh, Vietnam", city: "TP. Hồ Chí Minh", district: "Quận 1", hours: "Giờ mở cửa: 8:30 - 21:00 hàng ngày" },
            { address: "45 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh, Vietnam", city: "TP. Hồ Chí Minh", district: "Quận 1", hours: "Giờ mở cửa: 9:00 - 22:00 hàng ngày" },
            { address: "78 Lê Lợi, Quận 3, TP. Hồ Chí Minh, Vietnam", city: "TP. Hồ Chí Minh", district: "Quận 3", hours: "Giờ mở cửa: 8:00 - 20:00 hàng ngày" },
            { address: "90 Pasteur, Quận 3, TP. Hồ Chí Minh, Vietnam", city: "TP. Hồ Chí Minh", district: "Quận 3", hours: "Giờ mở cửa: 8:30 - 20:30 hàng ngày" },
            { address: "15 Hai Bà Trưng, Quận 5, TP. Hồ Chí Minh, Vietnam", city: "TP. Hồ Chí Minh", district: "Quận 5", hours: "Giờ mở cửa: 9:00 - 19:00 hàng ngày" },
            // Shanghai
            { address: "Nanjing West Road, Jing'an District, Shanghai, China", city: "Shanghai", district: "Jing'an District", hours: "Giờ mở cửa: 10:00 - 22:00 hàng ngày" },
            { address: "Huaihai Middle Road, Huangpu District, Shanghai, China", city: "Shanghai", district: "Huangpu District", hours: "Giờ mở cửa: 10:00 - 22:00 hàng ngày" },
            { address: "Xujiahui, Xuhui District, Shanghai, China", city: "Shanghai", district: "Xuhui District", hours: "Giờ mở cửa: 10:00 - 22:00 hàng ngày" },
            // London
            { address: "28 Old Bond Street, Mayfair, London, England", city: "London", district: "Mayfair", hours: "Giờ mở cửa: 9:30 - 20:00 Thứ 2-6, 11:00 - 18:00 Thứ 7-CN" },
            { address: "Oxford Street, Westminster, London, England", city: "London", district: "Westminster", hours: "Giờ mở cửa: 9:00 - 21:00 Thứ 2-6, 10:00 - 18:00 Thứ 7-CN" },
            { address: "Knightsbridge, Kensington, London, England", city: "London", district: "Kensington", hours: "Giờ mở cửa: 9:00 - 21:00 Thứ 2-6, 10:00 - 18:00 Thứ 7-CN" },
            // New York
            { address: "Fifth Avenue, Manhattan, New York, NY, USA", city: "New York", district: "Manhattan", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            { address: "Madison Avenue, Manhattan, New York, NY, USA", city: "New York", district: "Manhattan", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            { address: "SoHo, Manhattan, New York, NY, USA", city: "New York", district: "Manhattan", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            // Barcelona
            { address: "Paseo de Gracia, Eixample, Barcelona, Spain", city: "Barcelona", district: "Eixample", hours: "Giờ mở cửa: 10:00 - 20:30 Thứ 2-6, 10:00 - 15:00 Thứ 7" },
            { address: "La Rambla, Ciutat Vella, Barcelona, Spain", city: "Barcelona", district: "Ciutat Vella", hours: "Giờ mở cửa: 10:00 - 20:30 Thứ 2-6, 10:00 - 15:00 Thứ 7" },
            { address: "Avinguda Diagonal, Sants-Montjuïc, Barcelona, Spain", city: "Barcelona", district: "Sants-Montjuïc", hours: "Giờ mở cửa: 10:00 - 20:30 Thứ 2-6, 10:00 - 15:00 Thứ 7" },
            // Tokyo
            { address: "Ginza, Chuo City, Tokyo, Japan", city: "Tokyo", district: "Chuo City", hours: "Giờ mở cửa: 10:30 - 20:00 hàng ngày" },
            { address: "Shibuya, Shibuya City, Tokyo, Japan", city: "Tokyo", district: "Shibuya City", hours: "Giờ mở cửa: 10:30 - 20:00 hàng ngày" },
            { address: "Omotesando, Minato City, Tokyo, Japan", city: "Tokyo", district: "Minato City", hours: "Giờ mở cửa: 10:30 - 20:00 hàng ngày" },
            // Paris
            { address: "Champs-Élysées, 8th Arrondissement, Paris, France", city: "Paris", district: "8th Arrondissement", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            { address: "Rue de Rivoli, 4th Arrondissement, Paris, France", city: "Paris", district: "4th Arrondissement", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            { address: "Avenue Montaigne, 1st Arrondissement, Paris, France", city: "Paris", district: "1st Arrondissement", hours: "Giờ mở cửa: 10:00 - 21:00 hàng ngày" },
            // São Paulo
            { address: "Rua Augusta, Centro, São Paulo, Brazil", city: "São Paulo", district: "Centro", hours: "Giờ mở cửa: 10:00 - 19:00 Thứ 2-6, 10:00 - 16:00 Thứ 7" },
            { address: "Avenida Paulista, Pinheiros, São Paulo, Brazil", city: "São Paulo", district: "Pinheiros", hours: "Giờ mở cửa: 10:00 - 19:00 Thứ 2-6, 10:00 - 16:00 Thứ 7" },
            { address: "Rua Oscar Freire, Vila Mariana, São Paulo, Brazil", city: "São Paulo", district: "Vila Mariana", hours: "Giờ mở cửa: 10:00 - 19:00 Thứ 2-6, 10:00 - 16:00 Thứ 7" }
        ];

        // Danh sách quận/huyện theo thành phố
        const districts = {
            "Hà Nội": ["Đống Đa", "Hoàn Kiếm", "Hai Bà Trưng", "Ba Đình", "Hoàng Mai", "Hà Đông"],
            "TP. Hồ Chí Minh": ["Quận 1", "Quận 3", "Quận 5"],
            "Shanghai": ["Jing'an District", "Huangpu District", "Xuhui District"],
            "London": ["Mayfair", "Westminster", "Kensington"],
            "New York": ["Manhattan"],
            "Barcelona": ["Eixample", "Ciutat Vella", "Sants-Montjuïc"],
            "Tokyo": ["Chuo City", "Shibuya City", "Minato City"],
            "Paris": ["1st Arrondissement", "4th Arrondissement", "8th Arrondissement"],
            "São Paulo": ["Centro", "Pinheiros", "Vila Mariana"]
        };

        // Cập nhật dropdown Quận/Huyện khi chọn Thành phố
        document.getElementById('cityFilter').addEventListener('change', function() {
            const city = this.value;
            const districtSelect = document.getElementById('districtFilter');
            districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';

            if (city && districts[city]) {
                districts[city].forEach(district => {
                    if (district) {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtSelect.appendChild(option);
                    }
                });
            }
            filterStores();
        });

        // Biến để theo dõi cửa hàng được chọn
        let selectedStore = null;

        // Lọc cửa hàng dựa trên bộ lọc
        function filterStores() {
            const city = document.getElementById('cityFilter').value;
            const district = document.getElementById('districtFilter').value;
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const storeList = document.getElementById('storeList');
            const storeCountNumber = document.getElementById('storeCountNumber');

            // Lọc danh sách cửa hàng
            const filteredStores = stores.filter(store => {
                const matchesCity = city ? store.city === city : true;
                const matchesDistrict = district ? store.district === district : true;
                const matchesSearch = searchText ? 
                    (store.address.toLowerCase().includes(searchText) || 
                     store.hours.toLowerCase().includes(searchText)) : true;
                return matchesCity && matchesDistrict && matchesSearch;
            });

            // Cập nhật danh sách hiển thị
            storeList.innerHTML = '';
            filteredStores.forEach(store => {
                const li = document.createElement('li');
                li.innerHTML = `
                    ${store.address.replace(', Vietnam', '').replace(', China', '').replace(', England', '').replace(', NY, USA', '').replace(', Spain', '').replace(', Japan', '').replace(', France', '').replace(', Brazil', '')}
                    <br><small>${store.hours}</small>
                `;
                li.onclick = () => {
                    updateMap(store.address);
                    // Xóa lớp active khỏi tất cả các li
                    document.querySelectorAll('.store-list li').forEach(item => item.classList.remove('active'));
                    // Thêm lớp active cho li được chọn
                    li.classList.add('active');
                    selectedStore = store.address;
                };
                // Kiểm tra nếu đây là cửa hàng được chọn
                if (store.address === selectedStore) {
                    li.classList.add('active');
                }
                storeList.appendChild(li);
            });

            // Cập nhật số lượng cửa hàng
            storeCountNumber.textContent = filteredStores.length;

            // Nếu không có kết quả, hiển thị thông báo
            if (filteredStores.length === 0) {
                const li = document.createElement('li');
                li.textContent = "Không tìm thấy cửa hàng nào.";
                storeList.appendChild(li);
            }
        }

        // Cập nhật bản đồ
        function updateMap(address) {
            const iframe = document.getElementById('mapIframe');
            const encodedAddress = encodeURIComponent(address);
            iframe.src = `https://www.google.com/maps?q=${encodedAddress}&output=embed&t=h&z=15`;
        }

        // Gọi hàm filterStores khi trang được tải để hiển thị số lượng ban đầu
        window.onload = filterStores;

        // Tự động lọc khi nhập vào ô tìm kiếm
        document.getElementById('searchInput').addEventListener('input', filterStores);

        // Tự động lọc khi thay đổi quận/huyện
        document.getElementById('districtFilter').addEventListener('change', filterStores);
    </script>
</body>
</html>