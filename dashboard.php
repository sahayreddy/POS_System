<?php
include 'auth_check.php';
include 'db_connect.php';

// Fetch dashboard data
$today = date('Y-m-d');
$dashboard_data = [
    'sales' => 0,
    'orders' => 0,
    'products' => 0,
    'low_stock' => 0
];

// Get today's sales
$sales_query = "SELECT SUM(total_amount) as total_sales, COUNT(*) as total_orders 
                FROM orders 
                WHERE DATE(order_date) = ? AND order_status = 'Completed'";
$stmt = $conn->prepare($sales_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $dashboard_data['sales'] = $row['total_sales'] ?? 0;
    $dashboard_data['orders'] = $row['total_orders'] ?? 0;
}

// Get product stats
$product_query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock
                 FROM products";
$result = $conn->query($product_query);
if ($row = $result->fetch_assoc()) {
    $dashboard_data['products'] = $row['total_products'];
    $dashboard_data['low_stock'] = $row['low_stock'];
}

// Get top selling products
$top_products_query = "SELECT p.name, SUM(oi.quantity) as total_sold
                      FROM products p
                      JOIN order_items oi ON p.product_id = oi.product_id
                      JOIN orders o ON oi.order_id = o.order_id
                      WHERE o.order_status = 'Completed'
                      GROUP BY p.product_id
                      ORDER BY total_sold DESC
                      LIMIT 5";
$top_products = $conn->query($top_products_query)->fetch_all(MYSQLI_ASSOC);

// Get recent orders
$recent_orders_query = "SELECT o.*, c.name as customer_name
                       FROM orders o
                       LEFT JOIN customers c ON o.customer_id = c.customer_id
                       ORDER BY o.order_date DESC
                       LIMIT 5";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Top Navigation -->
        <nav class="bg-indigo-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold">POS Dashboard</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="px-3 py-2 rounded-md hover:bg-indigo-700">POS Terminal</a>
                        <a href="product_management.php" class="px-3 py-2 rounded-md hover:bg-indigo-700">Products</a>
                        <a href="logout.php" class="px-3 py-2 bg-red-500 rounded-md hover:bg-red-600">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Today's Sales -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Today's Sales</h3>
                    <p class="text-2xl font-bold text-indigo-600">₹<?php echo number_format($dashboard_data['sales'], 2); ?></p>
                </div>
                
                <!-- Today's Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Today's Orders</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo $dashboard_data['orders']; ?></p>
                </div>
                
                <!-- Total Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Products</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $dashboard_data['products']; ?></p>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Low Stock Items</h3>
                    <p class="text-2xl font-bold text-red-600"><?php echo $dashboard_data['low_stock']; ?></p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Selling Products -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Top Selling Products</h2>
                    <div class="overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        <?php echo $product['total_sold']; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Recent Orders</h2>
                    <div class="overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        #<?php echo $order['order_id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₹<?php echo number_format($order['total_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?php echo $order['order_status'] === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $order['order_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript for interactivity here
        document.addEventListener('DOMContentLoaded', function() {
            // You can add charts or other interactive features here
        });
    </script>
</body>
</html>