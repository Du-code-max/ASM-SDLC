<?php
session_start();
require 'connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Kiểm tra chế độ chỉnh sửa
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';

// Xử lý cập nhật thông tin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = $_POST['name'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    $update_stmt = $conn->prepare("UPDATE users SET username = ?, phone = ?, address = ? WHERE id = ?");
    if ($update_stmt->execute([$username, $phone, $address, $user_id])) {
        $_SESSION['message'] = "Cập nhật thông tin thành công!";
        header("Location: profile.php");
        exit();
    } else {
        $error = "Có lỗi xảy ra khi cập nhật thông tin";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Chỉnh sửa' : 'Thông tin' ?> tài khoản - GrabFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00B14F;
            --primary-hover: #009E45;
            --secondary: #2E3840;
            --light-gray: #F8FAFC;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            color: #2D3748;
        }
        
        .profile-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }
        
        .input-field {
            transition: all 0.2s ease;
        }
        
        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: white;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover:after {
            width: 100%;
        }
        
        .active-menu-item {
            background-color: #E8F5EE;
            color: var(--primary);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-gradient-to-r from-green-600 to-green-700 text-white p-4 sticky top-0 z-20 shadow-md">
        <div class="container mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <a href="customer.php" class="text-2xl font-bold tracking-tight flex items-center">
                    <i class="fas fa-utensils mr-2"></i> GrabFood
                </a>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="customer.php" class="nav-link hover:text-green-200">Trang chủ</a>
                    <a href="#contact" class="nav-link hover:text-green-200">Liên hệ</a>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 md:space-x-6">
                <div class="hidden sm:flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center text-green-700">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <a href="profile.php" class="hover:text-green-200 transition-colors">
                        <?= htmlspecialchars($user['username'] ?? 'Tài khoản') ?>
                    </a>
                </div>
                <a href="logout.php" class="hover:text-green-200 transition-colors flex items-center">
                    <i class="fas fa-sign-out-alt mr-1"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar Profile -->
                <div class="w-full md:w-1/3">
                    <div class="bg-white rounded-xl shadow-sm profile-card p-6 sticky top-24">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 rounded-full bg-gradient-to-r from-green-400 to-green-500 flex items-center justify-center text-white text-3xl mb-4">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($user['username']) ?></h3>
                            <p class="text-gray-500 text-sm mt-1">Thành viên từ <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                            
                            <div class="mt-6 w-full space-y-3">
                                <a href="profile.php" class="flex items-center space-x-2 px-3 py-2 rounded-lg <?= !$edit_mode ? 'active-menu-item' : 'text-gray-600 hover:bg-gray-50' ?>">
                                    <i class="fas fa-user-circle w-5"></i>
                                    <span>Thông tin cá nhân</span>
                                </a>
                                
                                <!-- Thêm nút quay lại trang chủ -->
                                <a href="customer.php" class="flex items-center space-x-2 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 mt-8 border-t border-gray-100 pt-4">
                                    <i class="fas fa-arrow-left w-5"></i>
                                    <span>Quay lại trang chủ</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Profile Content -->
                <div class="w-full md:w-2/3">
                    <div class="bg-white rounded-xl shadow-sm profile-card overflow-hidden">
                        <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800">
                                <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-user' ?> mr-2 text-green-500"></i>
                                <?= $edit_mode ? 'Chỉnh sửa thông tin' : 'Thông tin cá nhân' ?>
                            </h2>
                            
                            <?php if (!$edit_mode): ?>
                                <a href="profile.php?edit=true" class="btn-primary text-white px-4 py-2 rounded-lg text-sm flex items-center">
                                    <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <!-- Hiển thị thông báo -->
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span><?= htmlspecialchars($_SESSION['message']) ?></span>
                                    </div>
                                    <?php unset($_SESSION['message']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($error)): ?>
                                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span><?= htmlspecialchars($error) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Phần thông tin cá nhân -->
                            <?php if ($edit_mode): ?>
                                <!-- Form chỉnh sửa -->
                                <form method="post" action="profile.php">
                                    <div class="space-y-5">
                                        <div>
                                            <label for="name" class="block text-gray-600 text-sm font-medium mb-1">Họ tên</label>
                                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['username']) ?>" 
                                                   class="w-full px-4 py-2 border border-gray-200 rounded-lg input-field focus:outline-none" required>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-gray-600 text-sm font-medium mb-1">Email</label>
                                            <div class="flex items-center px-4 py-2 bg-gray-50 rounded-lg">
                                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                                <span class="text-gray-800"><?= htmlspecialchars($user['email']) ?></span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2">Liên hệ hỗ trợ để thay đổi email</p>
                                        </div>
                                        
                                        <div>
                                            <label for="phone" class="block text-gray-600 text-sm font-medium mb-1">Số điện thoại</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500">+84</span>
                                                </div>
                                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                                       class="w-full pl-12 px-4 py-2 border border-gray-200 rounded-lg input-field focus:outline-none" placeholder="Nhập số điện thoại">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="address" class="block text-gray-600 text-sm font-medium mb-1">Địa chỉ</label>
                                            <textarea id="address" name="address" rows="3"
                                                      class="w-full px-4 py-2 border border-gray-200 rounded-lg input-field focus:outline-none" placeholder="Nhập địa chỉ của bạn"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="flex justify-end space-x-3 pt-2">
                                            <a href="profile.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-2 rounded-lg transition-colors">
                                                Hủy bỏ
                                            </a>
                                            <button type="submit" name="update_profile" class="btn-primary text-white px-6 py-2 rounded-lg flex items-center">
                                                <i class="fas fa-save mr-2"></i> Lưu thay đổi
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- Hiển thị thông tin -->
                                <div class="space-y-5">
                                    <div class="flex items-start">
                                        <div class="w-1/3 text-gray-500">
                                            <i class="fas fa-user mr-2"></i> Họ tên
                                        </div>
                                        <div class="w-2/3 font-medium text-gray-800">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-1/3 text-gray-500">
                                            <i class="fas fa-envelope mr-2"></i> Email
                                        </div>
                                        <div class="w-2/3 font-medium text-gray-800">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-1/3 text-gray-500">
                                            <i class="fas fa-phone mr-2"></i> Số điện thoại
                                        </div>
                                        <div class="w-2/3 font-medium text-gray-800">
                                            <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-gray-400">Chưa cập nhật</span>' ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-1/3 text-gray-500">
                                            <i class="fas fa-map-marker-alt mr-2"></i> Địa chỉ
                                        </div>
                                        <div class="w-2/3 font-medium text-gray-800">
                                            <?= !empty($user['address']) ? htmlspecialchars($user['address']) : '<span class="text-gray-400">Chưa cập nhật</span>' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer id="contact" class="bg-gray-800 text-white py-12 mt-12">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-utensils mr-2 text-green-500"></i> GrabFood
                </h3>
                <p class="text-gray-400 text-sm">Giao đồ ăn nhanh chóng, tiện lợi, mọi lúc mọi nơi.</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Về chúng tôi</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-green-400 transition-colors">Giới thiệu</a></li>
                    <li><a href="#" class="hover:text-green-400 transition-colors">Tuyển dụng</a></li>
                    <li><a href="#" class="hover:text-green-400 transition-colors">Điều khoản</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Hỗ trợ</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-green-400 transition-colors">Trung tâm trợ giúp</a></li>
                    <li><a href="#" class="hover:text-green-400 transition-colors">An toàn thực phẩm</a></li>
                    <li><a href="#" class="hover:text-green-400 transition-colors">Chính sách bảo mật</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Liên hệ</h3>
                <div class="space-y-2 text-sm text-gray-400">
                    <p class="flex items-center">
                        <i class="fas fa-envelope mr-2 text-green-500"></i> support@grabfood.com
                    </p>
                    <p class="flex items-center">
                        <i class="fas fa-phone mr-2 text-green-500"></i> +84 123 456 789
                    </p>
                    <div class="flex space-x-4 mt-3">
                        <a href="#" class="text-gray-400 hover:text-green-500 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-green-500 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-green-500 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>© <?= date('Y') ?> GrabFood. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</body>
</html>