<?php
session_start();
require 'connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'delivery') {
    header("Location: login.php");
    exit();
}

$delivery_id = $_SESSION['user_id'];

// Kiểm tra xem user có phải là shipper hợp lệ không
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'delivery'");
$stmt->execute([$delivery_id]);
$valid_shipper = $stmt->fetch();

if (!$valid_shipper) {
    $_SESSION['error'] = "Tài khoản không có quyền giao hàng";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['start_delivery'])) {
        $order_id = $_POST['order_id'];
        
        // Kiểm tra đơn hàng có thuộc về shipper này không
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND delivery_id = ? AND status = 'confirmed'");
        $stmt->execute([$order_id, $delivery_id]);
        $valid_order = $stmt->fetch();
        
        if ($valid_order) {
            $stmt = $conn->prepare("UPDATE orders SET status = 'delivering' WHERE id = ? AND delivery_id = ?");
            $stmt->execute([$order_id, $delivery_id]);
            $_SESSION['message'] = "Đã bắt đầu giao đơn hàng #$order_id";
        } else {
            $_SESSION['error'] = "Đơn hàng không hợp lệ hoặc không thuộc quyền quản lý của bạn";
        }
    } 
    elseif (isset($_POST['complete_delivery'])) {
        $order_id = $_POST['order_id'];
        
        // Kiểm tra đơn hàng có thuộc về shipper này không
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND delivery_id = ? AND status = 'delivering'");
        $stmt->execute([$order_id, $delivery_id]);
        $valid_order = $stmt->fetch();
        
        if ($valid_order) {
            $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND delivery_id = ?");
            $stmt->execute([$order_id, $delivery_id]);
            $_SESSION['message'] = "Đã hoàn thành giao đơn hàng #$order_id";
        } else {
            $_SESSION['error'] = "Đơn hàng không hợp lệ hoặc không thuộc quyền quản lý của bạn";
        }
    }
    header("Location: delivery.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrabFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0fff4;
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .status-pending { background-color: #ffedd5; color: #9a3412; }
        .status-confirmed { background-color: #d1fae5; color: #065f46; }
        .status-delivering { background-color: #ccfbf1; color: #0d9488; }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
            background-color: white;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .order-card.confirmed { border-left-color: #10b981; }
        .order-card.delivering { border-left-color: #0d9488; }
        
        .info-box {
            background-color: #f8fafc;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .info-box:hover {
            background-color: #f1f5f9;
        }
        
        .btn-primary {
            background-color: #10b981;
        }
        .btn-primary:hover {
            background-color: #059669;
        }
        
        .nav-gradient {
            background: linear-gradient(to right, #00b14f, #00a650);
        }
    </style>
</head>
<body class="bg-green-50">
    <!-- Navigation Bar -->
    <nav class="nav-gradient shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-motorcycle text-white text-xl mr-2"></i>
                        <span class="text-xl font-bold text-white">GrabFood</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="logout.php" class="text-white hover:text-green-200 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="px-4 sm:px-0 mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Quản lý đơn hàng</h1>
                <div class="flex items-center space-x-2">
                    <span class="bg-white text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <i class="fas fa-user-shield mr-1"></i> Shipper
                    </span>
                    <span class="bg-white text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        ID: <?= $delivery_id ?>
                    </span>
                </div>
            </div>
            <p class="mt-1 text-sm text-gray-600">Danh sách các đơn hàng được phân công cho bạn</p>
        </div>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded-r">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle h-5 w-5 text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?= $_SESSION['message'] ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6 rounded-r">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle h-5 w-5 text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?= $_SESSION['error'] ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Orders List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <?php
            $stmt = $conn->prepare("SELECT o.*, u.username, u.phone, u.address AS customer_address, 
                                   r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone
                                   FROM orders o 
                                   JOIN users u ON o.customer_id = u.id 
                                   JOIN restaurants r ON o.restaurant_id = r.id 
                                   WHERE o.delivery_id = ? AND o.status IN ('confirmed', 'delivering')
                                   ORDER BY FIELD(o.status, 'delivering', 'confirmed'), o.created_at DESC");
            $stmt->execute([$delivery_id]);
            $orders = $stmt->fetchAll();
            
            if (empty($orders)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Không có đơn hàng nào</h3>
                    <p class="mt-1 text-sm text-gray-500">Các đơn hàng mới được phân công sẽ xuất hiện tại đây.</p>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                    <li class="order-card <?= $order['status'] ?> p-6">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        Đơn hàng #<?= $order['id'] ?>
                                        <span class="status-badge status-<?= $order['status'] ?> ml-2">
                                            <i class="fas <?= $order['status'] == 'confirmed' ? 'fa-clock' : 'fa-truck' ?> mr-1"></i>
                                            <?= match($order['status']) {
                                                'confirmed' => 'Chờ lấy hàng',
                                                'delivering' => 'Đang giao hàng',
                                                default => $order['status']
                                            } ?>
                                        </span>
                                    </h3>
                                    <span class="text-sm text-gray-500">
                                        <?= date('H:i d/m/Y', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                                
                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="info-box p-4">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-store text-green-500 mr-2"></i>
                                            <h4 class="font-medium text-gray-900">Nhà hàng</h4>
                                        </div>
                                        <p class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($order['restaurant_address']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-phone-alt mr-1"></i> <?= htmlspecialchars($order['restaurant_phone']) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="info-box p-4">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-user text-green-600 mr-2"></i>
                                            <h4 class="font-medium text-gray-900">Khách hàng</h4>
                                        </div>
                                        <p class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($order['username']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($order['customer_address']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-mobile-alt mr-1"></i> <?= htmlspecialchars($order['phone']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 md:mt-0 md:ml-4 flex flex-col items-end">
                                <div class="text-right mb-4">
                                    <p class="text-sm text-gray-500">Tổng giá trị</p>
                                    <p class="text-xl font-bold text-green-600">
                                        <?= number_format($order['total_price'], 0, ',', '.') ?>đ
                                    </p>
                                </div>
                                
                                <form method="POST" class="w-full md:w-auto">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <?php if ($order['status'] == 'confirmed'): ?>
                                        <button type="submit" name="start_delivery" 
                                                class="w-full md:w-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-play-circle mr-2"></i> Bắt đầu giao
                                        </button>
                                    <?php elseif ($order['status'] == 'delivering'): ?>
                                        <button type="submit" name="complete_delivery" 
                                                class="w-full md:w-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-check-circle mr-2"></i> Hoàn thành
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                &copy; <?= date('Y') ?> GrabFood Delivery. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>