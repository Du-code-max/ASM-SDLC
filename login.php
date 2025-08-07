<?php
session_start();
require 'connect.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] == 'customer') {
            header("Location: customer.php");
            exit();
        } elseif ($user['role'] == 'restaurant') {
            header("Location: restaurant.php");
            exit();
        } elseif ($user['role'] == 'delivery') {
            header("Location: delivery.php");
            exit();
        } elseif ($user['role'] == 'admin') {
            header("Location: admin.php");
            exit();
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Hệ thống đặt món</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00B14F; /* Màu xanh chính của GrabFood */
            --primary-hover: #009E45; /* Màu xanh khi hover */
            --secondary-color: #FFC244; /* Màu vàng phụ của Grab */
            --error-color: #FF3B30;
            --text-color: #1F1F1F;
            --light-gray: #F8F8F8;
            --border-color: #E0E0E0;
            --shadow: 0 4px 12px rgba(0, 177, 79, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-image: linear-gradient(to bottom, rgba(0, 177, 79, 0.05), rgba(0, 177, 79, 0.01));
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
            border: 1px solid rgba(0, 177, 79, 0.1);
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 24px;
            text-align: center;
            position: relative;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 20px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 20'%3E%3Cpath fill='%2300B14F' d='M20 20c11.046 0 20-8.954 20-20H0c0 11.046 8.954 20 20 20z'/%3E%3C/svg%3E") no-repeat;
            background-size: cover;
        }
        
        .login-body {
            padding: 32px;
        }
        
        .error-message {
            background-color: #FFEBEE;
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 59, 48, 0.2);
        }
        
        .error-message i {
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .input-field {
            position: relative;
        }
        
        .input-field input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .input-field input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.2);
        }
        
        .input-field i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }
        
        .login-button {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }
        
        .login-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 177, 79, 0.2);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-container {
                border-radius: 0;
            }
            
            body {
                padding: 0;
                background: white;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Chào mừng trở lại</h1>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-field">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
            </div>
            
            <button type="submit" class="login-button">
                Đăng nhập
            </button>
        </form>
        
        <div class="login-footer">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
    </div>
</div>
</body>
</html>