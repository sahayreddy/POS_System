<?php
include 'db_connect.php';

// --- Handle Status Update Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
}

// --- Fetch All Orders ---
$orders = [];
$orders_sql = "SELECT o.*, e.name as employee_name FROM orders o JOIN employees e ON o.employee_id = e.employee_id ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_sql);
if ($orders_result) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// --- Fetch Specific Order Details (if ID is provided) ---
$current_order_details = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $details_sql = "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
    $stmt = $conn->prepare($details_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $current_order_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Function to get status color
function getStatusColor($status) {
    return match ($status) {
        'Completed' => 'bg-green-600',
        'Pending' => 'bg-yellow-500',
        'Cancelled' => 'bg-red-600',
        'Delivered' => 'bg-blue-600',
        default => 'bg-gray-500',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 min-h-screen p-8">

    <div class="max-w-7xl mx-auto">
        <header class="flex justify-between items-center mb-10 pb-4 border-b border-gray-700">
            <h1 class="text-4xl font-extrabold text-white">Order History & Management</h1>
            <a href="index.php" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition duration-150 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left mr-2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Back to POS
            </a>
        </header>

        <div class="flex space-x-8">
            <!-- Left Column: Order List -->
            <div class="w-2/3 bg-gray-800 p-6 rounded-xl shadow-2xl border border-gray-700">
                <h2 class="text-2xl font-bold text-white mb-6">Recent Orders</h2>
                
                <div class="space-y-4">
                    <?php if (empty($orders)): ?>
                        <p class="text-gray-500 italic">No orders found.</p>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="p-4 bg-gray-700 rounded-lg flex justify-between items-center hover:bg-gray-600 transition duration-150">
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-white">Order #<?php echo $order['order_id']; ?></span>
                                    <span class="text-sm text-gray-400">Total: ₹<?php echo number_format($order['total_amount'], 2); ?> | By: <?php echo htmlspecialchars($order['employee_name']); ?></span>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full text-white <?php echo getStatusColor($order['order_status']); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                    <a href="order_details.php?order_id=<?php echo $order['order_id']; ?>" class="text-indigo-400 hover:text-indigo-300 font-semibold">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Details & Status Update -->
            <div class="w-1/3">
                <?php if ($current_order_details): ?>
                    <?php 
                        // Find the main order data for the current details view
                        $current_order_data = array_filter($orders, fn($o) => $o['order_id'] == $order_id);
                        $current_order = reset($current_order_data);
                    ?>
                    <div class="bg-gray-800 p-6 rounded-xl shadow-2xl border border-green-500">
                        <h2 class="text-2xl font-bold text-green-400 mb-4">Details for Order #<?php echo $order_id; ?></h2>
                        
                        <p class="text-gray-300 mb-4">
                            Status: <span class="px-2 py-0.5 text-sm font-semibold rounded-full text-white <?php echo getStatusColor($current_order['order_status']); ?>">
                                <?php echo htmlspecialchars($current_order['order_status']); ?>
                            </span>
                        </p>

                        <!-- Itemized Breakdown -->
                        <div class="space-y-3 mb-6 border-t border-b py-4 border-gray-700">
                            <?php foreach ($current_order_details as $item): ?>
                                <div class="flex justify-between text-gray-400 text-sm">
                                    <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></span>
                                    <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Status Update Form (Table 6 interaction) -->
                        <h3 class="text-lg font-semibold text-white mb-3">Update Status</h3>
                        <form method="POST" action="order_details.php?order_id=<?php echo $order_id; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <input type="hidden" name="update_status" value="1">
                            
                            <select name="new_status" class="w-full p-2 mb-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="Pending" <?php echo ($current_order['order_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo ($current_order['order_status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="Delivered" <?php echo ($current_order['order_status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo ($current_order['order_status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition duration-150">
                                Change Status
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-800 p-6 rounded-xl shadow-lg border border-gray-700">
                        <p class="text-gray-400">Select an order from the list to view its details, items, and change its status.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>