-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 22, 2025 at 03:35 AM
-- Server version: 5.5.20
-- PHP Version: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `da_ltw`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Nước hoa nam', 'Hương thơm mạnh mẽ dành cho nam giới'),
(2, 'Nước hoa nữ', 'Hương thơm nhẹ nhàng dành cho phái đẹp'),
(3, 'Nước hoa unisex', 'Hương thơm thích hợp cho cả nam và nữ');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE IF NOT EXISTS `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `name`, `description`, `image`) VALUES
(1, 'Hương Xuân', 'Bộ sưu tập nước hoa mang hương sắc mùa xuân, nhẹ nhàng và tươi mới.', 'uploads/collections/spring.jpg'),
(2, 'Hương Hạ', 'Bộ sưu tập nước hoa đậm chất mùa hè, nồng nàn và quyến rũ.', 'uploads/collections/summer.jpeg'),
(3, 'Hương Thu', 'Bộ sưu tập nước hoa mang tính hoài cổ, mát mẻ và dịu nhẹ', 'uploads/collections/autumn.webp'),
(4, 'Hương Đông', 'Bộ sưu tập nước hoa đậm chất mùa đông the mát, se se lạnh nhưng cũng dịu dàng.', 'uploads/collections/winter.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `address` text COLLATE utf8_unicode_ci,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gender` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Giới tính: Nam/Nữ/Khác',
  `avatar` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `full_name`, `address`, `phone`, `created_at`, `gender`, `avatar`) VALUES
(1, 3, 'Minh Thư', 'Long Xuyên, An Giang, Việt Nam', '0123456789', '2025-05-18 15:57:41', 'female', 'uploads/avatars/1747847228_avaT.jpg'),
(2, 4, 'Nguỹn Kim Hưn', 'Mỹ Khánh, Long Xuyên, An Giang', '0123654789', '2025-05-20 04:39:48', 'female', 'uploads/avatars/1747845507_lavender_garden.jpg'),
(3, 6, '', '', '', '2025-05-20 07:20:04', 'other', NULL),
(4, 7, 'Minh Nhựt', 'Mỹ Khánh, Long Xuyên, An Giang', '0123654789', '2025-05-21 11:12:32', 'male', NULL),
(5, 8, 'Cô Vy', 'Long Xuyên', '1023654789', '2025-05-22 03:15:14', 'female', 'uploads/avatars/1747883771_bg_login.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') COLLATE utf8_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_orders_customer` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=21 ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(3, 3, '9350000.00', 'shipped', '2025-05-19 05:08:00'),
(5, 3, '6400000.00', 'cancelled', '2025-05-19 18:30:00'),
(8, 3, '5800000.00', 'pending', '2025-05-20 10:43:00'),
(10, 7, '8550000.00', 'shipped', '2025-05-21 11:15:00'),
(11, 4, '5800000.00', 'pending', '2025-05-21 11:19:31'),
(12, 4, '3100000.00', 'pending', '2025-05-21 11:23:09'),
(13, 4, '5300000.00', 'pending', '2025-05-21 11:24:10'),
(14, 4, '5800000.00', 'pending', '2025-05-21 13:00:36'),
(15, 4, '5300000.00', 'shipped', '2025-05-21 13:10:00'),
(16, 4, '12100000.00', 'shipped', '2025-05-21 16:35:00'),
(17, 3, '12100000.00', 'pending', '2025-05-21 17:06:45'),
(18, 3, '25950000.00', 'pending', '2025-05-21 17:32:36'),
(19, 8, '12100000.00', 'pending', '2025-05-22 03:16:27'),
(20, 8, '5000000.00', 'pending', '2025-05-22 03:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE IF NOT EXISTS `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=94 ;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(15, 5, 2, 1, '0.00'),
(16, 5, 9, 1, '0.00'),
(37, 10, 17, 1, '0.00'),
(38, 10, 13, 1, '0.00'),
(39, 11, 18, 1, '5800000.00'),
(40, 12, 10, 1, '3100000.00'),
(41, 13, 2, 1, '5300000.00'),
(42, 14, 7, 1, '5800000.00'),
(50, 8, 18, 1, '0.00'),
(58, 15, 2, 1, '0.00'),
(59, 16, 8, 1, '0.00'),
(73, 17, 8, 1, '12100000.00'),
(84, 3, 18, 1, '0.00'),
(85, 3, 17, 1, '0.00'),
(86, 18, 7, 2, '5800000.00'),
(87, 18, 13, 1, '5000000.00'),
(88, 18, 18, 1, '5800000.00'),
(89, 18, 17, 1, '3550000.00'),
(90, 19, 8, 1, '12100000.00'),
(93, 20, 13, 1, '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `collection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=21 ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `created_at`, `collection_id`) VALUES
(1, 1, 'Dior Sauvage EDT', 'Hương cam chanh tươi mát', '4200.00', 'images/Dior Sauvage EDT.webp', 50, '2025-05-17 04:29:46', 1),
(2, 2, 'Miss Dior', 'Là sự kết hợp của hương thơm từ mật hoa và sự ngọt ngào của trái cây', '5300.00', 'uploads/1747826215_Miss Dior.webp', 30, '2025-05-17 04:29:46', 2),
(7, 3, 'Dior Purple Oud', 'Tên lọ nước hoa này được đặt theo hoa tử linh lan (Violet). Sự kết hợp của Trầm hương và Nghệ tây đã khéo léo khắc họa nên một bức mang âm hưởng phương Đông với màu sắc vui tươi và ấm áp. Hương cay và trầm, tôn vinh màu tím, mang lại sự táo bạo và sang trọng, thể hiện cá tính mạnh mẽ và sự khác biệt. ', '5800.00', 'images/perfume1.webp', 25, '2025-05-17 16:03:57', 2),
(8, 3, 'Dior Rouge Trafalgar', 'Ra mắt vào 2024, Rouge Trafalgar Esprit de Parfum tự hào có một sắc đỏ son môi tinh tế, đậm hơn và dày đặc hơn bao giờ hết, miêu tả một quả cherry chín mọng. Nhờ sử dụng các nốt hương mạnh mẽ như tiêu hồng rực lửa, hoa hồng Thổ Nhĩ Kỳ và Bulgaria tinh tế nhưng lộng lẫy, Rouge Trafalgar tựa như một cú đánh khứu giác đầy táo bạo, thích hợp cho những ai yêu thích sự nổi bật.', '12100.00', 'images/Dior Rouge Trafalgar.webp', 20, '2025-05-17 16:20:15', 1),
(9, 1, 'Dolce & Gabbana D&G The One Gold Intense', 'Ra mắt vào năm 2021, đây là một mùi hương thể hiện hình ảnh vương giả của những quý ngài lịch thiệp. Nước hoa nam The One Gold Intense For Men tôn vinh những người đàn ông làm rạng rỡ thế giới bằng sự sáng chói của mình, nhưng giữa cuộc sống đời thường, anh ấy có sức hút đầy tính tự nhiên.', '2500.00', 'images/perfume2.jpg', 15, '2025-05-17 16:27:21', 2),
(10, 2, 'Dolce & Gabbana Q EDP', 'Ra mắt vào năm 2023, đây là một mùi hương ngọt ngào nhưng không kém phần sang trọng và quý phái, tự nhưng một bữa tiệc đầy hoa thơm và mật ngọt giữa chốn địa đàng. Đam mê và nghị lực, quyền lực và quyến rũ, đây là chính xác là những gì mà các quý cô hiệ đại cần - tựa nhưng một nữ hoàng đương đại, kiều diễm nhưng không yếu mềm. ', '3100.00', 'images/D&G Q.webp', 12, '2025-05-17 16:36:17', 1),
(11, 1, 'Dolce & Gabbana K EDT', 'Cuộc gặp gỡ định mệnh với vị vua của cuộc đời mình - K. Với mùi tươi mát của Cam quýt, Cam đỏ, Chanh Sicily kèm sự năng động đến từ Quả bách xù đã gây ấn tượng sâu sắc ngay từ nốt hương đầu đã cho ta thấy được hình ảnh một vị vua khí chất, mạnh mẽ, đáng tin cậy - đó chính là định mệnh', '2500.00', 'images/D&G K.jpg', 10, '2025-05-17 16:46:13', 2),
(12, 1, 'Dior Homme', 'Ở phiên bản 2020, Dior Homme tự tin mang trong mình thông điệp “I’m your man", một mùi hương thể hiện phong thái lịch lãm và vẻ đẹp mạnh mẽ đầy hấp dẫn của cánh mày râu. Từ cảm giác ấm áp của gỗ Cashmere đến vị cay nhẹ của Gỗ tuyết tùng, đan xen cùng âm vị the mát của Hoắc hương và Hồng tiêu. Đâu đó phản phất bóng dáng của Xạ hương cùng những nhành Cỏ Vetiver, tất cả đã khác họa nên một người đàn ông cổ điển, hào phóng.', '2850.00', 'images/Dior Homme.jpg', 14, '2025-05-17 16:56:49', 1),
(13, 2, 'Dior J’adore Eau de Parfum', 'Một mùi hương cổ điển, J’Adore như nguồn cảm hứng vô hạn của một người phụ nữ sở hữu vẻ đẹp xuất chúng, sự nữ tính vô hạn, mang trong mình sắc hương như đóa hoa lan thơm ngát, êm ái như nhung của mận xứ Đamas và vị ngọt đậm của gỗ Amarante. Là sự kết hợp của vô số trái cây và hoa cỏ, mùi hương này khẳng định mình là một trong những nét hương mị hoặc nhất mọi thời đại', '5000.00', 'images/Dior JEDB5.jpg', 10, '2025-05-17 18:01:21', 3),
(14, 3, 'The Merchant of Venice Blue Tea', 'Nét huyền bí của phương Đông được thể hiện mạnh mẽ qua thiết kế lẫn mùi hương của Blue Tea. Đây tựa như một bữa tiệc trà với sự tham gia của một tách Trà Xanh đượm mùi hoa hồng, hoa mộc lan và hoa cam neroli, đậm đà mà đầy thơ mộng. Một mùi hương tinh tế, tươi mới và thơm ngon, cứ thế quấn quyện trên làn da của ta, rồi gói gọn lại trong nốt hương cuối của cỏ hương bài.', '5300.00', 'images/Blue Tea.jpg', 8, '2025-05-17 18:14:03', 4),
(15, 3, 'Roja A Midsummer Dream', 'A Midsummer Dream là sáng tạo mùi hương vô cùng khác biệt trong thế giới mùi hương. Ập đến trên da là hương the khói là lạ. Ấm nồng? Không hẳn. Tĩnh mịch? Có khả năng. Lớp hương có màu xanh ngút ngát.', '7200.00', 'images/Midsummer Dream.jpg', 5, '2025-05-17 18:24:35', 3),
(16, 3, 'Kilian Voulez Vous Coucher Avec Moi EDP', 'Ra mắt vào năm 2015, mùi hương thứ 5 trong bộ sưu tập The Garden of Good and Evil. Được truyền cảm hứng từ những tội lỗi đầy mị hoặc, mùi hương này tựa như một cuộc chơi đêm đầy xa xỉ và sang trọng. Từng tầng hương là một cuốn sách, đặc biệt là sự nổi bật của hoa ngọc lan và hoa huệ. Đáp lại sự nhẹ nhàng của hoa, lắng đọng ở hương cuối là Gỗ tuyết tùng, Vanilla và Gỗ đàn hương, tất cả tạo nên một mùi hương lưu luyến', '5500.00', 'images/Snake.webp', 15, '2025-05-18 05:21:34', 4),
(17, 2, 'Jean Paul Gaultier La Belle Paradise Garden EDP', 'Từ thiết kế cũng có thể dễ dàng thấy được sự quyến rũ, mĩ miều và quyến rũ của hương nước hoa này. Là hương thơm mang âm hưởng phương Đông được ra mắt vào năm 2024. Đây tựa như một hành trình vào chốn thiên đàng với mở đầu là Blue Loutus nở rộ giữa cánh đồng mênh mông. Kế đó là Iris kiều diễm cuối cùng là Vanila ấm áp, ngọt ngào', '3550.00', 'images/Flower.webp', 20, '2025-05-18 05:35:51', 3),
(18, 2, 'CHANCE EAU SPLENDIDE', 'Khu vườn của mùi hương, là sự kết hợp hài hòa giữa hương hoa oải hương tinh tế và nét quyến rũ gai góc của gỗ thông', '5800.00', 'uploads/1747826319_Chanel Purple.jpg', 20, '2025-05-21 11:18:39', NULL),
(19, 3, 'Orange Perfume', 'Hương chua nhẹ của cam và chanh vô cùng tươi mát, kết hợp cùng hương hoa ngọt ngào', '6100.00', 'uploads/1747883115_cam.webp', 12, '2025-05-22 03:05:15', NULL),
(20, 1, 'Coffee', 'Thơm', '5500.00', 'uploads/1747883967_wood.jpg', 10, '2025-05-22 03:19:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
--

CREATE TABLE IF NOT EXISTS `taikhoan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tendangnhap` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `matkhau` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `vaitro` enum('admin','user') COLLATE utf8_unicode_ci DEFAULT 'user',
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tendangnhap` (`tendangnhap`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=9 ;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`id`, `tendangnhap`, `matkhau`, `vaitro`, `email`, `created_at`) VALUES
(1, 'admin1', 'c6f9f3a05d6c8d3e5f2c1a9e0b7a3f4', 'admin', 'admin1@gmail.com', '0000-00-00 00:00:00'),
(3, 'thubikick', 'ae4720973b50ccde470e805acd5c930c', 'user', 'ngothu1@gmail.com', '0000-00-00 00:00:00'),
(4, 'kimhuong1', '98467a817e2ff8c8377c1bf085da7138', 'user', 'kimhuong@gmail.com', '2025-05-20 04:32:57'),
(5, 'lieu123', 'ad00e1f88c979490cbfc02365a5fed55', 'user', 'lieu1@gmail.com', '2025-05-20 04:36:28'),
(6, 'admin2', '23af4255c402219567c3267063514c29', 'admin', 'admin2@gmail.com', '2025-05-20 07:13:02'),
(7, 'minhnhut1', 'ec6d212e36d247eac47f3e954e3d3f09', 'user', 'nhut1@gmail.com', '2025-05-21 11:11:20'),
(8, 'covy1', '3f4a17695ced1291d4384524346efe33', 'user', 'covi1@gmail.com', '2025-05-22 03:14:39');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `taikhoan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `taikhoan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`user_id`) REFERENCES `customers` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `taikhoan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
