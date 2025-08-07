<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Hiển thị thông báo nếu có
if (isset($_GET['order_success'])) {
    $success_message = "Đơn hàng của bạn đã được đặt thành công!";
}

// Lấy danh sách đơn hàng
$stmt = $conn->prepare("SELECT o.*, r.name AS restaurant_name 
                       FROM orders o 
                       JOIN restaurants r ON o.restaurant_id = r.id 
                       WHERE o.customer_id = ? 
                       ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi | ShopeeFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary: #00B14F;
            --primary-hover: #00994A;
            --danger: #EF4444;
            --success: #10B981;
            --warning: #F59E0B;
            --info: #3B82F6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #00B14F 0%, #00994A 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: -1;
        }
        
        .orders-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .orders-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF5C00, #FF8C00, #FF5C00);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .status-badge:hover::before {
            left: 100%;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            color: #D97706;
            border: 2px solid #FCD34D;
            box-shadow: 0 4px 15px rgba(217, 119, 6, 0.2);
        }
        .status-confirmed { 
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            color: #1D4ED8;
            border: 2px solid #93C5FD;
            box-shadow: 0 4px 15px rgba(29, 78, 216, 0.2);
        }
        .status-delivering { 
            background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
            color: #047857;
            border: 2px solid #6EE7B7;
            box-shadow: 0 4px 15px rgba(4, 120, 87, 0.2);
        }
        .status-completed { 
            background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
            color: #5B21B6;
            border: 2px solid #C4B5FD;
            box-shadow: 0 4px 15px rgba(91, 33, 182, 0.2);
        }
        .status-cancelled { 
            background: linear-gradient(135deg, #FEF2F2 0%, #FECACA 100%);
            color: #B91C1C;
            border: 2px solid #FCA5A5;
            box-shadow: 0 4px 15px rgba(185, 28, 28, 0.2);
        }
        
        .order-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF5C00, #FF8C00);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .order-card:hover::before {
            opacity: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 92, 0, 0.4);
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .empty-state {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .price-highlight {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .floating-icon {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FF5C00, #FF8C00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(255, 92, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .floating-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(255, 92, 0, 0.4);
        }
        
        .section-title {
            background: linear-gradient(135deg, #FF5C00, #FF8C00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
    </style>
</head>
<body class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10">
            <div class="mb-6 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 flex items-center">
                    <i class="fas fa-receipt mr-4 text-green-300"></i>
                    Đơn hàng của tôi
                </h1>
                <p class="text-green-100 text-lg">Theo dõi và quản lý tất cả đơn hàng của bạn</p>
            </div>
            <a href="customer.php" class="inline-flex items-center px-8 py-4 bg-white text-green-500 rounded-full font-semibold hover:bg-green-50 transition-all duration-300 shadow-xl transform hover:scale-105">
                <i class="fas fa-arrow-left mr-3"></i> 
                Quay lại
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-6 mb-8 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <div>
                        <h4 class="font-semibold">Thành công</h4>
                        <p><?= $success_message ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-state p-16 text-center max-w-lg mx-auto">
                <div class="mb-8">
                                         <div class="w-32 h-32 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                         <i class="fas fa-box-open text-5xl text-green-500"></i>
                     </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Bạn chưa có đơn hàng nào</h2>
                <p class="text-gray-600 mb-10 text-lg">Hãy đặt món ngay để trải nghiệm dịch vụ của chúng tôi!</p>
                <a href="customer.php" class="inline-flex items-center px-8 py-4 bg-green-500 text-white rounded-full font-bold text-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-utensils mr-3"></i> 
                    Đặt món ngay
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="orders-container p-8">
                <h3 class="text-2xl font-bold mb-8 flex items-center section-title">
                    <i class="fas fa-list mr-4 text-2xl"></i>
                    Danh sách đơn hàng
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-2">Đơn hàng #<?= $order['id'] ?></h4>
                                    <p class="text-gray-500 text-sm"><?= date('H:i d/m/Y', strtotime($order['created_at'])) ?></p>
                                </div>
                                <span class="status-badge status-<?= $order['status'] ?>">
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
                                        default => $order['status']
                                    } ?>
                                </span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                                                 <div class="flex items-center">
                                     <i class="fas fa-store text-green-500 mr-3"></i>
                                     <span class="font-semibold text-gray-800"><?= htmlspecialchars($order['restaurant_name']) ?></span>
                                 </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Tổng tiền:</span>
                                    <div class="price-highlight">
                                        <?= number_format($order['total_price'], 0) ?>đ
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <a href="order.php?order_id=<?= $order['id'] ?>" 
                                   class="btn-primary inline-flex items-center px-4 py-2 text-white rounded-full font-semibold text-sm">
                                    <i class="fas fa-eye mr-2"></i>
                                    Xem chi tiết
                                </a>
                                
                                <?php if ($order['status'] == 'pending'): ?>
                                    <form method="post" action="cancel_order.php" class="inline">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" 
                                                class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-full font-semibold text-sm hover:bg-red-600 transition-all"
                                                onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= $order['id'] ?>?')">
                                            <i class="fas fa-times mr-2"></i>
                                            Hủy
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-icon" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </div>
</body>
</html>