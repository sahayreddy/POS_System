<?php
// CRITICAL: This MUST be the first line to secure the page
include 'auth_check.php';
// The auth_check.php file contains the session_start() call.

include 'db_connect.php'; 

$message = '';
$message_type = '';
$edit_product = null; // Variable to hold data of the product being edited

// --- CRUD: INSERT (Add New Product) ---
if (isset($_POST['add_product'])) {
    // Collect and sanitize data
    $name = htmlspecialchars($_POST['name']);
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);

    // Check for required fields and ensure category_id is valid (> 0)
    if (empty($name) || $price <= 0 || $category_id <= 0) { // <--- FIX APPLIED HERE
        $error = "Error: Product Name, Price, and a valid Category selection are required.";
        echo "<h1 style='color:red;'>$error</h1>";
        exit;
    }

    try {
        $sql = "INSERT INTO products (name, price, cost, stock, category_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddii", $name, $price, $cost, $stock, $category_id);

        if (!$stmt->execute()) {
            throw new Exception("MySQL Execute Error: " . $stmt->error);
        }
        $stmt->close();
        
        header("Location: product_management.php?status=success&message=" . urlencode("Product '$name' added successfully!"));
        exit;

    } catch (Exception $e) {
        $error = "FATAL DATABASE ERROR: " . $e->getMessage();
        echo "<h1 style='color:red;'>$error</h1>";
        $conn->close();
        exit;
    }
}

// --- CRUD: UPDATE ---
if (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $new_price = floatval($_POST['new_price']);
    $new_cost = floatval($_POST['new_cost']);
    $new_stock = intval($_POST['new_stock']);

    try {
        // Use a single UPDATE statement for price, cost, and stock
        $sql = "UPDATE products SET price = ?, cost = ?, stock = ? WHERE product_id = ?";
        $stmt = $conn->prepare($sql);

        // 'ddii' means: double (price), double (cost), integer (stock), integer (id)
        $stmt->bind_param("ddii", $new_price, $new_cost, $new_stock, $product_id);

        if (!$stmt->execute()) {
            throw new Exception("MySQL Update Error: " . $stmt->error);
        }
        $stmt->close();
        
        header("Location: product_management.php?status=success&message=" . urlencode("Product ID $product_id updated successfully!"));
        exit;

    } catch (Exception $e) {
        header("Location: product_management.php?status=error&message=" . urlencode("Update Failed: " . $e->getMessage()));
        exit;
    }
}

// --- CRUD: DELETE ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    try {
        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("MySQL Delete Error: " . $stmt->error);
        }
        $stmt->close();

        header("Location: product_management.php?status=success&message=" . urlencode("Product ID $id deleted successfully!"));
        exit;
    } catch (Exception $e) {
        header("Location: product_management.php?status=error&message=" . urlencode("Delete Failed: " . $e->getMessage()));
        exit;
    }
}

// --- Status Message Handling ---
if (isset($_GET['status'])) {
    $message_type = $_GET['status'];
    $message = htmlspecialchars(urldecode($_GET['message']));
}

// --- FETCHING DATA ---
// Fetch all categories for the dropdown
$categories_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch all products
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
        p.product_id DESC";
$products_result = $conn->query($products_sql);
$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management | Phoenix POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .input-field {
            /* Styles to ensure high contrast in dark mode */
            background-color: #374151 !important; /* gray-700 */
            color: #FFFFFF !important; /* white text */
            @apply p-3 border border-gray-600 rounded-lg shadow-inner focus:ring-indigo-500 focus:border-indigo-500 transition duration-150;
        }
        .input-field option {
            color: black !important;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-white">

    <!-- Header and Navigation -->
    <header class="bg-gray-800 p-4 shadow-xl border-b border-gray-700">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <h1 class="text-2xl font-black text-green-400">Inventory & Product Management</h1>
            <nav class="space-x-4 flex items-center">
                <a href="index.php" class="text-gray-300 hover:text-white transition duration-150">POS Terminal</a>
                <a href="sales_dashboard.php" class="text-gray-300 hover:text-white transition duration-150">Sales Analytics</a>
                <a href="order_details.php" class="text-gray-300 hover:text-white transition duration-150">Order History</a>
                <!-- LOGOUT LINK -->
                <a href="logout.php" class="py-1 px-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition duration-150 text-sm">
                    Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)
                </a>
            </nav>
        </div>
    </header>

    <main class="p-6 max-w-7xl mx-auto space-y-8">
        <h2 class="text-3xl font-bold text-white">Manage Product Inventory</h2>

        <!-- Status Message Box -->
        <?php if ($message): ?>
            <div class="p-4 rounded-xl font-bold <?php echo $message_type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Grid Container -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Product Creation Form (Column 1) -->
            <div class="lg:col-span-1 p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700 h-fit sticky top-6">
                <h3 class="text-2xl font-bold text-indigo-400 mb-6">Add New Product</h3>
                <form action="product_management.php" method="POST" class="space-y-4">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Product Name</label>
                        <input type="text" id="name" name="name" required class="input-field w-full">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-300 mb-1">Selling Price (₹)</label>
                            <input type="number" step="0.01" id="price" name="price" required class="input-field w-full" min="0.01">
                        </div>
                        <div>
                            <label for="cost" class="block text-sm font-medium text-gray-300 mb-1">Cost Price (₹)</label>
                            <input type="number" step="0.01" id="cost" name="cost" required class="input-field w-full" min="0">
                        </div>
                    </div>
                    
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-300 mb-1">Initial Stock Quantity</label>
                        <input type="number" id="stock" name="stock" required class="input-field w-full" min="0">
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                        <select id="category_id" name="category_id" required class="input-field w-full appearance-none">
                            <option value="0">--- Select Category ---</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="add_product" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white font-bold transition duration-150 shadow-lg">
                        Add New Product
                    </button>
                </form>
            </div>

            <!-- Product Listing Table (Columns 2 & 3) -->
            <div class="lg:col-span-2 p-6 rounded-xl shadow-2xl bg-gray-800 border border-gray-700">
                <h3 class="text-2xl font-bold text-white mb-6">Current Inventory List (<?php echo count($products); ?> Items)</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-400 uppercase tracking-wider bg-gray-700">
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Product Name</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Cost</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">No products found in the inventory.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-700 transition duration-150" id="row-<?php echo $product['product_id']; ?>">
                                        <td class="px-4 py-3 font-mono text-xs text-indigo-300"><?php echo $product['product_id']; ?></td>
                                        <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td class="px-4 py-3 text-green-400">₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="px-4 py-3 text-yellow-400">₹<?php echo number_format($product['cost'], 2); ?></td>
                                        <td class="px-4 py-3 font-bold <?php echo $product['stock'] < 10 ? 'text-red-400' : 'text-green-400'; ?>"><?php echo $product['stock']; ?></td>
                                        <td class="px-4 py-3 space-x-2">
                                            <!-- EDIT BUTTON: Triggers the edit form to appear below the row -->
                                            <a href="#edit-form-<?php echo $product['product_id']; ?>" 
                                               onclick="toggleEditForm(<?php echo $product['product_id']; ?>)"
                                               class="text-blue-500 hover:text-blue-300 text-sm font-medium transition duration-150">
                                                Edit
                                            </a>
                                            <a href="product_management.php?delete_id=<?php echo $product['product_id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($product['name']); ?>? This cannot be undone.');"
                                               class="text-red-500 hover:text-red-300 text-sm font-medium transition duration-150">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- INLINE EDIT FORM ROW (Initially hidden) -->
                                    <tr class="hidden bg-gray-700" id="edit-form-<?php echo $product['product_id']; ?>">
                                        <td colspan="7" class="p-4">
                                            <form action="product_management.php" method="POST" class="flex items-center space-x-4">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <span class="text-sm text-gray-400">Edit: <?php echo $product['name']; ?></span>
                                                
                                                <label class="text-sm text-gray-300 flex-shrink-0">Price (₹)</label>
                                                <input type="number" step="0.01" name="new_price" value="<?php echo number_format($product['price'], 2, '.', ''); ?>" class="input-field w-24 text-sm" required>

                                                <label class="text-sm text-gray-300 flex-shrink-0">Cost (₹)</label>
                                                <input type="number" step="0.01" name="new_cost" value="<?php echo number_format($product['cost'], 2, '.', ''); ?>" class="input-field w-24 text-sm" required>
                                                
                                                <label class="text-sm text-gray-300 flex-shrink-0">Stock</label>
                                                <input type="number" name="new_stock" value="<?php echo $product['stock']; ?>" class="input-field w-16 text-sm" required>
                                                
                                                <button type="submit" name="update_product" class="py-1 px-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-sm transition duration-150">
                                                    Save
                                                </button>
                                                <button type="button" onclick="toggleEditForm(<?php echo $product['product_id']; ?>)" class="py-1 px-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg text-sm transition duration-150">
                                                    Cancel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleEditForm(productId) {
            const formRow = document.getElementById(`edit-form-${productId}`);
            if (formRow) {
                formRow.classList.toggle('hidden');
            }
        }
    </script>

</body>
</html>
