<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

// Xử lý thêm vào giỏ hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $menu_item_id = $_POST['menu_item_id'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$menu_item_id])) {
        $_SESSION['cart'][$menu_item_id] += $quantity;
    } else {
        $_SESSION['cart'][$menu_item_id] = $quantity;
    }
    $_SESSION['message'] = "Đã thêm vào giỏ hàng!";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : '') . (isset($_GET['restaurant_id']) ? (isset($_GET['category']) ? '&' : '?') . 'restaurant_id=' . urlencode($_GET['restaurant_id']) : ''));
    exit();
}

// Xử lý đặt hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $customer_id = $_SESSION['user_id'];
    
    // Kiểm tra giỏ hàng không trống
    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Giỏ hàng trống!";
        header("Location: cart.php");
        exit();
    }

    // Kiểm tra tất cả sản phẩm cùng 1 nhà hàng
    $restaurant_ids = [];
    foreach (array_keys($_SESSION['cart']) as $menu_item_id) {
        $stmt = $conn->prepare("SELECT restaurant_id FROM menu_items WHERE id = ?");
        $stmt->execute([$menu_item_id]);
        $item = $stmt->fetch();
        $restaurant_ids[$item['restaurant_id']] = true;
    }

    if (count($restaurant_ids) > 1) {
        $_SESSION['error'] = "Không thể đặt món từ nhiều nhà hàng trong cùng 1 đơn hàng!";
        header("Location: cart.php");
        exit();
    }

    $restaurant_id = array_key_first($restaurant_ids);
    
    // Kiểm tra restaurant_id hợp lệ
    $stmt = $conn->prepare("SELECT id, name FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch();

    if (!$restaurant) {
        $_SESSION['error'] = "Nhà hàng không tồn tại!";
        header("Location: cart.php");
        exit();
    }

    $total_price = 0;

    // Tạo đơn hàng
    try {
        $conn->beginTransaction();
        
        // Tạo order
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, restaurant_id, total_price, status) 
                               VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$customer_id, $restaurant_id, 0]);
        $order_id = $conn->lastInsertId();

        // Thêm order items
        foreach ($_SESSION['cart'] as $menu_item_id => $quantity) {
            $stmt = $conn->prepare("SELECT price, name FROM menu_items WHERE id = ?");
            $stmt->execute([$menu_item_id]);
            $item = $stmt->fetch();
            
            if (!$item) {
throw new Exception("Món ăn không tồn tại!");
            }
            
            $price = $item['price'] * $quantity;
            $total_price += $price;

            $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $menu_item_id, $quantity, $price]);
        }

        // Cập nhật tổng tiền
        $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $stmt->execute([$total_price, $order_id]);
        
        $conn->commit();
        
        unset($_SESSION['cart']);
        $_SESSION['last_order_id'] = $order_id; // Thêm dòng này
        header("Location: order.php?order_id=$order_id"); // Sửa dòng này
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Có lỗi xảy ra khi đặt hàng: " . $e->getMessage();
        header("Location: cart.php");
        exit();
    }
}

// Xử lý tăng/giảm/xóa sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['increase'])) {
        $id = $_POST['menu_item_id'];
        $_SESSION['cart'][$id]++;
    }
    if (isset($_POST['decrease'])) {
        $id = $_POST['menu_item_id'];
        if ($_SESSION['cart'][$id] > 1) {
            $_SESSION['cart'][$id]--;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    if (isset($_POST['remove'])) {
        $id = $_POST['menu_item_id'];
        unset($_SESSION['cart'][$id]);
    }
}

// Lấy thông tin giỏ hàng
$cart_items = [];
$total_price = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $stmt = $conn->query("SELECT * FROM menu_items WHERE id IN ($ids)");
    while ($item = $stmt->fetch()) {
        $item['quantity'] = $_SESSION['cart'][$item['id']];
        $item['total'] = $item['quantity'] * $item['price'];
        $cart_items[] = $item;
        $total_price += $item['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng | ShopeeFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00B14F;
            --primary-hover: #00994A;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .cart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .cart-item {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .quantity-btn {
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            transform: scale(1.1);
        }
        
        .remove-btn {
            transition: all 0.2s ease;
        }
        
        .remove-btn:hover {
            transform: scale(1.05);
        }
        
        .order-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            transition: all 0.3s ease;
        }
        
        .order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 92, 0, 0.3);
        }
        
        .restaurant-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .price-highlight {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
    </style>
</head>
<body class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-shopping-cart text-green-500 mr-3"></i>
                    Giỏ hàng của bạn
                </h1>
                <p class="text-gray-600">Quản lý đơn hàng của bạn một cách dễ dàng</p>
            </div>
            <?php if (empty($cart_items)): ?>
                <a href="customer.php" class="inline-flex items-center px-6 py-3 bg-green-500 text-white rounded-full font-semibold hover:bg-green-600 transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-utensils mr-2"></i>
                    Tiếp tục mua sắm
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="cart-container p-12 text-center">
                <div class="mb-6">
                    <i class="fas fa-shopping-basket text-6xl text-gray-300 mb-4"></i>
                </div>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Giỏ hàng của bạn đang trống</h2>
                <p class="text-gray-500 mb-8">Hãy thêm một số món ăn ngon vào giỏ hàng!</p>
            </div>
        <?php else: ?>
            <!-- Restaurant Info -->
            <div class="cart-container p-6 mb-6">
                <?php 
                $stmt = $conn->prepare("SELECT name FROM restaurants WHERE id = ?");
                $stmt->execute([$cart_items[0]['restaurant_id']]);
                $restaurant = $stmt->fetch();
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="restaurant-badge p-2 rounded-full mr-4">
                            <i class="fas fa-store text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p class="text-sm text-gray-500">Tất cả sản phẩm từ cùng 1 nhà hàng</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-gray-500"><?= count($cart_items) ?> món</span>
                    </div>
                </div>
            </div>
            
            <!-- Cart Items -->
            <div class="cart-container p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-list mr-2 text-orange-500"></i>
                    Chi tiết đơn hàng
                </h3>
                
                <div class="space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item bg-white p-4 rounded-lg border border-gray-100">
                            <div class="flex items-center justify-between">
                                <!-- Image and Name -->
                                <div class="flex items-center flex-1">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836') ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="w-20 h-20 object-cover rounded-lg mr-4">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800 text-lg"><?= htmlspecialchars($item['name']) ?></h4>
                                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($restaurant['name']) ?></p>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div class="text-center mx-4">
                                    <div class="price-highlight px-3 py-1 rounded-full">
                                        <span class="font-bold text-green-600"><?= number_format($item['price'], 0) ?>đ</span>
                                    </div>
                                </div>
                                
                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-2 mx-4">
                                    <form method="post" action="cart.php" class="flex items-center">
                                        <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="decrease" 
                                                class="quantity-btn w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-green-200 transition-all">
                                            <i class="fas fa-minus text-gray-600"></i>
                                        </button>
                                        <span class="px-4 font-semibold text-gray-800"><?= $item['quantity'] ?></span>
                                        <button type="submit" name="increase" 
                                                class="quantity-btn w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-green-200 transition-all">
                                            <i class="fas fa-plus text-gray-600"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Total -->
                                <div class="text-center mx-4">
                                    <div class="font-bold text-lg text-green-600">
                                        <?= number_format($item['total'], 0) ?>đ
                                    </div>
                                </div>
                                
                                <!-- Remove Button -->
                                <div class="ml-4">
                                    <form method="post" action="cart.php">
                                        <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="remove" 
                                                class="remove-btn text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition-all">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="cart-container p-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <div class="text-2xl font-bold text-gray-800">
                            Tổng cộng: 
                            <span class="text-green-600 text-3xl"><?= number_format($total_price, 0) ?>đ</span>
                        </div>
                        <p class="text-gray-500 text-sm mt-1">Bao gồm phí giao hàng</p>
                    </div>
                    
                    <form method="post" action="cart.php" class="flex space-x-4">
                        <a href="customer.php" class="px-6 py-3 border border-green-500 text-green-500 rounded-full font-semibold hover:bg-green-50 transition-all duration-300">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Tiếp tục mua
                        </a>
                        <button type="submit" name="place_order" 
                                class="order-btn px-8 py-3 text-white rounded-full font-bold text-lg">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Đặt hàng ngay
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>