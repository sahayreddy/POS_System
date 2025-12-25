<?php
session_start();
// Include connection logic
include 'db_connect.php';

// Check if cart data and total are present (passed from index.php)
if (!isset($_POST['cart_data']) || !isset($_POST['total_amount']) || empty($_POST['cart_data'])) {
    header("Location: index.php?status=error&message=" . urlencode("No items found in cart."));
    exit;
}

// Get data passed from the index.php form
$cart_data_json = $_POST['cart_data'];
$total_amount = floatval($_POST['total_amount']);
$tax_rate = 0.08;
$subtotal = round($total_amount / (1 + $tax_rate), 2);
$tax_amount = round($total_amount - $subtotal, 2);

// Decode items for display
$cart_items = json_decode($cart_data_json, true);

// Set session variables to ensure data persists after refreshing this page
$_SESSION['checkout_data'] = [
    'cart_data_json' => $cart_data_json,
    'total_amount' => $total_amount,
    'subtotal' => $subtotal,
    'tax_amount' => $tax_amount,
    'cart_items' => $cart_items
];

// If data is coming from the session (e.g., after a refresh)
if (isset($_SESSION['checkout_data'])) {
    extract($_SESSION['checkout_data']);
} else {
    // Should not happen if the initial check passes, but safety redirect
    header("Location: index.php?status=error&message=" . urlencode("Cart data missing. Please re-add items."));
    exit;
}

// Function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalize Payment | Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* General dark input field style */
        .input-field {
            @apply p-3 border border-gray-600 rounded-xl bg-gray-900 shadow-inner text-white focus:ring-indigo-500 focus:border-indigo-500 transition duration-200;
        }
        
        /* FIX: Overriding browser defaults for text and background in inputs */
        /* Applying this style to ALL inputs and selects inside the main form */
        form input:not([type="hidden"]), 
        form select {
            /* Using a slightly lighter dark background for better visual contrast */
            background-color: #374151 !important; /* Tailwind gray-700 */
            color: #FFFFFF !important; /* White text */
            -webkit-appearance: none; /* Helps with select box styling */
            -moz-appearance: none;
            appearance: none;
        }

        /* New style to ensure option text is visible regardless of browser default background */
        .visible-options option {
            color: black !important; /* Force black text for contrast against the white option background */
        }
    </style>
    <!-- Font Awesome for icons (Card type) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-900 min-h-screen text-white flex items-center justify-center p-4">

    <div class="w-full max-w-2xl bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 p-8 space-y-8 transition-transform duration-300 hover:scale-[1.01]">
        
        <h2 class="text-4xl font-black text-indigo-400 border-b border-gray-700 pb-4 text-center">Finalize Order & Payment</h2>
        
        <!-- Order Summary -->
        <div class="space-y-4 p-5 rounded-xl bg-gray-700/30 border border-gray-600">
            <h3 class="text-xl font-bold text-indigo-300">Order Summary</h3>
            
            <!-- Item List -->
            <ul class="space-y-3 border-b border-gray-600 pb-4 max-h-48 overflow-y-auto pr-2">
                <?php foreach ($cart_items as $item): ?>
                    <li class="flex justify-between text-base">
                        <span class="text-gray-300 font-medium"><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="font-semibold text-white"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Totals -->
            <div class="flex justify-between text-lg text-gray-400">
                <span>Subtotal:</span>
                <span><?php echo formatCurrency($subtotal); ?></span>
            </div>
            <div class="flex justify-between text-lg text-gray-400">
                <span>Tax (8%):</span>
                <span><?php echo formatCurrency($tax_amount); ?></span>
            </div>
            <div class="flex justify-between text-4xl font-extrabold pt-4 border-t border-gray-600">
                <span>GRAND TOTAL:</span>
                <span class="text-green-400"><?php echo formatCurrency($total_amount); ?></span>
            </div>
        </div>

        <!-- Customer & Payment Form -->
        <form action="process_order.php" method="POST" class="space-y-6">
            
            <h3 class="text-2xl font-bold text-white border-b border-gray-700 pb-3">Customer & Payment Details</h3>
            
            <!-- Hidden Inputs to pass cart data to the processing script -->
            <input type="hidden" name="cart_data" value="<?php echo htmlspecialchars($cart_data_json); ?>">
            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">

            <!-- Customer Inputs -->
            <div class="space-y-4">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-300 mb-2">Customer Name (Optional)</label>
                    <input type="text" id="customer_name" name="customer_name" class="input-field w-full" placeholder="e.g., Aryan">
                </div>
                <div>
                    <label for="customer_mobile" class="block text-sm font-medium text-gray-300 mb-2">Mobile Number (Optional)</label>
                    <input type="text" id="customer_mobile" name="customer_mobile" class="input-field w-full" placeholder="e.g., 9876543210">
                </div>
            </div>

            <!-- Payment Method Dropdown -->
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-300 mb-2">Payment Method</label>
                <select id="payment_method" name="payment_method" required class="input-field w-full appearance-none visible-options" onchange="togglePaymentFields()">
                    <option value="">--- Select Payment Method ---</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="UPI">UPI</option>
                    <option value="Credit">Credit/Account</option>
                </select>
            </div>
            
            <!-- --- DYNAMIC PAYMENT FIELDS CONTAINER --- -->
            <div id="dynamic-payment-fields" class="space-y-4">

                <!-- 1. Card Payment Fields (Hidden by Default) -->
                <div id="card-details" class="p-4 rounded-xl bg-gray-700/50 border border-indigo-700 hidden space-y-4">
                    <h4 class="text-lg font-semibold text-indigo-300 flex justify-between items-center">
                        Enter Card Details
                        <div class="text-xl space-x-2 text-indigo-400">
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                            <i class="fab fa-cc-amex"></i>
                        </div>
                    </h4>
                    <div>
                        <label for="card_number" class="block text-sm font-medium text-gray-300 mb-1">Card Number</label>
                        <input type="text" id="card_number" name="card_number" class="input-field w-full" placeholder="XXXX XXXX XXXX XXXX">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="expiry" class="block text-sm font-medium text-gray-300 mb-1">Expiry (MM/YY)</label>
                            <input type="text" id="expiry" name="expiry" class="input-field w-full" placeholder="MM/YY">
                        </div>
                        <div class="col-span-1">
                            <label for="cvv" class="block text-sm font-medium text-gray-300 mb-1">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="input-field w-full" placeholder="CVV">
                        </div>
                    </div>
                </div>

                <!-- 2. UPI QR Code Placeholder (Hidden by Default) -->
                <div id="upi-qr" class="p-6 rounded-xl bg-gray-700/50 border border-green-700 hidden text-center space-y-3">
                    <h4 class="text-lg font-semibold text-green-300">Scan to Pay: UPI</h4>
                    <div class="w-48 h-48 mx-auto bg-white p-2 rounded-lg shadow-xl">
                        <!-- Placeholder for QR Code (since we cannot generate one) -->
                        <img src="https://placehold.co/180x180/0d9488/ffffff?text=UPI+QR+CODE" alt="UPI QR Placeholder" class="w-full h-full object-cover">
                    </div>
                    <p class="text-sm text-gray-400">Total Due: <span class="font-bold text-green-400 text-base"><?php echo formatCurrency($total_amount); ?></span></p>
                </div>
            </div>
            <!-- --- END DYNAMIC PAYMENT FIELDS CONTAINER --- -->


            <button type="submit" name="finalize_sale" class="w-full py-4 bg-green-600 hover:bg-green-500 rounded-xl text-white text-2xl font-black transition duration-200 shadow-xl mt-8">
                FINALIZE SALE
            </button>
        </form>

        <div class="pt-4 text-center">
            <a href="index.php" class="text-indigo-400 hover:text-indigo-300 transition duration-150 text-sm font-medium">&larr; Cancel and Go Back to POS Terminal</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', togglePaymentFields);

        /**
         * Shows or hides the relevant payment fields based on the selected method.
         */
        function togglePaymentFields() {
            const method = document.getElementById('payment_method').value;
            const cardDetails = document.getElementById('card-details');
            const upiQr = document.getElementById('upi-qr');

            // Hide all first
            cardDetails.classList.add('hidden');
            upiQr.classList.add('hidden');

            // Show the relevant section
            if (method === 'Card') {
                cardDetails.classList.remove('hidden');
            } else if (method === 'UPI') {
                upiQr.classList.remove('hidden');
            }
        }
    </script>

</body>
</html>
