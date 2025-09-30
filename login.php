<?php
session_start();
require 'connect.php'; 

// Lấy thông báo từ query param
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username) && !empty($password)) {

        $safe_username = mysqli_real_escape_string($conn, $username); 
        $query = "SELECT * FROM taikhoan WHERE tendangnhap = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $safe_username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {

            if (md5($password) == $user['matkhau']) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['tendangnhap'] = $user['tendangnhap'];
                $_SESSION['vaitro'] = $user['vaitro'];
                if ($user['vaitro'] === 'admin') {
                            header("Location: admin-orders.php");
                        } else {
                            header("Location: dashboard.php"); 
                        }
            } else {
                $error = "Mật khẩu không đúng!";
            }
        } else {
            $error = "Tên đăng nhập không tồn tại!";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jardin Secret | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/login-style.css"> 
</head>
<body>
    <form method="POST" action="" autocomplete="off">
        <h3>Login Here</h3>

        <?php if (!empty($message)) : ?>
            <p style="color: blue;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" name="username" placeholder="Username" id="username" autocomplete="off">

        <label for="password">Password</label>
        <input type="password" name="password" placeholder="Password" id="password" autocomplete="new-password">

        <button type="submit">Login</button>
        <p class="social-text">Login with a social media account </p>
        
        <div class="social-icons">
            <button class="social-icon fb"><i class="fa-brands fa-facebook"></i></button>
            <button class="social-icon tw"><i class="fa-brands fa-twitter"></i></button>
            <button class="social-icon in"><i class="fa-brands fa-instagram"></i></button>
        </div>

        <p class="social-text">Doesn't have a account ? <a href="register.php" class="register-link">Register</a></p>
    </form>
</body>
</html>