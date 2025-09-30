<?php
require 'connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $vaitro = 'user';

    // Kiểm tra đầu vào
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    }
    elseif ($password !== $confirm_password) {
        $error = "Mật khẩu không khớp!";
    }
    elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } 
    else {
        // Kiểm tra trùng username/email
        $check_query = "SELECT id FROM taikhoan WHERE tendangnhap = ? OR email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            // Mã hóa mật khẩu
            $hashed_password = md5($password);
            
            // Thêm tài khoản mới
            $insert_query = "INSERT INTO taikhoan (tendangnhap, email, matkhau, vaitro) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $vaitro);
            
            if ($insert_stmt->execute()) {
                // Đóng các kết nối trước khi chuyển hướng
                $insert_stmt->close();
                $stmt->close();
                $conn->close();
                
                // Chuyển hướng về trang login với thông báo thành công
                header("Location: login.php?register=success");
                exit(); // Luôn dùng exit sau header Location
            } else {
                $error = "Đăng ký không thành công: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jardin Secret | Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="css/login-style.css">
    <style>
        .error-message {
            color: #ff4d4d;
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        input.error {
            background: rgba(255, 0, 0, 0.1) !important;
            border-color: #ff4d4d !important;
        }
        input.typing {
            background: rgba(255, 255, 255, 0.3) !important;
        }
    </style>
</head>
<body class="auth-page register-page">
    <form method="POST">
        <h3>Register Here</h3>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?= $error ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" name="username" id="username" placeholder="Username" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Password" required>

        <label for="confirm_password">Confirm password</label>
        <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm Password" required>
        <div id="password-error" class="error-message" style="display: none;">Mật khẩu không khớp!</div>

        <div class="checkbox-container">
            <label>
                <input type="checkbox" id="showPassword" onclick="togglePassword()">
                Show password
            </label>
        </div>

        <button type="submit">Register account</button>
    </form>

    <script>
        function togglePassword() {
            const pwd1 = document.getElementById("password");
            const pwd2 = document.getElementById("confirm-password");
            const type = pwd1.type === "password" ? "text" : "password";
            pwd1.type = type;
            pwd2.type = type;
        }

        // Kiểm tra password match real-time
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const errorElement = document.getElementById('password-error');
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.classList.add('error');
                errorElement.style.display = 'block';
            } else {
                this.classList.remove('error');
                errorElement.style.display = 'none';
            }
        });
    </script>
</body>
</html>