// Biến để lưu trữ video elements
let video1, video2;

// Hàm được gọi khi trang load xong
document.addEventListener('DOMContentLoaded', function() {
    // Lấy video elements
    video1 = document.getElementById('video1');
    video2 = document.getElementById('video2');

    // Lấy video-box chứa video
    const videoBox1 = video1.closest('.video-box');
    const videoBox2 = video2.closest('.video-box');

    // Thêm sự kiện hover cho video 1
    videoBox1.addEventListener('mouseover', function() {
        video1.play(); // Phát video khi hover
    });

    videoBox1.addEventListener('mouseout', function() {
        video1.pause(); // Tạm dừng video khi rời chuột
    });

    // Thêm sự kiện hover cho video 2
    videoBox2.addEventListener('mouseover', function() {
        video2.play(); // Phát video khi hover
    });

    videoBox2.addEventListener('mouseout', function() {
        video2.pause(); // Tạm dừng video khi rời chuột
    });
});