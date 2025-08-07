<?php
session_start();
require 'connect.php';

// Xử lý thêm vào giỏ hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $menu_item_id = $_POST['menu_item_id'];
    $quantity = 1; // Mặc định số lượng là 1

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_GET['order_success'])) {
        $_SESSION['message'] = "Đơn hàng của bạn đã được đặt thành công!";
        header("Location: order.php?order_id=" . ($_SESSION['last_order_id'] ?? ''));
        exit();
    }
    if (isset($_SESSION['cart'][$menu_item_id])) {
        $_SESSION['cart'][$menu_item_id] += $quantity;
    } else {
        $_SESSION['cart'][$menu_item_id] = $quantity;
    }
    $_SESSION['message'] = "Đã thêm vào giỏ hàng!";
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . 
          (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : '') . 
          (isset($_GET['restaurant_id']) ? (isset($_GET['category']) ? '&' : '?') . 'restaurant_id=' . urlencode($_GET['restaurant_id']) : ''));
    exit();
}
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchResults = [];
$showSearchResults = false;

if (!empty($searchTerm)) {
    $searchParam = "%" . $searchTerm . "%";
    
    $stmt = $conn->prepare("
        SELECT mi.*, r.name AS restaurant_name 
        FROM menu_items mi 
        JOIN restaurants r ON mi.restaurant_id = r.id 
        WHERE mi.name LIKE ? OR mi.description LIKE ? OR r.name LIKE ?
    ");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $searchResults = $stmt->fetchAll();
    $showSearchResults = true;
}
$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grab - Giao Đồ Ăn</title>
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
        
        .banner-container {
            position: relative;
            height: 400px;
            overflow: hidden;
            background-color: var(--primary);
        }
        
        .banner-img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 1s ease-in-out;
        }
            
        .banner-content {
            position: relative;
            z-index: 2;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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
        
        .category-card {
            transition: all 0.2s ease;
        }
        
        .category-card:hover {
            transform: scale(1.05);
        }
        
        .filter-btn {
            transition: all 0.2s ease;
        }
        
        .filter-btn:hover {
            background-color: var(--primary) !important;
            color: white !important;
        }
        .search-highlight {
            background-color: #FFEDD5;
            padding: 0.1em 0.2em;
            border-radius: 0.2em;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 sticky top-0 z-20 shadow-lg">
    <div class="container mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-8">
            <a href="customer.php" class="text-2xl font-bold tracking-tight">GrabShip
            </a>
                <a href="customer.php" class="hover:text-green-200 transition-colors">Trang chủ</a>
                <a href="#contact" class="hover:text-green-200 transition-colors">Liên hệ</a>
        </div>
            
        <div class="flex items-center space-x-4">
            <!-- Search Bar -->
            <form method="get" action="customer.php" class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                       placeholder="Tìm món ăn..." 
                       class="w-64 p-2 pl-8 pr-2 rounded-full border-0 focus:outline-none focus:ring-2 focus:ring-white bg-white bg-opacity-20 text-white placeholder-white placeholder-opacity-70" />
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-white text-sm">
                        <i class="fa fa-search"></i>
                    </span>
                </form>
            
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
                <?php if ($user_id): ?>
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                                <i class="fa fa-user mr-2"></i> Tài khoản
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                                <i class="fa fa-sign-out-alt mr-2"></i> Đăng xuất
                            </a>
                <?php else: ?>
                            <a href="login.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                                <i class="fa fa-sign-in-alt mr-2"></i> Đăng nhập
                            </a>
                            <a href="register.php" class="block px-4 py-2 text-gray-800 hover:bg-green-100 transition-colors">
                                <i class="fa fa-user-plus mr-2"></i> Đăng ký
                            </a>
                <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </nav>
    <section id="home" class="banner-container flex items-center justify-center" style="height: 80vh; min-height: 500px;">
    <div id="banner-carousel" class="absolute inset-0 w-full h-full">
        <img src="SDLC Picture/Banner1.jpg" 
             class="banner-img opacity-100 w-full h-full object-cover" 
             style="z-index:1;"
             onerror="this.onerror=null;this.src='/Test/SDLC Picture/Caesar Salad.jpg'">
        <img src="SDLC Picture/Banner2.jpg" 
             class="banner-img opacity-0 w-full h-full object-cover" 
             style="z-index:1;"
             onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836'">
        <div class="banner-overlay"></div>
    </div>


    </section>

    <!-- Food Categories -->
    <section id="menu-categories" class="container mx-auto px-4 py-12">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-8 text-gray-800">Bạn muốn ăn gì hôm nay?</h2>
        <div class="flex flex-wrap justify-center gap-6 md:gap-8">
            <?php
            $categories = ['Cơm', 'Bún', 'Phở', 'Pizza', 'Đồ uống', 'Salad', 'Khác'];
            $images = [
                'Cơm' => 'SDLC Picture/cơm 6.jpg',
                'Bún' => 'SDLC Picture/Bún 1.jpg',
                'Phở' => 'SDLC Picture/phở 2.jpg',
                'Pizza' => 'SDLC Picture/pizza 3.jpg',
                'Đồ uống' => 'SDLC Picture/đồ uống 4.jpg',
                'Salad' => 'SDLC Picture/salad 5.jpg',
                'Khác' => 'SDLC Picture/bánh 5.jpg'
            ];
            foreach ($categories as $category):
            ?>
                <a href="customer.php?category=<?= urlencode($category) ?>#restaurants-by-category" 
                   class="category-card flex flex-col items-center p-6 rounded-lg bg-white shadow-md hover:shadow-lg">
                    <div class="w-24 h-24 md:w-32 md:h-32 rounded-lg overflow-hidden border-2 border-green-200">
                        <img src="<?= $images[$category] ?? '/Test/SDLC Picture/full.jpg' ?>" 
                             alt="<?= htmlspecialchars($category) ?>" class="w-full h-full object-cover">
                    </div>
                    <span class="mt-3 text-lg md:text-xl font-semibold text-gray-800"><?= htmlspecialchars($category) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Discounted Products -->
    <section class="container mx-auto px-4 py-8">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-8 text-gray-800">Sản phẩm đang giảm giá</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $stmt = $conn->query("SELECT mi.*, r.name AS restaurant_name FROM menu_items mi JOIN restaurants r ON mi.restaurant_id = r.id ORDER BY RAND() LIMIT 4");
            while ($row = $stmt->fetch()):
                $img = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836';
                $originalPrice = $row['price'] * 1.3; // Giả sử giá gốc cao hơn 30%
            ?>
                <div class="food-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all relative">
                    <!-- Badge giảm giá -->
                    <div class="absolute top-4 right-4 bg-red-500 text-white px-2 py-1 rounded-full text-sm font-bold">
                        -30%
                    </div>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($row['restaurant_name']) ?></p>
                    <div class="flex items-center gap-2 mb-3">
                        <p class="text-green-500 font-bold text-lg"><?= number_format($row['price'], 0) ?>đ</p>
                        <p class="text-gray-400 line-through text-sm"><?= number_format($originalPrice, 0) ?>đ</p>
                    </div>
                    <form method="post" action="customer.php">
                        <input type="hidden" name="menu_item_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="restaurant_id" value="<?= $row['restaurant_id'] ?>">
                        <button type="submit" name="add_to_cart" class="w-full bg-green-500 text-white py-2 px-4 rounded-full hover:bg-green-600 transition-colors">
                            Thêm vào giỏ
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

        <!-- Featured Dishes -->
        <section class="container mx-auto px-4 py-12">
            <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-8 text-gray-800">Món ăn nổi bật</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $stmt = $conn->query("SELECT mi.*, r.name AS restaurant_name FROM menu_items mi JOIN restaurants r ON mi.restaurant_id = r.id ORDER BY RAND() LIMIT 3");
                while ($row = $stmt->fetch()):
                    $img = !empty($row['image_url']) ? htmlspecialchars($row['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836';
                ?>
                    <div class="food-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                        <h3 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($row['name']) ?></h3>
                        <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($row['restaurant_name']) ?></h3>
                        <p class="text-green-500 font-bold mb-3"><?= number_format($row['price'], 0) ?>đ</p>
                                                 <form method="post" action="customer.php">
                             <input type="hidden" name="menu_item_id" value="<?= $row['id'] ?>">
                             <input type="hidden" name="restaurant_id" value="<?= $row['restaurant_id'] ?>">
                             <button type="submit" name="add_to_cart" class="w-full bg-green-500 text-white py-2 px-4 rounded-full hover:bg-green-600 transition-colors">
                                 Thêm vào giỏ
                             </button>
                         </form>
                    </div>
                <?php endwhile; ?>
        </div>
    </section>

    <!-- Kết quả tìm kiếm -->
    <?php if ($showSearchResults): ?>
        <section id="search-results" class="container mx-auto px-4 py-12">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-2">
                    Kết quả tìm kiếm cho "<?= htmlspecialchars($searchTerm) ?>"
                </h2>
                <span class="text-gray-600"><?= count($searchResults) ?> kết quả</span>
            </div>
            
                        <?php
                        // Hàm highlight từ khóa tìm kiếm
            if (!function_exists('highlightSearchTerm')) {
                        function highlightSearchTerm($text, $term) {
                            if (empty($term)) return htmlspecialchars($text);
                            return preg_replace(
                                '/(' . preg_quote($term, '/') . ')/i', 
                                '<span class="search-highlight">$1</span>', 
                                htmlspecialchars($text)
                            );
                        }
            }
            
            if (!empty($searchResults)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($searchResults as $item): ?>
                        <?php
                        $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836';
                        ?>
                        <div class="food-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">
                                <?= highlightSearchTerm($item['name'], $searchTerm) ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-2">
                                <?= highlightSearchTerm($item['restaurant_name'], $searchTerm) ?>
                            </p>
                            <?php if (!empty($item['description'])): ?>
                                <p class="text-gray-600 text-sm mb-2">
                                    <?= highlightSearchTerm($item['description'], $searchTerm) ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-green-500 font-bold mb-3"><?= number_format($item['price'], 0) ?>đ</p>
                            <form method="post" action="customer.php">
                                <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="restaurant_id" value="<?= $item['restaurant_id'] ?>">
                                <button type="submit" name="add_to_cart" class="w-full bg-green-500 text-white py-2 px-4 rounded-full hover:bg-green-600 transition-colors">
                                        Thêm vào giỏ
                                    </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Không tìm thấy kết quả</h3>
                    <p class="text-gray-500">Không có món ăn hoặc nhà hàng nào phù hợp với "<?= htmlspecialchars($searchTerm) ?>"</p>
                    <a href="customer.php" class="inline-block mt-4 text-green-500 hover:text-green-600 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại trang chủ
                    </a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
 
         
 


    <!-- Restaurants by Category -->
    <?php if (isset($_GET['category']) && !$showSearchResults): ?>
        <?php
        $category = htmlspecialchars($_GET['category']);
        $stmt = $conn->prepare("SELECT id, name, address FROM restaurants WHERE category = ?");
        $stmt->execute([$category]);
        ?>
        <section id="restaurants-by-category" class="container mx-auto px-4 py-12">
            <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-8 text-gray-800">
                Nhà hàng bán <span class="text-green-500"><?= $category ?></span>
            </h2>
            
            <div class="flex flex-wrap justify-center gap-2 mb-6">
                <button class="filter-btn px-3 py-1 md:px-4 md:py-2 bg-white border border-green-300 rounded-full text-gray-800">
                    Gần tôi
                </button>
                <button class="filter-btn px-3 py-1 md:px-4 md:py-2 bg-white border border-green-300 rounded-full text-gray-800">
                    Đánh giá cao
                </button>
                <button class="filter-btn px-3 py-1 md:px-4 md:py-2 bg-white border border-green-300 rounded-full text-gray-800">
                    Khuyến mãi
                </button>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while ($row = $stmt->fetch()): ?>
                        <?php
                        $stmtImg = $conn->prepare("SELECT image_url FROM menu_items WHERE restaurant_id = ? AND image_url != '' LIMIT 1");
                        $stmtImg->execute([$row['id']]);
                        $imgRow = $stmtImg->fetch();
                        $img = $imgRow && !empty($imgRow['image_url']) ? htmlspecialchars($imgRow['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836';
                        ?>
                        <div class="food-card bg-white rounded-xl shadow-lg hover:shadow-green-200 transition-all p-6">
                            <img src="<?= $img ?>" alt="Nhà hàng" class="w-full h-48 object-cover rounded-lg mb-4">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($row['name']) ?></h3>
                            <?php if (!empty($row['address'])): ?>
                                <p class="text-gray-600 text-sm mb-2">
                                    <i class="fa fa-map-marker-alt text-green-500"></i> <?= htmlspecialchars($row['address']) ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-gray-600 text-sm mb-3">4.5 ⭐ (200+ đánh giá)</p>
                            <a href="customer.php?restaurant_id=<?= $row['id'] ?>#restaurant-menu" 
                               class="inline-block bg-green-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-600 transition-colors">
                                Xem menu
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-8">
                        <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 text-lg">Chưa có nhà hàng nào bán loại này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Restaurant Menu -->
    <?php if (isset($_GET['restaurant_id']) && !$showSearchResults): ?>
        <?php
        $restaurant_id = (int)$_GET['restaurant_id'];
        $stmt = $conn->prepare("SELECT name FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch();
        ?>
        
        <?php if ($restaurant): ?>
            <section id="restaurant-menu" class="container mx-auto px-4 py-12">
                <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-8 text-gray-800">
                    Menu của <span class="text-green-500"><?= htmlspecialchars($restaurant['name']) ?></span>
                </h2>
                
                <?php
                $stmtMenu = $conn->prepare("SELECT * FROM menu_items WHERE restaurant_id = ?");
                $stmtMenu->execute([$restaurant_id]);
                ?>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($stmtMenu->rowCount() > 0): ?>
                        <?php while ($item = $stmtMenu->fetch()): ?>
                            <?php
                            $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836';
                            ?>
                            <div class="food-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                                <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-48 object-cover rounded-lg mb-4">
                                <h4 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($item['name']) ?></h4>
                                <?php if (!empty($item['description'])): ?>
                                    <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($item['description']) ?></p>
                                <?php endif; ?>
                                <p class="text-green-500 font-bold mb-3"><?= number_format($item['price'], 0) ?>đ</p>
                                <form method="post" action="customer.php">
                                    <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="restaurant_id" value="<?= $item['restaurant_id'] ?>">
                                     <button type="submit" name="add_to_cart" class="w-full bg-green-500 text-white py-2 px-4 rounded-full hover:bg-green-600 transition-colors">
                                            Thêm vào giỏ
                                        </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-8">
                            <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 text-lg">Nhà hàng chưa có món nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>


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

    <!-- Popup Message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div id="popup-message" class="popup-message">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <script>
        // Banner Carousel
        const images = document.querySelectorAll('#banner-carousel .banner-img');
        let current = 0;
        
        function showBanner(idx) {
            images.forEach((img, i) => {
                img.style.opacity = i === idx ? '1' : '0';
            });
        }
        
        function nextBanner() {
            current = (current + 1) % images.length;
            showBanner(current);
        }
        
        function prevBanner() {
            current = (current - 1 + images.length) % images.length;
            showBanner(current);
        }
        

        setInterval(nextBanner, 5000);
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Page transition effect
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '1';
            
            // Xử lý khi click vào các liên kết
            document.querySelectorAll('a').forEach(link => {
                if (link.href && !link.href.startsWith('javascript:') && 
                    !link.href.startsWith('mailto:') && 
                    !link.href.startsWith('tel:') &&
                    !link.href.startsWith('#')) {
                    link.addEventListener('click', function(e) {
                        // Nếu là liên kết về trang chủ và đang ở trang chủ thì không làm gì
                        if (link.getAttribute('href') === 'customer.php' && 
                            window.location.pathname.endsWith('customer.php') && 
                            !window.location.search && 
                            !window.location.hash) {
                            e.preventDefault();
                            return;
                        }
                        
                        // Skip nếu mở tab mới hoặc download
                        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                        if (link.hasAttribute('download')) return;
                        
                        e.preventDefault();
                        document.body.style.opacity = '0.5';
                        
                        setTimeout(() => {
                            window.location.href = link.href;
                        }, 300);
                    });
                }
            });

            // Tự động focus vào ô tìm kiếm nếu có từ khóa
            <?php if (!empty($searchTerm)): ?>
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.setSelectionRange(<?= mb_strlen($searchTerm) ?>, <?= mb_strlen($searchTerm) ?>);
                }
                
                // Tự động cuộn xuống phần kết quả tìm kiếm nếu có kết quả
                <?php if ($showSearchResults && !empty($searchResults)): ?>
                    const searchResults = document.getElementById('search-results');
                    if (searchResults) {
                        setTimeout(() => {
                            searchResults.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'start' 
                            });
                        }, 500);
                    }
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>