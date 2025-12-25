
<?php
session_start();
include 'db_connect.php';

// Fetch alert messages
$status_message = '';
$status_type = '';
if (isset($_GET['status'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $status_message = htmlspecialchars(urldecode($_GET['message']));
}

// Fetch products for display
$products_sql = "
    SELECT 
        p.product_id, 
        p.name, 
        p.price, 
        p.cost, 
        p.stock, 
        c.name AS category_name
    FROM 
        products p
    JOIN 
        categories c ON p.category_id = c.category_id
    ORDER BY 
        p.category_id, p.name";
$products_result = $conn->query($products_sql);
$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch metrics for display
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Dashboard | Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-white">

    <!-- Status Message Alert -->
    <?php if ($status_message): ?>
        <div class="fixed top-4 right-4 p-4 rounded-xl shadow-lg z-50 font-bold <?php echo $status_type === 'success' ? 'bg-green-600' : 'bg-red-600'; ?> text-white transition-opacity duration-300 opacity-100" id="status-alert">
            <?php echo $status_message; ?>
        </div>
        <script>
            setTimeout(() => {
                document.getElementById('status-alert').style.opacity = '0';
                setTimeout(() => document.getElementById('status-alert').remove(), 300);
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Header -->
    <header class="bg-gray-800 p-4 shadow-xl border-b border-gray-700">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <h1 class="text-2xl font-black text-green-400">Phoenix POS</h1>
            <nav class="space-x-4">
                <!-- LOGOUT LINK -->
                <a href="logout.php" class="py-1 px-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-150 text-sm">
                Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)
                </a>
                <a href="product_management.php" class="text-gray-300 hover:text-white transition duration-150">Product Management</a>
                <a href="sales_dashboard.php" class="text-gray-300 hover:text-white transition duration-150">Sales Analytics</a>
                <a href="order_details.php" class="text-gray-300 hover:text-white transition duration-150">Order History</a>
            </nav>
        </div>
    </header>

    <!-- Main Grid Layout -->
    <main class="grid grid-cols-1 lg:grid-cols-4 max-w-7xl mx-auto gap-8 p-6">
        
        <!-- Left Column: Summary and Navigation -->
        <div class="lg:col-span-1 space-y-6">
            <h2 class="text-2xl font-bold text-white mb-4 border-b border-gray-700 pb-2">Today's Summary</h2>
            
            <!-- Metric Boxes -->
            <div class="p-4 rounded-xl shadow-2xl bg-green-900/40 border border-green-700">
                <p class="text-lg text-green-300 font-semibold">Total Earnings</p>
                <p class="text-3xl font-extrabold mt-1 text-green-400">₹<?php echo $metrics['total_earnings']; ?></p>
            </div>
            <div class="p-4 rounded-xl shadow-2xl bg-yellow-900/40 border border-yellow-700">
                <p class="text-lg text-yellow-300 font-semibold">Total Profit</p>
                <p class="text-3xl font-extrabold mt-1 text-yellow-400">₹<?php echo $metrics['total_profit']; ?></p>
            </div>
            <div class="p-4 rounded-xl shadow-2xl bg-indigo-900/40 border border-indigo-700">
                <p class="text-lg text-indigo-300 font-semibold">Total Orders</p>
                <p class="text-3xl font-extrabold mt-1 text-indigo-400"><?php echo $metrics['total_orders']; ?></p>
            </div>

            <!-- Navigation Buttons -->
            <div class="pt-4 space-y-3 border-t border-gray-700">
                <a href="order_details.php" class="w-full flex items-center justify-center py-3 px-4 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white font-bold transition duration-150 shadow-lg">
                    Order History & Status
                </a>
                <a href="sales_dashboard.php" class="w-full flex items-center justify-center py-3 px-4 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-white font-bold transition duration-150 shadow-lg">
                    Sales Analytics
                </a>
            </div>
        </div>

        <!-- Center Column: Product Catalog -->
        <div class="lg:col-span-2 p-6 bg-gray-800 rounded-xl shadow-2xl border border-gray-700 overflow-y-auto">
            <h2 class="text-3xl font-bold text-white mb-6">Product Catalog</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6">
                <?php foreach ($products as $product): ?>
                    <button 
                        onclick="addToCart(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['cost']; ?>)" 
                        class="product-card w-full text-left p-4 rounded-xl shadow-2xl border border-gray-700 bg-gray-900 hover:bg-gray-700 transition duration-150 ease-in-out cursor-pointer group disabled:opacity-50 disabled:cursor-not-allowed"
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        >
                        <p class="text-xs text-green-400 font-bold mb-1 uppercase tracking-wider"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <h3 class="text-lg font-extrabold text-white truncate mb-2 group-hover:text-indigo-400 transition duration-150"><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xl font-black text-green-400">₹<?php echo number_format($product['price'], 2); ?></span>
                            <span class="text-sm font-semibold py-1 px-2 rounded-full <?php echo $product['stock'] < 10 ? 'bg-red-900 text-red-300' : 'bg-gray-700 text-gray-300'; ?>">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                        </div>
                        <?php if ($product['stock'] <= 0): ?>
                            <div class="mt-2 text-red-500 font-bold text-sm">OUT OF STOCK</div>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Column: Cart and Checkout -->
        <div class="lg:col-span-1 p-6 bg-gray-800 rounded-xl shadow-2xl border border-gray-700 space-y-6 flex flex-col h-fit sticky top-6">
            <h2 class="text-3xl font-bold text-white border-b border-gray-700 pb-2">Current Order</h2>
            
            <!-- Cart Items List -->
            <div id="cart-list" class="flex-grow space-y-3 min-h-[150px]">
                <p id="empty-cart-message" class="text-gray-500 italic text-center pt-8">Cart is empty.</p>
            </div>
            
            <!-- Totals Section -->
            <div class="space-y-2 border-t border-gray-700 pt-4">
                <div class="flex justify-between text-lg text-gray-400">
                    <span>Subtotal:</span>
                    <span id="subtotal-display">₹0.00</span>
                </div>
                <div class="flex justify-between text-lg text-gray-400">
                    <span>Tax (8%):</span>
                    <span id="tax-display">₹0.00</span>
                </div>
                <div class="flex justify-between text-3xl font-extrabold border-t border-gray-600 pt-3">
                    <span>TOTAL:</span>
                    <span id="total-display" class="text-green-400">₹0.00</span>
                </div>
            </div>

            <!-- Form for Checkout -->
            <form action="payment.php" method="POST" id="checkout-form" class="space-y-4">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <input type="hidden" name="total_amount" id="total-amount-input">
                
                <!-- MOVED CUSTOMER/PAYMENT FIELDS TO payment.php -->
                
                <button type="submit" id="checkout-btn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white font-bold transition duration-150 shadow-lg disabled:opacity-50" disabled>
                    PROCESS PAYMENT
                </button>
            </form>
            
            <button onclick="clearCart()" class="w-full py-3 bg-red-800 hover:bg-red-700 rounded-lg text-white font-bold transition duration-150 shadow-lg">
                CLEAR ORDER
            </button>
        </div>
    </main>

    <!-- JavaScript for Cart Logic -->
    <script>
        // Use localStorage to persist cart state for smoother experience
        let cart = JSON.parse(localStorage.getItem('pos_cart')) || {};
        const TAX_RATE = 0.08;

        // Element references
        const cartList = document.getElementById('cart-list');
        const subtotalEl = document.getElementById('subtotal-display');
        const taxEl = document.getElementById('tax-display');
        const totalEl = document.getElementById('total-display');
        const emptyMsgEl = document.getElementById('empty-cart-message');
        const checkoutBtn = document.getElementById('checkout-btn');
        const cartDataInput = document.getElementById('cart-data-input');
        const totalAmountInput = document.getElementById('total-amount-input');

        /**
         * Formats a number as currency (Rupees).
         * @param {number} amount
         */
        function formatCurrency(amount) {
            return `₹${amount.toFixed(2)}`;
        }

        /**
         * Adds an item to the cart or increments its quantity.
         */
        function addToCart(id, name, price, cost) {
            if (cart[id]) {
                cart[id].quantity += 1;
            } else {
                cart[id] = { 
                    id: id, 
                    name: name, 
                    price: parseFloat(price), 
                    cost: parseFloat(cost), 
                    quantity: 1 
                };
            }
            updateCartDisplay();
        }

        /**
         * Decrements item quantity or removes it entirely.
         */
        function removeFromCart(id) {
            if (cart[id]) {
                cart[id].quantity -= 1;
                if (cart[id].quantity <= 0) {
                    delete cart[id];
                }
            }
            updateCartDisplay();
        }

        /**
         * Clears the entire cart.
         */
        function clearCart() {
            cart = {};
            updateCartDisplay();
        }

        /**
         * Updates the visual display of the cart and recalculates totals.
         */
        function updateCartDisplay() {
            if (!cartList || !checkoutBtn) return; // Safety check

            // Save state to local storage
            localStorage.setItem('pos_cart', JSON.stringify(cart));

            cartList.innerHTML = '';
            let subtotal = 0;
            let totalItems = 0;

            for (const id in cart) {
                const item = cart[id];
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                const itemHtml = `
                    <div class="flex justify-between items-center p-2 rounded-lg bg-gray-700/50">
                        <div class="flex flex-col">
                            <span class="font-semibold text-white">${item.name}</span>
                            <span class="text-xs text-gray-400">₹${item.price.toFixed(2)} x ${item.quantity}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-indigo-300">₹${itemTotal.toFixed(2)}</span>
                            <button onclick="removeFromCart(${id})" class="text-red-500 hover:text-red-300 p-1 rounded-full text-lg leading-none" type="button" title="Remove 1 item">
                                &times;
                            </button>
                        </div>
                    </div>
                `;
                cartList.innerHTML += itemHtml;
            }

            // Calculate final totals
            const tax = subtotal * TAX_RATE;
            const total = subtotal + tax;

            // Update display elements
            if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
            if (taxEl) taxEl.textContent = formatCurrency(tax);
            if (totalEl) totalEl.textContent = formatCurrency(total);

            // Update checkout form hidden inputs
            cartDataInput.value = JSON.stringify(cart);
            totalAmountInput.value = total.toFixed(2);
            
            // Enable/Disable Checkout Button
            if (totalItems > 0) {
                if (emptyMsgEl) emptyMsgEl.style.display = 'none';
                checkoutBtn.disabled = false;
            } else {
                if (emptyMsgEl) emptyMsgEl.style.display = 'block';
                checkoutBtn.disabled = true;
            }
        }

        // Initialize display on load
        window.onload = updateCartDisplay;
    </script>
</body>
</html>
