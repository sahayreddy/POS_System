include 'auth_check.php';
<?php
session_start();
include 'db_connect.php';

// --- 1. Fetch Core Metrics (Total Orders, Earnings, Profit) ---
$metrics = [
    'total_earnings' => 0.00,
    'total_profit' => 0.00,
    'total_orders' => 0
];
$metrics_sql = "SELECT SUM(total_amount) as total_earnings, SUM(profit) as total_profit, COUNT(order_id) as total_orders FROM orders WHERE order_status = 'Completed'";
$metrics_result = $conn->query($metrics_sql);
if ($metrics_result && $row = $metrics_result->fetch_assoc()) {
    $metrics = [
        'total_earnings' => number_format($row['total_earnings'] ?? 0, 2),
        'total_profit' => number_format($row['total_profit'] ?? 0, 2),
        'total_orders' => $row['total_orders'] ?? 0
    ];
}

// --- 2. Fetch Top 5 Best-Selling Items (By Quantity Sold) ---
$best_sellers_sql = "
    SELECT 
        p.name, 
        SUM(oi.quantity) AS total_quantity, 
        SUM(oi.quantity * p.cost) AS total_cost
    FROM 
        order_items oi
    JOIN 
        products p ON oi.product_id = p.product_id
    GROUP BY 
        p.name
    ORDER BY 
        total_quantity DESC
    LIMIT 5";
$best_sellers_result = $conn->query($best_sellers_sql);
$best_sellers = [];
if ($best_sellers_result) {
    while ($row = $best_sellers_result->fetch_assoc()) {
        $best_sellers[] = $row;
    }
}

// Calculate max quantity for chart scaling
$max_quantity = $best_sellers ? $best_sellers[0]['total_quantity'] : 1;


// --- 3. Profit Data (Still fetched, but not displayed as a chart) ---
// Kept for metrics calculation, but chart display block is removed.
$profit_sql = "
    SELECT 
        p.name, 
        COALESCE(SUM(oi.quantity * (
            CAST(IFNULL(oi.unit_price, 0) AS DECIMAL(10, 2)) - 
            CAST(IFNULL(p.cost, 0) AS DECIMAL(10, 2))
        )), 0) AS total_profit_generated
    FROM 
        order_items oi
    JOIN 
        products p ON oi.product_id = p.product_id
    GROUP BY 
        p.name
    ORDER BY 
        total_profit_generated DESC
    LIMIT 5";
$profit_result = $conn->query($profit_sql);
$top_profit_generators = [];
if ($profit_result) {
    while ($row = $profit_result->fetch_assoc()) {
        $top_profit_generators[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics | Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-white">

    <!-- Header and Navigation -->
    <header class="bg-gray-800 p-6 shadow-xl border-b border-gray-700">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <h1 class="text-3xl font-black text-green-400">Phoenix POS Analytics</h1>
            <nav class="space-x-4">
                <a href="index.php" class="text-gray-300 hover:text-white transition duration-150">POS Terminal</a>
                <a href="order_details.php" class="text-gray-300 hover:text-white transition duration-150">Order History</a>
            </nav>
        </div>
    </header>

    <main class="p-6 max-w-7xl mx-auto space-y-8">
        <h2 class="text-3xl font-bold text-white">Today's Performance</h2>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-xl shadow-2xl bg-indigo-900/40 border border-indigo-700">
                <p class="text-lg text-indigo-300 font-semibold">Total Orders</p>
                <p class="text-5xl font-extrabold mt-2 text-indigo-400"><?php echo $metrics['total_orders']; ?></p>
            </div>
            <div class="p-6 rounded-xl shadow-2xl bg-green-900/40 border border-green-700">
                <p class="text-lg text-green-300 font-semibold">Total Earnings</p>
                <p class="text-5xl font-extrabold mt-2 text-green-400">₹<?php echo $metrics['total_earnings']; ?></p>
            </div>
            <div class="p-6 rounded-xl shadow-2xl bg-yellow-900/40 border border-yellow-700">
                <p class="text-lg text-yellow-300 font-semibold">Total Profit</p>
                <p class="text-5xl font-extrabold mt-2 text-yellow-400">₹<?php echo $metrics['total_profit']; ?></p>
            </div>
        </div>
        
        <hr class="border-gray-700">

        <!-- Chart Section -->
        <!-- NOTE: The grid structure is changed to make the remaining Best Sellers chart take full width -->
        <div class="grid grid-cols-1 gap-8">
            
            <!-- Best-Selling Items Chart (Quantity) - Now full width on desktop -->
            <div class="p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700">
                <h3 class="text-2xl font-bold text-white mb-6">Top 5 Best-Selling Items (Quantity)</h3>
                
                <?php if (empty($best_sellers)): ?>
                    <p class="text-gray-500 italic pt-4">No sales data yet to generate the chart. Please process an order on the POS Terminal.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($best_sellers as $item): 
                            $percentage = round(($item['total_quantity'] / $max_quantity) * 100);
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-gray-300 font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="text-sm text-green-400 font-bold"><?php echo $item['total_quantity']; ?> units</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3">
                                <div class="bg-indigo-500 h-3 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Removed Top Profit Generators (Value) Block -->
            
        </div>

        <!-- Item Growth Trend (Conceptual, needs more data) -->
        <div class="p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700">
            <h3 class="text-2xl font-bold text-white mb-3">Item Growth Trend (Conceptual)</h3>
            <p class="text-gray-500">*This section would typically require historical sales data analysis (week-over-week or month-over-month comparisons) which requires more complex, time-stamped data. For now, it shows the concept of a growth insight.</p>
            <p class="mt-3 p-4 bg-gray-700 rounded-lg text-green-300 font-mono">Example: **Iced Latte** sales grew **+15%** in the last 7 days (requires more data to calculate).</p>
        </div>

    </main>

</body>
</html>