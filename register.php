<?php
require 'connect.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];
    $role = $_POST['role'];

    // Lấy địa chỉ theo role
    if ($role == 'restaurant') {
        $address = isset($_POST['restaurant_address']) ? trim($_POST['restaurant_address']) : '';
    } elseif ($role == 'delivery') {
        $address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
    } else {
        $address = '';
    }

    // Validate input
    if (empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif ($password !== $repassword) {
        $error = "Mật khẩu nhập lại không khớp.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // Check for existing user
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $email, $phone]);
        if ($stmt->fetch()) {
            $error = "Tài khoản này đã tồn tại.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, email, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $role, $email, $phone, $address]);
                $user_id = $conn->lastInsertId();
                
                // Handle role-specific data
                if ($role == 'restaurant') {
                    $restaurant_name = trim($_POST['restaurant_name']);
                    if (empty($restaurant_name) || empty($address)) {
                        throw new Exception("Vui lòng điền đầy đủ thông tin nhà hàng.");
                    }
                    // Giả sử có bảng restaurants với cấu trúc phù hợp
                    $stmt = $conn->prepare("INSERT INTO restaurants (user_id, name, address, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $restaurant_name, $address, $phone]);
                } elseif ($role == 'delivery') {
                    // Giả sử có bảng delivery với cấu trúc phù hợp
                    $stmt = $conn->prepare("INSERT INTO delivery (user_id, name, phone, status) VALUES (?, ?, ?, 'available')");
                    $stmt->execute([$user_id, $username, $phone]);
                }
                // Commit transaction
                $conn->commit();
                
                header("Location: login.php");
                exit();
            } catch (Exception $e) {
                // Rollback transaction if error occurs
                $conn->rollBack();
                $error = "Đăng ký thất bại: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - GrabFood</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00B14F; /* Màu xanh lá đặc trưng của GrabFood */
            --primary-hover: #009944;
            --error-color: #FF3B30;
            --success-color: #34C759;
            --text-color: #1F1F1F;
            --light-text: #6B6B6B;
            --border-color: #E0E0E0;
            --bg-color: #F5F5F5;
            --card-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-image: url('https://food.grab.com/static/images/logo-pizza-hut.svg'); /* Optional: thêm background giống GrabFood */
            background-repeat: no-repeat;
            background-position: bottom right;
            background-size: 200px;
        }
        
        .register-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .register-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }
        
        .register-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .register-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background-color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .register-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 177, 79, 0.2);
        }
        
        .role-fields {
            background-color: #F8F8F8;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
            animation: fadeIn 0.3s ease-in-out;
            border: 1px solid var(--border-color);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 177, 79, 0.2);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--light-text);
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #FFEBEA;
            color: var(--error-color);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease-out;
            border: 1px solid #FFD1CF;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-message i {
            margin-right: 0.5rem;
        }
        
        .password-hint {
            font-size: 0.75rem;
            color: var(--light-text);
            margin-top: 0.25rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: var(--light-text);
        }
        
        .show-password {
            cursor: pointer;
        }
        
        /* Thêm logo GrabFood */
        .grab-logo {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .grab-logo img {
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <h1>Tạo tài khoản GrabFood</h1>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" class="form-control" required maxlength="50" placeholder="Nhập tên đăng nhập">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required maxlength="100" placeholder="example@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" class="form-control" required maxlength="20" placeholder="Nhập số điện thoại">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-with-icon">
                            <input type="password" id="password" name="password" class="form-control" required minlength="6" maxlength="255" placeholder="Ít nhất 6 ký tự">
                            <i class="fas fa-eye show-password" onclick="togglePassword('password')"></i>
                        </div>
                        <p class="password-hint">Mật khẩu phải có ít nhất 6 ký tự</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="repassword">Nhập lại mật khẩu</label>
                        <div class="input-with-icon">
                            <input type="password" id="repassword" name="repassword" class="form-control" required minlength="6" maxlength="255">
                            <i class="fas fa-eye show-password" onclick="togglePassword('repassword')"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Vai trò</label>
                        <select id="role" name="role" class="form-control" onchange="toggleRoleFields()" required>
                            <option value="customer">Khách hàng</option>
                            <option value="restaurant">Nhà hàng</option>
                            <option value="delivery">Giao hàng</option>
                        </select>
                    </div>
                    
                    <div id="restaurant-fields" class="role-fields">
                        <div class="form-group">
                            <label for="restaurant_name">Tên nhà hàng</label>
                            <input type="text" id="restaurant_name" name="restaurant_name" class="form-control" placeholder="Nhập tên nhà hàng">
                        </div>
                        <div class="form-group">
                            <label for="restaurant_address">Địa chỉ nhà hàng</label>
                            <input type="text" id="restaurant_address" name="restaurant_address" class="form-control" maxlength="255" placeholder="Nhập địa chỉ nhà hàng">
                        </div>
                    </div>
                    
                    <div id="delivery-fields" class="role-fields">
                        <div class="form-group">
                            <label for="delivery_address">Địa chỉ</label>
                            <input type="text" id="delivery_address" name="delivery_address" class="form-control" maxlength="255" placeholder="Nhập địa chỉ của bạn">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Đăng ký ngay
                    </button>
                </form>
                
                <div class="register-footer">
                    Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('restaurant-fields').style.display = role === 'restaurant' ? 'block' : 'none';
            document.getElementById('delivery-fields').style.display = role === 'delivery' ? 'block' : 'none';

            // Đồng bộ address field khi chuyển đổi role
            if (role === 'restaurant') {
                document.getElementById('delivery_address').value = '';
            } else if (role === 'delivery') {
                document.getElementById('restaurant_address').value = '';
            }
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Initialize fields visibility on page load
        window.onload = function() {
            toggleRoleFields();
        };
    </script>
</body>
</html>