<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = null;
$order_items = [];
$error_message = '';

define('DEFAULT_DELIVERY_FEE', 15000);
define('DEFAULT_DISCOUNT', 0);

try {
    if ($order_id > 0) {
        $stmt = $conn->prepare("SELECT o.*, u.username, u.phone, u.address AS customer_address, 
                               r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone
                               FROM orders o 
                               JOIN users u ON o.customer_id = u.id 
                               JOIN restaurants r ON o.restaurant_id = r.id 
                               WHERE o.id = ? AND o.customer_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch();

        if ($order) {
            $stmt = $conn->prepare("SELECT oi.*, mi.name, mi.image_url, mi.price, mi.description 
                                   FROM order_items oi 
                                   JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                   WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            $subtotal = array_reduce($order_items, function($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);
            
            $order['subtotal'] = $subtotal;
            $order['delivery_fee'] = DEFAULT_DELIVERY_FEE;
            $order['discount_amount'] = DEFAULT_DISCOUNT;
            $order['total_amount'] = $subtotal + DEFAULT_DELIVERY_FEE - DEFAULT_DISCOUNT;
        } else {
            $error_message = "Không tìm thấy đơn hàng #$order_id hoặc bạn không có quyền xem đơn hàng này.";
        }
    } else {
        $error_message = "Thiếu thông tin mã đơn hàng.";
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Đã xảy ra lỗi khi tải thông tin đơn hàng. Vui lòng thử lại sau.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $order ? "Đơn hàng #$order_id" : "Không tìm thấy đơn hàng" ?> | Food Delivery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00B14F;
            --primary-hover: #00994A;
            --danger: #EF4444;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #FAFAFA;
            transition: opacity 0.3s ease;
        }
        
        .order-status {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #dbeafe; color: #1e40af; }
        .status-delivering { background-color: #e0f2fe; color: #0369a1; }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .food-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .popup-message {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out, fadeOut 0.5s ease-in 2.5s forwards;
        }
        
        @keyframes slideIn {
            from { bottom: -50px; opacity: 0; }
            to { bottom: 20px; opacity: 1; }
        }
        
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 sticky top-0 z-20 shadow-lg">
        <div class="container mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <a href="customer.php" class="text-2xl font-bold tracking-tight">ShopeeFood</a>
                <a href="customer.php" class="hover:text-green-200 transition-colors">Trang chủ</a>
                <a href="#contact" class="hover:text-green-200 transition-colors">Liên hệ</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Cart Icon -->
                <div class="relative group">
                                    <div class="flex items-center justify-center cursor-pointer w-10 h-10 rounded-full bg-white bg-opacity-20 hover:bg-green-400 transition-colors">
                    <i class="fa fa-shopping-cart text-xl"></i>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                            <?= array_sum($_SESSION['cart']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Cart Menu Dropdown -->
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                    <div class="py-2">
                        <a href="cart.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                            <i class="fa fa-shopping-cart mr-2"></i> Giỏ hàng
                        </a>
                        <a href="order_details.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                            <i class="fa fa-list-alt mr-2"></i> Đơn hàng
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Icon -->
            <div class="relative group">
                <div class="flex items-center justify-center cursor-pointer w-10 h-10 rounded-full bg-white bg-opacity-20 hover:bg-green-400 transition-colors">
                    <i class="fa fa-user text-xl"></i>
                </div>
                
                <!-- User Menu Dropdown -->
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                    <div class="py-2">
                        <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                            <i class="fa fa-user mr-2"></i> Tài khoản
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                            <i class="fa fa-sign-out-alt mr-2"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-6 mb-8 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold">Lỗi</h4>
                        <p><?= $_SESSION['error'] ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-6 mb-8 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold">Thành công</h4>
                        <p><?= $_SESSION['success'] ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10">
            <div class="mb-6 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-receipt mr-4 text-green-500"></i>
                    Chi tiết đơn hàng
                </h1>
                <p class="text-gray-600 text-lg">Theo dõi trạng thái và thông tin đơn hàng của bạn</p>
            </div>
            <a href="customer.php" class="inline-flex items-center px-8 py-4 bg-green-500 text-white rounded-full font-semibold hover:bg-green-600 transition-all duration-300 shadow-xl transform hover:scale-105">
                <i class="fas fa-arrow-left mr-3"></i> 
                Quay lại
            </a>
        </div>

        <?php if (!empty($error_message) || !$order): ?>
            <!-- Error State -->
            <div class="bg-white rounded-xl shadow-lg p-16 text-center max-w-lg mx-auto">
                <div class="mb-8">
                    <div class="w-32 h-32 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-circle text-5xl text-orange-500"></i>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6"><?= empty($error_message) ? 'Không tìm thấy đơn hàng' : 'Có lỗi xảy ra' ?></h2>
                <p class="text-gray-600 mb-10 text-lg"><?= $error_message ?: "Đơn hàng #$order_id không tồn tại hoặc không thuộc về tài khoản của bạn." ?></p>
                <a href="customer.php" class="inline-flex items-center px-8 py-4 bg-green-500 text-white rounded-full font-bold text-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-list mr-3"></i> 
                    Xem đơn hàng của bạn
                </a>
            </div>
        <?php else: ?>
            <!-- Order Header -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Đơn hàng #<?= htmlspecialchars($order['id']) ?></h2>
                        <p class="text-gray-600 text-lg"><?= date('H:i d/m/Y', strtotime($order['created_at'])) ?></p>
                    </div>
                    <span class="order-status status-<?= $order['status'] ?>">
                        <i class="fas fa-<?= 
                            $order['status'] === 'pending' ? 'clock' : 
                            ($order['status'] === 'confirmed' ? 'check-circle' : 
                            ($order['status'] === 'delivering' ? 'truck' : 
                            ($order['status'] === 'completed' ? 'check-circle' : 'times-circle')))
                        ?> mr-2"></i>
                        <?= match($order['status']) {
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'delivering' => 'Đang giao hàng',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy',
                            default => htmlspecialchars($order['status'])
                        } ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Order Summary -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                            Tóm tắt đơn hàng
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?= count($order_items) ?></div>
                                <div class="text-sm text-gray-600">Số món đã đặt</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-lg font-bold text-green-600 truncate"><?= htmlspecialchars($order['restaurant_name']) ?></div>
                                <div class="text-sm text-gray-600">Nhà hàng</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</div>
                                <div class="text-sm text-gray-600">Tổng tiền</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-2xl font-bold mb-8 flex items-center text-gray-800">
                            <i class="fas fa-utensils mr-4 text-green-500"></i>
                            Chi tiết món ăn đã đặt
                        </h3>
                        <div class="space-y-6">
                            <?php foreach ($order_items as $index => $item): ?>
                                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start">
                                        <div class="mr-6 flex-shrink-0">
                                            <img src="<?= !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836' ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="w-24 h-24 rounded-lg object-cover">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="font-bold text-gray-900 text-xl"><?= htmlspecialchars($item['name']) ?></h4>
                                                <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Số lượng: <?= $item['quantity'] ?></span>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($item['description']) ?></p>
                                            <?php endif; ?>
                                            <div class="flex items-center justify-between mt-4">
                                                <div class="text-gray-600">
                                                    <span class="font-semibold">Đơn giá:</span> 
                                                    <span class="text-lg"><?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm text-gray-500">Thành tiền</div>
                                                    <div class="font-bold text-green-500 text-xl"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Total Section -->
                        <div class="bg-green-50 rounded-xl p-8 mt-8">
                            <h4 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-calculator mr-3 text-green-500"></i>
                                Chi tiết thanh toán
                            </h4>
                            <div class="space-y-4">
                                <div class="flex justify-between py-3 border-b border-green-200">
                                    <span class="text-gray-700 font-semibold text-lg">Tạm tính (<?= count($order_items) ?> món):</span>
                                    <span class="font-bold text-lg"><?= number_format($order['subtotal'], 0, ',', '.') ?>đ</span>
                                </div>
                                <div class="flex justify-between py-3 border-b border-green-200">
                                    <span class="text-gray-700 font-semibold text-lg">Phí vận chuyển:</span>
                                    <span class="font-bold text-lg"><?= number_format($order['delivery_fee'], 0, ',', '.') ?>đ</span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="flex justify-between py-3 border-b border-green-200">
                                        <span class="text-gray-700 font-semibold text-lg">Giảm giá:</span>
                                        <span class="text-green-600 font-bold text-lg">-<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between pt-6 mt-4">
                                    <span class="text-2xl font-bold text-gray-800">Tổng cộng:</span>
                                    <span class="text-3xl font-bold text-green-500"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                    <!-- Delivery Info -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-xl font-bold mb-6 flex items-center text-gray-800">
                            <i class="fas fa-map-marker-alt mr-4 text-green-500"></i>
                            Thông tin giao hàng
                        </h3>
                        <div class="space-y-5">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Người nhận</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['username']) ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Điện thoại</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['phone']) ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Địa chỉ giao hàng</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['customer_address']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Restaurant Info -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-xl font-bold mb-6 flex items-center text-gray-800">
                            <i class="fas fa-store mr-4 text-green-500"></i>
                            Thông tin nhà hàng
                        </h3>
                        <div class="space-y-5 mb-8">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Tên nhà hàng</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Địa chỉ nhà hàng</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['restaurant_address']) ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-2">Số điện thoại</p>
                                <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($order['restaurant_phone']) ?></p>
                            </div>
                        </div>
                        <a href="customer.php?restaurant_id=<?= $order['restaurant_id'] ?>" class="w-full inline-flex items-center justify-center px-6 py-4 bg-green-500 text-white rounded-full font-bold text-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-utensils mr-3"></i> 
                            Đặt lại từ nhà hàng này
                        </a>
                    </div>

                    <!-- Cancel Order -->
                    <?php if ($order['status'] == 'pending'): ?>
                        <div class="bg-white rounded-xl shadow-lg p-8">
                            <h3 class="text-xl font-bold mb-6 flex items-center text-red-600">
                                <i class="fas fa-exclamation-triangle mr-4 text-2xl"></i>
                                Hủy đơn hàng
                            </h3>
                            <p class="text-gray-600 mb-6 text-lg">Bạn có thể hủy đơn hàng khi chưa được xác nhận.</p>
                            <form method="post" action="cancel_order.php">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-4 bg-red-500 text-white rounded-full font-bold text-lg hover:bg-red-600 transition-colors" 
                                        onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= $order['id'] ?>?')">
                                    <i class="fas fa-times-circle mr-3"></i> 
                                    Hủy đơn hàng
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer id="contact" class="bg-green-600 text-white py-12 mt-16">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <h3 class="text-lg font-bold mb-4">ShopeeFood</h3>
                <p class="text-sm">Giao đồ ăn nhanh chóng, tiện lợi, mọi lúc mọi nơi.</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Liên hệ</h3>
                <p class="text-sm">Email: nguyenvuvietanh02102005@gmail.com</p>
                <p class="text-sm">Hotline: +0976150732</p>
            </div>
            <div>
                <h3 class="text-lg font-bold mb-4">Theo dõi chúng tôi</h3>
                <div class="flex justify-center gap-4">
                    <a href="https://www.facebook.com/nguyen.v.anh.345985/" target="_blank" class="text-white hover:text-green-200 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
        </div>
        <p class="text-center text-sm mt-8">© <?= date('Y') ?> ShopeeFood. Tất cả quyền được bảo lưu.</p>
    </footer>
</body>
</html>