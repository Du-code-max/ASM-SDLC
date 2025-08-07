<?php
session_start();
require 'connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
}

// Handle edit user
if (isset($_POST['edit_user_submit'])) {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
    $stmt->execute([
        $_POST['edit_username'],
        $_POST['edit_email'],
        $_POST['edit_role'],
        $_POST['edit_user_id']
    ]);
    header("Location: admin.php#users");
    exit();
}
// Handle add delivery user
if (isset($_POST['add_delivery_user'])) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'delivery')");
    $stmt->execute([
        $_POST['delivery_username'],
        $_POST['delivery_email'],
        password_hash($_POST['delivery_password'], PASSWORD_DEFAULT)
    ]);
    header("Location: admin.php#delivery");
    exit();
}
// Handle edit delivery user
if (isset($_POST['edit_delivery_user_submit'])) {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->execute([
        $_POST['edit_delivery_username'],
        $_POST['edit_delivery_email'],
        $_POST['edit_delivery_user_id']
    ]);
    header("Location: admin.php#delivery");
    exit();
}
// Handle delete delivery user
if (isset($_POST['delete_delivery_user'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_POST['delivery_user_id']]);
    header("Location: admin.php#delivery");
    exit();
}
// Handle edit restaurant
if (isset($_POST['edit_restaurant_submit'])) {
    $stmt = $conn->prepare("UPDATE restaurants SET name=?, address=?, category=? WHERE id=?");
    $stmt->execute([
        $_POST['edit_restaurant_name'],
        $_POST['edit_restaurant_address'],
        $_POST['edit_restaurant_category'],
        $_POST['edit_restaurant_id']
    ]);
    header("Location: admin.php#restaurants");
    exit();
}
if (isset($_POST['approve_restaurant_user'])) {
    // Cập nhật status nhà hàng của user này thành 'approved'
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("UPDATE restaurants SET status = 'approved' WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: admin.php#users");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #00b14f 0%, #008a3c 100%);
        }
        .sidebar-tab-active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #fff;
        }
        .sidebar-tab-inactive {
            transition: all 0.3s ease;
        }
        .sidebar-tab-inactive:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, #00b14f 0%, #008a3c 100%);
            color: white;
        }
        .btn-primary {
            background-color: #00b14f;
            color: white;
        }
        .btn-primary:hover {
            background-color: #008a3c;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 shadow-lg flex flex-col py-8 px-4 sticky top-0 h-screen text-white">
            <div class="flex items-center justify-center mb-8">
                <i class="fas fa-leaf text-2xl mr-3 text-white"></i>
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            
            <nav class="space-y-2">
                <button class="sidebar-btn sidebar-tab-active w-full text-left px-4 py-3 font-medium rounded-lg flex items-center" onclick="showTab('users')" id="tab-users">
                    <i class="fas fa-users mr-3"></i> Users Management
                </button>
                <button class="sidebar-btn sidebar-tab-inactive w-full text-left px-4 py-3 font-medium rounded-lg flex items-center" onclick="showTab('restaurants')" id="tab-restaurants">
                    <i class="fas fa-utensils mr-3"></i> Restaurants
                </button>
                <button class="sidebar-btn sidebar-tab-inactive w-full text-left px-4 py-3 font-medium rounded-lg flex items-center" onclick="showTab('delivery')" id="tab-delivery">
                    <i class="fas fa-motorcycle mr-3"></i> Delivery
                </button>
                <button class="sidebar-btn sidebar-tab-inactive w-full text-left px-4 py-3 font-medium rounded-lg flex items-center" onclick="showTab('orders')" id="tab-orders">
                    <i class="fas fa-chart-bar mr-3"></i> Order Analytics
                </button>
            </nav>
            
            <div class="mt-auto">
                <div class="px-4 py-3 text-sm text-gray-100">Logged in as: <span class="font-bold"><?= $_SESSION['username'] ?? 'Admin' ?></span></div>
                <a href="logout.php" class="flex items-center px-4 py-3 text-white hover:bg-red-600 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 px-8 py-6">
            <!-- Header -->
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Dashboard Overview</h1>
                <p class="text-gray-600">Welcome back, Administrator</p>
            </header>

            <!-- Manage Users -->
            <section id="users" class="tab-section mb-10">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-users mr-2 text-green-600"></i> Users Management
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $edit_user_id = isset($_POST['edit_user']) ? $_POST['user_id'] : null;
                    $stmt = $conn->query("
                        SELECT * FROM users 
                        WHERE role IN ('admin', 'customer')
                        OR (role = 'restaurant' AND id NOT IN (SELECT user_id FROM restaurants WHERE status = 'approved'))
                    ");
                    while ($row = $stmt->fetch()) {
                        echo '<div class="bg-white card rounded-xl shadow-md overflow-hidden border border-gray-100">';
                        if ($edit_user_id == $row['id']) {
                            // Edit form
                            echo '<form method="post" action="admin.php#users" class="p-5">';
                            echo '<input type="hidden" name="edit_user_id" value="'.$row['id'].'">';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Username</label>';
                            echo '<input type="text" name="edit_username" value="'.$row['username'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Email</label>';
                            echo '<input type="email" name="edit_email" value="'.$row['email'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Role</label>';
                            echo '<select name="edit_role" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">';
                            foreach (["admin","restaurant","customer","delivery"] as $role) {
                                $selected = $row['role']==$role ? "selected" : "";
                                echo "<option value='$role' $selected>$role</option>";
                            }
                            echo '</select>';
                            echo '</div>';
                            echo '<div class="flex justify-end space-x-2 mt-4">';
                            echo '<button type="submit" name="edit_user_submit" class="btn-primary py-2 px-4 rounded-lg flex items-center">';
                            echo '<i class="fas fa-save mr-2"></i> Save';
                            echo '</button>';
                            echo '</div>';
                            echo '</form>';
                        } else {
                            echo '<div class="p-5">';
                            echo '<div class="flex items-center mb-3">';
                            echo '<div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">';
                            echo '<i class="fas fa-user"></i>';
                            echo '</div>';
                            echo '<h3 class="font-bold text-lg">'.$row['username'].'</h3>';
                            echo '</div>';
                            
                            echo '<div class="space-y-2 text-sm text-gray-600 mb-4">';
                            echo '<p class="flex items-center"><i class="fas fa-envelope mr-2"></i> '.$row['email'].'</p>';
                            echo '<p class="flex items-center"><i class="fas fa-user-tag mr-2"></i> ';
                            echo '<span class="px-2 py-1 rounded-full text-xs '.getRoleBadgeClass($row['role']).'">'.$row['role'].'</span></p>';
                            echo '</div>';
                            
                            echo '<div class="flex justify-end space-x-2 border-t pt-4">';
                            echo '<form method="post" action="admin.php#users" class="inline">';
                            echo '<input type="hidden" name="user_id" value="'.$row['id'].'">';
                            echo '<button type="submit" name="edit_user" class="bg-green-100 hover:bg-green-200 text-green-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                            echo '<i class="fas fa-edit mr-1"></i> Edit';
                            echo '</button>';
                            echo '</form>';
                            
                            echo '<form method="post" action="admin.php#users" class="inline">';
                            echo '<input type="hidden" name="user_id" value="'.$row['id'].'">';
                            echo '<button type="submit" name="delete_user" class="bg-red-100 hover:bg-red-200 text-red-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                            echo '<i class="fas fa-trash-alt mr-1"></i> Delete';
                            echo '</button>';
                            echo '</form>';
                            
                            if ($row['role'] == 'restaurant') {
                                echo '<form method="post" action="admin.php#users" class="inline">';
                                echo '<input type="hidden" name="user_id" value="'.$row['id'].'">';
                                echo '<button type="submit" name="approve_restaurant_user" class="bg-green-100 hover:bg-green-200 text-green-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                                echo '<i class="fas fa-check mr-1"></i> Approve';
                                echo '</button>';
                                echo '</form>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    function getRoleBadgeClass($role) {
                        switch($role) {
                            case 'admin': return 'bg-purple-100 text-purple-800';
                            case 'restaurant': return 'bg-yellow-100 text-yellow-800';
                            case 'customer': return 'bg-green-100 text-green-800';
                            case 'delivery': return 'bg-blue-100 text-blue-800';
                            default: return 'bg-gray-100 text-gray-800';
                        }
                    }
                    ?>
                </div>
            </section>

            <!-- Manage Restaurants -->
            <section id="restaurants" class="tab-section mb-10" style="display:none">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-utensils mr-2 text-green-600"></i> Restaurants Management
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $edit_restaurant_id = isset($_POST['edit_restaurant']) ? $_POST['restaurant_id'] : null;
                    $stmt = $conn->prepare("SELECT r.*, u.username FROM restaurants r JOIN users u ON r.user_id = u.id WHERE r.status = 'approved'");
                    $stmt->execute();
                    while ($row = $stmt->fetch()) {
                        echo '<div class="bg-white card rounded-xl shadow-md overflow-hidden border border-gray-100">';
                        if ($edit_restaurant_id == $row['id']) {
                            // Edit form
                            echo '<form method="post" action="admin.php#restaurants" class="p-5">';
                            echo '<input type="hidden" name="edit_restaurant_id" value="'.$row['id'].'">';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Restaurant Name</label>';
                            echo '<input type="text" name="edit_restaurant_name" value="'.$row['name'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Address</label>';
                            echo '<input type="text" name="edit_restaurant_address" value="'.$row['address'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Category</label>';
                            echo '<input type="text" name="edit_restaurant_category" value="'.$row['category'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="flex justify-end space-x-2 mt-4">';
                            echo '<button type="submit" name="edit_restaurant_submit" class="btn-primary py-2 px-4 rounded-lg flex items-center">';
                            echo '<i class="fas fa-save mr-2"></i> Save';
                            echo '</button>';
                            echo '</div>';
                            echo '</form>';
                        } else {
                            echo '<div class="p-5">';
                            echo '<div class="flex items-center mb-3">';
                            echo '<div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mr-3">';
                            echo '<i class="fas fa-store"></i>';
                            echo '</div>';
                            echo '<div>';
                            echo '<h3 class="font-bold text-lg">'.$row['name'].'</h3>';
                            echo '<p class="text-sm text-gray-500">Owner: '.$row['username'].'</p>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '<div class="space-y-2 text-sm text-gray-600 mb-4">';
                            echo '<p class="flex items-center"><i class="fas fa-map-marker-alt mr-2"></i> '.$row['address'].'</p>';
                            echo '<p class="flex items-center"><i class="fas fa-tag mr-2"></i> '.$row['category'].'</p>';
                            echo '<p class="flex items-center"><i class="fas fa-info-circle mr-2"></i> Status: ';
                            echo '<span class="px-2 py-1 rounded-full text-xs '.getStatusBadgeClass($row['status']).'">'.$row['status'].'</span></p>';
                            echo '</div>';
                            
                            echo '<div class="flex justify-end space-x-2 border-t pt-4">';
                            echo '<form method="post" action="admin.php#restaurants" class="inline">';
                            echo '<input type="hidden" name="restaurant_id" value="'.$row['id'].'">';
                            echo '<button type="submit" name="edit_restaurant" class="bg-green-100 hover:bg-green-200 text-green-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                            echo '<i class="fas fa-edit mr-1"></i> Edit';
                            echo '</button>';
                            echo '</form>';
                            
                            if ($row['status'] == 'pending') {
                                echo '<form method="post" action="admin.php#restaurants" class="inline">';
                                echo '<input type="hidden" name="restaurant_id" value="'.$row['id'].'">';
                                echo '<button type="submit" name="approve_restaurant" class="bg-green-100 hover:bg-green-200 text-green-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                                echo '<i class="fas fa-check mr-1"></i> Approve';
                                echo '</button>';
                                echo '</form>';
                                
                                echo '<form method="post" action="admin.php#restaurants" class="inline">';
                                echo '<input type="hidden" name="restaurant_id" value="'.$row['id'].'">';
                                echo '<button type="submit" name="reject_restaurant" class="bg-red-100 hover:bg-red-200 text-red-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                                echo '<i class="fas fa-times mr-1"></i> Reject';
                                echo '</button>';
                                echo '</form>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    function getStatusBadgeClass($status) {
                        switch($status) {
                            case 'approved': return 'bg-green-100 text-green-800';
                            case 'pending': return 'bg-yellow-100 text-yellow-800';
                            case 'rejected': return 'bg-red-100 text-red-800';
                            default: return 'bg-gray-100 text-gray-800';
                        }
                    }
                    ?>
                </div>
            </section>

            <!-- Manage Delivery -->
            <section id="delivery" class="tab-section mb-10" style="display:none">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-motorcycle mr-2 text-green-600"></i> Delivery Management
                    </h2>
                    <button onclick="openAddDeliveryModal()" class="btn-primary py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Delivery
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $edit_delivery_user_id = isset($_POST['edit_delivery_user']) ? $_POST['delivery_user_id'] : null;
                    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'delivery'");
                    $stmt->execute();
                    while ($row = $stmt->fetch()) {
                        echo '<div class="bg-white card rounded-xl shadow-md overflow-hidden border border-gray-100">';
                        if ($edit_delivery_user_id == $row['id']) {
                            // Edit form
                            echo '<form method="post" action="admin.php#delivery" class="p-5">';
                            echo '<input type="hidden" name="edit_delivery_user_id" value="'.$row['id'].'">';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Username</label>';
                            echo '<input type="text" name="edit_delivery_username" value="'.$row['username'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="mb-4">';
                            echo '<label class="block text-gray-700 text-sm font-bold mb-2">Email</label>';
                            echo '<input type="email" name="edit_delivery_email" value="'.$row['email'].'" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>';
                            echo '</div>';
                            echo '<div class="flex justify-end space-x-2 mt-4">';
                            echo '<button type="submit" name="edit_delivery_user_submit" class="btn-primary py-2 px-4 rounded-lg flex items-center">';
                            echo '<i class="fas fa-save mr-2"></i> Save';
                            echo '</button>';
                            echo '</div>';
                            echo '</form>';
                        } else {
                            echo '<div class="p-5">';
                            echo '<div class="flex items-center mb-3">';
                            echo '<div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">';
                            echo '<i class="fas fa-user-shield"></i>';
                            echo '</div>';
                            echo '<h3 class="font-bold text-lg">'.$row['username'].'</h3>';
                            echo '</div>';
                            
                            echo '<div class="space-y-2 text-sm text-gray-600 mb-4">';
                            echo '<p class="flex items-center"><i class="fas fa-envelope mr-2"></i> '.$row['email'].'</p>';
                            echo '<p class="flex items-center"><i class="fas fa-user-tag mr-2"></i> ';
                            echo '<span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">'.$row['role'].'</span></p>';
                            echo '</div>';
                            
                            echo '<div class="flex justify-end space-x-2 border-t pt-4">';
                            echo '<form method="post" action="admin.php#delivery" class="inline">';
                            echo '<input type="hidden" name="delivery_user_id" value="'.$row['id'].'">';
                            echo '<button type="submit" name="edit_delivery_user" class="bg-green-100 hover:bg-green-200 text-green-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                            echo '<i class="fas fa-edit mr-1"></i> Edit';
                            echo '</button>';
                            echo '</form>';
                            
                            echo '<form method="post" action="admin.php#delivery" class="inline">';
                            echo '<input type="hidden" name="delivery_user_id" value="'.$row['id'].'">';
                            echo '<button type="submit" name="delete_delivery_user" class="bg-red-100 hover:bg-red-200 text-red-600 py-1 px-3 rounded-lg text-sm flex items-center">';
                            echo '<i class="fas fa-trash-alt mr-1"></i> Delete';
                            echo '</button>';
                            echo '</form>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </section>

            <!-- Order Statistics -->
            <section id="orders" class="tab-section mb-10" style="display:none">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-chart-bar mr-2 text-green-600"></i> Order Analytics
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
                    $total_orders = $stmt->fetch()['total_orders'];
                    $stmt = $conn->query("SELECT COUNT(*) as completed_orders FROM orders WHERE status = 'completed'");
                    $completed_orders = $stmt->fetch()['completed_orders'];
                    $stmt = $conn->query("SELECT SUM(total_price) as total_revenue FROM orders");
                    $total_revenue = $stmt->fetch()['total_revenue'] ?? 0;
                    ?>
                    
                    <div class="stat-card rounded-xl shadow-md p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-80">Total Orders</p>
                                <h3 class="text-3xl font-bold"><?= $total_orders ?></h3>
                            </div>
                            <div class="p-3 rounded-full bg-white bg-opacity-20">
                                <i class="fas fa-shopping-bag text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card rounded-xl shadow-md p-6 text-white" style="background: linear-gradient(135deg, #00b14f 0%, #008a3c 100%);">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-80">Total Revenue</p>
                                <h3 class="text-3xl font-bold"><?= number_format($total_revenue, 0, ',', '.') ?> VND</h3>
                            </div>
                            <div class="p-3 rounded-full bg-white bg-opacity-20">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-receipt mr-2 text-green-600"></i> Recent Orders
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $stmt = $conn->query("SELECT id, total_price, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
                                while ($row = $stmt->fetch()) {
                                    echo '<tr>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#'.$row['id'].'</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">'.number_format($row['total_price'], 0, ',', '.').' VND</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm">';
                                    echo '<span class="px-2 py-1 rounded-full text-xs '.getStatusBadgeClass($row['status']).'">'.$row['status'].'</span>';
                                    echo '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">'.date('d/m/Y H:i', strtotime($row['created_at'])).'</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Add Delivery Modal -->
    <div id="addDeliveryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Add New Delivery</h3>
                    <button onclick="closeAddDeliveryModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="post" action="admin.php#delivery">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                        <input type="text" name="delivery_username" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="delivery_email" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" name="delivery_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddDeliveryModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="add_delivery_user" class="btn-primary py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Delivery
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showTab(tab) {
        // Hide all tabs
        document.querySelectorAll('.tab-section').forEach(s => s.style.display = 'none');
        document.getElementById(tab).style.display = '';
        
        // Update sidebar buttons
        document.querySelectorAll('.sidebar-btn').forEach(btn => {
            btn.classList.remove('sidebar-tab-active');
            btn.classList.add('sidebar-tab-inactive');
        });
        document.getElementById('tab-'+tab).classList.add('sidebar-tab-active');
        document.getElementById('tab-'+tab).classList.remove('sidebar-tab-inactive');
        
        // Scroll to top of the section
        window.scrollTo(0, 0);
    }
    
    function openAddDeliveryModal() {
        document.getElementById('addDeliveryModal').classList.remove('hidden');
    }
    
    function closeAddDeliveryModal() {
        document.getElementById('addDeliveryModal').classList.add('hidden');
    }
    
    // Check URL hash on load
    window.addEventListener('load', function() {
        const hash = window.location.hash.substring(1);
        if (hash && ['users', 'restaurants', 'delivery', 'orders'].includes(hash)) {
            showTab(hash);
        } else {
            showTab('users');
        }
    });
    </script>
</body>
</html>